<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager');

$pdo  = getDBConnection();
$user = currentUser();

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok'=>false,'msg'=>'CSRF invalid']); exit;
}

$invoiceId     = (int)($_POST['invoice_id']     ?? 0);
$paymentDate   = trim($_POST['payment_date']    ?? date('Y-m-d'));
$amount        = (float)($_POST['amount']       ?? 0);
$paymentMethod = trim($_POST['payment_method']  ?? 'cash');
$note          = trim($_POST['note']            ?? '') ?: null;

if (!$invoiceId || $amount <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Thiếu thông tin']); exit;
}

try {
    $pdo->beginTransaction();

    // Lấy tổng đã thu + tổng HĐ
    $inv = $pdo->prepare("SELECT total_amount FROM invoices WHERE id = ? FOR UPDATE");
    $inv->execute([$invoiceId]);
    $totalAmount = (float)$inv->fetchColumn();

    $paid = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?");
    $paid->execute([$invoiceId]);
    $paidSoFar = (float)$paid->fetchColumn();

    $remaining = $totalAmount - $paidSoFar;
    if ($amount > $remaining + 0.01) {
        $pdo->rollBack();
        echo json_encode(['ok'=>false,'msg'=>"Số tiền thu ($amount) vượt quá còn nợ ($remaining)"]); exit;
    }

    // Insert payment
    $pdo->prepare("
        INSERT INTO payments (invoice_id, payment_date, amount, payment_method, note, created_by)
        VALUES (?,?,?,?,?,?)
    ")->execute([$invoiceId, $paymentDate, $amount, $paymentMethod, $note, $user['id']]);

    // Cập nhật status invoice
    $newPaid  = $paidSoFar + $amount;
    $newStatus = $newPaid >= $totalAmount - 0.01 ? 'paid' : 'partial';
    $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$newStatus, $invoiceId]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'msg'=>'Đã ghi nhận thanh toán']);

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['ok'=>false,'msg'=>'Lỗi hệ thống']);
}