<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
header('Content-Type: application/json');
requireRole('director', 'accountant', 'manager', 'production');

$pdo    = getDBConnection();
$userId = (int)($_GET['user_id'] ?? 0);
$date   = $_GET['date'] ?? '';

if (!$userId || !$date) { echo json_encode([]); exit; }

// Chấm công
$att = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id=? AND work_date=?");
$att->execute([$userId, $date]);
$attData = $att->fetch(PDO::FETCH_ASSOC) ?: null;
if ($attData) {
    $attData += [
        'check_in_ip' => null,
        'check_in_lat' => null,
        'check_in_lng' => null,
        'check_in_location_flag' => 'unknown',
        'check_out_ip' => null,
        'check_out_lat' => null,
        'check_out_lng' => null,
        'check_out_location_flag' => 'unknown',
        'check_in_photo' => null,
        'check_out_photo' => null,
    ];
}

// OT
$ot = $pdo->prepare("SELECT * FROM overtime_requests WHERE user_id=? AND ot_date=? AND status='approved'");
$ot->execute([$userId, $date]);
$otData = $ot->fetch(PDO::FETCH_ASSOC) ?: null;

// Nghỉ phép
$lv = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id=? AND start_date<=? AND end_date>=? AND status='approved'");
$lv->execute([$userId, $date, $date]);
$lvData = $lv->fetch(PDO::FETCH_ASSOC) ?: null;

// Ca làm việc
$sh = $pdo->prepare("
    SELECT ws.* FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.user_id=? AND es.effective_date<=? AND (es.end_date IS NULL OR es.end_date>=?)
    ORDER BY es.effective_date DESC LIMIT 1
");
$sh->execute([$userId, $date, $date]);
$shData = $sh->fetch(PDO::FETCH_ASSOC) ?: null;

// Thông tin nhân viên
$emp = $pdo->prepare("SELECT u.full_name, u.employee_code, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.id=?");
$emp->execute([$userId]);
$empData = $emp->fetch(PDO::FETCH_ASSOC) ?: null;

// Lịch sử sửa (nếu có bảng audit)
$auditData = [];
try {
    $audit = $pdo->prepare("
        SELECT al.*, u.full_name AS changed_by_name
        FROM attendance_audit_logs al
        JOIN users u ON al.changed_by = u.id
        WHERE al.attendance_log_id = ?
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    if ($attData) {
        $audit->execute([$attData['id']]);
        $auditData = $audit->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Bảng audit chưa có → bỏ qua
}

echo json_encode([
    'att'   => $attData,
    'ot'    => $otData,
    'leave' => $lvData,
    'shift' => $shData,
    'emp'   => $empData,
    'audit' => $auditData,
]);