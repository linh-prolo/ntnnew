<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';

requireLogin();

header('Content-Type: application/json');

$itemId = (int)($_GET['item_id'] ?? 0);
if ($itemId <= 0) {
    echo json_encode(['ok' => false, 'stock' => 0, 'unit' => '']);
    exit;
}

$pdo = getDBConnection();
$item = fetchOneSafe($pdo, 'SELECT unit FROM inv_items WHERE id = ? AND is_active = 1', [$itemId]);
$totalIn = (float)fetchScalarSafe($pdo, 'SELECT COALESCE(SUM(quantity), 0) FROM inv_imports WHERE item_id = ?', [$itemId], 0);
$totalOut = (float)fetchScalarSafe($pdo, 'SELECT COALESCE(SUM(quantity), 0) FROM inv_exports WHERE item_id = ?', [$itemId], 0);
$stock = $totalIn - $totalOut;

echo json_encode([
    'ok' => true,
    'stock' => $stock,
    'unit' => $item['unit'] ?? '',
]);
?>
