<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}
if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']);
    exit;
}

$pdo = getDBConnection();
$user = currentUser();

$expenseId = (int)($_POST['expense_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$paymentDate = trim($_POST['payment_date'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'cash');
$note = trim($_POST['note'] ?? '') ?: null;

if (!$expenseId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu đề xuất chi phí']);
    exit;
}
if ($amount <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Số tiền thanh toán phải lớn hơn 0']);
    exit;
}
$parsedDate = DateTime::createFromFormat('Y-m-d', $paymentDate);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $paymentDate) {
    echo json_encode(['ok' => false, 'msg' => 'Ngày thanh toán không hợp lệ']);
    exit;
}
if (!in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Hình thức thanh toán không hợp lệ']);
    exit;
}

try {
    $pdo->beginTransaction();

    $expenseStmt = $pdo->prepare("SELECT id, amount, status FROM expense_requests WHERE id = ? FOR UPDATE");
    $expenseStmt->execute([$expenseId]);
    $expense = $expenseStmt->fetch();
    if (!$expense) {
        throw new RuntimeException('Không tìm thấy đề xuất chi phí');
    }
    if ($expense['status'] !== 'approved') {
        throw new RuntimeException('Chỉ ghi nhận thanh toán cho đề xuất đã được duyệt');
    }

    $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expense_payments WHERE expense_id = ?");
    $paidStmt->execute([$expenseId]);
    $alreadyPaid = (float)$paidStmt->fetchColumn();
    $remaining = (float)$expense['amount'] - $alreadyPaid;

    if ($amount > $remaining + 0.001) {
        throw new RuntimeException('Số tiền thanh toán vượt quá số tiền còn lại (' . number_format($remaining, 0, ',', '.') . ' ₫)');
    }

    $pdo->prepare("INSERT INTO expense_payments (expense_id, payment_date, amount, payment_method, paid_by, note)
        VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$expenseId, $paymentDate, $amount, $paymentMethod, $user['id'], $note]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => 'Đã ghi nhận thanh toán']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
