<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireRole('director');

$pdo   = getDBConnection();
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$rowIds = $input['row_ids'] ?? [];
$userId = (int)($input['user_id'] ?? 0);

if (empty($rowIds) || !$userId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin']); exit;
}

// Lọc chỉ lấy id là số nguyên hợp lệ
$rowIds = array_map('intval', $rowIds);
$rowIds = array_filter($rowIds, fn($id) => $id > 0);

if (empty($rowIds)) {
    echo json_encode(['ok' => false, 'msg' => 'Không có khoản lương hợp lệ']); exit;
}

$placeholders = implode(',', array_fill(0, count($rowIds), '?'));

// Kiểm tra tất cả row thuộc đúng user và đang ở trạng thái pending
$chk = $pdo->prepare("
    SELECT id FROM employee_salaries
    WHERE id IN ($placeholders)
      AND user_id = ?
      AND approval_status = 'pending'
      AND is_active = 1
");
$chk->execute(array_merge($rowIds, [$userId]));
$validIds = array_column($chk->fetchAll(PDO::FETCH_ASSOC), 'id');

if (empty($validIds)) {
    echo json_encode(['ok' => false, 'msg' => 'Không có khoản lương nào cần duyệt']); exit;
}

try {
    $ph = implode(',', array_fill(0, count($validIds), '?'));
    $stmt = $pdo->prepare("
        UPDATE employee_salaries
        SET approval_status = 'approved',
            approved_by     = ?,
            approved_at     = NOW()
        WHERE id IN ($ph)
    ");
    $stmt->execute(array_merge([$user['id']], $validIds));

    echo json_encode([
        'ok'    => true,
        'msg'   => '✅ Đã duyệt ' . count($validIds) . ' khoản lương',
        'count' => count($validIds),
    ]);
} catch (Exception $e) {
    error_log('bulk_approve.php error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi server']);
}
