<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireRole('director');

$pdo   = getDBConnection();
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$rowId  = (int)($input['row_id']  ?? 0);
$userId = (int)($input['user_id'] ?? 0);

if (!$rowId || !$userId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin']); exit;
}

// Kiểm tra khoản lương thuộc đúng user
$chk = $pdo->prepare("
    SELECT es.id, es.custom_name, es.approval_status,
           sc.component_name
    FROM employee_salaries es
    LEFT JOIN salary_components sc ON es.component_id = sc.id
    WHERE es.id = ? AND es.user_id = ?
");
$chk->execute([$rowId, $userId]);
$row = $chk->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy khoản lương']); exit;
}

if (($row['approval_status'] ?? 'pending') === 'approved') {
    echo json_encode(['ok' => false, 'msg' => 'Khoản lương này đã được duyệt rồi']); exit;
}

try {
    $pdo->prepare("
        UPDATE employee_salaries
        SET approval_status = 'approved',
            approved_by     = ?,
            approved_at     = NOW()
        WHERE id = ?
    ")->execute([$user['id'], $rowId]);

    echo json_encode([
        'ok'  => true,
        'msg' => '✅ Đã duyệt khoản lương: ' . ($row['custom_name'] ?: ($row['component_name'] ?? '')),
        'id'  => $rowId,
    ]);
} catch (Throwable $e) {
    error_log("approve_row.php error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi server: ' . $e->getMessage()]);
}
