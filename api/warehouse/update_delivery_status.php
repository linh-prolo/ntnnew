<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'manager');
ensurePostCsrf();

$pdo = getDBConnection();
$id = (int)($_POST['id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));
if ($id <= 0 || !in_array($status, ['draft', 'delivered'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}
$ok = $pdo->prepare('UPDATE oqc_deliveries SET status = ? WHERE id = ?')->execute([$status, $id]);
echo json_encode(['ok' => (bool)$ok]);
