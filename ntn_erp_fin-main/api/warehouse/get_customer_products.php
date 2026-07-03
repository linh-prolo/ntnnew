<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');

$pdo = getDBConnection();
$customerId = (int)($_GET['customer_id'] ?? 0);
if ($customerId <= 0) {
    echo json_encode(['ok' => false, 'products' => []]);
    exit;
}

$products = fetchAllSafe($pdo, "SELECT DISTINCT pc.id, pc.product_code, pc.description, pc.unit
                                FROM product_codes pc
                                INNER JOIN customer_prices cp ON cp.product_code_id = pc.id
                                WHERE cp.customer_id = ? AND pc.is_active = 1
                                ORDER BY pc.product_code", [$customerId]);

echo json_encode(['ok' => true, 'products' => $products]);
