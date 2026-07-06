<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();

// ── Schema bootstrap: thêm cột cảnh báo thiếu check-out nếu chưa tồn tại ──
foreach ([
    "ALTER TABLE attendance_logs ADD COLUMN missing_checkout TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE attendance_logs ADD COLUMN missing_checkout_note VARCHAR(255) NULL",
    "ALTER TABLE attendance_logs ADD COLUMN auto_closed_at DATETIME NULL",
] as $_sql) {
    try { $pdo->exec($_sql); } catch (Throwable $_e) { /* cột đã tồn tại */ }
}

// ── Lấy ca làm việc của user tại ngày cụ thể ────────────────────────────────
if (!function_exists('attGetShiftAtDate')) {
    function attGetShiftAtDate(PDO $pdo, int $userId, string $date): ?array {
        try {
            $st = $pdo->prepare("
                SELECT ws.* FROM employee_shifts es
                JOIN work_shifts ws ON es.shift_id = ws.id
                WHERE es.user_id = ? AND es.effective_date <= ?
                  AND (es.end_date IS NULL OR es.end_date >= ?)
                ORDER BY es.effective_date DESC LIMIT 1
            ");
            $st->execute([$userId, $date, $date]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

// ── Tính mốc thời gian reset cho log mở (Unix timestamp) ────────────────────
// Ca đêm (is_night_shift=1): reset sau 11:59:59 ngày hôm sau
// Ca ngày / hành chính:     reset sau 23:59:59 của work_date
if (!function_exists('attGetResetThreshold')) {
    function attGetResetThreshold(?array $shift, string $workDate): int {
        if ((int)($shift['is_night_shift'] ?? 0) === 1) {
            return (int)strtotime($workDate . ' +1 day 11:59:59');
        }
        return (int)strtotime($workDate . ' 23:59:59');
    }
}

// ── Đánh dấu log mở là thiếu check-out (không điền check_out giả) ─────────
if (!function_exists('attMarkMissingCheckout')) {
    function attMarkMissingCheckout(PDO $pdo, int $logId): void {
        try {
            $pdo->prepare("
                UPDATE attendance_logs
                SET missing_checkout = 1,
                    missing_checkout_note = 'Quên chấm công ra (tự động đánh dấu)',
                    auto_closed_at = NOW()
                WHERE id = ? AND check_out IS NULL
            ")->execute([$logId]);
        } catch (Throwable $e) {
            error_log('attMarkMissingCheckout failed: ' . $e->getMessage());
        }
    }
}

// Xử lý form chấm công thủ công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['check_in', 'check_out'], true)) {
        setFlash('danger', 'Yêu cầu chấm công không hợp lệ.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }
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
            error_log('device check error: ' . $e->getMessage());
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
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    $base64 = substr($photoData, strlen($photoPrefix));
    $imgBinary = base64_decode($base64, true);
    if ($imgBinary === false || strlen($imgBinary) < $minPhotoBytes || strlen($imgBinary) > $maxPhotoBytes) {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    $imgInfo = @getimagesizefromstring($imgBinary);
    if ($imgInfo === false || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        setFlash('danger', '📸 Máy chủ chưa hỗ trợ xử lý ảnh chấm công. Liên hệ quản trị viên.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    $image = @imagecreatefromstring($imgBinary);
    if ($image === false) {
        setFlash('danger', '📸 Không thể xử lý ảnh chụp. Vui lòng thử lại.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    ob_start();
    imagejpeg($image, null, 80);
    $compressedBinary = ob_get_clean();
    imagedestroy($image);
    if ($compressedBinary === false) {
        setFlash('danger', '📸 Không thể xử lý ảnh chụp. Vui lòng thử lại.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    if ($compressedBinary === '' || strlen($compressedBinary) < $minPhotoBytes || strlen($compressedBinary) > $maxCompressedPhotoBytes) {
        setFlash('danger', '📸 Ảnh chụp không hợp lệ. Vui lòng chụp lại gần khuôn mặt hơn.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    $uploadDate = date('Y-m-d');
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/erp/uploads/attendance/' . $uploadDate . '/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        setFlash('danger', '📸 Không thể tạo thư mục lưu ảnh. Liên hệ quản trị viên.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

    $actionFileTag = $action === 'check_in' ? 'in' : 'out';
    $filename = $user['id'] . '_' . $actionFileTag . '_' . date('Ymd_His') . '.jpg';
    $fullPath = $uploadDir . $filename;
    $photoPath = '/erp/uploads/attendance/' . $uploadDate . '/' . $filename;
    if (file_put_contents($fullPath, $compressedBinary) === false) {
        setFlash('danger', '📸 Không thể lưu ảnh. Liên hệ quản trị viên.');
        header('Location: /erp/modules/attendance/index.php');
        exit();
    }

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
        // ── Kiểm tra log mở từ ngày trước – áp dụng quy tắc reset theo ca ───
        try {
            $priorOpenStmt = $pdo->prepare("
                SELECT * FROM attendance_logs
                WHERE user_id = ?
                  AND check_in IS NOT NULL
                  AND check_out IS NULL
                  AND (missing_checkout = 0 OR missing_checkout IS NULL)
                  AND work_date < ?
                ORDER BY work_date DESC
                LIMIT 1
            ");
            $priorOpenStmt->execute([$user['id'], $today]);
            $priorOpenLog = $priorOpenStmt->fetch(PDO::FETCH_ASSOC);

            if ($priorOpenLog) {
                $priorShift     = attGetShiftAtDate($pdo, (int)$user['id'], $priorOpenLog['work_date']);
                $resetThreshold = attGetResetThreshold($priorShift, $priorOpenLog['work_date']);
                if (time() <= $resetThreshold) {
                    // Vẫn trong cửa sổ chặn – không cho check-in mới
                    $blockDate = date('d/m/Y', strtotime($priorOpenLog['work_date']));
                    $shiftName = $priorShift ? htmlspecialchars($priorShift['shift_name']) : 'ca trước';
                    setFlash('danger', "⚠️ Bạn chưa chấm công ra <strong>{$shiftName}</strong> ngày <strong>{$blockDate}</strong>. Vui lòng chấm công ra trước khi bắt đầu ca mới.");
                    header('Location: /erp/modules/attendance/index.php');
                    exit();
                } else {
                    // Đã qua mốc reset – đánh dấu quên chấm ra
                    attMarkMissingCheckout($pdo, (int)$priorOpenLog['id']);
                }
            }
        } catch (Throwable $e) {
            error_log('prior open log check failed: ' . $e->getMessage());
        }

        $isLate = 0;
        $lateMinutes = 0;
        try {
            $shStmt = $pdo->prepare("
                SELECT ws.* FROM employee_shifts es
                JOIN work_shifts ws ON es.shift_id = ws.id
                WHERE es.user_id = ? AND es.effective_date <= ?
                  AND (es.end_date IS NULL OR es.end_date >= ?)
                ORDER BY es.effective_date DESC LIMIT 1
            ");
            $shStmt->execute([$user['id'], $today, $today]);
            $shift = $shStmt->fetch(PDO::FETCH_ASSOC);

            if ($shift) {
                $shiftStart = strtotime($today . ' ' . $shift['start_time']);
                $threshold  = $shiftStart + (((int)($shift['late_threshold'] ?? 0)) * 60);
                $actualIn   = strtotime($now);
                if ($actualIn > $threshold) {
                    $isLate      = 1;
                    $lateMinutes = (int)(($actualIn - $shiftStart) / 60);
                }
            }
        } catch (Throwable $e) {
            error_log('check_in shift query failed: ' . $e->getMessage());
        }

        $existStmt = $pdo->prepare("SELECT id, check_in FROM attendance_logs WHERE user_id = ? AND work_date = ?");
        $existStmt->execute([$user['id'], $today]);
        $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

        try {
            if ($existing) {
                if (!$existing['check_in']) {
                    $pdo->prepare("UPDATE attendance_logs
                        SET check_in = ?, source = 'manual',
                            check_in_ip = ?, check_in_lat = ?, check_in_lng = ?, check_in_location_flag = ?,
                            device_id = ?, same_device_alert = ?, check_in_photo = ?, is_late = ?, late_minutes = ?
                        WHERE id = ?")
                        ->execute([$now, $ip, $lat, $lng, $locationFlag, $deviceId, $sameDeviceAlert, $photoPath, $isLate, $lateMinutes, $existing['id']]);
                }
            } else {
                $pdo->prepare("INSERT INTO attendance_logs
                    (user_id, check_in, work_date, source, check_in_ip, check_in_lat, check_in_lng, check_in_location_flag, device_id, same_device_alert, check_in_photo, is_late, late_minutes)
                    VALUES (?, ?, ?, 'manual', ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$user['id'], $now, $today, $ip, $lat, $lng, $locationFlag, $deviceId, $sameDeviceAlert, $photoPath, $isLate, $lateMinutes]);
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
                error_log('same_device_alert notification error: ' . $e->getMessage());
            }

            try {
                $otherIds = array_column($sameDeviceUsers, 'user_id');
                $placeholders = implode(',', array_fill(0, count($otherIds), '?'));
                $updateParams = array_merge([$today], $otherIds);
                $pdo->prepare("UPDATE attendance_logs SET same_device_alert = 1 WHERE work_date = ? AND user_id IN ($placeholders)")
                    ->execute($updateParams);
            } catch (Throwable $e) {
                error_log('same_device_alert update others error: ' . $e->getMessage());
            }
        }

        setFlash('success', 'Chấm công vào ca thành công lúc ' . date('H:i') . $flagMsg);

    } elseif ($action === 'check_out') {
        // Tìm bản ghi check_in chưa có check_out trong hôm nay hoặc hôm qua (cho ca đêm qua ngày)
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
                error_log('check_out with location failed: ' . $e->getMessage());
                $pdo->prepare("UPDATE attendance_logs SET check_out = ?, work_hours = ROUND(TIMESTAMPDIFF(MINUTE, check_in, ?) / 60, 2) WHERE id = ? AND check_out IS NULL")
                    ->execute([$now, $now, $openLogId]);
            }
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
// Lấy bản ghi chấm công hiện tại: ưu tiên hôm nay, nếu chưa check_out thì tìm cả hôm qua (ca đêm qua ngày)
$stmt  = $pdo->prepare("
    SELECT * FROM attendance_logs
    WHERE user_id = ?
      AND work_date >= DATE_SUB(?, INTERVAL 1 DAY)
      AND work_date <= ?
    ORDER BY
        -- 0 = bản ghi đang mở (chưa check_out) được ưu tiên trước, 1 = đã hoàn thành
        CASE WHEN check_in IS NOT NULL AND check_out IS NULL THEN 0 ELSE 1 END ASC,
        work_date DESC
    LIMIT 1
");
$stmt->execute([$user['id'], $today, $today]);
$todayLog = $stmt->fetch();

// Nếu log hiện tại là từ ngày trước (open) và đã qua mốc reset – tự động đánh dấu quên checkout
if ($todayLog && $todayLog['work_date'] < $today && $todayLog['check_in'] && !$todayLog['check_out']
    && !(int)($todayLog['missing_checkout'] ?? 0)) {
    $priorShift = attGetShiftAtDate($pdo, (int)$user['id'], $todayLog['work_date']);
    if (time() > attGetResetThreshold($priorShift, $todayLog['work_date'])) {
        attMarkMissingCheckout($pdo, (int)$todayLog['id']);
        // Re-fetch: chỉ lấy log hôm nay
        $stmtToday = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND work_date = ?");
        $stmtToday->execute([$user['id'], $today]);
        $todayLog = $stmtToday->fetch() ?: null;
    }
}

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
        if (!empty($log['is_late'])) $lateDays++;
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
                            <input type="hidden" name="device_id" id="inputDeviceId" value="">
                            <input type="hidden" name="photo_data" id="inputPhotoData" value="">

                            <?php if ($canCheckIn): ?>
                                <input type="hidden" name="action" value="check_in">
                            <?php elseif ($canCheckOut): ?>
                                <input type="hidden" name="action" value="check_out">
                            <?php endif; ?>

                            <!-- Trạng thái GPS -->
                            <div id="gpsStatus" class="alert alert-warning py-2 small mb-2">
                                <i class="fas fa-spinner fa-spin me-1"></i>
                                <span id="gpsStatusText">Đang lấy vị trí GPS, vui lòng chờ...</span>
                            </div>

                            <div id="cameraError" class="alert alert-danger py-2 small mb-2" style="display:none;"></div>

                            <div class="text-muted small mb-3">
                                <i class="fas fa-network-wired me-1"></i>
                                IP của bạn: <code id="displayIp">—</code>
                            </div>

                            <div id="cameraSection" class="mb-3">
                                <div class="border rounded p-2 bg-light">
                                    <video id="cameraVideo" aria-label="Camera preview for attendance photo" class="w-100 rounded" autoplay playsinline muted style="max-height:280px;background:#000;"></video>
                                </div>
                                <button type="button" class="btn btn-outline-primary w-100 mt-2" id="btnCapture" disabled>
                                    📸 Chụp ảnh khuôn mặt
                                </button>
                            </div>

                            <div id="previewSection" style="display:none;">
                                <div class="border rounded p-2 bg-light mb-2 text-center">
                                    <img id="photoPreview" class="img-fluid rounded" alt="Ảnh đã chụp" style="max-height:280px;">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-secondary" id="btnRetake">🔄 Chụp lại</button>
                                    <?php if ($canCheckIn): ?>
                                    <button type="button" class="btn btn-success btn-lg" id="btnConfirmPhoto" disabled>
                                        <i class="fas fa-sign-in-alt me-2"></i>Xác nhận chấm công VÀO
                                    </button>
                                    <?php elseif ($canCheckOut): ?>
                                    <div class="alert alert-info py-2 small mb-0">
                                        Đã vào: <?= date('H:i', strtotime($todayLog['check_in'])) ?>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-lg" id="btnConfirmPhoto" disabled>
                                        <i class="fas fa-sign-out-alt me-2"></i>Xác nhận chấm công RA
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <canvas id="photoCanvas" style="display:none;"></canvas>
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
                        $daysInMonth = (int) date('t', mktime(0, 0, 0, $viewMonth, 1, $viewYear));
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
                                $isLate = !empty($log['is_late']);
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
const formEl      = document.getElementById('attendanceForm');
const inputPhotoData = document.getElementById('inputPhotoData');
const btnCapture = document.getElementById('btnCapture');
const btnRetake = document.getElementById('btnRetake');
const btnConfirmPhoto = document.getElementById('btnConfirmPhoto');
const cameraVideo = document.getElementById('cameraVideo');
const photoCanvas = document.getElementById('photoCanvas');
const photoPreview = document.getElementById('photoPreview');
const cameraSection = document.getElementById('cameraSection');
const previewSection = document.getElementById('previewSection');
const cameraError = document.getElementById('cameraError');
const displayIpEl = document.getElementById('displayIp');
let gpsReady = false;
let photoReady = false;
let stream = null;
const MAX_PHOTO_BYTES = 300000;
const PHOTO_DATA_PREFIX = 'data:image/jpeg;base64,';

// Hiện IP
if (displayIpEl) {
    fetch('/erp/api/attendance/get_ip.php')
        .then(r => r.json())
        .then(d => { displayIpEl.textContent = d.ip || 'N/A'; })
        .catch(() => { displayIpEl.textContent = 'N/A'; });
}

function checkReadyToSubmit() {
    if (btnConfirmPhoto) {
        btnConfirmPhoto.disabled = !(gpsReady && photoReady);
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

async function startCamera() {
    if (!cameraVideo || !btnCapture) return;

    if (!window.isSecureContext && !['localhost', '127.0.0.1', '::1'].includes(location.hostname)) {
        showCameraError('<strong>⚠️ Camera yêu cầu HTTPS.</strong> Vui lòng truy cập hệ thống qua HTTPS để chấm công.');
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('<strong>⚠️ Trình duyệt không hỗ trợ camera.</strong> Vui lòng dùng Chrome/Safari/Firefox mới nhất.');
        return;
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        });
        cameraVideo.srcObject = stream;
        cameraVideo.onloadedmetadata = () => {
            btnCapture.disabled = false;
            btnCapture.title = '';
        };
    } catch (err) {
        showCameraError(`<strong>⚠️ Không thể mở camera:</strong> ${err.message}<br><small>Vui lòng cho phép trình duyệt truy cập camera để chấm công.</small>`);
    }
}

btnCapture?.addEventListener('click', () => {
    if (!cameraVideo || !photoCanvas || !photoPreview || !inputPhotoData) return;
    const context = photoCanvas.getContext('2d');
    if (!context) return;

    photoCanvas.width = cameraVideo.videoWidth || 640;
    photoCanvas.height = cameraVideo.videoHeight || 480;
    context.drawImage(cameraVideo, 0, 0, photoCanvas.width, photoCanvas.height);

    const imgData = photoCanvas.toDataURL('image/jpeg', 0.8);
    // Base64 quy đổi: 4 ký tự mã hóa tương ứng ~3 bytes dữ liệu nhị phân.
    const approxBytes = Math.ceil((imgData.length - PHOTO_DATA_PREFIX.length) * 3 / 4);
    if (approxBytes > MAX_PHOTO_BYTES) {
        alert('Ảnh chụp quá lớn. Vui lòng chụp lại gần hơn.');
        return;
    }

    photoPreview.src = imgData;
    inputPhotoData.value = imgData;

    cameraSection.style.display = 'none';
    previewSection.style.display = '';
    photoReady = true;
    checkReadyToSubmit();
});

btnRetake?.addEventListener('click', () => {
    if (previewSection) previewSection.style.display = 'none';
    if (cameraSection) cameraSection.style.display = '';
    if (inputPhotoData) inputPhotoData.value = '';
    photoReady = false;
    checkReadyToSubmit();
});

btnConfirmPhoto?.addEventListener('click', () => {
    if (!photoReady || !gpsReady || !formEl) return;
    const action = <?= json_encode($canCheckIn ? 'check_in' : 'check_out') ?>;
    const actionText = action === 'check_in' ? 'VÀO' : 'RA';
    if (!confirm(`Xác nhận chấm công ${actionText} lúc ${new Date().toLocaleTimeString('vi-VN')}?`)) return;

    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    formEl.submit();
});

formEl?.addEventListener('submit', (event) => {
    if (!gpsReady || !photoReady || !inputPhotoData?.value) {
        event.preventDefault();
    }
});

// GPS bắt buộc — chỉ được xác nhận khi có tọa độ hợp lệ
if (gpsStatusEl && gpsTextEl && inputLat && inputLng) {
    if (!navigator.geolocation) {
        // Browser không hỗ trợ GPS
        gpsStatusEl.className = 'alert alert-danger py-2 small mb-2';
        gpsTextEl.innerHTML = `<i class="fas fa-times-circle me-1"></i>
            <strong>Trình duyệt không hỗ trợ định vị.</strong>
            Vui lòng dùng Chrome / Firefox hoặc ứng dụng di động để chấm công.`;
        gpsReady = false;
        checkReadyToSubmit();
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
                        gpsReady = false;
                    } else {
                        gpsReady = true;
                    }
                    checkReadyToSubmit();
                } else {
                    gpsStatusEl.className = 'alert alert-success py-2 small mb-2';
                    gpsTextEl.innerHTML = `<i class="fas fa-check-circle me-1"></i>GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)} (±${Math.round(pos.coords.accuracy)}m)`;
                    gpsReady = true;
                    checkReadyToSubmit();
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
                gpsReady = false;
                checkReadyToSubmit();
            },
            { timeout: 10000, enableHighAccuracy: true }
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

startCamera();
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
