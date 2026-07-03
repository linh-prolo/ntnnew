<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
ensurePostCsrf();

$pdo = getDBConnection();
$customerId   = (int)($_POST['customer_id'] ?? 0);
$receivedDate = trim((string)($_POST['received_date'] ?? ''));
$receivedBy   = (int)($_POST['received_by'] ?? 0);
$note         = trim((string)($_POST['note'] ?? '')) ?: null;
$items        = $_POST['items'] ?? [];

if ($customerId <= 0 || $receivedBy <= 0 || $receivedDate === '' || !is_array($items)) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ']);
    exit;
}

$validItems = [];
foreach ($items as $item) {
    $productCodeId = (int)($item['product_code_id'] ?? 0);
    $qty           = (float)($item['qty'] ?? 0);
    $unit          = trim((string)($item['unit'] ?? 'cái')) ?: 'cái';
    $itemNote      = trim((string)($item['note'] ?? '')) ?: null;
    if ($productCodeId > 0 && $qty > 0) {
        $validItems[] = [
            'product_code_id' => $productCodeId,
            'qty'             => $qty,
            'unit'            => $unit,
            'note'            => $itemNote,
        ];
    }
}

if (!$validItems) {
    echo json_encode(['ok' => false, 'msg' => 'Phiếu IQC phải có ít nhất 1 dòng hợp lệ']);
    exit;
}

try {
    // Sinh số chứng từ TRƯỚC khi mở transaction chính
    // (generateDocNo tự dùng transaction riêng bên trong)
    $receiptNo = generateDocNo($pdo, 'IQC');
    $orderNo   = generateDocNo($pdo, 'PO');

    // Bắt đầu transaction chính
    $pdo->beginTransaction();

    $pdo->prepare('INSERT INTO iqc_receipts (receipt_no, customer_id, received_date, received_by, note, status, created_by)
                   VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$receiptNo, $customerId, $receivedDate, $receivedBy, $note, 'open', currentUserId()]);
    $receiptId = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO production_orders (order_no, iqc_receipt_id, status, note, created_by)
                   VALUES (?, ?, ?, ?, ?)')
        ->execute([$orderNo, $receiptId, 'pending', $note, currentUserId()]);
    $orderId = (int)$pdo->lastInsertId();

    $insertIqcItem  = $pdo->prepare('INSERT INTO iqc_receipt_items (receipt_id, product_code_id, qty, unit, note)
                                     VALUES (?, ?, ?, ?, ?)');
    $insertProdItem = $pdo->prepare('INSERT INTO production_items (order_id, iqc_item_id, qty_total, qty_done, qty_error, status)
                                     VALUES (?, ?, ?, 0, 0, ?)');

    foreach ($validItems as $item) {
        $insertIqcItem->execute([$receiptId, $item['product_code_id'], $item['qty'], $item['unit'], $item['note']]);
        $iqcItemId = (int)$pdo->lastInsertId();
        $insertProdItem->execute([$orderId, $iqcItemId, $item['qty'], 'in_progress']);
    }

    $pdo->prepare("UPDATE iqc_receipts SET status = 'in_production' WHERE id = ?")
        ->execute([$receiptId]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'receipt_no' => $receiptNo, 'order_no' => $orderNo]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[save_iqc] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi DB: ' . $e->getMessage()]);
}
