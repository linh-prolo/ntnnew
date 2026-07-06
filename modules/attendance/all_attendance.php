<?php
// ... (giữ nguyên toàn bộ PHP phía trên không đổi)
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant', 'manager', 'production');

$pdo  = getDBConnection();
$user = currentUser();
$isDirector = hasRole('director');

// ── Schema bootstrap: thêm cột cảnh báo thiếu check-out nếu chưa tồn tại ──
foreach ([
    "ALTER TABLE attendance_logs ADD COLUMN missing_checkout TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE attendance_logs ADD COLUMN missing_checkout_note VARCHAR(255) NULL",
    "ALTER TABLE attendance_logs ADD COLUMN auto_closed_at DATETIME NULL",
] as $_sql) {
    try { $pdo->exec($_sql); } catch (Throwable $_e) { /* cột đã tồn tại */ }
}

// ... (toàn bộ PHP query giữ nguyên)

// ── Tham số lọc ──────────────────────────────────────────────────────────
$viewMonth  = (int)($_GET['month']   ?? date('m'));
$viewYear   = (int)($_GET['year']    ?? date('Y'));
$filterDept = (int)($_GET['dept']    ?? 0);
$filterUser = (int)($_GET['user_id'] ?? 0);
$viewMode   = $_GET['view']          ?? 'month';

if ($viewMonth < 1)  { $viewMonth = 12; $viewYear--; }
if ($viewMonth > 12) { $viewMonth = 1;  $viewYear++; }

$viewDay   = (int)($_GET['day'] ?? date('j'));
$daysInMon = (int) date('t', mktime(0, 0, 0, $viewMonth, 1, $viewYear));
if ($viewDay < 1 || $viewDay > $daysInMon) $viewDay = (int)date('j');

$empSQL = "SELECT u.id, u.full_name, u.employee_code, d.name AS dept_name, d.id AS dept_id,
                  r.display_name AS role_name,
                  ws.shift_name, ws.color AS shift_color,
                  ws.start_time AS shift_start, ws.end_time AS shift_end,
                  ws.work_hours AS shift_work_hours, ws.late_threshold,
                  ws.is_night_shift
           FROM users u
           LEFT JOIN departments d ON u.department_id = d.id
           LEFT JOIN roles r ON u.role_id = r.id
           LEFT JOIN employee_shifts es ON es.id = (
               SELECT id FROM employee_shifts es2
               WHERE es2.user_id = u.id
                 AND es2.effective_date <= LAST_DAY(?)
                 AND (es2.end_date IS NULL OR es2.end_date >= ?)
               ORDER BY es2.effective_date DESC
               LIMIT 1
           )
           LEFT JOIN work_shifts ws ON es.shift_id = ws.id
           WHERE u.is_active = 1 AND r.name != 'director'";
$empParams = ["$viewYear-$viewMonth-01", "$viewYear-$viewMonth-01"];

if ($filterDept) { $empSQL .= " AND u.department_id = ?"; $empParams[] = $filterDept; }
if ($filterUser) { $empSQL .= " AND u.id = ?";            $empParams[] = $filterUser; }
$empSQL .= " ORDER BY d.name, u.full_name";

$empStmt = $pdo->prepare($empSQL);
$empStmt->execute($empParams);
$employees = $empStmt->fetchAll();

$attStmt = $pdo->prepare("
    SELECT al.*
    FROM attendance_logs al
    WHERE MONTH(al.work_date) = ? AND YEAR(al.work_date) = ?
    ORDER BY al.work_date
");
$attStmt->execute([$viewMonth, $viewYear]);
$attMap = [];
foreach ($attStmt->fetchAll() as $a) {
    $attMap[$a['user_id']][$a['work_date']] = $a;
}

$leaveStmt = $pdo->prepare("
    SELECT user_id, start_date, end_date, leave_type
    FROM leave_requests
    WHERE status = 'approved'
      AND ((MONTH(start_date)=? AND YEAR(start_date)=?)
        OR (MONTH(end_date)=?   AND YEAR(end_date)=?))
");
$leaveStmt->execute([$viewMonth, $viewYear, $viewMonth, $viewYear]);
$leaveMap = [];
foreach ($leaveStmt->fetchAll() as $lv) {
    $s = strtotime($lv['start_date']);
    $e = strtotime($lv['end_date']);
    for ($d = $s; $d <= $e; $d += 86400) {
        $leaveMap[$lv['user_id']][date('Y-m-d', $d)] = $lv['leave_type'];
    }
}

$otStmt = $pdo->prepare("
    SELECT user_id, ot_date, hours, ot_type
    FROM overtime_requests
    WHERE status = 'approved'
      AND MONTH(ot_date) = ? AND YEAR(ot_date) = ?
");
$otStmt->execute([$viewMonth, $viewYear]);
$otMap = [];
foreach ($otStmt->fetchAll() as $ot) {
    $otMap[$ot['user_id']][$ot['ot_date']] = $ot;
}

$holidays = $pdo->prepare("SELECT holiday_date FROM holidays WHERE MONTH(holiday_date)=? AND YEAR(holiday_date)=?");
$holidays->execute([$viewMonth, $viewYear]);
$holidayDates = array_column($holidays->fetchAll(), 'holiday_date');

// Query cảnh báo cùng thiết bị hôm nay
$deviceAlerts = [];
try {
    $alertsToday = $pdo->prepare("
        SELECT al.user_id, al.work_date, al.device_id,
               u.full_name, u.employee_code, d.name AS dept_name,
               GROUP_CONCAT(DISTINCT CONCAT(u2.full_name, ' (', u2.employee_code, ')') SEPARATOR ', ') AS shared_with
        FROM attendance_logs al
        JOIN users u ON u.id = al.user_id
        LEFT JOIN departments d ON d.id = u.department_id
        JOIN attendance_logs al2 ON al2.device_id = al.device_id
            AND al2.work_date = al.work_date
            AND al2.user_id != al.user_id
        JOIN users u2 ON u2.id = al2.user_id
        WHERE al.same_device_alert = 1
          AND al.work_date = ?
        GROUP BY al.user_id, al.work_date
        ORDER BY al.work_date DESC
    ");
    $alertsToday->execute([date('Y-m-d')]);
    $deviceAlerts = $alertsToday->fetchAll();
} catch (Throwable $e) {
    error_log('device alerts query error: ' . $e->getMessage());
}

function calcStats($userId, $attMap, $leaveMap, $otMap, $viewMonth, $viewYear, $daysInMon, $holidayDates) {
    $stats = [
        'work_days'=>0,'absent_days'=>0,'leave_days'=>0,
        'late_count'=>0,'late_minutes'=>0,
        'early_count'=>0,'early_minutes'=>0,
        'total_hours'=>0,
        'ot_hours'=>0,'ot_weekday'=>0,'ot_weekend'=>0,'ot_holiday'=>0,
        'sunday_work'=>0,
        'missing_checkout_count'=>0,
    ];
    for ($d = 1; $d <= $daysInMon; $d++) {
        $dateStr  = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
        $dow      = date('N', strtotime($dateStr));
        $isSun    = ($dow == 7);
        $isFuture = ($dateStr > date('Y-m-d'));
        if ($isFuture) continue;

        $att   = $attMap[$userId][$dateStr]   ?? null;
        $leave = $leaveMap[$userId][$dateStr] ?? null;
        $ot    = $otMap[$userId][$dateStr]    ?? null;

        if ($ot) {
            $stats['ot_hours'] += $ot['hours'];
            if ($ot['ot_type'] === 'holiday')     $stats['ot_holiday'] += $ot['hours'];
            elseif ($ot['ot_type'] === 'weekend') $stats['ot_weekend'] += $ot['hours'];
            else                                  $stats['ot_weekday'] += $ot['hours'];
        }

        if ($isSun) {
            if ($att && $att['check_in']) $stats['sunday_work']++;
            continue;
        }

        if ($leave && !$att) {
            $stats['leave_days']++;
        } elseif ($att && $att['check_in']) {
            $stats['work_days']++;
            $stats['total_hours'] += $att['work_hours'];
            if ($att['is_late']) {
                $stats['late_count']++;
                $stats['late_minutes'] += $att['late_minutes'];
            }
            if ($att['early_leave']) {
                $stats['early_count']++;
                $stats['early_minutes'] += (int)($att['early_leave_minutes'] ?? 0);
            }
            if (!empty($att['missing_checkout'])) {
                $stats['missing_checkout_count']++;
            }
        } else {
            $stats['absent_days']++;
        }
    }
    return $stats;
}

$depts   = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$empList = $pdo->query("SELECT id, full_name, employee_code FROM users WHERE is_active=1 ORDER BY full_name")->fetchAll();

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT user_id)                                          AS total_checkins,
        SUM(is_late)                                                     AS total_late,
        ROUND(SUM(work_hours), 1)                                        AS total_hours,
        COUNT(CASE WHEN check_in IS NOT NULL AND check_out IS NULL THEN 1 END) AS missing_checkout
    FROM attendance_logs
    WHERE MONTH(work_date)=? AND YEAR(work_date)=?
");
$summaryStmt->execute([$viewMonth, $viewYear]);
$summary = $summaryStmt->fetch();

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
                    <h4 class="mb-1">📊 Bảng chấm công tổng hợp</h4>
        <p class="text-muted small mb-0">
            Tháng <?= $viewMonth ?>/<?= $viewYear ?> &bull; <?= count($employees) ?> nhân viên
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/erp/modules/attendance/import_attendance.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-upload me-1"></i>Import Chấm Công
        </a>
        <button onclick="exportExcel()" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel me-1"></i>Xuất Excel
        </button>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i>In
        </button>
    </div>
</div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= count($employees) ?></div>
                <div class="small text-muted">👥 Tổng nhân viên</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= $summary['total_checkins'] ?? 0 ?></div>
                <div class="small text-muted">✅ NV đã chấm công</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning"><?= $summary['total_late'] ?? 0 ?></div>
                <div class="small text-muted">⚡ Lượt đi trễ</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger"><?= $summary['missing_checkout'] ?? 0 ?></div>
                <div class="small text-muted">⚠️ Thiếu giờ ra</div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tháng</label>
                    <select name="month" class="form-select form-select-sm">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $viewMonth ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label small fw-semibold mb-1">Năm</label>
                    <select name="year" class="form-select form-select-sm">
                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $viewYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Phòng ban</label>
                    <select name="dept" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($depts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Nhân viên</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($empList as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $filterUser == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['employee_code'] . ' - ' . $e['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-search me-1"></i>Lọc
                    </button>
                    <a href="/erp/modules/attendance/all_attendance.php" class="btn btn-outline-secondary btn-sm">↺</a>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Chế độ xem</label>
                    <div class="btn-group w-100">
                        <a href="?month=<?= $viewMonth ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>&user_id=<?= $filterUser ?>&view=month"
                           class="btn btn-sm <?= $viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-calendar-alt"></i> Tháng
                        </a>
                        <a href="?month=<?= $viewMonth ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>&user_id=<?= $filterUser ?>&view=summary"
                           class="btn btn-sm <?= $viewMode === 'summary' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-table"></i> Tổng hợp
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ CHẾ ĐỘ THÁNG ══ -->
    <?php if (!empty($deviceAlerts)): ?>
    <div class="alert alert-danger border-start border-5 border-danger mb-3">
        <h6 class="fw-bold mb-2">⚠️ Cảnh báo nghi vấn chấm công hộ hôm nay</h6>
        <?php foreach ($deviceAlerts as $a): ?>
        <div class="mb-1 small">
            🔴 <strong><?= htmlspecialchars($a['full_name']) ?></strong> (<?= htmlspecialchars($a['employee_code']) ?>)
            — cùng thiết bị với: <?= htmlspecialchars($a['shared_with']) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($viewMode === 'month'): ?>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <?php $legends = [
            ['leg-ok',    '✓ Đúng giờ'],
            ['leg-late',  '⚡ Đi trễ'],
            ['leg-early', '🔚 Về sớm'],
            ['leg-leave', '🏖️ Nghỉ phép'],
            ['leg-absent','✗ Vắng'],
            ['leg-missing-co', '⚠️ Quên chấm ra'],
            ['leg-loc-ok',      '📍 Tại công ty'],
            ['leg-loc-outside', '📍 Ngoài phạm vi'],
            ['leg-ot',    '🌙 OT'],
            ['leg-sun',   '— Chủ nhật'],
        ]; foreach ($legends as [$cls, $lbl]): ?>
        <span class="legend-badge <?= $cls ?>"><?= $lbl ?></span>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm att-table mb-0" id="attTable">
                    <thead>
                        <tr class="table-dark">
                            <th class="sticky-col text-center" rowspan="2" style="min-width:170px;vertical-align:middle;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <a href="?month=<?= $viewMonth-1 ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>&user_id=<?= $filterUser ?>&view=month"
                                       class="btn btn-xs btn-outline-light"><i class="fas fa-chevron-left"></i></a>
                                    <span>T<?= $viewMonth ?>/<?= $viewYear ?></span>
                                    <a href="?month=<?= $viewMonth+1 ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>&user_id=<?= $filterUser ?>&view=month"
                                       class="btn btn-xs btn-outline-light"><i class="fas fa-chevron-right"></i></a>
                                </div>
                            </th>
                            <?php for ($d = 1; $d <= $daysInMon; $d++):
                                $ds  = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
                                $dow = date('N', strtotime($ds));
                                $isToday   = ($ds === date('Y-m-d'));
                                $isSun     = ($dow == 7);
                                $isSat     = ($dow == 6);
                                $isHoliday = in_array($ds, $holidayDates);
                            ?>
                            <th class="text-center px-0
                                <?= $isToday ? 'bg-primary' : '' ?>
                                <?= !$isToday && $isHoliday ? 'bg-danger bg-opacity-75' : '' ?>
                                <?= !$isToday && !$isHoliday && $isSun ? 'text-danger' : '' ?>
                                <?= !$isToday && !$isHoliday && $isSat ? 'text-warning' : '' ?>"
                                style="min-width:36px;font-size:11px;white-space:nowrap;"
                                title="<?= $isHoliday ? 'Ngày lễ' : '' ?>">
                                <div><?= ['','T2','T3','T4','T5','T6','T7','CN'][$dow] ?></div>
                                <div class="fw-bold"><?= $d ?></div>
                                <?php if ($isHoliday): ?><div style="font-size:9px;">🎉</div><?php endif; ?>
                            </th>
                            <?php endfor; ?>
                            <th class="text-center" style="min-width:40px;font-size:11px;">Công</th>
                            <th class="text-center" style="min-width:38px;font-size:11px;">Phép</th>
                            <th class="text-center" style="min-width:38px;font-size:11px;">Vắng</th>
                            <th class="text-center" style="min-width:38px;font-size:11px;">Trễ</th>
                            <th class="text-center" style="min-width:50px;font-size:11px;">Trừ(p)</th>
                            <th class="text-center" style="min-width:42px;font-size:11px;">OT(h)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $prevDept = null;
                    foreach ($employees as $emp):
                        if ($emp['dept_name'] !== $prevDept):
                            $prevDept = $emp['dept_name'];
                    ?>
                    <tr class="dept-row">
                        <td colspan="<?= $daysInMon + 7 ?>" class="py-1 ps-3 fw-bold small">
                            🏢 <?= htmlspecialchars($emp['dept_name'] ?? 'Chưa phân phòng ban') ?>
                        </td>
                    </tr>
                    <?php endif;
                        $st = calcStats($emp['id'], $attMap, $leaveMap, $otMap, $viewMonth, $viewYear, $daysInMon, $holidayDates);
                        // Tổng phút trừ = trễ + về sớm
                        $totalDeductMin = $st['late_minutes'] + $st['early_minutes'];
                        // Kiểm tra có cảnh báo cùng thiết bị trong tháng không
                        $hasSameDeviceAlert = false;
                        foreach ($attMap[$emp['id']] ?? [] as $attRow) {
                            if (!empty($attRow['same_device_alert'])) { $hasSameDeviceAlert = true; break; }
                        }
                    ?>
                    <tr class="emp-row">
                        <td class="sticky-col py-1">
                            <div class="fw-semibold" style="font-size:12px;line-height:1.2;">
                                <?= htmlspecialchars($emp['full_name']) ?>
                                <?php if ($hasSameDeviceAlert): ?>
                                <span class="badge bg-danger ms-1" style="font-size:9px;" title="Nghi vấn chấm công hộ - cùng thiết bị">⚠️ Hộ?</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:10px;" class="text-muted">
                                <?= $emp['employee_code'] ?>
                                <?php if ($emp['shift_name']): ?>
                                &bull; <span style="color:<?= $emp['shift_color'] ?>;font-weight:600;">
                                    <?= htmlspecialchars($emp['shift_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <?php for ($d = 1; $d <= $daysInMon; $d++):
                            $dateStr   = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
                            $dow       = date('N', strtotime($dateStr));
                            $isSun     = ($dow == 7);
                            $isFuture  = ($dateStr > date('Y-m-d'));
                            $isHoliday = in_array($dateStr, $holidayDates);
                            $att   = $attMap[$emp['id']][$dateStr]   ?? null;
                            $leave = $leaveMap[$emp['id']][$dateStr] ?? null;
                            $ot    = $otMap[$emp['id']][$dateStr]    ?? null;

                            $bg = '#fff'; $content = ''; $title = '';

                            if ($isSun) {
                                $bg = '#f5f5f5';
                                $content = '<span style="color:#ccc;font-size:10px;">—</span>';
                            } elseif ($isHoliday && !$att) {
                                $bg = '#fff0f0';
                                $content = '<span style="font-size:11px;" title="Ngày lễ">🎉</span>';
                            } elseif ($isFuture) {
                                $bg = '#fafafa';
                            } elseif ($leave && !$att) {
                                $leaveLabels = ['annual'=>'Phép','sick'=>'Ốm','unpaid'=>'KL','other'=>'Khác'];
                                $bg = '#e8f4fd';
                                $content = '<span style="font-size:10px;color:#0284c7;font-weight:600;">' . ($leaveLabels[$leave] ?? 'Phép') . '</span>';
                                $title = 'Nghỉ phép';
                            } elseif ($att && $att['check_in']) {
                                $isLate    = $att['is_late'];
                                $isEarly   = $att['early_leave'];
                                $isMissingCO = !empty($att['missing_checkout']);
                                $checkIn   = date('H:i', strtotime($att['check_in']));
                                $checkOut  = $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : '?';

                                if ($isLate && $isEarly)      $bg = '#fff7e6';
                                elseif ($isLate)              $bg = '#fffbf0';
                                elseif ($isEarly)             $bg = '#fdf2f8';
                                elseif ($isMissingCO)         $bg = '#fff8f0';
                                else                          $bg = '#f0fff4';

                                $title = "Vào: $checkIn | Ra: $checkOut";
                                if ($isLate)       $title .= " | Trễ: {$att['late_minutes']}p";
                                if ($isEarly)      $title .= " | Về sớm: {$att['early_leave_minutes']}p";
                                if ($ot)           $title .= " | OT: {$ot['hours']}h";
                                if ($isMissingCO)  $title .= " | ⚠️ Quên chấm ra";
                                $locFlag = $att['check_in_location_flag'] ?? 'unknown';
                                $locDot = match ($locFlag) {
                                    'verified' => '<span style="font-size:7px;color:#16a34a;" title="Tại công ty">●</span>',
                                    'outside'  => '<span style="font-size:7px;color:#d97706;" title="Ngoài phạm vi">●</span>',
                                    'no_gps'   => '<span style="font-size:7px;color:#aaa;" title="Không có GPS">●</span>',
                                    default    => '',
                                };

                                $content = '<div style="font-size:9px;line-height:1.4;">';
if ($isMissingCO) {
    $content .= '<span style="color:#f59e0b;font-weight:bold;" title="Quên chấm ra">⚠️</span><br>';
} elseif ($isLate && $isEarly) {
    $content .= '<span style="color:#d97706;">⚡🔚</span><br>';
} elseif ($isLate) {
    $content .= '<span style="color:#d97706;font-weight:bold;">⚡</span><br>';
} elseif ($isEarly) {
    $content .= '<span style="color:#9333ea;font-weight:bold;">🔚</span><br>';
} else {
    $content .= '<span style="color:#16a34a;font-weight:bold;">✓</span><br>';
}
$content .= '<span style="color:#333;">' . $checkIn . '</span><br>';
$checkOutColor = $att['check_out'] ? '#666' : ($isMissingCO ? '#f59e0b' : '#dc2626');
$content .= '<span style="color:' . $checkOutColor . ';">' . $checkOut . '</span>';
$content .= $locDot ? '<br>' . $locDot : '';
if ($ot) $content .= '<br><span style="color:#6f42c1;font-size:8px;">OT</span>';
$content .= '</div>';
                            } else {
                                $bg = '#fff5f5';
                                $content = '<span style="color:#dc2626;font-size:11px;font-weight:bold;">✗</span>';
                                $title = 'Vắng không phép';
                            }
                        ?>
                        <td class="text-center p-0 att-cell"
                            style="min-width:46px;font-size:11px;white-space:nowrap;"
                            title="<?= htmlspecialchars($title) ?>"
                            onclick="showDayDetail(<?= $emp['id'] ?>, '<?= $dateStr ?>', '<?= htmlspecialchars(addslashes($emp['full_name'])) ?>')">
                            <?= $content ?>
                        </td>
                        <?php endfor; ?>

                        <td class="text-center fw-bold text-success small"><?= $st['work_days'] ?></td>
                        <td class="text-center text-info small"><?= $st['leave_days'] ?: '—' ?></td>
                        <td class="text-center small <?= $st['absent_days'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $st['absent_days'] ?: '—' ?></td>
                        <td class="text-center small">
                            <?= $st['late_count'] > 0 ? '<span class="badge-sm bg-warning text-dark">' . $st['late_count'] . '</span>' : '<span class="text-muted">—</span>' ?>
                        </td>
                        <!-- ✅ Cột Trừ (phút đi trễ + về sớm) -->
                        <td class="text-center small <?= $totalDeductMin > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                            <?= $totalDeductMin > 0 ? $totalDeductMin . 'p' : '—' ?>
                        </td>
                        <td class="text-center small">
                            <?= $st['ot_hours'] > 0 ? '<span class="badge-sm bg-purple">' . number_format($st['ot_hours'],1) . '</span>' : '<span class="text-muted">—</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══ CHẾ ĐỘ TỔNG HỢP ══ -->
    <?php elseif ($viewMode === 'summary'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold d-flex justify-content-between">
            <span>📋 Bảng tổng hợp tháng <?= $viewMonth ?>/<?= $viewYear ?></span>
            <small class="text-muted fw-normal"><?= count($employees) ?> nhân viên</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="summaryTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="sticky-col" style="min-width:180px;">Nhân viên</th>
                            <th class="text-center">Ca</th>
                            <th class="text-center">Ngày công</th>
                            <th class="text-center">Giờ làm</th>
                            <th class="text-center">Nghỉ phép</th>
                            <th class="text-center">Vắng</th>
                            <th class="text-center">Đi trễ</th>
                            <th class="text-center">Phút trễ</th>
                            <th class="text-center">Về sớm</th>
                            <th class="text-center">Phút về sớm</th>
                            <!-- ✅ Cột tổng phút trừ -->
                            <th class="text-center text-danger fw-bold">Tổng phút trừ</th>
                            <th class="text-center">OT thường</th>
                            <th class="text-center">OT T7/CN</th>
                            <th class="text-center">OT lễ</th>
                            <th class="text-center fw-bold">Tổng OT</th>
                            <th class="text-center">Làm CN</th>
                            <th class="text-center text-warning fw-bold">⚠️ Quên ra</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $prevDept    = null;
                    $grandTotals = array_fill_keys([
                        'work_days','total_hours','leave_days','absent_days',
                        'late_count','late_minutes','early_count','early_minutes',
                        'ot_hours','ot_weekday','ot_weekend','ot_holiday','sunday_work',
                        'missing_checkout_count',
                    ], 0);

                    foreach ($employees as $emp):
                        if ($emp['dept_name'] !== $prevDept):
                            $prevDept = $emp['dept_name'];
                    ?>
                    <tr class="table-secondary">
                        <td colspan="17" class="fw-bold small py-1 ps-3">
                            🏢 <?= htmlspecialchars($emp['dept_name'] ?? 'Chưa phân phòng ban') ?>
                        </td>
                    </tr>
                    <?php endif;
                        $st = calcStats($emp['id'], $attMap, $leaveMap, $otMap, $viewMonth, $viewYear, $daysInMon, $holidayDates);
                        foreach ($grandTotals as $k => $v) $grandTotals[$k] += $st[$k];
                        $totalDeductMin = $st['late_minutes'] + $st['early_minutes'];
                        $hasSameDeviceAlertSum = false;
                        foreach ($attMap[$emp['id']] ?? [] as $attRow) {
                            if (!empty($attRow['same_device_alert'])) { $hasSameDeviceAlertSum = true; break; }
                        }
                    ?>
                    <tr>
                        <td class="sticky-col">
                            <div class="fw-semibold small">
                                <?= htmlspecialchars($emp['full_name']) ?>
                                <?php if ($hasSameDeviceAlertSum): ?>
                                <span class="badge bg-danger ms-1" style="font-size:9px;" title="Nghi vấn chấm công hộ - cùng thiết bị">⚠️ Hộ?</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted" style="font-size:10px;">
                                <?= $emp['employee_code'] ?>
                                <?php if ($emp['shift_name']): ?>
                                &bull; <span style="color:<?= $emp['shift_color'] ?>"><?= htmlspecialchars($emp['shift_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($emp['shift_name']): ?>
                            <span class="badge" style="background:<?= $emp['shift_color'] ?>;font-size:10px;">
                                <?= htmlspecialchars($emp['shift_name']) ?>
                            </span>
                            <?php else: ?><span class="text-danger small">Chưa có</span><?php endif; ?>
                        </td>
                        <td class="text-center fw-bold text-success"><?= $st['work_days'] ?></td>
                        <td class="text-center"><?= number_format($st['total_hours'],1) ?>h</td>
                        <td class="text-center text-info"><?= $st['leave_days'] ?: '—' ?></td>
                        <td class="text-center <?= $st['absent_days'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= $st['absent_days'] ?: '—' ?>
                        </td>
                        <td class="text-center">
                            <?= $st['late_count'] > 0
                                ? '<span class="badge bg-warning text-dark">' . $st['late_count'] . ' lần</span>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-center small <?= $st['late_minutes'] > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">
                            <?= $st['late_minutes'] > 0 ? $st['late_minutes'] . 'p' : '—' ?>
                        </td>
                        <td class="text-center">
                            <?= $st['early_count'] > 0
                                ? '<span class="badge bg-purple">' . $st['early_count'] . ' lần</span>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-center small <?= $st['early_minutes'] > 0 ? 'text-purple fw-semibold' : 'text-muted' ?>">
                            <?= $st['early_minutes'] > 0 ? $st['early_minutes'] . 'p' : '—' ?>
                        </td>
                        <!-- ✅ Tổng phút trừ -->
                        <td class="text-center fw-bold <?= $totalDeductMin > 0 ? 'text-danger' : 'text-muted' ?>">
                            <?= $totalDeductMin > 0 ? $totalDeductMin . 'p' : '—' ?>
                        </td>
                        <td class="text-center small"><?= $st['ot_weekday'] > 0 ? number_format($st['ot_weekday'],1).'h' : '—' ?></td>
                        <td class="text-center small"><?= $st['ot_weekend'] > 0 ? number_format($st['ot_weekend'],1).'h' : '—' ?></td>
                        <td class="text-center small"><?= $st['ot_holiday'] > 0 ? number_format($st['ot_holiday'],1).'h' : '—' ?></td>
                        <td class="text-center fw-bold">
                            <?= $st['ot_hours'] > 0
                                ? '<span class="badge bg-purple">' . number_format($st['ot_hours'],1) . 'h</span>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-center small text-muted">
                            <?= $st['sunday_work'] > 0 ? $st['sunday_work'] . ' ngày' : '—' ?>
                        </td>
                        <td class="text-center small">
                            <?= $st['missing_checkout_count'] > 0
                                ? '<span class="badge bg-warning text-dark">' . $st['missing_checkout_count'] . ' ngày</span>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark fw-bold">
                        <tr>
                            <td class="sticky-col" colspan="2">📊 TỔNG CỘNG</td>
                            <td class="text-center text-success"><?= $grandTotals['work_days'] ?></td>
                            <td class="text-center"><?= number_format($grandTotals['total_hours'],1) ?>h</td>
                            <td class="text-center text-info"><?= $grandTotals['leave_days'] ?></td>
                            <td class="text-center text-danger"><?= $grandTotals['absent_days'] ?></td>
                            <td class="text-center text-warning"><?= $grandTotals['late_count'] ?></td>
                            <td class="text-center"><?= $grandTotals['late_minutes'] ?>p</td>
                            <td class="text-center"><?= $grandTotals['early_count'] ?></td>
                            <td class="text-center"><?= $grandTotals['early_minutes'] ?>p</td>
                            <td class="text-center text-danger"><?= ($grandTotals['late_minutes'] + $grandTotals['early_minutes']) ?>p</td>
                            <td class="text-center"><?= number_format($grandTotals['ot_weekday'],1) ?>h</td>
                            <td class="text-center"><?= number_format($grandTotals['ot_weekend'],1) ?>h</td>
                            <td class="text-center"><?= number_format($grandTotals['ot_holiday'],1) ?>h</td>
                            <td class="text-center text-warning"><?= number_format($grandTotals['ot_hours'],1) ?>h</td>
                            <td class="text-center"><?= $grandTotals['sunday_work'] ?></td>
                            <td class="text-center text-warning"><?= $grandTotals['missing_checkout_count'] ?: '—' ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- ══ MODAL Chi tiết + Sửa giờ ══ -->
<div class="modal fade" id="dayDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="dayDetailTitle">Chi tiết chấm công</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dayDetailBody">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<style>
.sticky-col { position:sticky; left:0; background:#fff; z-index:3; box-shadow:2px 0 5px rgba(0,0,0,.06); }
.att-table thead th.sticky-col { background:#212529; z-index:4; }
.dept-row td { background:#f1f5f9 !important; }
.att-cell { transition:filter .15s; }
.att-cell:hover { filter:brightness(.9); }
.bg-purple   { background:#6f42c1 !important; color:#fff; }
.text-purple { color:#6f42c1 !important; }
.badge-sm { display:inline-block; padding:1px 5px; border-radius:3px; font-size:10px; }
.btn-xs { padding:1px 6px; font-size:11px; }

/* Legend */
.legend-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:500; }
.leg-ok     { background:#f0fff4; color:#16a34a; border:1px solid #bbf7d0; }
.leg-late   { background:#fffbf0; color:#d97706; border:1px solid #fde68a; }
.leg-early  { background:#fdf2f8; color:#9333ea; border:1px solid #e9d5ff; }
.leg-leave  { background:#e8f4fd; color:#0284c7; border:1px solid #bae6fd; }
.leg-absent { background:#fff5f5; color:#dc2626; border:1px solid #fecaca; }
.leg-missing-co { background:#fff8f0; color:#b45309; border:1px solid #fcd34d; }
.leg-loc-ok      { background:#f0fff4; color:#16a34a; border:1px solid #bbf7d0; }
.leg-loc-outside { background:#fffbf0; color:#d97706; border:1px solid #fde68a; }
.leg-ot     { background:#f5f3ff; color:#6f42c1; border:1px solid #ddd6fe; }
.leg-sun    { background:#f5f5f5; color:#aaa;    border:1px solid #e5e7eb; }

@media print {
    .main-content { margin-left:0 !important; }
    .sidebar, nav.navbar, .card-header form, .btn, .modal { display:none !important; }
    .att-table { font-size:9px; }
    .sticky-col { position:static; box-shadow:none; }
}
</style>

<script>
const IS_DIRECTOR = <?= json_encode($isDirector) ?>;

function formatLocationFlag(flag) {
    const map = {
        verified: '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Tại công ty</span>',
        outside:  '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Ngoài phạm vi</span>',
        no_gps:   '<span class="badge bg-secondary"><i class="fas fa-question-circle me-1"></i>Không có GPS</span>',
        unknown:  '<span class="badge bg-light text-dark">Chưa xác định</span>',
    };
    return map[flag] || map.unknown;
}

function hasValidLocation(lat, lng) {
    return Number.isFinite(Number(lat)) && Number.isFinite(Number(lng));
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildLocationLink(lat, lng) {
    const numLat = Number(lat);
    const numLng = Number(lng);
    if (!Number.isFinite(numLat) || !Number.isFinite(numLng)) {
        return '—';
    }

    const rawLat = numLat.toString();
    const rawLng = numLng.toString();
    const safeLat = escapeHtml(rawLat);
    const safeLng = escapeHtml(rawLng);
    const query = encodeURIComponent(`${rawLat},${rawLng}`);
    return `<a href="https://maps.google.com/?q=${query}" target="_blank" rel="noopener noreferrer" class="small"><i class="fas fa-map-marker-alt me-1"></i>${safeLat}, ${safeLng}</a>`;
}

async function showDayDetail(userId, dateStr, empName) {
    document.getElementById('dayDetailTitle').textContent = `📅 ${empName} — ${dateStr}`;
    document.getElementById('dayDetailBody').innerHTML =
        '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
    new bootstrap.Modal(document.getElementById('dayDetailModal')).show();

    try {
        const res  = await fetch(`/erp/api/attendance/detail.php?user_id=${userId}&date=${dateStr}`);
        const data = await res.json();

        const checkInVal  = data.att?.check_in  ? data.att.check_in.substring(11,16)  : '';
        const checkOutVal = data.att?.check_out ? data.att.check_out.substring(11,16) : '';

        let html = `<div class="row g-3">`;

        // ── Thông tin chấm công ──
        html += `<div class="col-md-6">
            <div class="card border-0 bg-light h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">⏰ Chấm công</h6>
                    <table class="table table-sm mb-0">
                        <tr><th width="40%">Giờ vào</th>
                            <td class="fw-bold text-success">${checkInVal || '—'}</td></tr>
                        <tr><th>Giờ ra</th>
                            <td class="fw-bold text-danger">${checkOutVal || '⚠️ Chưa ra'}</td></tr>
                        <tr><th>Số giờ</th>
                            <td>${data.att?.work_hours ?? '—'}h</td></tr>
                        <tr><th>Đi trễ</th>
                            <td>${data.att?.is_late
                                ? `<span class="badge bg-warning text-dark">⚡ ${data.att.late_minutes}p</span>`
                                : '<span class="text-success small">✓ Đúng giờ</span>'}</td></tr>
                        <tr><th>Về sớm</th>
                            <td>${data.att?.early_leave
                                ? `<span class="badge bg-purple">🔚 ${data.att.early_leave_minutes}p</span>`
                                : '<span class="text-muted small">—</span>'}</td></tr>
                        <tr><th>Nguồn</th>
                            <td><span class="badge bg-secondary">
                                ${data.att?.source === 'machine' ? '🖥️ Máy chấm' : '✍️ Thủ công'}
                            </span></td></tr>
                        <tr><th>Vị trí vào</th><td>${formatLocationFlag(data.att?.check_in_location_flag)}</td></tr>
                        <tr><th>IP vào</th><td><code class="small">${data.att?.check_in_ip ? escapeHtml(data.att.check_in_ip) : '—'}</code></td></tr>
                        ${hasValidLocation(data.att?.check_in_lat, data.att?.check_in_lng) ? `<tr><th>Tọa độ vào</th><td>${buildLocationLink(data.att.check_in_lat, data.att.check_in_lng)}</td></tr>` : ''}
                        <tr><th>Vị trí ra</th><td>${formatLocationFlag(data.att?.check_out_location_flag)}</td></tr>
                        <tr><th>IP ra</th><td><code class="small">${data.att?.check_out_ip ? escapeHtml(data.att.check_out_ip) : '—'}</code></td></tr>
                        ${hasValidLocation(data.att?.check_out_lat, data.att?.check_out_lng) ? `<tr><th>Tọa độ ra</th><td>${buildLocationLink(data.att.check_out_lat, data.att.check_out_lng)}</td></tr>` : ''}
                    </table>
                    <div class="row g-2 mt-2">
                        ${data.att?.check_in_photo ? `<div class="col-6 text-center">
                            <div class="small text-muted mb-1">📸 Ảnh vào</div>
                            <img src="${escapeHtml(data.att.check_in_photo)}" class="img-fluid rounded" style="max-height:180px; border:2px solid #28a745;" onerror="this.style.display='none'">
                        </div>` : `<div class="col-6 text-center text-muted small d-flex align-items-center justify-content-center"
                            style="height:100px; background:#f8f9fa; border-radius:8px;">Không có ảnh vào</div>`}
                        ${data.att?.check_out_photo ? `<div class="col-6 text-center">
                            <div class="small text-muted mb-1">📸 Ảnh ra</div>
                            <img src="${escapeHtml(data.att.check_out_photo)}" class="img-fluid rounded" style="max-height:180px; border:2px solid #dc3545;" onerror="this.style.display='none'">
                        </div>` : `<div class="col-6 text-center text-muted small d-flex align-items-center justify-content-center"
                            style="height:100px; background:#f8f9fa; border-radius:8px;">Không có ảnh ra</div>`}
                    </div>
                </div>
            </div>
        </div>`;

        // ── OT & Nghỉ phép ──
        html += `<div class="col-md-6">
            <div class="card border-0 bg-light h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📋 OT & Nghỉ phép</h6>`;
        if (data.ot) {
            const otTypes = {weekday:'Ngày thường',weekend:'Cuối tuần',holiday:'Ngày lễ'};
            html += `<div class="alert alert-light border-start border-4 border-purple py-2 mb-2">
                <strong>🌙 OT ${data.ot.hours}h</strong><br>
                <small class="text-muted">${data.ot.ot_date} · ${otTypes[data.ot.ot_type]||data.ot.ot_type}</small>
            </div>`;
        }
        if (data.leave) {
            const leaveTypes = {annual:'Phép năm',sick:'Nghỉ ốm',unpaid:'Không lương',other:'Khác'};
            html += `<div class="alert alert-info py-2 mb-2">
                <strong>🏖️ ${leaveTypes[data.leave.leave_type]||data.leave.leave_type}</strong><br>
                <small>${data.leave.reason||''}</small>
            </div>`;
        }
        if (!data.ot && !data.leave) html += `<p class="text-muted small mt-2">Không có OT hoặc đơn nghỉ</p>`;
        html += `</div></div></div>`;

        // ── Ca làm việc ──
        if (data.shift) {
            html += `<div class="col-12">
                <div class="card border-0 border-start border-4" style="border-color:${data.shift.color}!important;">
                    <div class="card-body py-2">
                        <span class="badge me-2" style="background:${data.shift.color}">${data.shift.shift_name}</span>
                        <small class="text-muted">
                            Giờ chuẩn: ${data.shift.start_time.substring(0,5)} – ${data.shift.end_time.substring(0,5)}
                            &nbsp;|&nbsp; ${data.shift.work_hours}h/ca
                            &nbsp;|&nbsp; Ngưỡng trễ: ${data.shift.late_threshold ?? 0} phút
                        </small>
                    </div>
                </div>
            </div>`;
        }

        // ── Form sửa giờ (chỉ Director) ──
        if (IS_DIRECTOR) {
            html += `<div class="col-12">
                <div class="card border-warning border-2">
                    <div class="card-header bg-warning bg-opacity-10 py-2">
                        <h6 class="mb-0 fw-bold text-warning">
                            <i class="fas fa-edit me-1"></i>Chỉnh sửa thủ công
                            <small class="text-muted fw-normal ms-2">(Chỉ Giám đốc)</small>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Giờ vào</label>
                                <input type="time" id="editCheckIn" class="form-control form-control-sm"
                                       value="${checkInVal}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Giờ ra</label>
                                <input type="time" id="editCheckOut" class="form-control form-control-sm"
                                       value="${checkOutVal}">
                                <div class="form-text text-muted" style="font-size:11px;">
                                    💡 Ca đêm: giờ ra có thể nhỏ hơn giờ vào (VD: vào 20:00, ra 05:00)
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Ghi chú lý do sửa</label>
                                <input type="text" id="editNote" class="form-control form-control-sm"
                                       placeholder="VD: Máy chấm công lỗi ngày...">
                            </div>
                        </div>
                        <div id="editResult" class="mt-2"></div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-warning btn-sm" onclick="saveAttendance(${userId}, '${dateStr}')">
                                <i class="fas fa-save me-1"></i>Lưu chỉnh sửa
                            </button>
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="if(confirm('Xóa dữ liệu chấm công ngày này?')) deleteAttendance(${userId}, '${dateStr}')">
                                <i class="fas fa-trash me-1"></i>Xóa bản ghi
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // ── Lịch sử sửa ──
        if (data.audit && data.audit.length > 0) {
            html += `<div class="col-12">
                <div class="card border-0 bg-light">
                    <div class="card-body py-2">
                        <h6 class="fw-bold small mb-2">📜 Lịch sử chỉnh sửa</h6>
                        <table class="table table-sm mb-0" style="font-size:11px;">
                            <thead><tr>
                                <th>Thời gian</th><th>Người sửa</th>
                                <th>Giờ vào mới</th><th>Giờ ra mới</th><th>Ghi chú</th>
                            </tr></thead>
                            <tbody>`;
            data.audit.forEach(a => {
                html += `<tr>
                    <td>${a.created_at?.substring(0,16)||'—'}</td>
                    <td>${a.changed_by_name||'—'}</td>
                    <td>${a.new_check_in?.substring(11,16)||'—'}</td>
                    <td>${a.new_check_out?.substring(11,16)||'—'}</td>
                    <td class="text-muted">${a.note||'—'}</td>
                </tr>`;
            });
            html += `</tbody></table></div></div></div>`;
        }

        html += `</div>`;
        document.getElementById('dayDetailBody').innerHTML = html;

    } catch(e) {
        document.getElementById('dayDetailBody').innerHTML =
            '<div class="alert alert-danger">Lỗi tải dữ liệu.</div>';
    }
}

async function saveAttendance(userId, dateStr) {
    const checkIn  = document.getElementById('editCheckIn').value;
    const checkOut = document.getElementById('editCheckOut').value;
    const note     = document.getElementById('editNote').value;
    const result   = document.getElementById('editResult');

    if (!checkIn) { result.innerHTML = '<div class="alert alert-warning py-1 small">Vui lòng nhập giờ vào.</div>'; return; }

    result.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...</div>';

    try {
        const res  = await fetch('/erp/api/attendance/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, date: dateStr, check_in: checkIn, check_out: checkOut, note })
        });
        const data = await res.json();
        if (data.ok) {
            result.innerHTML = `<div class="alert alert-success py-1 small">
                ✅ Đã lưu! Giờ làm: <strong>${data.work_hours}h</strong>
                ${data.is_late ? `&nbsp;|&nbsp; <span class="text-warning">⚡ Trễ ${data.late_minutes}p</span>` : ''}
                ${data.early_leave ? `&nbsp;|&nbsp; <span class="text-purple">🔚 Về sớm ${data.early_minutes}p</span>` : ''}
            </div>`;
            // Reload page sau 1.5s để cập nhật bảng
            setTimeout(() => location.reload(), 1500);
        } else {
            result.innerHTML = `<div class="alert alert-danger py-1 small">❌ ${data.msg}</div>`;
        }
    } catch(e) {
        result.innerHTML = '<div class="alert alert-danger py-1 small">Lỗi kết nối server.</div>';
    }
}

async function deleteAttendance(userId, dateStr) {
    try {
        const res  = await fetch('/erp/api/attendance/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, date: dateStr, check_in: '', check_out: '', note: 'Xóa bản ghi', action: 'delete' })
        });
        const data = await res.json();
        if (data.ok) { alert('Đã xóa!'); location.reload(); }
        else alert('❌ ' + data.msg);
    } catch(e) { alert('Lỗi kết nối.'); }
}

function exportExcel() {
    const tableId = document.getElementById('summaryTable') ? 'summaryTable' : 'attTable';
    const table   = document.getElementById(tableId);
    if (!table) { alert('Vui lòng chuyển sang chế độ Tổng hợp để xuất Excel.'); return; }
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, 'Chấm công T<?= $viewMonth ?>/<?= $viewYear ?>');
    XLSX.writeFile(wb, `ChamCong_T<?= $viewMonth ?>_<?= $viewYear ?>.xlsx`);
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>