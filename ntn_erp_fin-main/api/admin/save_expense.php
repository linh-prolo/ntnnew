<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager','warehouse','production','employee');

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
$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);
$canViewAll = hasRole('director', 'accountant', 'manager');
$canApprove = hasRole('director', 'accountant');
$canReject = hasRole('director', 'accountant');

$findExpense = static function (PDO $pdo, int $expenseId) {
    $stmt = $pdo->prepare('SELECT * FROM expense_requests WHERE id = ?');
    $stmt->execute([$expenseId]);
    return $stmt->fetch();
};

$canModifyExpense = static function (array $expense, array $user, bool $canViewAll): bool {
    return $canViewAll || (int)$expense['requested_by'] === (int)$user['id'];
};

if (in_array($action, ['delete', 'submit', 'approve', 'reject'], true)) {
    if (!$id) {
        echo json_encode(['ok' => false, 'msg' => 'Thiếu ID']);
        exit;
    }

    $expense = $findExpense($pdo, $id);
    if (!$expense) {
        echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy đề xuất']);
        exit;
    }
}

if ($action === 'delete') {
    if ($expense['status'] !== 'draft') {
        echo json_encode(['ok' => false, 'msg' => 'Chỉ xoá được đề xuất nháp']);
        exit;
    }
    if (!$canModifyExpense($expense, $user, $canViewAll)) {
        echo json_encode(['ok' => false, 'msg' => 'Bạn không có quyền xoá đề xuất này']);
        exit;
    }

    $pdo->prepare('DELETE FROM expense_requests WHERE id = ?')->execute([$id]);
    echo json_encode(['ok' => true, 'msg' => 'Đã xoá đề xuất']);
    exit;
}

if ($action === 'submit') {
    if ($expense['status'] !== 'draft') {
        echo json_encode(['ok' => false, 'msg' => 'Chỉ gửi duyệt được đề xuất nháp']);
        exit;
    }
    if (!$canModifyExpense($expense, $user, $canViewAll)) {
        echo json_encode(['ok' => false, 'msg' => 'Bạn không có quyền gửi duyệt đề xuất này']);
        exit;
    }

    $pdo->prepare("UPDATE expense_requests SET status = 'submitted', reject_reason = NULL, updated_at = NOW() WHERE id = ?")
        ->execute([$id]);
    echo json_encode(['ok' => true, 'msg' => 'Đã gửi đề xuất chờ duyệt']);
    exit;
}

if ($action === 'approve') {
    if (!$canApprove) {
        echo json_encode(['ok' => false, 'msg' => 'Bạn không có quyền duyệt']);
        exit;
    }
    if ($expense['status'] !== 'submitted') {
        echo json_encode(['ok' => false, 'msg' => 'Chỉ duyệt được đề xuất đang chờ duyệt']);
        exit;
    }

    $pdo->prepare("UPDATE expense_requests
        SET status = 'approved', approved_by = ?, approved_at = NOW(), reject_reason = NULL, updated_at = NOW()
        WHERE id = ?")
        ->execute([$user['id'], $id]);
    echo json_encode(['ok' => true, 'msg' => 'Đã duyệt đề xuất']);
    exit;
}

if ($action === 'reject') {
    if (!$canReject) {
        echo json_encode(['ok' => false, 'msg' => 'Bạn không có quyền từ chối']);
        exit;
    }
    if ($expense['status'] !== 'submitted') {
        echo json_encode(['ok' => false, 'msg' => 'Chỉ từ chối được đề xuất đang chờ duyệt']);
        exit;
    }

    $rejectReason = trim($_POST['reject_reason'] ?? '');
    if ($rejectReason === '') {
        echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập lý do từ chối']);
        exit;
    }

    $pdo->prepare("UPDATE expense_requests
        SET status = 'rejected', approved_by = ?, approved_at = NOW(), reject_reason = ?, updated_at = NOW()
        WHERE id = ?")
        ->execute([$user['id'], $rejectReason, $id]);
    echo json_encode(['ok' => true, 'msg' => 'Đã từ chối đề xuất']);
    exit;
}

if (!in_array($action, ['add', 'edit'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Action không hợp lệ']);
    exit;
}

$categoryId = (int)($_POST['category_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
$purpose = trim($_POST['purpose'] ?? '');
$hasInvoice = !empty($_POST['has_invoice']) ? 1 : 0;
$invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
$invoiceDate = trim($_POST['invoice_date'] ?? '') ?: null;
$invoiceCompany = trim($_POST['invoice_company'] ?? '') ?: null;
$paymentMethod = trim($_POST['payment_method'] ?? 'cash');
$note = trim($_POST['note'] ?? '') ?: null;

if (!$categoryId || !$expenseDate || $amount <= 0 || $purpose === '') {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu dữ liệu bắt buộc']);
    exit;
}
$parsedDate = DateTime::createFromFormat('Y-m-d', $expenseDate);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $expenseDate) {
    echo json_encode(['ok' => false, 'msg' => 'Ngày chi phí không hợp lệ']);
    exit;
}
if (!in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Hình thức thanh toán không hợp lệ']);
    exit;
}
if (!$hasInvoice) {
    $invoiceNo = null;
    $invoiceDate = null;
    $invoiceCompany = null;
}

try {
    $pdo->beginTransaction();

    if ($action === 'edit') {
        if (!$id) {
            throw new RuntimeException('Thiếu ID');
        }
        $expense = $findExpense($pdo, $id);
        if (!$expense) {
            throw new RuntimeException('Không tìm thấy đề xuất');
        }
        if ($expense['status'] !== 'draft') {
            throw new RuntimeException('Chỉ sửa được đề xuất nháp');
        }
        if (!$canModifyExpense($expense, $user, $canViewAll)) {
            throw new RuntimeException('Bạn không có quyền sửa đề xuất này');
        }

        $pdo->prepare("UPDATE expense_requests
            SET category_id = ?, amount = ?, expense_date = ?, purpose = ?, has_invoice = ?,
                invoice_no = ?, invoice_date = ?, invoice_company = ?, payment_method = ?, note = ?, updated_at = NOW()
            WHERE id = ?")
            ->execute([
                $categoryId,
                $amount,
                $expenseDate,
                $purpose,
                $hasInvoice,
                $invoiceNo,
                $invoiceDate,
                $invoiceCompany,
                $paymentMethod,
                $note,
                $id,
            ]);
    } else {
        $nextSeq = (int)$pdo->query('SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(request_no, \'-\', -1) AS UNSIGNED)), 0) + 1 FROM expense_requests FOR UPDATE')->fetchColumn();
        $requestNo = 'EXP-' . date('Ymd', strtotime($expenseDate)) . '-' . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT);

        $pdo->prepare("INSERT INTO expense_requests
            (request_no, category_id, amount, expense_date, purpose, has_invoice, invoice_no, invoice_date, invoice_company, payment_method, status, requested_by, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)")
            ->execute([
                $requestNo,
                $categoryId,
                $amount,
                $expenseDate,
                $purpose,
                $hasInvoice,
                $invoiceNo,
                $invoiceDate,
                $invoiceCompany,
                $paymentMethod,
                $user['id'],
                $note,
            ]);
        $id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => 'Đã lưu đề xuất chi phí', 'id' => $id]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
