<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
ensurePostCsrf();

$pdo = getDBConnection();
$id = (int)($_POST['id'] ?? 0);
$categoryId = (int)($_POST['category_id'] ?? 0);
$itemCode = strtoupper(trim((string)($_POST['item_code'] ?? '')));
$itemName = trim((string)($_POST['item_name'] ?? ''));
$unit = trim((string)($_POST['unit'] ?? 'cái')) ?: 'cái';
$minStock = (float)($_POST['min_stock'] ?? 0);
$isActive = (int)($_POST['is_active'] ?? 1) ? 1 : 0;

if ($categoryId <= 0 || $itemCode === '' || $itemName === '' || $minStock < 0) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

$dup = (int)fetchScalarSafe($pdo, 'SELECT COUNT(*) FROM wa_items WHERE item_code = ? AND id != ?', [$itemCode, $id], 0);
if ($dup > 0) {
    echo json_encode(['ok' => false, 'msg' => 'Mã vật tư đã tồn tại']);
    exit;
}

if ($id > 0) {
    $ok = $pdo->prepare('UPDATE wa_items SET category_id = ?, item_code = ?, item_name = ?, unit = ?, min_stock = ?, is_active = ? WHERE id = ?')
        ->execute([$categoryId, $itemCode, $itemName, $unit, $minStock, $isActive, $id]);
} else {
    $ok = $pdo->prepare('INSERT INTO wa_items (category_id, item_code, item_name, unit, min_stock, is_active) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$categoryId, $itemCode, $itemName, $unit, $minStock, $isActive]);
    $id = (int)$pdo->lastInsertId();
}

echo json_encode(['ok' => (bool)$ok, 'id' => $id]);
