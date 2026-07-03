<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();

// Xử lý form chấm công thủ công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $today  = date('Y-m-d');
    $now    = date('Y-m-d H:i:s');

    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' && is_numeric($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' && is_numeric($_POST['lng']) ? (float)$_POST['lng'] : null;
    if ($lat !== null && ($lat < -90  || $lat > 90))  $lat = null;
    if ($lng !== null && ($lng < -180 || $lng > 180)) $lng = null;

    // Bắt buộc phải có GPS — nếu không có thì từ chối
    if ($lat === null || $lng === null) {
        setFlash('danger', '📍 Không thể chấm công: Vui lòng bật định vị (GPS) và cho phép trình duyệt truy cập vị trí trước khi chấm công.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    // ── Kiểm tra cài đặt vị trí công ty ──────────────────────────────
    try {
        $locStmt = $pdo->query("SELECT * FROM attendance_location_settings LIMIT 1");
        $locSetting = $locStmt ? $locStmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (Throwable $e) {
        $locSetting = null;
    }

    if ($locSetting && (int)$locSetting['is_enabled'] === 1) {
        $R    = 6371000;
        $lat1 = deg2rad((float)$locSetting['latitude']);
        $lat2 = deg2rad($lat);
        $dLat = deg2rad($lat - (float)$locSetting['latitude']);
        $dLng = deg2rad($lng - (float)$locSetting['longitude']);
        $a    = sin($dLat/2)*sin($dLat/2) + cos($lat1)*cos($lat2)*sin($dLng/2)*sin($dLng/2);
        $dist = $R * 2 * atan2(sqrt($a), sqrt(1-$a));

        if ($dist > (int)$locSetting['radius_meters']) {
            $distRound = round($dist);
            setFlash('danger', "❌ Bạn chưa có mặt tại vị trí <strong>" . htmlspecialchars($locSetting['location_name']) . "</strong>. Khoảng cách hiện tại: <strong>{$distRound}m</strong> (cho phép trong {$locSetting['radius_meters']}m).");
            header('Location: /erp/modules/attendance/index.php');
            exit();
        }
    }

    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0]);

    // Tính location flag
    $locationFlag = 'unknown';
    try {
        $cfgStmt = $pdo->query("SELECT config_key, config_value FROM company_location_config");
        $cfg = [];
        foreach ($cfgStmt->fetchAll(PDO::FETCH_ASSOC) as $r) $cfg[$r['config_key']] = $r['config_value'];
        $companyLat = (float)($cfg['lat'] ?? 0);
        $companyLng = (float)($cfg['lng'] ?? 0);
        $radiusM    = (float)($cfg['radius_meters'] ?? 500);
        $earthR = 6371000;
        $dLat = deg2rad($lat - $companyLat);
        $dLng = deg2rad($lng - $companyLng);
        $a    = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($companyLat))*cos(deg2rad($lat))*sin($dLng/2)*sin($dLng/2);
        $dist = $earthR * 2 * atan2(sqrt($a), sqrt(1-$a));
        $locationFlag = ($dist <= $radiusM) ? 'verified' : 'outside';
    } catch (Throwable $e) {
        $locationFlag = 'unknown';
    }

    $flagMsg = match ($locationFlag) {
        'verified' => ' ✅ Vị trí đã xác minh',
        'outside'  => ' ⚠️ Ngoài phạm vi công ty',
        default    => '',
    };

    if ($action === 'check_in') {
        $existStmt = $pdo->prepare("SELECT id, check_in FROM attendance_logs WHERE user_id = ? AND work_date = ?");
        $existStmt->execute([$user['id'], $today]);
        $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

        try {
            if ($existing) {
                if (!$existing['check_in']) {
                    $pdo->prepare("UPDATE attendance_logs
                        SET check_in = ?, source = 'manual',
                            check_in_ip = ?, check_in_lat = ?, check_in_lng = ?, check_in_location_flag = ?
                        WHERE id = ?")
                        ->execute([$now, $ip, $lat, $lng, $locationFlag, $existing['id']]);
                }
            } else {
                $pdo->prepare("INSERT INTO attendance_logs
                    (user_id, check_in, work_date, source, check_in_ip, check_in_lat, check_in_lng, check_in_location_flag)
                    VALUES (?, ?, ?, 'manual', ?, ?, ?, ?)")
                    ->execute([$user['id'], $now, $today, $ip, $lat, $lng, $locationFlag]);
            }
        } catch (Throwable $e) {
            error_log('check_in with location failed: ' . $e->getMessage());
            try {
                if ($existing) {
                    if (!$existing['check_in'])
                        $pdo->prepare("UPDATE attendance_logs SET check_in = ?, source = 'manual' WHERE id = ?")
                            ->execute([$now, $existing['id']]);
                } else {
                    $pdo->prepare("INSERT INTO attendance_logs (user_id, check_in, work_date, source) VALUES (?, ?, ?, 'manual')")
                        ->execute([$user['id'], $now, $today]);
                }
            } catch (Throwable $e2) {
                error_log('check_in fallback failed: ' . $e2->getMessage());
            }
        }
        setFlash('success', 'Chấm công vào ca thành công lúc ' . date('H:i') . $flagMsg);

    } elseif ($action === 'check_out') {
        try {
            $pdo->prepare("UPDATE attendance_logs
                SET check_out = ?,
                    work_hours = ROUND(TIMESTAMPDIFF(MINUTE, check_in, ?) / 60, 2),
                    check_out_ip = ?, check_out_lat = ?, check_out_lng = ?, check_out_location_flag = ?
                WHERE user_id = ? AND work_date = ? AND check_out IS NULL")
                ->execute([$now, $now, $ip, $lat, $lng, $locationFlag, $user['id'], $today]);
        } catch (Throwable $e) {
            error_log('check_out with location failed: ' . $e->getMessage());
            $pdo->prepare("UPDATE attendance_logs SET check_out = ?, work_hours = ROUND(TIMESTAMPDIFF(MINUTE, check_in, ?) / 60, 2) WHERE user_id = ? AND work_date = ? AND check_out IS NULL")
                ->execute([$now, $now, $user['id'], $today]);
        }
        setFlash('success', 'Chấm công ra ca thành công lúc ' . date('H:i') . $flagMsg);
    }
    header('Location: /erp/modules/attendance/index.php');
    exit();
}

$viewMonth = (int)($_GET['month'] ?? date('m'));
$viewYear  = (int)($_GET['year']  ?? date('Y'));
if ($viewMonth < 1)  { $viewMonth = 12; $viewYear--; }
if ($viewMonth > 12) { $viewMonth = 1;  $viewYear++; }

$today = date('Y-m-d');
$stmt  = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND work_date = ?");
$stmt->execute([$user['id'], $today]);
$todayLog = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? ORDER BY work_date");
$stmt->execute([$user['id'], $viewMonth, $viewYear]);
$monthLogs = [];
foreach ($stmt->fetchAll() as $log) $monthLogs[$log['work_date']] = $log;

$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? AND status = 'approved' AND (MONTH(start_date) = ? OR MONTH(end_date) = ?) AND (YEAR(start_date) = ? OR YEAR(end_date) = ?)");
$stmt->execute([$user['id'], $viewMonth, $viewMonth, $viewYear, $viewYear]);
$leaveDays = [];
foreach ($stmt->fetchAll() as $leave) {
    for ($d = strtotime($leave['start_date']); $d <= strtotime($leave['end_date']); $d += 86400)
        $leaveDays[date('Y-m-d', $d)] = $leave['leave_type'];
}

$totalWorkDays = 0; $totalWorkHours = 0; $lateDays = 0;
foreach ($monthLogs as $log) {
    if ($log['check_in']) {
        $totalWorkDays++;
        $totalWorkHours += $log['work_hours'];
        if (date('H:i', strtotime($log['check_in'])) > '08:15') $lateDays++;
    }
}

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">⏰ Chấm công</h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($user['full_name']) ?> &bull; <?= date('l, d/m/Y') ?></p>
        </div>
        <?php if (hasRole('director','manager','accountant','production')): ?>
        <a href="/erp/modules/attendance/all_attendance.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-table me-1"></i> Xem tất cả nhân viên
        </a>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">📅 Hôm nay - <?= date('d/m/Y') ?></h6>
                </div>
                <div class="card-body text-center py-4">
                    <?php
                    $canCheckIn  = !$todayLog || !$todayLog['check_in'];
                    $canCheckOut = $todayLog && $todayLog['check_in'] && !$todayLog['check_out'];
                    ?>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-6 border-end">
                                <div class="text-muted small mb-1">Giờ vào</div>
                                <div class="fs-4 fw-bold <?= $todayLog && $todayLog['check_in'] ? 'text-success' : 'text-muted' ?>">
                                    <?= $todayLog && $todayLog['check_in'] ? date('H:i', strtotime($todayLog['check_in'])) : '--:--' ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small mb-1">Giờ ra</div>
                                <div class="fs-4 fw-bold <?= $todayLog && $todayLog['check_out'] ? 'text-danger' : 'text-muted' ?>">
                                    <?= $todayLog && $todayLog['check_out'] ? date('H:i', strtotime($todayLog['check_out'])) : '--:--' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($todayLog && $todayLog['check_out']): ?>
                        <div class="alert alert-success py-2">
                            ✅ Đã hoàn thành ca hôm nay<br>
                            <strong><?= $todayLog['work_hours'] ?> giờ</strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Chú ý:</strong> Đang dùng chấm công thủ công.<br>
                            Khi lắp máy chấm công sẽ tự động.
                        </div>
                        <form method="POST" id="attendanceForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="lat" id="inputLat" value="">
                            <input type="hidden" name="lng" id="inputLng" value="">

                            <!-- Trạng thái GPS -->
                            <div id="gpsStatus" class="alert alert-warning py-2 small mb-2">
                                <i class="fas fa-spinner fa-spin me-1"></i>
                                <span id="gpsStatusText">Đang lấy vị trí GPS, vui lòng chờ...</span>
                            </div>

                            <div class="text-muted small mb-3">
                                <i class="fas fa-network-wired me-1"></i>
                                IP của bạn: <code id="displayIp">—</code>
                            </div>

                            <?php if ($canCheckIn): ?>
                                <input type="hidden" name="action" value="check_in">
                                <button type="submit" class="btn btn-success btn-lg w-100 mb-2"
                                        id="btnSubmit"
                                        disabled
                                        onclick="return confirm('Xác nhận chấm công VÀO lúc ' + new Date().toLocaleTimeString('vi-VN') + '?')">
                                    <i class="fas fa-sign-in-alt me-2"></i>Chấm công VÀO
                                </button>
                            <?php elseif ($canCheckOut): ?>
                                <input type="hidden" name="action" value="check_out">
                                <div class="alert alert-info py-2 small mb-2">
                                    Đã vào: <?= date('H:i', strtotime($todayLog['check_in'])) ?>
                                </div>
                                <button type="submit" class="btn btn-danger btn-lg w-100"
                                        id="btnSubmit"
                                        disabled
                                        onclick="return confirm('Xác nhận chấm công RA lúc ' + new Date().toLocaleTimeString('vi-VN') + '?')">
                                    <i class="fas fa-sign-out-alt me-2"></i>Chấm công RA
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">📊 Tháng <?= $viewMonth . '/' . $viewYear ?></h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-check-circle text-success me-2"></i>Ngày công</span>
                            <strong><?= $totalWorkDays ?> ngày</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-clock text-primary me-2"></i>Tổng giờ làm</span>
                            <strong><?= number_format($totalWorkHours, 1) ?> giờ</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-exclamation-circle text-warning me-2"></i>Đi trễ</span>
                            <strong class="text-warning"><?= $lateDays ?> lần</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-umbrella-beach text-info me-2"></i>Ngày nghỉ phép</span>
                            <strong class="text-info"><?= count($leaveDays) ?> ngày</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <a href="?month=<?= $viewMonth-1 ?>&year=<?= $viewYear ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h6 class="mb-0 fw-bold">📅 Tháng <?= $viewMonth . '/' . $viewYear ?></h6>
                    <a href="?month=<?= $viewMonth+1 ?>&year=<?= $viewYear ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div class="card-body p-2">
                    <div class="d-flex flex-wrap gap-2 mb-3 px-2">
                        <span class="badge-legend bg-success text-white">✅ Đúng giờ</span>
                        <span class="badge-legend bg-warning text-dark">⚠️ Đi trễ</span>
                        <span class="badge-legend bg-info text-white">🏖️ Nghỉ phép</span>
                        <span class="badge-legend bg-danger text-white">❌ Vắng</span>
                        <span class="badge-legend bg-light text-muted">– Nghỉ CN</span>
                    </div>

                    <table class="table table-bordered calendar-table mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Thứ 2</th><th>Thứ 3</th><th>Thứ 4</th>
                                <th>Thứ 5</th><th>Thứ 6</th><th>Thứ 7</th><th class="text-danger">CN</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $firstDay    = mktime(0,0,0,$viewMonth,1,$viewYear);
                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN,$viewMonth,$viewYear);
                        $startDow    = date('N', $firstDay);
                        echo '<tr>';
                        for ($i = 1; $i < $startDow; $i++) echo '<td class="bg-light"></td>';
                        $col = $startDow;
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateStr  = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $day);
                            $dow      = date('N', mktime(0,0,0,$viewMonth,$day,$viewYear));
                            $isToday  = ($dateStr === date('Y-m-d'));
                            $isSunday = ($dow == 7);
                            $log      = $monthLogs[$dateStr] ?? null;
                            $isLeave  = isset($leaveDays[$dateStr]);
                            $isFuture = $dateStr > date('Y-m-d');
                            $cellClass = $isToday ? ' today-cell' : '';
                            $content   = '';
                            if ($isSunday) {
                                $cellClass .= ' bg-light text-muted';
                                $content = '<small class="text-muted">CN</small>';
                            } elseif ($isFuture) {
                                $content = '';
                            } elseif ($isLeave && !$log) {
                                $cellClass .= ' leave-cell';
                                $content = '<div class="small text-info fw-bold">🏖️ Phép</div>';
                            } elseif ($log && $log['check_in']) {
                                $isLate = date('H:i', strtotime($log['check_in'])) > '08:15';
                                $locBadge = match ($log['check_in_location_flag'] ?? 'unknown') {
                                    'verified' => '<span class="badge bg-success badge-sm mt-1">📍✅</span>',
                                    'outside'  => '<span class="badge bg-warning text-dark badge-sm mt-1">📍⚠️</span>',
                                    'no_gps'   => '<span class="badge bg-secondary badge-sm mt-1">📍?</span>',
                                    default    => '',
                                };
                                $cellClass .= $isLate ? ' late-cell' : ' present-cell';
                                $content = '<div class="att-time">
                                    <span class="badge bg-success badge-sm">▶ ' . date('H:i', strtotime($log['check_in'])) . '</span><br>
                                    <span class="badge bg-danger badge-sm mt-1">◼ ' . ($log['check_out'] ? date('H:i', strtotime($log['check_out'])) : '?') . '</span>' .
                                    $locBadge . '</div>';
                            } else {
                                $cellClass .= ' absent-cell';
                                $content = '<div class="small text-danger">❌</div>';
                            }
                            echo "<td class='calendar-day $cellClass " . ($isToday ? 'border border-primary border-2' : '') . "'>
                                    <div class='day-number " . ($isToday ? 'fw-bold text-primary' : '') . "'>$day</div>
                                    $content
                                  </td>";
                            if ($col % 7 == 0 && $day < $daysInMonth) echo '</tr><tr>';
                            $col++;
                        }
                        while ($col % 7 != 1) { echo '<td class="bg-light"></td>'; $col++; }
                        echo '</tr>';
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
.calendar-table td { height: 65px; vertical-align: top; padding: 4px; }
.day-number { font-size: 12px; font-weight: 600; margin-bottom: 2px; }
.present-cell { background: #f0fff4; }
.late-cell    { background: #fffbf0; }
.leave-cell   { background: #e8f4fd; }
.absent-cell  { background: #fff5f5; }
.today-cell   { outline: 2px solid #0d6efd !important; }
.att-time .badge-sm { font-size: 10px; padding: 2px 5px; }
.badge-legend { font-size: 11px; padding: 3px 8px; border-radius: 20px; }
</style>

<?php
// Lấy location setting cho JS client-side preview
try {
    $jsLocStmt = $pdo->query("SELECT * FROM attendance_location_settings LIMIT 1");
    $jsLocSetting = $jsLocStmt ? $jsLocStmt->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable $e) {
    $jsLocSetting = null;
}
?>
<script>
const locationConfig = <?= json_encode($jsLocSetting ? [
    'enabled' => (bool)(int)$jsLocSetting['is_enabled'],
    'lat'     => (float)$jsLocSetting['latitude'],
    'lng'     => (float)$jsLocSetting['longitude'],
    'radius'  => (int)$jsLocSetting['radius_meters'],
    'name'    => $jsLocSetting['location_name'],
] : ['enabled' => false]) ?>;

function haversineDistance(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)*Math.sin(dLat/2) +
              Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*
              Math.sin(dLng/2)*Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

const gpsStatusEl = document.getElementById('gpsStatus');
const gpsTextEl   = document.getElementById('gpsStatusText');
const inputLat    = document.getElementById('inputLat');
const inputLng    = document.getElementById('inputLng');
const btnSubmit   = document.getElementById('btnSubmit');
const displayIpEl = document.getElementById('displayIp');

// Hiện IP
if (displayIpEl) {
    fetch('/erp/api/attendance/get_ip.php')
        .then(r => r.json())
        .then(d => { displayIpEl.textContent = d.ip || 'N/A'; })
        .catch(() => { displayIpEl.textContent = 'N/A'; });
}

// GPS bắt buộc — nút chỉ được kích hoạt khi có tọa độ
if (btnSubmit && gpsStatusEl && gpsTextEl && inputLat && inputLng) {
    if (!navigator.geolocation) {
        // Browser không hỗ trợ GPS
        gpsStatusEl.className = 'alert alert-danger py-2 small mb-2';
        gpsTextEl.innerHTML = `<i class="fas fa-times-circle me-1"></i>
            <strong>Trình duyệt không hỗ trợ định vị.</strong>
            Vui lòng dùng Chrome / Firefox hoặc ứng dụng di động để chấm công.`;
        btnSubmit.disabled = true;
        btnSubmit.title = 'Cần bật GPS để chấm công';
    } else {
        navigator.geolocation.getCurrentPosition(
            // ✅ Lấy GPS thành công
            (pos) => {
                inputLat.value = pos.coords.latitude.toFixed(7);
                inputLng.value = pos.coords.longitude.toFixed(7);

                if (locationConfig.enabled) {
                    const dist = haversineDistance(pos.coords.latitude, pos.coords.longitude, locationConfig.lat, locationConfig.lng);
                    const distRound = Math.round(dist);
                    const inRange = dist <= locationConfig.radius;

                    gpsStatusEl.className = 'alert py-2 small mb-2 ' + (inRange ? 'alert-success' : 'alert-danger');
                    gpsTextEl.innerHTML = (inRange
                        ? `<i class="fas fa-check-circle me-1"></i>✅ Tại <strong>${locationConfig.name}</strong> (~${distRound}m)`
                        : `<i class="fas fa-exclamation-triangle me-1"></i>⚠️ Ngoài phạm vi <strong>${locationConfig.name}</strong> (~${distRound}m, cho phép ${locationConfig.radius}m)`)
                        + ` &nbsp;<small class="opacity-75">GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}</small>`;

                    if (!inRange) {
                        btnSubmit.disabled = true;
                        btnSubmit.title = 'Bạn đang ngoài phạm vi công ty';
                    } else {
                        btnSubmit.disabled = false;
                    }
                } else {
                    gpsStatusEl.className = 'alert alert-success py-2 small mb-2';
                    gpsTextEl.innerHTML = `<i class="fas fa-check-circle me-1"></i>GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)} (±${Math.round(pos.coords.accuracy)}m)`;
                    btnSubmit.disabled = false;
                }
            },
            // ❌ Không lấy được GPS — khóa nút, hiện hướng dẫn
            (err) => {
                const reasons = {
                    1: 'Bạn đã từ chối quyền truy cập vị trí.',
                    2: 'Không lấy được tín hiệu GPS.',
                    3: 'Hết thời gian chờ GPS.',
                };
                gpsStatusEl.className = 'alert alert-danger py-2 small mb-2';
                gpsTextEl.innerHTML = `<i class="fas fa-map-marker-alt me-1"></i>
                    <strong>⚠️ Không thể chấm công:</strong> ${reasons[err.code] || 'Lỗi định vị.'}<br>
                    <span class="mt-1 d-block">
                        👉 Hãy <strong>bật định vị</strong> trên thiết bị và <strong>cho phép trình duyệt</strong>
                        truy cập vị trí, sau đó <a href="" class="alert-link">tải lại trang</a>.
                    </span>`;
                btnSubmit.disabled = true;
                btnSubmit.title = 'Cần bật GPS để chấm công';
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    }
}
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
