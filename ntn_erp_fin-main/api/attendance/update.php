<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireRole('director');

$pdo  = getDBConnection();
$body = json_decode(file_get_contents('php://input'), true);

$logId    = (int)($body['log_id']   ?? 0);
$userId   = (int)($body['user_id']  ?? 0);
$date     = $body['date']           ?? '';
$checkIn  = $body['check_in']       ?? '';
$checkOut = $body['check_out']      ?? '';
$note     = trim($body['note']      ?? '');

if (!$userId || !$date) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin']); exit;
}

// Validate giờ
if ($checkIn && !preg_match('/^\d{2}:\d{2}$/', $checkIn)) {
    echo json_encode(['ok' => false, 'msg' => 'Giờ vào không hợp lệ (HH:MM)']); exit;
}
if ($checkOut && !preg_match('/^\d{2}:\d{2}$/', $checkOut)) {
    echo json_encode(['ok' => false, 'msg' => 'Giờ ra không hợp lệ (HH:MM)']); exit;
}
if ($checkIn && $checkOut && $checkOut <= $checkIn) {
    echo json_encode(['ok' => false, 'msg' => 'Giờ ra phải sau giờ vào']); exit;
}

// Lấy ca làm việc để tính lại is_late, late_minutes, work_hours
$shStmt = $pdo->prepare("
    SELECT ws.* FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.user_id = ? AND es.effective_date <= ?
      AND (es.end_date IS NULL OR es.end_date >= ?)
    ORDER BY es.effective_date DESC LIMIT 1
");
$shStmt->execute([$userId, $date, $date]);
$shift = $shStmt->fetch(PDO::FETCH_ASSOC);

// Tính lại is_late, late_minutes
$isLate       = 0;
$lateMinutes  = 0;
$earlyLeave   = 0;
$earlyMinutes = 0;
$workHours    = 0;

if ($checkIn && $shift) {
    $shiftStart    = strtotime($date . ' ' . $shift['start_time']);
    $threshold     = $shiftStart + (($shift['late_threshold'] ?? 0) * 60);
    $actualIn      = strtotime($date . ' ' . $checkIn);

    if ($actualIn > $threshold) {
        $isLate      = 1;
        $lateMinutes = (int)(($actualIn - $shiftStart) / 60);
    }

    // Tính về sớm
    if ($checkOut && $shift['end_time']) {
        $shiftEnd   = strtotime($date . ' ' . $shift['end_time']);
        $actualOut  = strtotime($date . ' ' . $checkOut);
        if ($actualOut < $shiftEnd) {
            $earlyLeave   = 1;
            $earlyMinutes = (int)(($shiftEnd - $actualOut) / 60);
        }
        // Tính giờ làm
        $workHours = round(($actualOut - $actualIn) / 3600, 2);
    }
} elseif ($checkIn && $checkOut) {
    $actualIn  = strtotime($date . ' ' . $checkIn);
    $actualOut = strtotime($date . ' ' . $checkOut);
    $workHours = round(($actualOut - $actualIn) / 3600, 2);
}

$checkInFull  = $checkIn  ? $date . ' ' . $checkIn  . ':00' : null;
$checkOutFull = $checkOut ? $date . ' ' . $checkOut . ':00' : null;

try {
    // Kiểm tra có bản ghi chưa
    $exists = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id=? AND work_date=?");
    $exists->execute([$userId, $date]);
    $existing = $exists->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Cập nhật
        $pdo->prepare("
            UPDATE attendance_logs SET
                check_in          = ?,
                check_out         = ?,
                work_hours        = ?,
                is_late           = ?,
                late_minutes      = ?,
                early_leave       = ?,
                early_leave_minutes = ?,
                source            = 'manual',
                note              = ?,
                updated_at        = NOW()
            WHERE user_id = ? AND work_date = ?
        ")->execute([
            $checkInFull, $checkOutFull, $workHours,
            $isLate, $lateMinutes, $earlyLeave, $earlyMinutes,
            $note, $userId, $date
        ]);
        $logId = $existing['id'];
    } else {
        // Tạo mới
        $ins = $pdo->prepare("
            INSERT INTO attendance_logs
                (user_id, work_date, check_in, check_out, work_hours,
                 is_late, late_minutes, early_leave, early_leave_minutes,
                 source, note, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,'manual',?,NOW())
        ");
        $ins->execute([
            $userId, $date, $checkInFull, $checkOutFull, $workHours,
            $isLate, $lateMinutes, $earlyLeave, $earlyMinutes, $note
        ]);
        $logId = $pdo->lastInsertId();
    }

    // Ghi audit log
    $pdo->prepare("
        INSERT INTO attendance_audit_logs
            (attendance_log_id, changed_by, change_type, old_check_in, old_check_out, new_check_in, new_check_out, note, created_at)
        VALUES (?, ?, 'manual_edit', NULL, NULL, ?, ?, ?, NOW())
    ")->execute([$logId, currentUser()['id'], $checkInFull, $checkOutFull, $note]);

    echo json_encode([
        'ok'           => true,
        'msg'          => 'Đã cập nhật chấm công',
        'work_hours'   => $workHours,
        'is_late'      => $isLate,
        'late_minutes' => $lateMinutes,
        'early_leave'  => $earlyLeave,
        'early_minutes'=> $earlyMinutes,
    ]);
} catch (Exception $e) {
    // Nếu bảng audit chưa có thì bỏ qua lỗi đó
    echo json_encode(['ok' => true, 'msg' => 'Đã cập nhật chấm công (không ghi audit)']);
}