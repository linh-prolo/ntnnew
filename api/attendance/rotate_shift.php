<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');

// Chỉ cho phép director hoặc accountant
if (!hasRole('director', 'accountant')) {
    echo json_encode(['ok' => false, 'msg' => 'Không có quyền thực hiện']); exit;
}

$pdo  = getDBConnection();
$user = currentUser();
$body = json_decode(file_get_contents('php://input'), true);

$userId  = (int)($body['user_id']  ?? 0);
$month   = (int)($body['month']   ?? 0);
$year    = (int)($body['year']    ?? 0);
$shift1Id = (int)($body['shift1_id'] ?? 0);
$shift2Id = (int)($body['shift2_id'] ?? 0);

// Validate đầu vào
if (!$userId || $month < 1 || $month > 12 || $year < 2000 || !$shift1Id || !$shift2Id) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu hoặc sai thông tin đầu vào']); exit;
}

// Kiểm tra nhân viên tồn tại
$empCheck = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1");
$empCheck->execute([$userId]);
$emp = $empCheck->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    echo json_encode(['ok' => false, 'msg' => 'Nhân viên không tồn tại hoặc không còn hoạt động']); exit;
}

// Kiểm tra 2 ca tồn tại
$shiftCheck = $pdo->prepare("SELECT id, shift_name FROM work_shifts WHERE id IN (?, ?) AND is_active = 1");
$shiftCheck->execute([$shift1Id, $shift2Id]);
if ($shiftCheck->rowCount() < 2) {
    echo json_encode(['ok' => false, 'msg' => 'Một hoặc cả 2 ca không hợp lệ hoặc chưa được kích hoạt']); exit;
}

// Tính ngày đầu, giữa, cuối tháng
$firstDay  = sprintf('%04d-%02d-01', $year, $month);
$midDay    = sprintf('%04d-%02d-15', $year, $month);
$midDayPlus1 = sprintf('%04d-%02d-16', $year, $month);
$lastDay   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

try {
    $pdo->beginTransaction();

    // Xóa tất cả bản ghi ca của nhân viên trong tháng đó
    $pdo->prepare("
        DELETE FROM employee_shifts
        WHERE user_id = ?
          AND effective_date <= ?
          AND (end_date IS NULL OR end_date >= ?)
    ")->execute([$userId, $lastDay, $firstDay]);

    // Bản ghi 1: ca nửa đầu tháng (ngày 1 – 15)
    $pdo->prepare("
        INSERT INTO employee_shifts (user_id, shift_id, effective_date, end_date, created_by)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$userId, $shift1Id, $firstDay, $midDay, $user['id']]);

    // Bản ghi 2: ca nửa sau tháng (ngày 16 – cuối tháng)
    $pdo->prepare("
        INSERT INTO employee_shifts (user_id, shift_id, effective_date, end_date, created_by)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$userId, $shift2Id, $midDayPlus1, $lastDay, $user['id']]);

    $pdo->commit();

    echo json_encode([
        'ok'  => true,
        'msg' => "Đã phân ca luân phiên cho {$emp['full_name']}: ca 1 từ $firstDay đến $midDay, ca 2 từ $midDayPlus1 đến $lastDay"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
