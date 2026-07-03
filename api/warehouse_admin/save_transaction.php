<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
ensurePostCsrf();

$pdo = getDBConnection();
$itemId = (int)($_POST['item_id'] ?? 0);
$type = trim((string)($_POST['type'] ?? ''));
$qty = (float)($_POST['qty'] ?? 0);
$refNo = trim((string)($_POST['ref_no'] ?? '')) ?: null;
$note = trim((string)($_POST['note'] ?? '')) ?: null;
$transactedAt = trim((string)($_POST['transacted_at'] ?? date('Y-m-d')));

if ($itemId <= 0 || !in_array($type, ['import', 'export'], true) || $qty <= 0 || $transactedAt === '') {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

$currentStock = (float)fetchScalarSafe($pdo,
    "SELECT COALESCE(SUM(CASE WHEN type='import' THEN qty ELSE -qty END), 0) FROM wa_transactions WHERE item_id = ?",
    [$itemId],
    0
);

if ($type === 'export' && ($currentStock - $qty) < 0) {
    echo json_encode(['ok' => false, 'msg' => 'Xuất kho vượt tồn hiện tại']);
    exit;
}

$ok = $pdo->prepare('INSERT INTO wa_transactions (item_id, type, qty, ref_no, note, transacted_by, transacted_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([$itemId, $type, $qty, $refNo, $note, currentUserId(), $transactedAt]);

echo json_encode(['ok' => (bool)$ok]);
