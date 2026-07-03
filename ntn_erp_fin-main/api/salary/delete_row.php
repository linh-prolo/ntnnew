<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireRole('director', 'accountant');

$pdo   = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$rowId  = (int)($input['row_id']  ?? 0);
$userId = (int)($input['user_id'] ?? 0);

if (!$rowId || !$userId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin']); exit;
}

// Kiểm tra khoản lương thuộc đúng user
$chk = $pdo->prepare("SELECT id, custom_name FROM employee_salaries WHERE id = ? AND user_id = ?");
$chk->execute([$rowId, $userId]);
$row = $chk->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy khoản lương']); exit;
}

try {
    // Soft delete: đánh dấu is_active = 0 thay vì xóa hẳn
    $pdo->prepare("UPDATE employee_salaries SET is_active = 0 WHERE id = ?")
        ->execute([$rowId]);

    echo json_encode([
        'ok'  => true,
        'msg' => '🗑️ Đã xóa khoản lương: ' . ($row['custom_name'] ?? ''),
        'id'  => $rowId,
    ]);
} catch (Throwable $e) {
    error_log("delete_row.php error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi server: ' . $e->getMessage()]);
}