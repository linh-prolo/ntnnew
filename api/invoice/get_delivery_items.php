<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
header('Content-Type: application/json');
requireLogin();

$pdo = getDBConnection();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'msg'=>'Missing id']); exit; }

try {
    $items = $pdo->prepare("
        SELECT di.product_code_id,
               pc.description,
               pc.unit,
               di.quantity, di.unit_price, di.total_price,
               pc.product_code
        FROM delivery_items di
        JOIN product_codes pc ON di.product_code_id = pc.id
        WHERE di.delivery_id = ?
        ORDER BY di.id
    ");
    $items->execute([$id]);
    echo json_encode(['ok'=>true,'items'=>$items->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('get_delivery_items error: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'msg'=>'Lỗi truy vấn dữ liệu']);
}