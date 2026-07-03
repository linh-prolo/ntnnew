<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant');

$pdo  = getDBConnection();
$user = currentUser();

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok'=>false,'msg'=>'CSRF invalid']); exit;
}

$customerId  = (int)($_POST['customer_id']  ?? 0);
$id          = (int)($_POST['id']           ?? 0);
$invoiceDate = trim($_POST['invoice_date']  ?? date('Y-m-d'));
$dueDate     = trim($_POST['due_date']      ?? '') ?: null;
$vatRate     = (float)($_POST['vat_rate']   ?? 0);
$note        = trim($_POST['note']          ?? '') ?: null;
$deliveryId  = (int)($_POST['delivery_id']  ?? 0) ?: null;
$deliveryIds = json_decode($_POST['delivery_ids'] ?? '[]', true);
$deliveryIds = is_array($deliveryIds) ? array_values(array_unique(array_filter(array_map('intval', $deliveryIds)))) : [];
if ($deliveryId && !in_array($deliveryId, $deliveryIds, true)) {
    $deliveryIds[] = $deliveryId;
}
$items       = $_POST['items'] ?? [];

if (!$customerId || empty($items)) {
    echo json_encode(['ok'=>false,'msg'=>'Thiếu khách hàng hoặc sản phẩm']); exit;
}

$validItems = [];
foreach ($items as $it) {
    $pcId  = (int)($it['product_code_id'] ?? 0);
    $qty   = (float)($it['quantity']      ?? 0);
    $price = (float)($it['unit_price']    ?? 0);
    if ($pcId && $qty > 0) {
        $validItems[] = [
            'product_code_id' => $pcId,
            'description'     => trim($it['description'] ?? ''),
            'unit'            => trim($it['unit'] ?? ''),
            'quantity'        => $qty,
            'unit_price'      => $price,
            'total_price'     => round($qty * $price),
        ];
    }
}
if (empty($validItems)) {
    echo json_encode(['ok'=>false,'msg'=>'Không có dòng hợp lệ']); exit;
}

// Kiểm tra khoá — chỉ director được sửa hoá đơn đã khoá
if ($id) {
    $lockCheck = $pdo->prepare("SELECT is_locked FROM invoices WHERE id = ?");
    $lockCheck->execute([$id]);
    $lockRow = $lockCheck->fetch(PDO::FETCH_ASSOC);
    if ($lockRow && !empty($lockRow['is_locked']) && !hasRole('director')) {
        echo json_encode(['ok' => false, 'msg' => 'Hoá đơn đã được khoá. Chỉ Giám đốc mới được sửa.']); exit;
    }
}

try {
    $pdo->beginTransaction();

    // Sinh số HĐ INV-YYYYMMDD-XXX
    $pdo->prepare("
        INSERT INTO document_sequences (doc_type, doc_date, last_seq) VALUES ('INV',?,1)
        ON DUPLICATE KEY UPDATE last_seq = last_seq + 1
    ")->execute([$invoiceDate]);
    $seq = $pdo->query("
        SELECT last_seq FROM document_sequences WHERE doc_type='INV' AND doc_date='$invoiceDate'
    ")->fetchColumn();
    $invoiceNo = 'INV-' . date('Ymd', strtotime($invoiceDate)) . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

    $subtotal    = array_sum(array_column($validItems, 'total_price'));
    $vatAmount   = round($subtotal * $vatRate / 100);
    $totalAmount = $subtotal + $vatAmount;

    // Insert invoice header
    $pdo->prepare("
        INSERT INTO invoices
            (invoice_no, invoice_date, due_date, customer_id,
             subtotal, vat_rate, vat_amount, total_amount,
             delivery_id, note, status, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,'unpaid',?)
    ")->execute([
        $invoiceNo, $invoiceDate, $dueDate, $customerId,
        $subtotal, $vatRate, $vatAmount, $totalAmount,
        null, $note, $user['id']
    ]);
    $invoiceId = $pdo->lastInsertId();

    // Insert items
    $stmtItem = $pdo->prepare("
        INSERT INTO invoice_items
            (invoice_id, delivery_note_id, delivery_note_item_id,
             product_code_id, description, unit, quantity, unit_price, total_price)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    foreach ($validItems as $it) {
        $stmtItem->execute([
            $invoiceId,
            null,  // delivery_note_id — không dùng trong luồng OQC
            null,  // delivery_note_item_id — không dùng trong luồng OQC
            $it['product_code_id'], $it['description'],
            $it['unit'], $it['quantity'], $it['unit_price'], $it['total_price']
        ]);
    }

    // Cập nhật status biên bản → delivered (invoiced không tồn tại trong ENUM)
    if (!empty($deliveryIds)) {
        $ph = implode(',', array_fill(0, count($deliveryIds), '?'));
        $pdo->prepare("UPDATE oqc_deliveries SET status='delivered' WHERE id IN ($ph)")->execute($deliveryIds);
    }

    $pdo->commit();
    echo json_encode(['ok'=>true,'msg'=>'Đã tạo hoá đơn','invoice_no'=>$invoiceNo,'id'=>$invoiceId]);

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['ok'=>false,'msg'=>'Lỗi hệ thống: '.$e->getMessage()]);
}