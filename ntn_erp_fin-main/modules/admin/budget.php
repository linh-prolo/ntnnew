<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director');

$pdo = getDBConnection();
$errors = [];
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$selectedMonth = (int)($_GET['month'] ?? date('n'));
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int)date('Y');
}
if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = (int)date('n');
}

$budgetPageUrl = static function (array $overrides = []) use ($selectedYear, $selectedMonth): string {
    $params = [
        'year' => (int)($overrides['year'] ?? $selectedYear),
        'month' => (int)($overrides['month'] ?? $selectedMonth),
    ];
    if (!empty($overrides['show_form'])) {
        $params['show_form'] = 1;
    }
    return 'modules/admin/budget.php?' . http_build_query($params);
};

$categories = getExpenseCategories($pdo);
$categoryIds = array_map(static fn(array $row): int => (int)$row['id'], $categories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePostCsrf();
    $action = trim($_POST['action'] ?? '');
    if ($action === 'save') {
        $year = (int)($_POST['budget_year'] ?? $selectedYear);
        $month = (int)($_POST['budget_month'] ?? $selectedMonth);
        $budgets = $_POST['budgets'] ?? [];

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            $errors[] = 'Kỳ ngân sách không hợp lệ.';
        }
        if (!is_array($budgets)) {
            $errors[] = 'Dữ liệu ngân sách không hợp lệ.';
        }
        if (!$categories) {
            $errors[] = 'Chưa có loại chi phí nào để thiết lập ngân sách.';
        }

        foreach ($budgets as $categoryId => $row) {
            if (!in_array((int)$categoryId, $categoryIds, true)) {
                continue;
            }
            $amount = isset($row['amount']) && $row['amount'] !== '' ? (float)$row['amount'] : 0;
            if ($amount < 0) {
                $errors[] = 'Ngân sách không được âm.';
                break;
            }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO admin_budgets
                    (budget_year, budget_month, category_id, budget_amount, note, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        budget_amount = VALUES(budget_amount),
                        note = VALUES(note),
                        updated_at = CURRENT_TIMESTAMP");
                foreach ($categoryIds as $categoryId) {
                    $row = $budgets[$categoryId] ?? ['amount' => 0, 'note' => ''];
                    $amount = isset($row['amount']) && $row['amount'] !== '' ? (float)$row['amount'] : 0;
                    $note = trim((string)($row['note'] ?? '')) ?: null;
                    $stmt->execute([$year, $month, $categoryId, $amount, $note, currentUserId()]);
                }
                $pdo->commit();
                setFlash('success', 'Đã lưu ngân sách.');
                redirect($budgetPageUrl(['year' => $year, 'month' => $month]));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Không thể lưu ngân sách.';
            }
        }
        $selectedYear = $year;
        $selectedMonth = $month;
    }
}

$budgetRows = fetchAllSafe($pdo, 'SELECT * FROM admin_budgets WHERE budget_year = ? AND budget_month = ?', [$selectedYear, $selectedMonth]);
$budgetByCategory = [];
foreach ($budgetRows as $row) {
    $budgetByCategory[(int)$row['category_id']] = $row;
}
$startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$endDate = date('Y-m-t', strtotime($startDate));
$actualRows = fetchAllSafe(
    $pdo,
    "SELECT category_id, COALESCE(SUM(amount), 0) AS actual_amount
     FROM expense_requests
     WHERE status = 'approved' AND expense_date BETWEEN ? AND ?
     GROUP BY category_id",
    [$startDate, $endDate]
);
$actualByCategory = [];
foreach ($actualRows as $row) {
    $actualByCategory[(int)$row['category_id']] = (float)$row['actual_amount'];
}
$rows = [];
$totalBudget = 0;
$totalActual = 0;
foreach ($categories as $category) {
    $categoryId = (int)$category['id'];
    $budgetAmount = (float)($budgetByCategory[$categoryId]['budget_amount'] ?? 0);
    $actualAmount = (float)($actualByCategory[$categoryId] ?? 0);
    $remaining = $budgetAmount - $actualAmount;
    $usage = $budgetAmount > 0 ? round(($actualAmount / $budgetAmount) * 100, 2) : ($actualAmount > 0 ? 100 : 0);
    $rows[] = [
        'id' => $categoryId,
        'category_name' => $category['category_name'],
        'budget' => $budgetAmount,
        'actual' => $actualAmount,
        'remaining' => $remaining,
        'usage' => $usage,
        'note' => $budgetByCategory[$categoryId]['note'] ?? '',
    ];
    $totalBudget += $budgetAmount;
    $totalActual += $actualAmount;
}
$totalRemaining = $totalBudget - $totalActual;
$totalUsage = $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 2) : ($totalActual > 0 ? 100 : 0);
$showForm = !empty($errors) || isset($_GET['show_form']);

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-chart-pie me-2 text-primary"></i>Ngân sách hành chính</h4>
                <p class="text-muted mb-0">Thiết lập ngân sách tháng và so sánh với thực chi đã duyệt.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#budget-form-card" aria-expanded="<?= $showForm ? 'true' : 'false' ?>" <?= $categories ? '' : 'disabled' ?>>
                <i class="fas fa-sliders-h me-1"></i> Thiết lập ngân sách
            </button>
        </div>

        <?php showFlash(); ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="get" class="row g-2 align-items-center">
                    <div class="col-md-2"><input type="number" name="year" min="2000" max="2100" class="form-control form-control-sm" value="<?= $selectedYear ?>"></div>
                    <div class="col-md-2"><select name="month" class="form-select form-select-sm"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option><?php endfor; ?></select></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Xem</button></div>
                </form>
            </div>
        </div>

        <div class="collapse <?= $showForm ? 'show' : '' ?> mb-4" id="budget-form-card">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Thiết lập ngân sách tháng <?= $selectedMonth ?>/<?= $selectedYear ?></h5></div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <?php if (!$categories): ?>
                        <div class="alert alert-warning mb-0">Chưa có loại chi phí nào. Vui lòng thêm dữ liệu vào bảng <code>expense_categories</code>.</div>
                    <?php else: ?>
                        <form method="post">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="budget_year" value="<?= $selectedYear ?>">
                            <input type="hidden" name="budget_month" value="<?= $selectedMonth ?>">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light"><tr><th>Loại chi phí</th><th class="text-end" width="180">Ngân sách</th><th>Ghi chú</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($row['category_name']) ?></td>
                                            <td><input type="number" step="0.01" min="0" name="budgets[<?= $row['id'] ?>][amount]" class="form-control text-end" value="<?= e((string)$row['budget']) ?>"></td>
                                            <td><input type="text" name="budgets[<?= $row['id'] ?>][note]" class="form-control" value="<?= e($row['note']) ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu ngân sách</button></div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Loại CP</th><th class="text-end">Ngân sách</th><th class="text-end">Thực chi</th><th class="text-end">Còn lại</th><th>% sử dụng</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu ngân sách.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $usageBar = min(100, max(0, $row['usage'])); $barClass = $row['usage'] >= 100 ? 'bg-danger' : ($row['usage'] >= 80 ? 'bg-warning' : 'bg-success'); ?>
                            <tr>
                                <td><div class="fw-semibold"><?= e($row['category_name']) ?></div><div class="small text-muted"><?= e($row['note'] ?: '—') ?></div></td>
                                <td class="text-end fw-semibold"><?= e(formatCurrency($row['budget'])) ?></td>
                                <td class="text-end"><?= e(formatCurrency($row['actual'])) ?></td>
                                <td class="text-end <?= $row['remaining'] < 0 ? 'text-danger fw-semibold' : '' ?>"><?= e(formatCurrency($row['remaining'])) ?></td>
                                <td>
                                    <div class="d-flex justify-content-between small mb-1"><span><?= e(number_format($row['usage'], 0, ',', '.')) ?>%</span><span><?= e(formatCurrency($row['actual'])) ?>/<?= e(formatCurrency($row['budget'])) ?></span></div>
                                    <div class="progress" style="height:10px;"><div class="progress-bar <?= $barClass ?>" style="width: <?= $usageBar ?>%"></div></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>Tổng cộng</th>
                            <th class="text-end"><?= e(formatCurrency($totalBudget)) ?></th>
                            <th class="text-end"><?= e(formatCurrency($totalActual)) ?></th>
                            <th class="text-end <?= $totalRemaining < 0 ? 'text-danger' : '' ?>"><?= e(formatCurrency($totalRemaining)) ?></th>
                            <th>
                                <div class="d-flex justify-content-between small mb-1"><span><?= e(number_format($totalUsage, 0, ',', '.')) ?>%</span><span><?= e(formatCurrency($totalActual)) ?>/<?= e(formatCurrency($totalBudget)) ?></span></div>
                                <div class="progress" style="height:10px;"><div class="progress-bar <?= $totalUsage >= 100 ? 'bg-danger' : ($totalUsage >= 80 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= min(100, max(0, $totalUsage)) ?>%"></div></div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php';
