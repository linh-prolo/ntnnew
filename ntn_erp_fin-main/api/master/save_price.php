<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager');

$pdo  = getDBConnection();
$user = currentUser();

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']); exit;
}

$productCodeId = (int)($_POST['product_code_id'] ?? 0);
$unitPrice     = (int)($_POST['unit_price']     ?? 0);
$effectiveFrom = trim($_POST['effective_from']  ?? '');
$note          = trim($_POST['note']            ?? '') ?: null;

if (!$productCodeId || !$unitPrice || !$effectiveFrom) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin bắt buộc']); exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO product_prices (product_code_id, unit_price, effective_from, note, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$productCodeId, $unitPrice, $effectiveFrom, $note, $user['id']]);
    echo json_encode(['ok' => true, 'msg' => 'Đã thêm giá mới']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi hệ thống']);
}