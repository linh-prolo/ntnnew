<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo = getDBConnection();
$user = currentUser();
$canApprove = hasRole('director');
$canManageCategories = hasRole('director', 'accountant');
$canViewHistory = hasRole('director', 'accountant', 'manager');
$canRecordPayment = hasRole('director', 'accountant', 'manager');
$errors = [];
$oldInputWasFlashed = false;
$paymentTolerance = 0.001; // tránh lỗi làm tròn số thực khi so sánh số tiền còn lại

$categories = getExpenseCategories($pdo);
$allCategories = fetchAllSafe(
    $pdo,
    "SELECT ec.id, ec.category_name, ec.is_active, COUNT(er.id) AS usage_count
     FROM expense_categories ec
     LEFT JOIN expense_requests er ON er.category_id = ec.id
     GROUP BY ec.id, ec.category_name, ec.is_active
     ORDER BY ec.is_active DESC, ec.category_name ASC"
);
$categoryIds = array_map(static fn(array $row): int => (int)$row['id'], $categories);
$filterMonth = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$filterCategory = (int)($_GET['category_id'] ?? 0);
$filterStatus = trim($_GET['status'] ?? '');
$allowedTabs = ['mine', 'pending', 'history', 'categories'];
$activeTab = in_array($_GET['tab'] ?? 'mine', $allowedTabs, true) ? (string)($_GET['tab'] ?? 'mine') : 'mine';
if ($activeTab === 'pending' && !$canApprove) {
    $activeTab = 'mine';
}
if ($activeTab === 'categories' && !$canManageCategories) {
    $activeTab = 'mine';
}

$expensePageUrl = static function (array $overrides = []) use ($filterMonth, $filterCategory, $filterStatus, $activeTab): string {
    $params = [
        'month' => $overrides['month'] ?? $filterMonth,
        'tab' => $overrides['tab'] ?? $activeTab,
    ];
    $categoryId = $overrides['category_id'] ?? $filterCategory;
    $status = $overrides['status'] ?? $filterStatus;
    if ((int)$categoryId > 0) {
        $params['category_id'] = (int)$categoryId;
    }
    if ($status !== '') {
        $params['status'] = (string)$status;
    }
    if (!empty($overrides['edit_id'])) {
        $params['edit_id'] = (int)$overrides['edit_id'];
    }
    if (!empty($overrides['show_form'])) {
        $params['show_form'] = 1;
    }
    if (!empty($overrides['payment'])) {
        $params['payment'] = (int)$overrides['payment'];
    }
    return 'modules/admin/expenses.php?' . http_build_query($params);
};

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return false;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

$findExpense = static function (int $expenseId) use ($pdo): ?array {
    return fetchOneSafe(
        $pdo,
        "SELECT er.*, ec.category_name, ru.full_name AS requested_name, au.full_name AS approved_name,
                COALESCE(SUM(ep.amount), 0) AS paid_amount
         FROM expense_requests er
         JOIN expense_categories ec ON ec.id = er.category_id
         JOIN users ru ON ru.id = er.requested_by
         LEFT JOIN users au ON au.id = er.approved_by
         LEFT JOIN expense_payments ep ON ep.expense_id = er.id
         WHERE er.id = ?
         GROUP BY er.id
         LIMIT 1",
        [$expenseId]
    );
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePostCsrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create_category') {
        if (!$canManageCategories) {
            setFlash('danger', 'Bạn không có quyền thực hiện thao tác này.');
            redirect($expensePageUrl(['tab' => 'mine']));
        }

        $catName = trim($_POST['category_name'] ?? '');
        if ($catName === '') {
            setFlash('danger', 'Tên loại chi phí không được để trống.');
        } else {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM expense_categories WHERE category_name = ?');
            $exists->execute([$catName]);
            if ((int)$exists->fetchColumn() > 0) {
                setFlash('danger', 'Loại chi phí "' . e($catName) . '" đã tồn tại.');
            } else {
                $pdo->prepare('INSERT INTO expense_categories (category_name, is_active) VALUES (?, 1)')
                    ->execute([$catName]);
                setFlash('success', 'Đã thêm loại chi phí: ' . e($catName));
            }
        }
        redirect($expensePageUrl(['tab' => 'categories']));
    }

    if ($action === 'edit_category') {
        if (!$canManageCategories) {
            setFlash('danger', 'Bạn không có quyền thực hiện thao tác này.');
            redirect($expensePageUrl(['tab' => 'categories']));
        }

        $catId = (int)($_POST['category_id'] ?? 0);
        $catName = trim($_POST['category_name'] ?? '');
        if ($catId <= 0 || $catName === '') {
            setFlash('danger', 'Dữ liệu không hợp lệ.');
        } else {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM expense_categories WHERE category_name = ? AND id != ?');
            $exists->execute([$catName, $catId]);
            if ((int)$exists->fetchColumn() > 0) {
                setFlash('danger', 'Tên loại chi phí đã tồn tại.');
            } else {
                $pdo->prepare('UPDATE expense_categories SET category_name = ? WHERE id = ?')
                    ->execute([$catName, $catId]);
                setFlash('success', 'Đã cập nhật loại chi phí.');
            }
        }
        redirect($expensePageUrl(['tab' => 'categories']));
    }

    if ($action === 'toggle_category') {
        if (!$canManageCategories) {
            setFlash('danger', 'Bạn không có quyền thực hiện thao tác này.');
            redirect($expensePageUrl(['tab' => 'categories']));
        }

        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $pdo->prepare('UPDATE expense_categories SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?')
                ->execute([$catId]);
            setFlash('success', 'Đã cập nhật trạng thái loại chi phí.');
        }
        redirect($expensePageUrl(['tab' => 'categories']));
    }

    if ($action === 'create') {
        $expenseId = (int)($_POST['expense_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        $hasInvoice = !empty($_POST['has_invoice']) ? 1 : 0;
        $invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
        $invoiceDate = trim($_POST['invoice_date'] ?? '') ?: null;
        $invoiceCompany = trim($_POST['invoice_company'] ?? '') ?: null;
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
        $note = trim($_POST['note'] ?? '') ?: null;

        if (!$categories) {
            $errors[] = 'Chưa có loại chi phí nào để tạo đề xuất.';
        }
        if (!in_array($categoryId, $categoryIds, true)) {
            $errors[] = 'Loại chi phí không hợp lệ.';
        }
        if (!$isValidDate($expenseDate)) {
            $errors[] = 'Ngày chi phí không hợp lệ.';
        }
        if ($amount <= 0) {
            $errors[] = 'Số tiền phải lớn hơn 0.';
        }
        if ($purpose === '') {
            $errors[] = 'Vui lòng nhập mục đích chi phí.';
        }
        if (!in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
            $errors[] = 'Hình thức thanh toán không hợp lệ.';
        }
        if ($hasInvoice && $invoiceDate !== null && !$isValidDate($invoiceDate)) {
            $errors[] = 'Ngày hoá đơn không hợp lệ.';
        }
        if (!$hasInvoice) {
            $invoiceNo = null;
            $invoiceDate = null;
            $invoiceCompany = null;
        }

        $existingExpense = null;
        if ($expenseId > 0) {
            $existingExpense = fetchOneSafe($pdo, 'SELECT * FROM expense_requests WHERE id = ? LIMIT 1', [$expenseId]);
            if (!$existingExpense) {
                $errors[] = 'Không tìm thấy đề xuất cần cập nhật.';
            } elseif ($existingExpense['status'] !== 'draft') {
                $errors[] = 'Chỉ có thể sửa đề xuất ở trạng thái nháp.';
            } elseif ((int)$existingExpense['requested_by'] !== currentUserId()) {
                $errors[] = 'Bạn không có quyền sửa đề xuất này.';
            }
        }

        if (!$errors) {
            try {
                if ($expenseId > 0) {
                    $stmt = $pdo->prepare("UPDATE expense_requests
                        SET category_id = ?, amount = ?, expense_date = ?, purpose = ?, has_invoice = ?, invoice_no = ?,
                            invoice_date = ?, invoice_company = ?, payment_method = ?, note = ?, reject_reason = NULL,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?");
                    $stmt->execute([
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
                        $expenseId,
                    ]);
                    setFlash('success', 'Đã cập nhật đề xuất chi phí.');
                } else {
                    $prefix = 'EXP-' . date('Ymd') . '-';
                    $last = fetchScalarSafe(
                        $pdo,
                        'SELECT request_no FROM expense_requests WHERE request_no LIKE ? ORDER BY id DESC LIMIT 1',
                        [$prefix . '%']
                    );
                    $seq = $last ? ((int)substr((string)$last, -3) + 1) : 1;
                    $requestNo = $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

                    $stmt = $pdo->prepare("INSERT INTO expense_requests
                        (request_no, category_id, amount, expense_date, purpose, has_invoice, invoice_no, invoice_date,
                         invoice_company, payment_method, status, requested_by, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)");
                    $stmt->execute([
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
                        currentUserId(),
                        $note,
                    ]);
                    setFlash('success', 'Đã tạo đề xuất chi phí mới.');
                }
                clearOldInput();
                redirect($expensePageUrl(['tab' => 'mine']));
            } catch (Throwable $e) {
                $errors[] = 'Không thể lưu đề xuất chi phí.';
            }
        }
    }

    if ($action === 'submit') {
        $expenseId = (int)($_POST['id'] ?? 0);
        $expense = $findExpense($expenseId);
        if (!$expense) {
            setFlash('danger', 'Không tìm thấy đề xuất.');
        } elseif ($expense['status'] !== 'draft') {
            setFlash('danger', 'Chỉ có thể gửi duyệt đề xuất ở trạng thái nháp.');
        } elseif ((int)$expense['requested_by'] !== currentUserId()) {
            setFlash('danger', 'Bạn không có quyền gửi duyệt đề xuất này.');
        } else {
            $pdo->prepare("UPDATE expense_requests SET status = 'submitted', reject_reason = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$expenseId]);
            setFlash('success', 'Đã gửi đề xuất chờ duyệt.');
        }
        redirect($expensePageUrl(['tab' => 'mine']));
    }

    if ($action === 'delete') {
        $expenseId = (int)($_POST['id'] ?? 0);
        $expense = $findExpense($expenseId);
        if (!$expense) {
            setFlash('danger', 'Không tìm thấy đề xuất.');
        } elseif ($expense['status'] !== 'draft') {
            setFlash('danger', 'Chỉ có thể xoá đề xuất ở trạng thái nháp.');
        } elseif ((int)$expense['requested_by'] !== currentUserId()) {
            setFlash('danger', 'Bạn không có quyền xoá đề xuất này.');
        } else {
            $pdo->prepare('DELETE FROM expense_requests WHERE id = ?')->execute([$expenseId]);
            setFlash('success', 'Đã xoá đề xuất chi phí.');
        }
        redirect($expensePageUrl(['tab' => 'mine']));
    }

    if ($action === 'approve') {
        $expenseId = (int)($_POST['id'] ?? 0);
        $expense = $findExpense($expenseId);
        if (!$canApprove) {
            setFlash('danger', 'Bạn không có quyền duyệt đề xuất.');
        } elseif (!$expense) {
            setFlash('danger', 'Không tìm thấy đề xuất.');
        } elseif ($expense['status'] !== 'submitted') {
            setFlash('danger', 'Chỉ có thể duyệt đề xuất đang chờ duyệt.');
        } else {
            $pdo->prepare("UPDATE expense_requests
                SET status = 'approved', approved_by = ?, approved_at = CURRENT_TIMESTAMP, reject_reason = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?")
                ->execute([currentUserId(), $expenseId]);
            setFlash('success', 'Đã duyệt đề xuất chi phí.');
        }
        redirect($expensePageUrl(['tab' => 'pending']));
    }

    if ($action === 'reject') {
        $expenseId = (int)($_POST['id'] ?? 0);
        $rejectReason = trim($_POST['reject_reason'] ?? '');
        $expense = $findExpense($expenseId);
        if (!$canApprove) {
            setFlash('danger', 'Bạn không có quyền từ chối đề xuất.');
        } elseif (!$expense) {
            setFlash('danger', 'Không tìm thấy đề xuất.');
        } elseif ($expense['status'] !== 'submitted') {
            setFlash('danger', 'Chỉ có thể từ chối đề xuất đang chờ duyệt.');
        } elseif ($rejectReason === '') {
            setFlash('danger', 'Vui lòng nhập lý do từ chối.');
        } else {
            $pdo->prepare("UPDATE expense_requests
                SET status = 'rejected', approved_by = ?, approved_at = CURRENT_TIMESTAMP, reject_reason = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?")
                ->execute([currentUserId(), $rejectReason, $expenseId]);
            setFlash('success', 'Đã từ chối đề xuất chi phí.');
        }
        redirect($expensePageUrl(['tab' => 'pending']));
    }

    if ($action === 'payment') {
        $expenseId = (int)($_POST['expense_id'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
        $note = trim($_POST['note'] ?? '') ?: null;
        $expense = $findExpense($expenseId);

        if (!$canRecordPayment) {
            setFlash('danger', 'Bạn không có quyền ghi nhận thanh toán.');
        } elseif (!$expense) {
            setFlash('danger', 'Không tìm thấy đề xuất cần thanh toán.');
        } elseif ($expense['status'] !== 'approved') {
            setFlash('danger', 'Chỉ có thể ghi nhận thanh toán cho đề xuất đã duyệt.');
        } elseif (!$isValidDate($paymentDate)) {
            setFlash('danger', 'Ngày thanh toán không hợp lệ.');
        } elseif ($amount <= 0) {
            setFlash('danger', 'Số tiền thanh toán phải lớn hơn 0.');
        } elseif (!in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
            setFlash('danger', 'Hình thức thanh toán không hợp lệ.');
        } else {
            $paidAmount = (float)fetchScalarSafe($pdo, 'SELECT COALESCE(SUM(amount), 0) FROM expense_payments WHERE expense_id = ?', [$expenseId], 0);
            $remaining = (float)$expense['amount'] - $paidAmount;
            if ($amount > $remaining + $paymentTolerance) {
                setFlash('danger', 'Số tiền thanh toán vượt quá số tiền còn lại.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO expense_payments (expense_id, payment_date, amount, payment_method, paid_by, note) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$expenseId, $paymentDate, $amount, $paymentMethod, currentUserId(), $note]);
                setFlash('success', 'Đã ghi nhận thanh toán.');
            }
        }
        redirect($expensePageUrl(['tab' => 'history']));
    }

    if ($errors) {
        flashOldInput($_POST);
        $oldInputWasFlashed = true;
    }
}

$editExpenseId = (int)($_GET['edit_id'] ?? 0);
$editExpense = null;
if ($editExpenseId > 0) {
    $editExpense = fetchOneSafe($pdo, 'SELECT * FROM expense_requests WHERE id = ? LIMIT 1', [$editExpenseId]);
    if (!$editExpense || $editExpense['status'] !== 'draft' || (int)$editExpense['requested_by'] !== currentUserId()) {
        $editExpense = null;
        setFlash('danger', 'Không thể mở đề xuất cần sửa.');
    }
}

$formValues = [
    'expense_id' => $editExpense['id'] ?? '',
    'category_id' => (string)($editExpense['category_id'] ?? ''),
    'expense_date' => $editExpense['expense_date'] ?? date('Y-m-d'),
    'amount' => isset($editExpense['amount']) ? (string)(float)$editExpense['amount'] : '',
    'purpose' => $editExpense['purpose'] ?? '',
    'has_invoice' => isset($editExpense['has_invoice']) ? (string)(int)$editExpense['has_invoice'] : '0',
    'invoice_no' => $editExpense['invoice_no'] ?? '',
    'invoice_date' => $editExpense['invoice_date'] ?? '',
    'invoice_company' => $editExpense['invoice_company'] ?? '',
    'payment_method' => $editExpense['payment_method'] ?? 'cash',
    'note' => $editExpense['note'] ?? '',
];

if (isset($_SESSION['_old_input']) && is_array($_SESSION['_old_input'])) {
    $formValues = array_merge($formValues, [
        'expense_id' => (string)($_SESSION['_old_input']['expense_id'] ?? $formValues['expense_id']),
        'category_id' => (string)($_SESSION['_old_input']['category_id'] ?? $formValues['category_id']),
        'expense_date' => (string)($_SESSION['_old_input']['expense_date'] ?? $formValues['expense_date']),
        'amount' => (string)($_SESSION['_old_input']['amount'] ?? $formValues['amount']),
        'purpose' => (string)($_SESSION['_old_input']['purpose'] ?? $formValues['purpose']),
        'has_invoice' => !empty($_SESSION['_old_input']['has_invoice']) ? '1' : '0',
        'invoice_no' => (string)($_SESSION['_old_input']['invoice_no'] ?? $formValues['invoice_no']),
        'invoice_date' => (string)($_SESSION['_old_input']['invoice_date'] ?? $formValues['invoice_date']),
        'invoice_company' => (string)($_SESSION['_old_input']['invoice_company'] ?? $formValues['invoice_company']),
        'payment_method' => (string)($_SESSION['_old_input']['payment_method'] ?? $formValues['payment_method']),
        'note' => (string)($_SESSION['_old_input']['note'] ?? $formValues['note']),
    ]);
}

$showForm = !empty($errors) || $editExpense !== null || isset($_GET['show_form']);
$monthStart = $filterMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$baseWhere = ['er.expense_date BETWEEN ? AND ?'];
$params = [$monthStart, $monthEnd];
if ($filterCategory > 0) {
    $baseWhere[] = 'er.category_id = ?';
    $params[] = $filterCategory;
}
if ($filterStatus !== '' && $activeTab === 'mine') {
    $baseWhere[] = 'er.status = ?';
    $params[] = $filterStatus;
}

switch ($activeTab) {
    case 'pending':
        $baseWhere[] = "er.status = 'submitted'";
        break;
    case 'history':
        $baseWhere[] = "er.status IN ('approved', 'rejected')";
        if (!$canViewHistory) {
            $baseWhere[] = 'er.requested_by = ?';
            $params[] = currentUserId();
        }
        break;
    case 'mine':
    default:
        $baseWhere[] = 'er.requested_by = ?';
        $params[] = currentUserId();
        break;
}

$expenses = fetchAllSafe(
    $pdo,
    "SELECT er.*, ec.category_name, ru.full_name AS requested_name, au.full_name AS approved_name,
            COALESCE(SUM(ep.amount), 0) AS paid_amount
     FROM expense_requests er
     JOIN expense_categories ec ON ec.id = er.category_id
     JOIN users ru ON ru.id = er.requested_by
     LEFT JOIN users au ON au.id = er.approved_by
     LEFT JOIN expense_payments ep ON ep.expense_id = er.id
     WHERE " . implode(' AND ', $baseWhere) . "
     GROUP BY er.id
     ORDER BY er.expense_date DESC, er.id DESC",
    $params
);

$paymentsByExpense = [];
$expenseIds = array_column($expenses, 'id');
if ($expenseIds) {
    $placeholders = implode(',', array_fill(0, count($expenseIds), '?'));
    $payments = fetchAllSafe(
        $pdo,
        "SELECT ep.*, u.full_name AS paid_by_name
         FROM expense_payments ep
         LEFT JOIN users u ON u.id = ep.paid_by
         WHERE ep.expense_id IN ($placeholders)
         ORDER BY ep.payment_date DESC, ep.id DESC",
        $expenseIds
    );
    foreach ($payments as $payment) {
        $paymentsByExpense[(int)$payment['expense_id']][] = $payment;
    }
}

$pendingCount = (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM expense_requests WHERE status = 'submitted'", [], 0);
$mineCount = (int)fetchScalarSafe($pdo, 'SELECT COUNT(*) FROM expense_requests WHERE requested_by = ?', [currentUserId()], 0);
$historyCount = (int)fetchScalarSafe(
    $pdo,
    $canViewHistory
        ? "SELECT COUNT(*) FROM expense_requests WHERE status IN ('approved', 'rejected')"
        : "SELECT COUNT(*) FROM expense_requests WHERE requested_by = ? AND status IN ('approved', 'rejected')",
    $canViewHistory ? [] : [currentUserId()],
    0
);

$paymentExpenseId = (int)($_GET['payment'] ?? 0);
$paymentExpense = $paymentExpenseId > 0 ? $findExpense($paymentExpenseId) : null;
if ($paymentExpense && $paymentExpense['status'] !== 'approved') {
    $paymentExpense = null;
}

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Quản lý chi phí hành chính</h4>
                <p class="text-muted mb-0">Tạo đề xuất, gửi duyệt và theo dõi thanh toán theo luồng form POST truyền thống.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canManageCategories): ?>
                    <a href="/erp/<?= e($expensePageUrl(['tab' => 'categories'])) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-tags me-1"></i>Loại chi phí
                    </a>
                <?php endif; ?>
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#expense-form-card" aria-expanded="<?= $showForm ? 'true' : 'false' ?>" <?= $categories ? '' : 'disabled' ?> >
                    <i class="fas fa-plus me-1"></i> Tạo đề xuất
                </button>
            </div>
        </div>

        <?php showFlash(); ?>

        <div class="collapse <?= $showForm ? 'show' : '' ?> mb-4" id="expense-form-card">
            <div class="card border-0 shadow-sm" id="expense-form">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= !empty($formValues['expense_id']) ? 'Cập nhật đề xuất' : 'Tạo đề xuất chi phí' ?></h5>
                    <?php if (!empty($formValues['expense_id'])): ?>
                        <a href="/erp/<?= e($expensePageUrl(['tab' => 'mine'])) ?>" class="btn btn-sm btn-outline-secondary">Huỷ sửa</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger mb-3">
                            <div class="fw-semibold mb-1">Vui lòng kiểm tra lại dữ liệu:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!$categories): ?>
                        <div class="alert alert-warning mb-0">
                            Chưa có loại chi phí nào.
                            <?php if ($canManageCategories): ?>
                                Vui lòng thêm tại tab <strong>Loại chi phí</strong>.
                            <?php else: ?>
                                Vui lòng liên hệ kế toán hoặc giám đốc để bổ sung dữ liệu.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="expense_id" value="<?= e((string)$formValues['expense_id']) ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Loại chi phí <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">-- Chọn loại chi phí --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= (int)$category['id'] ?>" <?= (string)$category['id'] === (string)$formValues['category_id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Ngày chi phí <span class="text-danger">*</span></label>
                                    <input type="date" name="expense_date" class="form-control" value="<?= e($formValues['expense_date']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Số tiền <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control text-end" min="0" step="0.01" value="<?= e($formValues['amount']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Mục đích <span class="text-danger">*</span></label>
                                    <textarea name="purpose" class="form-control" rows="3" required><?= e($formValues['purpose']) ?></textarea>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check mt-md-4 pt-md-2">
                                        <input class="form-check-input" type="checkbox" id="hasInvoice" name="has_invoice" value="1" <?= $formValues['has_invoice'] === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="hasInvoice">Có hoá đơn</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Hình thức thanh toán</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="cash" <?= $formValues['payment_method'] === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                                        <option value="bank_transfer" <?= $formValues['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Ghi chú</label>
                                    <input type="text" name="note" class="form-control" value="<?= e($formValues['note']) ?>">
                                </div>
                            </div>
                            <div class="row g-3 mt-1 <?= $formValues['has_invoice'] === '1' ? '' : 'd-none' ?>" id="invoiceFields">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Số hoá đơn</label>
                                    <input type="text" name="invoice_no" class="form-control" value="<?= e($formValues['invoice_no']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Ngày hoá đơn</label>
                                    <input type="date" name="invoice_date" class="form-control" value="<?= e($formValues['invoice_date']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Công ty xuất hoá đơn</label>
                                    <input type="text" name="invoice_company" class="form-control" value="<?= e($formValues['invoice_company']) ?>">
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu nháp</button>
                                <?php if (!empty($formValues['expense_id'])): ?>
                                    <a href="/erp/<?= e($expensePageUrl(['tab' => 'mine'])) ?>" class="btn btn-outline-secondary">Huỷ</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($activeTab !== 'categories'): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="get" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                        <div class="col-md-2">
                            <input type="month" name="month" class="form-control form-control-sm" value="<?= e($filterMonth) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">-- Loại chi phí --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= $filterCategory === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">-- Trạng thái --</option>
                                <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Nháp</option>
                                <option value="submitted" <?= $filterStatus === 'submitted' ? 'selected' : '' ?>>Chờ duyệt</option>
                                <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button>
                            <a href="/erp/<?= e($expensePageUrl(['status' => '', 'category_id' => 0])) ?>" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link <?= $activeTab === 'mine' ? 'active' : '' ?>" href="/erp/<?= e($expensePageUrl(['tab' => 'mine'])) ?>">Của tôi <span class="badge bg-secondary ms-1"><?= $mineCount ?></span></a></li>
            <?php if ($canApprove): ?>
                <li class="nav-item"><a class="nav-link <?= $activeTab === 'pending' ? 'active' : '' ?>" href="/erp/<?= e($expensePageUrl(['tab' => 'pending', 'status' => ''])) ?>">Chờ duyệt <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?>" href="/erp/<?= e($expensePageUrl(['tab' => 'history', 'status' => ''])) ?>">Lịch sử <span class="badge bg-success ms-1"><?= $historyCount ?></span></a></li>
            <?php if ($canManageCategories): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'categories' ? 'active' : '' ?>" href="/erp/<?= e($expensePageUrl(['tab' => 'categories'])) ?>">
                        <i class="fas fa-tags me-1"></i>Loại chi phí
                        <span class="badge bg-secondary ms-1"><?= count($allCategories) ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <?php if ($activeTab === 'categories' && $canManageCategories): ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-plus-circle me-2 text-success"></i>Thêm loại chi phí mới</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="create_category">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tên loại chi phí <span class="text-danger">*</span></label>
                                    <input type="text" name="category_name" class="form-control" placeholder="VD: Tiền điện, Thuê văn phòng..." maxlength="100" required>
                                    <div class="form-text">Tên sẽ hiển thị trong form tạo đề xuất chi phí.</div>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-1"></i>Thêm loại chi phí
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Danh sách loại chi phí</h6>
                            <span class="text-muted small"><?= count($allCategories) ?> loại</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!$allCategories): ?>
                                <div class="p-4 text-center text-muted">Chưa có loại chi phí nào.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Tên loại chi phí</th>
                                                <th class="text-center">Số đề xuất</th>
                                                <th class="text-center">Trạng thái</th>
                                                <th class="text-center">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($allCategories as $i => $cat): ?>
                                            <tr class="<?= !(int)$cat['is_active'] ? 'table-secondary opacity-75' : '' ?>">
                                                <td class="text-muted small"><?= $i + 1 ?></td>
                                                <td>
                                                    <span class="fw-semibold <?= !(int)$cat['is_active'] ? 'text-muted text-decoration-line-through' : '' ?>">
                                                        <?= e($cat['category_name']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ((int)$cat['usage_count'] > 0): ?>
                                                        <span class="badge bg-info"><?= (int)$cat['usage_count'] ?> đề xuất</span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Chưa dùng</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ((int)$cat['is_active'] === 1): ?>
                                                        <span class="badge bg-success">Đang dùng</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Đã ẩn</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick='openEditModal(<?= (int)$cat['id'] ?>, <?= json_encode((string)$cat['category_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                                            title="Sửa tên"
                                                        >
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('<?= (int)$cat['is_active'] === 1 ? 'Ẩn' : 'Kích hoạt' ?> loại chi phí này?')">
                                                            <?= csrfInput() ?>
                                                            <input type="hidden" name="action" value="toggle_category">
                                                            <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                                            <button type="submit" class="btn btn-sm <?= (int)$cat['is_active'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= (int)$cat['is_active'] === 1 ? 'Ẩn' : 'Kích hoạt' ?>">
                                                                <i class="fas <?= (int)$cat['is_active'] === 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="edit_category">
                            <input type="hidden" name="category_id" id="editCategoryId">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Sửa loại chi phí</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label fw-semibold">Tên loại chi phí <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" id="editCategoryName" class="form-control" maxlength="100" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Số phiếu</th>
                                <th>Ngày</th>
                                <th>Loại</th>
                                <th>Mục đích</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$expenses): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Chưa có đề xuất chi phí nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <?php
                                $paidAmount = (float)$expense['paid_amount'];
                                $remainingAmount = (float)$expense['amount'] - $paidAmount;
                                $statusBadgeClass = match ($expense['status']) {
                                    'submitted' => 'warning text-dark',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary',
                                };
                                $statusLabel = match ($expense['status']) {
                                    'submitted' => 'Chờ duyệt',
                                    'approved' => 'Đã duyệt',
                                    'rejected' => 'Từ chối',
                                    default => 'Nháp',
                                };
                                ?>
                                <tr>
                                    <td class="fw-semibold text-primary"><?= e($expense['request_no']) ?></td>
                                    <td><?= e(formatDate($expense['expense_date'])) ?></td>
                                    <td><?= e($expense['category_name']) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e($expense['purpose']) ?></div>
                                        <div class="small text-muted">Người đề xuất: <?= e($expense['requested_name']) ?></div>
                                        <?php if (!empty($expense['note'])): ?>
                                            <div class="small text-muted">Ghi chú: <?= e($expense['note']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($expense['status'] === 'rejected' && !empty($expense['reject_reason'])): ?>
                                            <div class="small text-danger">Lý do từ chối: <?= e($expense['reject_reason']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-semibold"><?= e(formatCurrency($expense['amount'])) ?></div>
                                        <?php if ($paidAmount > 0): ?>
                                            <div class="small text-success">Đã TT: <?= e(formatCurrency($paidAmount)) ?></div>
                                            <div class="small <?= $remainingAmount > 0 ? 'text-danger' : 'text-muted' ?>">Còn lại: <?= e(formatCurrency(max(0, $remainingAmount))) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                                        <?php if (!empty($expense['approved_name'])): ?>
                                            <div class="small text-muted mt-1"><?= e($expense['approved_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if ($expense['status'] === 'draft' && (int)$expense['requested_by'] === currentUserId()): ?>
                                                <a href="/erp/<?= e($expensePageUrl(['tab' => 'mine', 'edit_id' => (int)$expense['id'], 'show_form' => 1])) ?>#expense-form" class="btn btn-sm btn-outline-primary">Sửa</a>
                                                <form method="post" class="d-inline">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="action" value="submit">
                                                    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Gửi đề xuất này để duyệt?');">Gửi duyệt</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá đề xuất này?');">Xóa</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($expense['status'] === 'submitted' && $canApprove): ?>
                                                <form method="post" class="d-inline">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Duyệt đề xuất này?');">Duyệt</button>
                                                </form>
                                                <form method="post" class="d-inline-flex flex-wrap gap-1 align-items-center mt-1">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">
                                                    <input type="text" name="reject_reason" class="form-control form-control-sm" placeholder="Lý do từ chối" required>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Từ chối</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($expense['status'] === 'approved' && $canRecordPayment && $remainingAmount > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-payment"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#paymentModal"
                                                        data-expense-id="<?= (int)$expense['id'] ?>"
                                                        data-request-no="<?= e($expense['request_no']) ?>"
                                                        data-remaining="<?= e((string)$remainingAmount) ?>">
                                                    Ghi thanh toán
                                                </button>
                                            <?php endif; ?>

                                            <?php if (!empty($paymentsByExpense[(int)$expense['id']])): ?>
                                                <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#payments-<?= (int)$expense['id'] ?>">Lịch sử TT</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php if (!empty($paymentsByExpense[(int)$expense['id']])): ?>
                                    <tr class="collapse" id="payments-<?= (int)$expense['id'] ?>">
                                        <td colspan="7" class="bg-light">
                                            <div class="small fw-semibold mb-2">Lịch sử thanh toán</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Ngày</th>
                                                            <th class="text-end">Số tiền</th>
                                                            <th>Hình thức</th>
                                                            <th>Người ghi nhận</th>
                                                            <th>Ghi chú</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($paymentsByExpense[(int)$expense['id']] as $payment): ?>
                                                            <tr>
                                                                <td><?= e(formatDate($payment['payment_date'])) ?></td>
                                                                <td class="text-end"><?= e(formatCurrency($payment['amount'])) ?></td>
                                                                <td><?= $payment['payment_method'] === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt' ?></td>
                                                                <td><?= e($payment['paid_by_name'] ?? '—') ?></td>
                                                                <td><?= e($payment['note'] ?? '—') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Ghi nhận thanh toán</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="payment">
                    <input type="hidden" name="expense_id" id="paymentExpenseId" value="<?= $paymentExpense ? (int)$paymentExpense['id'] : 0 ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đề xuất</label>
                        <input type="text" class="form-control" id="paymentRequestNo" value="<?= $paymentExpense ? e($paymentExpense['request_no']) : '' ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày thanh toán <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="paymentAmount" class="form-control text-end" min="0.01" step="0.01" required>
                        <div class="form-text" id="paymentRemainingText"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hình thức thanh toán</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Lưu thanh toán</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('hasInvoice')?.addEventListener('change', function () {
    document.getElementById('invoiceFields')?.classList.toggle('d-none', !this.checked);
});

function openEditModal(id, name) {
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryName').value = name;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

document.querySelectorAll('.btn-payment').forEach((button) => {
    button.addEventListener('click', () => {
        const expenseId = button.getAttribute('data-expense-id') || '';
        const requestNo = button.getAttribute('data-request-no') || '';
        const remaining = button.getAttribute('data-remaining') || '0';
        const numericRemaining = Number(remaining);
        document.getElementById('paymentExpenseId').value = expenseId;
        document.getElementById('paymentRequestNo').value = requestNo;
        document.getElementById('paymentAmount').value = numericRemaining > 0 ? numericRemaining.toFixed(2) : '';
        document.getElementById('paymentRemainingText').textContent = 'Số tiền còn lại: ' + numericRemaining.toLocaleString('vi-VN') + ' ₫';
    });
});
</script>
<?php
if ($oldInputWasFlashed || isset($_SESSION['_old_input'])) {
    clearOldInput();
}
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php';
