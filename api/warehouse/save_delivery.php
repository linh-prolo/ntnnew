<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
ensurePostCsrf();

$pdo = getDBConnection();
$customerId = (int)($_POST['customer_id'] ?? 0);
$deliveryDate = trim((string)($_POST['delivery_date'] ?? ''));
$senderName = trim((string)($_POST['sender_name'] ?? '')) ?: null;
$vehiclePlate = trim((string)($_POST['vehicle_plate'] ?? '')) ?: null;
$driverName = trim((string)($_POST['driver_name'] ?? '')) ?: null;
$note = trim((string)($_POST['note'] ?? '')) ?: null;
$items = $_POST['items'] ?? [];

$validItems = [];
foreach ($items as $item) {
    $productionItemId = (int)($item['production_item_id'] ?? 0);
    $qty = (float)($item['qty_deliver'] ?? 0);
    $type = trim((string)($item['type'] ?? 'done'));
    $itemNote = trim((string)($item['note'] ?? '')) ?: null;
    if ($productionItemId > 0 && $qty > 0 && in_array($type, ['done', 'error'], true)) {
        $validItems[] = [
            'production_item_id' => $productionItemId,
            'qty_deliver' => $qty,
            'type' => $type,
            'note' => $itemNote,
        ];
    }
}

if ($customerId <= 0 || $deliveryDate === '' || !$validItems) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    $deliveryNo = generateDocNo($pdo, 'DEL');
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO oqc_deliveries (delivery_no, customer_id, delivery_date, sender_name, vehicle_plate, driver_name, note, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$deliveryNo, $customerId, $deliveryDate, $senderName, $vehiclePlate, $driverName, $note, 'draft', currentUserId()]);
    $deliveryId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare('INSERT INTO oqc_delivery_items (delivery_id, production_item_id, qty_deliver, type, note) VALUES (?, ?, ?, ?, ?)');
    foreach ($validItems as $item) {
        $insertItem->execute([$deliveryId, $item['production_item_id'], $item['qty_deliver'], $item['type'], $item['note']]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'id' => $deliveryId, 'delivery_no' => $deliveryNo]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => 'Không thể lưu phiếu xuất']);
}
