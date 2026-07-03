<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','warehouse','manager');

$pdo   = getDBConnection();
$user  = currentUser();
$input = $_POST;

if (!verifyCSRF($input['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']); exit;
}

$id          = (int)($input['id'] ?? 0);
$productCode = strtoupper(trim($input['product_code'] ?? ''));
$description = trim($input['description'] ?? '');
$unit        = trim($input['unit'] ?? 'cái');
$category    = trim($input['category'] ?? '') ?: null;
$isActive    = isset($input['is_active']) ? 1 : 0;

if (!$productCode || !$description) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu mã SP hoặc mô tả']); exit;
}

try {
    if ($id) {
        // Cập nhật
        $stmt = $pdo->prepare("
            UPDATE product_codes
            SET product_code = ?, description = ?, unit = ?,
                category = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$productCode, $description, $unit, $category, $isActive, $id]);
        echo json_encode(['ok' => true, 'msg' => 'Đã cập nhật']);
    } else {
        // Thêm mới
        $stmt = $pdo->prepare("
            INSERT INTO product_codes (product_code, description, unit, category, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$productCode, $description, $unit, $category, $isActive, $user['id']]);
        echo json_encode(['ok' => true, 'msg' => 'Đã thêm mới', 'id' => $pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
        if ($e->getCode() == 23000) {
        echo json_encode(['ok' => false, 'msg' => "Mã SP '$productCode' đã tồn tại"]);
    } else {
        error_log($e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Lỗi hệ thống']);
    }
}