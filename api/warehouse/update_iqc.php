<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'manager', 'warehouse');
ensurePostCsrf();

$pdo = getDBConnection();
$id = (int)($_POST['id'] ?? 0);
$note = trim((string)($_POST['note'] ?? '')) ?: null;
$status = trim((string)($_POST['status'] ?? ''));

if ($id <= 0 || !in_array($status, ['open', 'in_production', 'done'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

$ok = $pdo->prepare('UPDATE iqc_receipts SET note = ?, status = ? WHERE id = ?')->execute([$note, $status, $id]);
echo json_encode(['ok' => (bool)$ok]);
