<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once __DIR__ . '/common.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $action = $_POST['action'] ?? '';
    $today  = date('Y-m-d');
    $now    = date('Y-m-d H:i:s');

    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' && is_numeric($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' && is_numeric($_POST['lng']) ? (float)$_POST['lng'] : null;
    if ($lat !== null && ($lat < -90  || $lat > 90))  $lat = null;
    if ($lng !== null && ($lng < -180 || $lng > 180)) $lng = null;

    if ($lat === null || $lng === null) {
        setFlash('danger', '📍 Không thể chấm công: Vui lòng bật định vị (GPS) và cho phép trình duyệt truy cập vị trí trước khi chấm công.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

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
        $a    = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLng / 2) * sin($dLng / 2);
        $dist = $R * 2 * atan2(sqrt($a), sqrt(1 - $a));

        if ($dist > (int)$locSetting['radius_meters']) {
            $distRound = round($dist);
            setFlash('danger', "❌ Bạn chưa có mặt tại vị trí <strong>" . htmlspecialchars($locSetting['location_name']) . "</strong>. Khoảng cách hiện tại: <strong>{$distRound}m</strong> (cho phép trong {$locSetting['radius_meters']}m).");
            header('Location: /erp/mobile/index.php');
            exit();
        }
    }

    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0]);

    $locationFlag = 'unknown';
    try {
        $cfgStmt = $pdo->query("SELECT config_key, config_value FROM company_location_config");
        $cfg = [];
        foreach ($cfgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cfg[$row['config_key']] = $row['config_value'];
        }
        $companyLat = (float)($cfg['lat'] ?? 0);
        $companyLng = (float)($cfg['lng'] ?? 0);
        $radiusM    = (float)($cfg['radius_meters'] ?? 500);
        $earthR = 6371000;
        $dLat = deg2rad($lat - $companyLat);
        $dLng = deg2rad($lng - $companyLng);
        $a    = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($companyLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
        $dist = $earthR * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
            error_log('mobile check_in with location failed: ' . $e->getMessage());
            try {
                if ($existing) {
                    if (!$existing['check_in']) {
                        $pdo->prepare("UPDATE attendance_logs SET check_in = ?, source = 'manual' WHERE id = ?")
                            ->execute([$now, $existing['id']]);
                    }
                } else {
                    $pdo->prepare("INSERT INTO attendance_logs (user_id, check_in, work_date, source) VALUES (?, ?, ?, 'manual')")
                        ->execute([$user['id'], $now, $today]);
                }
            } catch (Throwable $e2) {
                error_log('mobile check_in fallback failed: ' . $e2->getMessage());
            }
        }
        setFlash('success', 'Chấm công vào ca thành công lúc ' . date('H:i') . $flagMsg);
    } elseif ($action === 'check_out') {
        $openLog = $pdo->prepare("
            SELECT id FROM attendance_logs
            WHERE user_id = ?
              AND check_in IS NOT NULL
              AND check_out IS NULL
              AND work_date >= DATE_SUB(?, INTERVAL 1 DAY)
              AND work_date <= ?
            ORDER BY check_in DESC
            LIMIT 1
        ");
        $openLog->execute([$user['id'], $today, $today]);
        $openLogId = $openLog->fetchColumn();

        if ($openLogId) {
            try {
                $pdo->prepare("UPDATE attendance_logs
                    SET check_out = ?,
                        work_hours = ROUND(TIMESTAMPDIFF(MINUTE, check_in, ?) / 60, 2),
                        check_out_ip = ?, check_out_lat = ?, check_out_lng = ?, check_out_location_flag = ?
                    WHERE id = ? AND check_out IS NULL")
                    ->execute([$now, $now, $ip, $lat, $lng, $locationFlag, $openLogId]);
            } catch (Throwable $e) {
                error_log('mobile check_out with location failed: ' . $e->getMessage());
                $pdo->prepare("UPDATE attendance_logs SET check_out = ?, work_hours = ROUND(TIMESTAMPDIFF(MINUTE, check_in, ?) / 60, 2) WHERE id = ? AND check_out IS NULL")
                    ->execute([$now, $now, $openLogId]);
            }
        }
        setFlash('success', 'Chấm công ra ca thành công lúc ' . date('H:i') . $flagMsg);
    }

    header('Location: /erp/mobile/index.php');
    exit();
}

$today = date('Y-m-d');
$currentMonth = (int)date('m');
$currentYear = (int)date('Y');

$stmt = $pdo->prepare("
    SELECT * FROM attendance_logs
    WHERE user_id = ?
      AND work_date >= DATE_SUB(?, INTERVAL 1 DAY)
      AND work_date <= ?
    ORDER BY CASE WHEN check_in IS NOT NULL AND check_out IS NULL THEN 0 ELSE 1 END ASC,
             work_date DESC
    LIMIT 1
");
$stmt->execute([$user['id'], $today, $today]);
$todayLog = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$shiftStmt = $pdo->prepare("
    SELECT ws.shift_name, ws.start_time, ws.end_time, ws.color, ws.is_night_shift
    FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.user_id = ?
      AND es.effective_date <= ?
      AND (es.end_date IS NULL OR es.end_date >= ?)
    ORDER BY es.effective_date DESC
    LIMIT 1
");
$shiftStmt->execute([$user['id'], $today, $today]);
$todayShift = $shiftStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$monthStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? ORDER BY work_date");
$monthStmt->execute([$user['id'], $currentMonth, $currentYear]);
$monthLogs = [];
foreach ($monthStmt->fetchAll(PDO::FETCH_ASSOC) as $log) {
    $monthLogs[$log['work_date']] = $log;
}

$leaveStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? AND status = 'approved' AND (MONTH(start_date) = ? OR MONTH(end_date) = ?) AND (YEAR(start_date) = ? OR YEAR(end_date) = ?)");
$leaveStmt->execute([$user['id'], $currentMonth, $currentMonth, $currentYear, $currentYear]);
$leaveDays = [];
foreach ($leaveStmt->fetchAll(PDO::FETCH_ASSOC) as $leave) {
    for ($d = strtotime($leave['start_date']); $d <= strtotime($leave['end_date']); $d += 86400) {
        if ((int)date('m', $d) === $currentMonth && (int)date('Y', $d) === $currentYear) {
            $leaveDays[date('Y-m-d', $d)] = $leave['leave_type'];
        }
    }
}

$totalWorkDays = 0;
$totalWorkHours = 0;
$lateDays = 0;
foreach ($monthLogs as $log) {
    if (!empty($log['check_in'])) {
        $totalWorkDays++;
        $totalWorkHours += (float)($log['work_hours'] ?? 0);
        if (date('H:i', strtotime($log['check_in'])) > '08:15') {
            $lateDays++;
        }
    }
}

$historyStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? ORDER BY work_date DESC, id DESC LIMIT 7");
$historyStmt->execute([$user['id']]);
$recentLogs = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();
$canCheckIn  = !$todayLog || !$todayLog['check_in'];
$canCheckOut = $todayLog && $todayLog['check_in'] && !$todayLog['check_out'];

try {
    $jsLocStmt = $pdo->query("SELECT * FROM attendance_location_settings LIMIT 1");
    $jsLocSetting = $jsLocStmt ? $jsLocStmt->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable $e) {
    $jsLocSetting = null;
}

mobilePageStart('Chấm công', $user);
showFlash();
?>

<div class="mobile-card mb-3">
    <div class="clock-panel">
        <div class="date-text" id="liveDate"><?= e(formatDate($today, 'd/m/Y')) ?></div>
        <div class="time-text" id="liveClock"><?= date('H:i:s') ?></div>
    </div>
</div>

<div class="card attendance-card mb-3">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
            <div>
                <div class="label-muted">Hôm nay</div>
                <div class="fw-bold fs-5"><?= e(formatDate($today, 'd/m/Y')) ?></div>
            </div>
            <?php if ($todayShift): ?>
            <span class="badge rounded-pill" style="background: <?= e($todayShift['is_night_shift'] ? '#dbeafe' : '#eef2ff') ?>; color: #1e3a8a;">
                <?= e($todayShift['shift_name']) ?>
                (<?= e(substr($todayShift['start_time'], 0, 5)) ?>–<?= e(substr($todayShift['end_time'], 0, 5)) ?>)
            </span>
            <?php else: ?>
            <span class="badge text-bg-secondary rounded-pill">Chưa phân ca</span>
            <?php endif; ?>
        </div>

        <div class="row text-center mb-3">
            <div class="col-6 border-end">
                <div class="label-muted">Giờ vào</div>
                <div class="fs-3 fw-bold <?= $todayLog && $todayLog['check_in'] ? 'text-success' : 'text-muted' ?>">
                    <?= $todayLog && $todayLog['check_in'] ? e(date('H:i', strtotime($todayLog['check_in']))) : '--:--' ?>
                </div>
            </div>
            <div class="col-6">
                <div class="label-muted">Giờ ra</div>
                <div class="fs-3 fw-bold <?= $todayLog && $todayLog['check_out'] ? 'text-danger' : 'text-muted' ?>">
                    <?= $todayLog && $todayLog['check_out'] ? e(date('H:i', strtotime($todayLog['check_out']))) : '--:--' ?>
                </div>
            </div>
        </div>

        <?php if ($todayLog && $todayLog['check_out']): ?>
        <div class="alert alert-success mb-0 text-center">
            ✅ Đã hoàn thành ca hôm nay<br>
            <strong><?= number_format((float)($todayLog['work_hours'] ?? 0), 2) ?> giờ</strong>
        </div>
        <?php else: ?>
        <form method="POST" id="attendanceForm">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="lat" id="inputLat" value="">
            <input type="hidden" name="lng" id="inputLng" value="">

            <?php if ($canCheckOut): ?>
            <div class="alert alert-info py-2 small text-center">
                Đã vào lúc <?= e(date('H:i', strtotime($todayLog['check_in']))) ?>
            </div>
            <?php endif; ?>

            <?php if ($canCheckIn): ?>
                <input type="hidden" name="action" value="check_in">
                <button type="submit" class="btn btn-success w-100 check-btn mb-2" id="btnSubmit" disabled onclick="return confirm('Xác nhận chấm công VÀO lúc ' + new Date().toLocaleTimeString('vi-VN') + '?')">
                    CHẤM CÔNG VÀO
                </button>
            <?php elseif ($canCheckOut): ?>
                <input type="hidden" name="action" value="check_out">
                <button type="submit" class="btn btn-danger w-100 check-btn mb-2" id="btnSubmit" disabled onclick="return confirm('Xác nhận chấm công RA lúc ' + new Date().toLocaleTimeString('vi-VN') + '?')">
                    CHẤM CÔNG RA
                </button>
            <?php endif; ?>

            <div id="gpsStatus" class="alert alert-warning py-2 small mb-2 text-center">
                <i class="fas fa-spinner fa-spin me-1"></i>
                <span id="gpsStatusText">Đang lấy vị trí GPS, vui lòng chờ...</span>
            </div>
            <div class="text-center text-muted small">
                <i class="fas fa-network-wired me-1"></i>
                IP: <code id="displayIp">—</code>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="stats-grid mb-3">
    <div class="stat-tile">
        <span class="icon">📅</span>
        <div class="value"><?= $totalWorkDays ?></div>
        <div class="label">Ngày công</div>
    </div>
    <div class="stat-tile">
        <span class="icon">⏱</span>
        <div class="value"><?= number_format($totalWorkHours, 1) ?></div>
        <div class="label">Giờ làm</div>
    </div>
    <div class="stat-tile">
        <span class="icon">⚡</span>
        <div class="value"><?= $lateDays ?></div>
        <div class="label">Đi trễ</div>
    </div>
    <div class="stat-tile">
        <span class="icon">🏖</span>
        <div class="value"><?= count($leaveDays) ?></div>
        <div class="label">Nghỉ phép</div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="fw-bold">Lịch sử 7 ngày gần nhất</div>
    <a href="/erp/modules/attendance/index.php" class="small text-decoration-none">Xem desktop</a>
</div>
<div class="list-compact">
    <?php foreach ($recentLogs as $log): ?>
        <?php
        $workDate = $log['work_date'];
        $label = formatDate($workDate, 'd/m/Y');
        if ($workDate === date('Y-m-d')) {
            $label = 'Hôm nay';
        } elseif ($workDate === date('Y-m-d', strtotime('-1 day'))) {
            $label = 'Hôm qua';
        }
        ?>
        <div class="history-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-semibold"><?= e($label) ?></div>
                    <div class="small text-muted">
                        ✓ Vào: <?= $log['check_in'] ? e(date('H:i', strtotime($log['check_in']))) : '--:--' ?>
                        &nbsp; • &nbsp;
                        Ra: <?= $log['check_out'] ? e(date('H:i', strtotime($log['check_out']))) : '--:--' ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-primary"><?= number_format((float)($log['work_hours'] ?? 0), 1) ?>h</div>
                    <?php if (!empty($log['check_in_location_flag'])): ?>
                    <div class="small text-muted">GPS: <?= e($log['check_in_location_flag']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($recentLogs)): ?>
    <div class="history-item text-center text-muted">Chưa có lịch sử chấm công.</div>
    <?php endif; ?>
</div>

<?php
$locationConfig = $jsLocSetting ? [
    'enabled' => (bool)(int)$jsLocSetting['is_enabled'],
    'lat' => (float)$jsLocSetting['latitude'],
    'lng' => (float)$jsLocSetting['longitude'],
    'radius' => (int)$jsLocSetting['radius_meters'],
    'name' => $jsLocSetting['location_name'],
] : ['enabled' => false];
?>
<script>
const locationConfig = <?= json_encode($locationConfig, JSON_UNESCAPED_UNICODE) ?>;

function updateClock() {
    const now = new Date();
    const timeText = now.toLocaleTimeString('vi-VN');
    const weekday = now.toLocaleDateString('vi-VN', { weekday: 'long' });
    const dateText = `${weekday.charAt(0).toUpperCase()}${weekday.slice(1)}, ${now.toLocaleDateString('vi-VN')}`;
    const liveDate = document.getElementById('liveDate');
    const liveClock = document.getElementById('liveClock');
    if (liveDate) liveDate.textContent = dateText;
    if (liveClock) liveClock.textContent = timeText;
}

function haversineDistance(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

updateClock();
setInterval(updateClock, 1000);

const gpsStatusEl = document.getElementById('gpsStatus');
const gpsTextEl = document.getElementById('gpsStatusText');
const inputLat = document.getElementById('inputLat');
const inputLng = document.getElementById('inputLng');
const btnSubmit = document.getElementById('btnSubmit');
const displayIpEl = document.getElementById('displayIp');

if (displayIpEl) {
    fetch('/erp/api/attendance/get_ip.php')
        .then((response) => response.json())
        .then((data) => { displayIpEl.textContent = data.ip || 'N/A'; })
        .catch(() => { displayIpEl.textContent = 'N/A'; });
}

if (btnSubmit && gpsStatusEl && gpsTextEl && inputLat && inputLng) {
    if (!navigator.geolocation) {
        gpsStatusEl.className = 'alert alert-danger py-2 small mb-2 text-center';
        gpsTextEl.innerHTML = '<strong>Trình duyệt không hỗ trợ định vị.</strong>';
        btnSubmit.disabled = true;
    } else {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                inputLat.value = pos.coords.latitude.toFixed(7);
                inputLng.value = pos.coords.longitude.toFixed(7);

                if (locationConfig.enabled) {
                    const dist = haversineDistance(pos.coords.latitude, pos.coords.longitude, locationConfig.lat, locationConfig.lng);
                    const distRound = Math.round(dist);
                    const inRange = dist <= locationConfig.radius;
                    gpsStatusEl.className = 'alert py-2 small mb-2 text-center ' + (inRange ? 'alert-success' : 'alert-danger');
                    gpsTextEl.innerHTML = inRange
                        ? `✅ Tại <strong>${locationConfig.name}</strong> (~${distRound}m)`
                        : `⚠️ Ngoài phạm vi <strong>${locationConfig.name}</strong> (~${distRound}m, cho phép ${locationConfig.radius}m)`;
                    btnSubmit.disabled = !inRange;
                } else {
                    gpsStatusEl.className = 'alert alert-success py-2 small mb-2 text-center';
                    gpsTextEl.innerHTML = `GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`;
                    btnSubmit.disabled = false;
                }
            },
            (err) => {
                const reasons = {
                    1: 'Bạn đã từ chối quyền truy cập vị trí.',
                    2: 'Không lấy được tín hiệu GPS.',
                    3: 'Hết thời gian chờ GPS.',
                };
                gpsStatusEl.className = 'alert alert-danger py-2 small mb-2 text-center';
                gpsTextEl.innerHTML = `⚠️ ${reasons[err.code] || 'Lỗi định vị.'}`;
                btnSubmit.disabled = true;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
}
</script>

<?php mobilePageEnd(); ?>
