<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant');

$pdo  = getDBConnection();
$user = currentUser();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (!verifyCSRF($body['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF không hợp lệ']); exit;
}

$invoiceId  = (int)($body['invoice_id']  ?? 0);
$bkavNo     = trim($body['bkav_no']      ?? '');
$bkavDate   = trim($body['bkav_date']    ?? '');

if (!$invoiceId) { echo json_encode(['ok' => false, 'msg' => 'Thiếu invoice_id']); exit; }
if (!$bkavNo)    { echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập số hoá đơn BKAV']); exit; }
if (!$bkavDate)  { echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập ngày hoá đơn BKAV']); exit; }

// Validate date format
$d = DateTime::createFromFormat('Y-m-d', $bkavDate);
if (!$d || $d->format('Y-m-d') !== $bkavDate) {
    echo json_encode(['ok' => false, 'msg' => 'Ngày hoá đơn không hợp lệ']); exit;
}

$stmt = $pdo->prepare("SELECT id, is_locked FROM invoices WHERE id = ?");
$stmt->execute([$invoiceId]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inv) { echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy hoá đơn']); exit; }
if (!empty($inv['is_locked'])) { echo json_encode(['ok' => false, 'msg' => 'Hoá đơn đã được khoá rồi']); exit; }

$pdo->prepare("
    UPDATE invoices
    SET is_locked=1, locked_bkav_no=?, locked_bkav_date=?, locked_at=NOW(), locked_by=?
    WHERE id=?
")->execute([$bkavNo, $bkavDate, $user['id'], $invoiceId]);

echo json_encode(['ok' => true, 'msg' => 'Đã khoá hoá đơn']);
