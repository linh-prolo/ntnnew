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
    if (!in_array($action, ['check_in', 'check_out'], true)) {
        setFlash('danger', 'Yêu cầu chấm công không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/index.php');
        exit();
    }
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

    // ── Device Fingerprint ────────────────────────────────────────────────
    $deviceId = trim($_POST['device_id'] ?? '');
    // Chỉ chấp nhận hex 64 ký tự (SHA-256)
    if (!preg_match('/^[0-9a-f]{64}$/', $deviceId)) {
        $deviceId = null;
    }

    // Kiểm tra cùng thiết bị với NV khác trong ngày hôm nay
    $sameDeviceAlert = 0;
    $sameDeviceUsers = [];
    if ($deviceId) {
        try {
            $devStmt = $pdo->prepare("
                SELECT al.user_id, u.full_name, u.employee_code
                FROM attendance_logs al
                JOIN users u ON u.id = al.user_id
                WHERE al.device_id = ?
                  AND al.work_date = ?
                  AND al.user_id != ?
                  AND al.check_in IS NOT NULL
            ");
            $devStmt->execute([$deviceId, $today, $user['id']]);
            $sameDeviceUsers = $devStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($sameDeviceUsers)) {
                $sameDeviceAlert = 1;
            }
        } catch (Throwable $e) {
            error_log('mobile device check error: ' . $e->getMessage());
        }
    }

    // ── Xử lý ảnh chụp ────────────────────────────────────────────────────
    $photoData = trim($_POST['photo_data'] ?? '');
    $photoPath = null;
    $photoPrefix = 'data:image/jpeg;base64,';
    // Giới hạn đầu vào gốc lớn hơn file nén để tránh payload base64 quá lớn.
    $minPhotoBytes = 1000;
    $maxPhotoBytes = 800000;
    $maxCompressedPhotoBytes = 300000;

    if ($photoData === '' || !str_starts_with($photoData, $photoPrefix)) {
        setFlash('danger', '📸 Không thể chấm công: Vui lòng chụp ảnh xác nhận trước khi chấm công.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $base64 = substr($photoData, 23);
    $imgBinary = base64_decode($base64, true);
    if ($imgBinary === false || strlen($imgBinary) < $minPhotoBytes || strlen($imgBinary) > $maxPhotoBytes) {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $imgInfo = @getimagesizefromstring($imgBinary);
    if ($imgInfo === false || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        setFlash('danger', '📸 Máy chủ chưa hỗ trợ xử lý ảnh chấm công. Liên hệ quản trị viên.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $image = @imagecreatefromstring($imgBinary);
    if ($image === false) {
        setFlash('danger', '📸 Không thể xử lý ảnh chụp. Vui lòng thử lại.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    ob_start();
    imagejpeg($image, null, 80);
    $compressedBinary = (string)ob_get_clean();
    imagedestroy($image);

    if ($compressedBinary === '' || strlen($compressedBinary) < $minPhotoBytes || strlen($compressedBinary) > $maxCompressedPhotoBytes) {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng chụp lại gần khuôn mặt hơn.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $uploadDate = date('Y-m-d');
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/erp/uploads/attendance/' . $uploadDate . '/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        setFlash('danger', '📸 Không thể tạo thư mục lưu ảnh. Liên hệ quản trị viên.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

    $actionFileTag = $action === 'check_in' ? 'in' : 'out';
    $filename = $user['id'] . '_' . $actionFileTag . '_' . date('Ymd_His') . '.jpg';
    $fullPath = $uploadDir . $filename;
    $photoPath = '/erp/uploads/attendance/' . $uploadDate . '/' . $filename;
    if (file_put_contents($fullPath, $compressedBinary) === false) {
        setFlash('danger', '📸 Không thể lưu ảnh. Liên hệ quản trị viên.');
        header('Location: /erp/mobile/index.php');
        exit();
    }

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
                            check_in_ip = ?, check_in_lat = ?, check_in_lng = ?, check_in_location_flag = ?,
                            device_id = ?, same_device_alert = ?, check_in_photo = ?
                        WHERE id = ?")
                        ->execute([$now, $ip, $lat, $lng, $locationFlag, $deviceId, $sameDeviceAlert, $photoPath, $existing['id']]);
                }
            } else {
                $pdo->prepare("INSERT INTO attendance_logs
                    (user_id, check_in, work_date, source, check_in_ip, check_in_lat, check_in_lng, check_in_location_flag, device_id, same_device_alert, check_in_photo)
                    VALUES (?, ?, ?, 'manual', ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$user['id'], $now, $today, $ip, $lat, $lng, $locationFlag, $deviceId, $sameDeviceAlert, $photoPath]);
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

        // Gửi notification cảnh báo cho director/manager/accountant
        if ($sameDeviceAlert && !empty($sameDeviceUsers)) {
            try {
                $otherNames = implode(', ', array_map(fn($u) => $u['full_name'] . ' (' . $u['employee_code'] . ')', $sameDeviceUsers));
                $alertMsg = "⚠️ Cảnh báo chấm công hộ: " . $user['full_name'] . " (" . $user['employee_code'] . ") dùng cùng thiết bị với {$otherNames} vào ngày " . date('d/m/Y') . ". Vui lòng kiểm tra!";

                $mgrStmt = $pdo->query("
                    SELECT u.id FROM users u
                    JOIN roles r ON r.id = u.role_id
                    WHERE r.name IN ('director', 'manager', 'accountant') AND u.is_active = 1
                ");
                $managers = $mgrStmt->fetchAll(PDO::FETCH_COLUMN);

                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, reference_id, created_at)
                    VALUES (?, '⚠️ Nghi vấn chấm công hộ', ?, 'same_device_alert', ?, NOW())
                ");
                foreach ($managers as $mgr) {
                    $notifStmt->execute([$mgr, $alertMsg, $user['id']]);
                }
            } catch (Throwable $e) {
                error_log('mobile same_device_alert notification error: ' . $e->getMessage());
            }

            try {
                $otherIds = array_column($sameDeviceUsers, 'user_id');
                $placeholders = implode(',', array_fill(0, count($otherIds), '?'));
                $updateParams = array_merge([$today], $otherIds);
                $pdo->prepare("UPDATE attendance_logs SET same_device_alert = 1 WHERE work_date = ? AND user_id IN ($placeholders)")
                    ->execute($updateParams);
            } catch (Throwable $e) {
                error_log('mobile same_device_alert update others error: ' . $e->getMessage());
            }

            setFlash('warning', '⚠️ Cảnh báo: Thiết bị của bạn đã được dùng để chấm công bởi nhân viên khác trong ngày hôm nay. Quản lý đã được thông báo để kiểm tra.');
        } else {
            setFlash('success', 'Chấm công vào ca thành công lúc ' . date('H:i') . $flagMsg);
        }
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
                        check_out_ip = ?, check_out_lat = ?, check_out_lng = ?, check_out_location_flag = ?, check_out_photo = ?
                    WHERE id = ? AND check_out IS NULL")
                    ->execute([$now, $now, $ip, $lat, $lng, $locationFlag, $photoPath, $openLogId]);
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
            <input type="hidden" name="device_id" id="inputDeviceId" value="">
            <input type="hidden" name="photo_data" id="inputPhotoData" value="">

            <?php if ($canCheckOut): ?>
            <div class="alert alert-info py-2 small text-center">
                Đã vào lúc <?= e(date('H:i', strtotime($todayLog['check_in']))) ?>
            </div>
            <?php endif; ?>

            <?php if ($canCheckIn): ?>
                <input type="hidden" name="action" value="check_in">
                <button type="button" class="btn btn-success w-100 check-btn mb-2" id="btnStartCamera" disabled>
                    CHẤM CÔNG VÀO
                </button>
            <?php elseif ($canCheckOut): ?>
                <input type="hidden" name="action" value="check_out">
                <button type="button" class="btn btn-danger w-100 check-btn mb-2" id="btnStartCamera" disabled>
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

        <div id="mobileCameraOverlay" class="attendance-camera-overlay" style="display:none;">
            <div class="attendance-camera-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>📸 Xác nhận khuôn mặt</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCloseCamera">Đóng</button>
                </div>
                <div id="cameraError" class="alert alert-danger py-2 small mb-2" style="display:none;"></div>
                <div id="cameraSection" class="mb-2">
                    <video id="cameraVideo" class="w-100 rounded" autoplay playsinline muted style="background:#000;max-height:65vh;"></video>
                    <button type="button" class="btn btn-outline-primary w-100 mt-2" id="btnCapture" disabled>📸 Chụp ảnh</button>
                </div>
                <div id="previewSection" style="display:none;">
                    <img id="photoPreview" class="img-fluid rounded w-100 mb-2" alt="Ảnh đã chụp" style="max-height:65vh;object-fit:cover;">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="btnRetake">🔄 Chụp lại</button>
                        <?php if ($canCheckIn): ?>
                        <button type="button" class="btn btn-success" id="btnConfirmPhoto" disabled>Xác nhận chấm công VÀO</button>
                        <?php elseif ($canCheckOut): ?>
                        <button type="button" class="btn btn-danger" id="btnConfirmPhoto" disabled>Xác nhận chấm công RA</button>
                        <?php endif; ?>
                    </div>
                </div>
                <canvas id="photoCanvas" style="display:none;"></canvas>
            </div>
        </div>
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
<style>
.attendance-camera-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(15, 23, 42, 0.94);
    padding: 12px;
}
.attendance-camera-card {
    background: #fff;
    border-radius: 14px;
    height: 100%;
    overflow: auto;
    padding: 12px;
}
</style>
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
const formEl = document.getElementById('attendanceForm');
const inputPhotoData = document.getElementById('inputPhotoData');
const btnStartCamera = document.getElementById('btnStartCamera');
const btnCapture = document.getElementById('btnCapture');
const btnRetake = document.getElementById('btnRetake');
const btnConfirmPhoto = document.getElementById('btnConfirmPhoto');
const btnCloseCamera = document.getElementById('btnCloseCamera');
const cameraVideo = document.getElementById('cameraVideo');
const photoCanvas = document.getElementById('photoCanvas');
const photoPreview = document.getElementById('photoPreview');
const cameraSection = document.getElementById('cameraSection');
const previewSection = document.getElementById('previewSection');
const cameraError = document.getElementById('cameraError');
const cameraOverlay = document.getElementById('mobileCameraOverlay');
const displayIpEl = document.getElementById('displayIp');
let gpsReady = false;
let photoReady = false;
let stream = null;
const MAX_PHOTO_BYTES = 300000;

if (displayIpEl) {
    fetch('/erp/api/attendance/get_ip.php')
        .then((response) => response.json())
        .then((data) => { displayIpEl.textContent = data.ip || 'N/A'; })
        .catch(() => { displayIpEl.textContent = 'N/A'; });
}

function checkReadyToSubmit() {
    if (btnConfirmPhoto) {
        btnConfirmPhoto.disabled = !(gpsReady && photoReady);
    }
    if (btnStartCamera) {
        btnStartCamera.disabled = !gpsReady;
    }
}

function showCameraError(message) {
    if (cameraError) {
        cameraError.style.display = '';
        cameraError.innerHTML = `<i class="fas fa-times-circle me-1"></i>${message}`;
    }
    if (btnCapture) {
        btnCapture.disabled = true;
        btnCapture.title = 'Cần bật camera để chấm công';
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
}

function resetPreview() {
    if (previewSection) previewSection.style.display = 'none';
    if (cameraSection) cameraSection.style.display = '';
    if (inputPhotoData) inputPhotoData.value = '';
    photoReady = false;
    checkReadyToSubmit();
}

async function startCamera() {
    if (!cameraVideo || !btnCapture) return;

    if (!window.isSecureContext && !['localhost', '127.0.0.1', '::1'].includes(location.hostname)) {
        showCameraError('<strong>⚠️ Camera yêu cầu HTTPS.</strong> Vui lòng truy cập bằng HTTPS để chấm công.');
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('<strong>⚠️ Trình duyệt không hỗ trợ camera.</strong>');
        return;
    }

    if (cameraError) {
        cameraError.style.display = 'none';
        cameraError.innerHTML = '';
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false,
        });
        cameraVideo.srcObject = stream;
        cameraVideo.onloadedmetadata = () => {
            btnCapture.disabled = false;
            btnCapture.title = '';
        };
    } catch (err) {
        showCameraError(`<strong>⚠️ Không thể mở camera:</strong> ${err.message}<br><small>Vui lòng cho phép quyền camera để chấm công.</small>`);
    }
}

btnStartCamera?.addEventListener('click', async () => {
    if (!gpsReady || !cameraOverlay) return;
    cameraOverlay.style.display = '';
    resetPreview();
    await startCamera();
});

btnCloseCamera?.addEventListener('click', () => {
    if (cameraOverlay) cameraOverlay.style.display = 'none';
    stopCamera();
    resetPreview();
});

btnCapture?.addEventListener('click', () => {
    if (!cameraVideo || !photoCanvas || !photoPreview || !inputPhotoData) return;
    const context = photoCanvas.getContext('2d');
    if (!context) return;

    photoCanvas.width = cameraVideo.videoWidth || 640;
    photoCanvas.height = cameraVideo.videoHeight || 480;
    context.drawImage(cameraVideo, 0, 0, photoCanvas.width, photoCanvas.height);

    const imgData = photoCanvas.toDataURL('image/jpeg', 0.8);
    const approxBytes = Math.ceil((imgData.length - 'data:image/jpeg;base64,'.length) * 3 / 4);
    if (approxBytes > MAX_PHOTO_BYTES) {
        alert('Ảnh chụp quá lớn. Vui lòng chụp lại gần hơn.');
        return;
    }

    photoPreview.src = imgData;
    inputPhotoData.value = imgData;
    if (cameraSection) cameraSection.style.display = 'none';
    if (previewSection) previewSection.style.display = '';
    photoReady = true;
    checkReadyToSubmit();
});

btnRetake?.addEventListener('click', () => {
    resetPreview();
});

btnConfirmPhoto?.addEventListener('click', () => {
    if (!formEl || !gpsReady || !photoReady) return;
    const action = <?= json_encode($canCheckIn ? 'check_in' : 'check_out') ?>;
    const actionText = action === 'check_in' ? 'VÀO' : 'RA';
    if (!confirm(`Xác nhận chấm công ${actionText} lúc ${new Date().toLocaleTimeString('vi-VN')}?`)) return;
    stopCamera();
    if (cameraOverlay) cameraOverlay.style.display = 'none';
    formEl.submit();
});

formEl?.addEventListener('submit', (event) => {
    if (!gpsReady || !photoReady || !inputPhotoData?.value) {
        event.preventDefault();
    }
});

if (gpsStatusEl && gpsTextEl && inputLat && inputLng) {
    if (!navigator.geolocation) {
        gpsStatusEl.className = 'alert alert-danger py-2 small mb-2 text-center';
        gpsTextEl.innerHTML = '<strong>Trình duyệt không hỗ trợ định vị.</strong> Không thể chấm công.';
        gpsReady = false;
        checkReadyToSubmit();
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
                    gpsReady = inRange;
                    checkReadyToSubmit();
                } else {
                    gpsStatusEl.className = 'alert alert-success py-2 small mb-2 text-center';
                    gpsTextEl.innerHTML = `GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`;
                    gpsReady = true;
                    checkReadyToSubmit();
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
                gpsReady = false;
                checkReadyToSubmit();
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
}

// ── Device Fingerprint ──────────────────────────────────────────────────
async function getDeviceId() {
    const raw = [
        navigator.userAgent,
        navigator.language,
        screen.width + 'x' + screen.height,
        screen.colorDepth,
        Intl.DateTimeFormat().resolvedOptions().timeZone,
        navigator.hardwareConcurrency || '',
        navigator.platform || '',
    ].join('|');

    const encoder = new TextEncoder();
    const data = encoder.encode(raw);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

getDeviceId().then(id => {
    const el = document.getElementById('inputDeviceId');
    if (el) el.value = id;
}).catch(() => {});

window.addEventListener('beforeunload', stopCamera);
</script>

<?php mobilePageEnd(); ?>
