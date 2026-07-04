<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant', 'manager', 'production');

$pdo = getDBConnection();
$user = currentUser();

$viewMonth = (int)($_GET['month'] ?? date('m'));
$viewYear  = (int)($_GET['year']  ?? date('Y'));
$filterDept = (int)($_GET['dept'] ?? 0);

if ($viewMonth < 1)  { $viewMonth = 12; $viewYear--; }
if ($viewMonth > 12) { $viewMonth = 1;  $viewYear++; }

$daysInMon = cal_days_in_month(CAL_GREGORIAN, $viewMonth, $viewYear);
$firstDay  = sprintf('%04d-%02d-01', $viewYear, $viewMonth);
$lastDay   = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $daysInMon);

// Lấy danh sách nhân viên (không JOIN employee_shifts để tránh fan-out)
$sql = "SELECT u.id, u.full_name, u.employee_code, u.department_id,
               d.name AS dept_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.is_active = 1";
$params = [];

if ($filterDept) {
    $sql .= " AND u.department_id = ?";
    $params[] = $filterDept;
}
$sql .= " ORDER BY d.name, u.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Query tất cả ca phân công trong tháng (hỗ trợ nhiều ca/tháng)
$shiftStmt = $pdo->prepare("
    SELECT es.user_id, es.effective_date, es.end_date,
           ws.shift_name, ws.shift_code, ws.color AS shift_color,
           ws.start_time, ws.end_time, ws.is_night_shift
    FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.effective_date <= :lastDay
      AND (es.end_date IS NULL OR es.end_date >= :firstDay)
    ORDER BY es.user_id, es.effective_date ASC
");
$shiftStmt->execute([':lastDay' => $lastDay, ':firstDay' => $firstDay]);

// Build map: $shiftMap[user_id] = [ ['effective_date'=>..., 'end_date'=>..., ...], ... ]
$shiftMap = [];
foreach ($shiftStmt->fetchAll() as $s) {
    $shiftMap[$s['user_id']][] = $s;
}

// Helper: trả về ca áp dụng cho ngày đã cho
function getShiftForDate(int $userId, string $date, array $shiftMap): ?array {
    foreach ($shiftMap[$userId] ?? [] as $s) {
        $from = $s['effective_date'];
        $to   = $s['end_date'] ?? '9999-12-31';
        if ($date >= $from && $date <= $to) return $s;
    }
    return null;
}

// Lấy chấm công trong tháng
$attStmt = $pdo->prepare("
    SELECT al.user_id, al.work_date, al.check_in, al.check_out,
           al.work_hours, al.is_late, al.late_minutes, al.shift_id,
           ws.color AS shift_color
    FROM attendance_logs al
    LEFT JOIN work_shifts ws ON al.shift_id = ws.id
    WHERE MONTH(al.work_date) = ? AND YEAR(al.work_date) = ?
");
$attStmt->execute([$viewMonth, $viewYear]);
$attMap = [];
foreach ($attStmt->fetchAll() as $a) {
    $attMap[$a['user_id']][$a['work_date']] = $a;
}

// Lấy nghỉ phép đã duyệt
$leaveStmt = $pdo->prepare("
    SELECT user_id, start_date, end_date, leave_type
    FROM leave_requests
    WHERE status = 'approved'
      AND (MONTH(start_date) = ? OR MONTH(end_date) = ?)
      AND (YEAR(start_date)  = ? OR YEAR(end_date)  = ?)
");
$leaveStmt->execute([$viewMonth, $viewMonth, $viewYear, $viewYear]);
$leaveMap = [];
foreach ($leaveStmt->fetchAll() as $lv) {
    $s = strtotime($lv['start_date']);
    $e = strtotime($lv['end_date']);
    for ($d = $s; $d <= $e; $d += 86400) {
        $leaveMap[$lv['user_id']][date('Y-m-d', $d)] = $lv['leave_type'];
    }
}

$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">📅 Lịch ca tháng <?= $viewMonth . '/' . $viewYear ?></h4>
            <p class="text-muted small mb-0"><?= count($employees) ?> nhân viên</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/erp/modules/attendance/shift_assign.php" class="btn btn-success btn-sm">
                <i class="fas fa-user-clock me-1"></i>Phân công ca
            </a>
            <a href="/erp/modules/attendance/shift_setup.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog me-1"></i>Setup ca
            </a>
        </div>
    </div>

    <!-- Điều hướng tháng + lọc -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <a href="?month=<?= $viewMonth-1 ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>"
                       class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i></a>
                    <strong>Tháng <?= $viewMonth ?>/<?= $viewYear ?></strong>
                    <a href="?month=<?= $viewMonth+1 ?>&year=<?= $viewYear ?>&dept=<?= $filterDept ?>"
                       class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-right"></i></a>
                    <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>&dept=<?= $filterDept ?>"
                       class="btn btn-sm btn-outline-primary">Tháng này</a>
                </div>
                <form method="GET" class="d-flex gap-2 ms-auto">
                    <input type="hidden" name="month" value="<?= $viewMonth ?>">
                    <input type="hidden" name="year"  value="<?= $viewYear ?>">
                    <select name="dept" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
                        <option value="">-- Tất cả phòng ban --</option>
                        <?php foreach ($depts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Chú thích -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="legend-item"><span style="display:inline-block;width:16px;height:12px;background:#ffffff;border:1px solid #dee2e6;border-radius:2px;"></span> Ca ngày</span>
        <span class="legend-item"><span style="display:inline-block;width:16px;height:12px;background:#e8f0ff;border:1px solid #b8c8ff;border-radius:2px;"></span> Ca đêm</span>
        <span class="legend-item"><span class="dot bg-success"></span>Đúng giờ</span>
        <span class="legend-item"><span class="dot bg-warning"></span>Đi trễ</span>
        <span class="legend-item"><span class="dot bg-info"></span>Nghỉ phép</span>
        <span class="legend-item"><span class="dot bg-danger"></span>Vắng</span>
        <span class="legend-item"><span class="dot bg-light border"></span>Chủ nhật</span>
    </div>

    <!-- Bảng lịch ca -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="table table-bordered table-sm schedule-table mb-0">
                    <thead>
                        <tr class="table-dark">
                            <th class="sticky-col fw-bold" style="min-width:160px;">Nhân viên</th>
                            <th class="text-center" style="min-width:60px;">Ca</th>
                            <?php for ($d = 1; $d <= $daysInMon; $d++):
                                $dateStr = "$viewYear-" . str_pad($viewMonth,2,'0',STR_PAD_LEFT) . "-" . str_pad($d,2,'0',STR_PAD_LEFT);
                                $dow = date('N', strtotime($dateStr));
                                $isToday = $dateStr === date('Y-m-d');
                                $isSun = $dow == 7;
                                $isSat = $dow == 6;
                            ?>
                            <th class="text-center px-1 <?= $isSun?'text-danger':($isSat?'text-warning':'') ?> <?= $isToday?'bg-primary text-white':'' ?>"
                                style="min-width:36px; font-size:11px;">
                                <div><?= ['','T2','T3','T4','T5','T6','T7','CN'][$dow] ?></div>
                                <div class="<?= $isToday?'':'text-muted' ?>"><?= $d ?></div>
                            </th>
                            <?php endfor; ?>
                            <th class="text-center" style="min-width:60px;">Ngày công</th>
                            <th class="text-center" style="min-width:50px;">Trễ</th>
                            <th class="text-center" style="min-width:50px;">Phép</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $prevDept = null;
                    foreach ($employees as $emp):
                        // Header phòng ban
                        if ($emp['dept_name'] !== $prevDept):
                            $prevDept = $emp['dept_name'];
                    ?>
                    <tr class="table-secondary">
                        <td colspan="<?= $daysInMon + 5 ?>" class="fw-bold small py-1 ps-3">
                            🏢 <?= htmlspecialchars($emp['dept_name'] ?? 'Chưa phân phòng ban') ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php
                        $workDays = 0; $lateDays = 0; $leaveDays = 0;
                    ?>
                    <tr>
                        <!-- Tên nhân viên -->
                        <td class="sticky-col" style="min-width:160px;">
                            <div class="fw-semibold small"><?= htmlspecialchars($emp['full_name']) ?></div>
                            <div class="text-muted" style="font-size:10px;"><?= $emp['employee_code'] ?></div>
                        </td>
                        <!-- Ca (timeline trong tháng) -->
                        <td class="text-center">
                            <?php
                            $userShifts = $shiftMap[$emp['id']] ?? [];
                            if (empty($userShifts)): ?>
                            <span style="font-size:10px;color:red;">Chưa có</span>
                            <?php else:
                                foreach ($userShifts as $s):
                                    $sfrom = max($s['effective_date'], $firstDay);
                                    $sto   = min($s['end_date'] ?? '9999-12-31', $lastDay);
                                    $fromDay = (int)substr($sfrom, 8, 2);
                                    $toDay   = $s['end_date'] ? (int)substr($sto, 8, 2) : $daysInMon;
                            ?>
                            <div class="mb-1">
                                <span class="badge" style="background:<?= htmlspecialchars($s['shift_color']) ?>; font-size:10px;"><?= htmlspecialchars($s['shift_code']) ?></span>
                                <div style="font-size:9px;color:#666;"><?= $fromDay ?>–<?= $toDay ?></div>
                            </div>
                            <?php endforeach; endif; ?>
                        </td>

                        <!-- Từng ngày -->
                        <?php for ($d = 1; $d <= $daysInMon; $d++):
                            $dateStr = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
                            $dow = date('N', strtotime($dateStr));
                            $isSun = $dow == 7;
                            $isFuture = $dateStr > date('Y-m-d');
                            $att   = $attMap[$emp['id']][$dateStr] ?? null;
                            $leave = $leaveMap[$emp['id']][$dateStr] ?? null;

                            $shiftToday = getShiftForDate($emp['id'], $dateStr, $shiftMap);
                            $isNightDay = !empty($shiftToday['is_night_shift']);

                            $cellBg = ''; $cellContent = '';

                            if ($isSun) {
                                $cellBg = '#f8f9fa';
                                $cellContent = '<span style="font-size:10px;color:#aaa;">CN</span>';
                            } elseif ($isFuture) {
                                $cellBg = $isNightDay ? '#f0f4ff' : '';
                                $cellContent = '';
                            } elseif ($leave && !$att) {
                                $leaveDays++;
                                $cellBg = '#e8f4fd';
                                $cellContent = '<span style="font-size:11px;" title="Nghỉ phép">🏖️</span>';
                            } elseif ($att && $att['check_in']) {
                                $workDays++;
                                if ($att['is_late']) {
                                    $lateDays++;
                                    $cellBg = $isNightDay ? '#dde8ff' : '#fffbf0';
                                    $cellContent = '<span class="text-warning fw-bold" style="font-size:11px;" title="Trễ ' . $att['late_minutes'] . ' phút">⚡</span>';
                                } else {
                                    $cellBg = $isNightDay ? '#e8f0ff' : '#ffffff';
                                    $cellContent = '<span class="text-success fw-bold" style="font-size:11px;">✓</span>';
                                }
                            } else {
                                $cellBg = $isNightDay ? '#f5e8ff' : '#fff5f5';
                                $cellContent = '<span class="text-danger" style="font-size:11px;">✗</span>';
                            }
                        ?>
                        <td class="text-center px-0" style="background:<?= $cellBg ?>;">
                            <?= $cellContent ?>
                        </td>
                        <?php endfor; ?>

                        <!-- Tổng kết -->
                        <td class="text-center fw-bold text-success small"><?= $workDays ?></td>
                        <td class="text-center fw-bold text-warning small"><?= $lateDays ?></td>
                        <td class="text-center fw-bold text-info small"><?= $leaveDays ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<style>
.schedule-table th, .schedule-table td { vertical-align: middle; }
.sticky-col { position: sticky; left: 0; background: #fff; z-index: 2; box-shadow: 2px 0 4px rgba(0,0,0,.08); }
.legend-item { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; }
.dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
</style>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>