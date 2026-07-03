<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
$pdo = getDBConnection();

// KPI
$totalItems = (int)fetchScalarSafe($pdo, 'SELECT COUNT(*) FROM wa_items', [], 0);
$totalCategories = (int)fetchScalarSafe($pdo, 'SELECT COUNT(*) FROM wa_categories WHERE is_active = 1', [], 0);
$lowStock = (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM (
    SELECT i.id, COALESCE(SUM(CASE WHEN t.type='import' THEN t.qty ELSE -t.qty END),0) AS stock, i.min_stock
    FROM wa_items i LEFT JOIN wa_transactions t ON t.item_id = i.id GROUP BY i.id
) x WHERE x.stock < x.min_stock", [], 0);
$txnMonth = (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM wa_transactions WHERE DATE_FORMAT(transacted_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')", [], 0);
$importMonth = (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty),0) FROM wa_transactions WHERE type='import' AND DATE_FORMAT(transacted_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')", [], 0);
$exportMonth = (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty),0) FROM wa_transactions WHERE type='export' AND DATE_FORMAT(transacted_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')", [], 0);

// Low stock items
$lowStockItems = fetchAllSafe($pdo, "
    SELECT i.item_code, i.item_name, i.unit, i.min_stock, c.name AS category_name,
           COALESCE(SUM(CASE WHEN t.type='import' THEN t.qty ELSE -t.qty END),0) AS stock
    FROM wa_items i
    LEFT JOIN wa_categories c ON c.id = i.category_id
    LEFT JOIN wa_transactions t ON t.item_id = i.id
    GROUP BY i.id
    HAVING stock < i.min_stock
    ORDER BY (stock - i.min_stock) ASC
    LIMIT 10
");

// Chart data (6 months)
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym    = date('Y-m', strtotime("-$i months"));
    $label = date('m/Y', strtotime("-$i months"));
    $imp   = (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty),0) FROM wa_transactions WHERE type='import' AND DATE_FORMAT(transacted_at,'%Y-%m')=?", [$ym], 0);
    $exp   = (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty),0) FROM wa_transactions WHERE type='export' AND DATE_FORMAT(transacted_at,'%Y-%m')=?", [$ym], 0);
    $chartData[] = ['label' => $label, 'import' => $imp, 'export' => $exp];
}

// Top 5 exports this month
$topExport = fetchAllSafe($pdo, "
    SELECT i.item_code, i.item_name, i.unit, SUM(t.qty) AS total_qty
    FROM wa_transactions t
    JOIN wa_items i ON i.id = t.item_id
    WHERE t.type='export' AND DATE_FORMAT(t.transacted_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
    GROUP BY t.item_id
    ORDER BY total_qty DESC
    LIMIT 5
");

// All stock (top 20 by stock asc)
$stocks = fetchAllSafe($pdo, "SELECT i.item_code, i.item_name, i.unit, i.min_stock, c.name AS category_name,
                         COALESCE(SUM(CASE WHEN t.type='import' THEN t.qty ELSE -t.qty END),0) AS stock
                         FROM wa_items i
                         LEFT JOIN wa_categories c ON c.id = i.category_id
                         LEFT JOIN wa_transactions t ON t.item_id = i.id
                         GROUP BY i.id
                         ORDER BY stock ASC
                         LIMIT 20");

// Recent 10 transactions
$recentTxns = fetchAllSafe($pdo, "
    SELECT t.transacted_at, t.type, t.qty, t.ref_no, t.note,
           i.item_code, i.item_name, i.unit,
           u.full_name
    FROM wa_transactions t
    JOIN wa_items i ON i.id = t.item_id
    LEFT JOIN users u ON u.id = t.transacted_by
    ORDER BY t.id DESC
    LIMIT 10
");

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">

    <!-- Page title -->
    <div class="mb-4">
        <h4 class="mb-1"><i class="fas fa-warehouse me-2 text-primary"></i>Tổng quan kho vật tư</h4>
        <p class="text-muted mb-0">Theo dõi tồn kho và nhập/xuất vật tư hành chính.</p>
    </div>

    <!-- 6 KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Tổng vật tư</div>
                    <div class="fs-4 fw-bold text-primary"><?= e((string)$totalItems) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Tổng nhóm</div>
                    <div class="fs-4 fw-bold text-info"><?= e((string)$totalCategories) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Vật tư thiếu hàng</div>
                    <div class="fs-4 fw-bold text-danger"><?= e((string)$lowStock) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Giao dịch tháng này</div>
                    <div class="fs-4 fw-bold text-secondary"><?= e((string)$txnMonth) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Nhập tháng này</div>
                    <div class="fs-4 fw-bold text-success"><?= e(number_format($importMonth, 2, ',', '.')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Xuất tháng này</div>
                    <div class="fs-4 fw-bold text-warning"><?= e(number_format($exportMonth, 2, ',', '.')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcut links -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="/erp/modules/warehouse_admin/categories.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-tags me-1"></i>Quản lý nhóm</a>
        <a href="/erp/modules/warehouse_admin/items.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-list-alt me-1"></i>Danh mục vật tư</a>
        <a href="/erp/modules/warehouse_admin/transactions.php" class="btn btn-success btn-sm"><i class="fas fa-arrow-down me-1"></i>Nhập kho</a>
        <a href="/erp/modules/warehouse_admin/transactions.php" class="btn btn-warning btn-sm"><i class="fas fa-arrow-up me-1"></i>Xuất kho</a>
    </div>

    <!-- Low stock warning -->
    <?php if ($lowStock > 0): ?>
    <div class="card border-danger border-2 shadow-sm mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo vật tư thiếu hàng</h6>
            <span class="badge bg-white text-danger"><?= e((string)$lowStock) ?> vật tư</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Mã VT</th>
                        <th>Tên vật tư</th>
                        <th>Nhóm</th>
                        <th>ĐVT</th>
                        <th class="text-end">Tồn hiện tại</th>
                        <th class="text-end">Tồn tối thiểu</th>
                        <th class="text-end">Thiếu</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lowStockItems as $ls):
                    $deficit = (float)$ls['stock'] - (float)$ls['min_stock'];
                ?>
                    <tr>
                        <td><?= e($ls['item_code']) ?></td>
                        <td><?= e($ls['item_name']) ?></td>
                        <td><?= e($ls['category_name']) ?></td>
                        <td><?= e($ls['unit']) ?></td>
                        <td class="text-end"><?= e(number_format((float)$ls['stock'], 2, ',', '.')) ?></td>
                        <td class="text-end"><?= e(number_format((float)$ls['min_stock'], 2, ',', '.')) ?></td>
                        <td class="text-end text-danger fw-bold"><?= e(number_format($deficit, 2, ',', '.')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i>Tất cả vật tư đang ở mức tồn kho đủ.</div>
    <?php endif; ?>

    <!-- Chart + Top 5 exports -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Nhập/Xuất 6 tháng gần nhất</h6>
                </div>
                <div class="card-body">
                    <canvas id="txnChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top 5 xuất nhiều nhất tháng này</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã VT</th>
                                <th>Tên vật tư</th>
                                <th class="text-end">SL</th>
                                <th>ĐVT</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$topExport): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                        <?php endif; ?>
                        <?php foreach ($topExport as $te): ?>
                            <tr>
                                <td><?= e($te['item_code']) ?></td>
                                <td><?= e($te['item_name']) ?></td>
                                <td class="text-end"><?= e(number_format((float)$te['total_qty'], 2, ',', '.')) ?></td>
                                <td><?= e($te['unit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Tồn kho tất cả vật tư</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Mã vật tư</th>
                        <th>Tên</th>
                        <th>Nhóm</th>
                        <th>ĐVT</th>
                        <th class="text-end">Tồn hiện tại</th>
                        <th class="text-end">Tồn tối thiểu</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$stocks): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr>
                <?php endif; ?>
                <?php foreach ($stocks as $s):
                    $isLow = (float)$s['stock'] < (float)$s['min_stock'];
                ?>
                    <tr class="<?= $isLow ? 'table-danger' : '' ?>">
                        <td><?= e($s['item_code']) ?></td>
                        <td><?= e($s['item_name']) ?></td>
                        <td><?= e($s['category_name']) ?></td>
                        <td><?= e($s['unit']) ?></td>
                        <td class="text-end"><?= e(number_format((float)$s['stock'], 2, ',', '.')) ?></td>
                        <td class="text-end"><?= e(number_format((float)$s['min_stock'], 2, ',', '.')) ?></td>
                        <td><span class="badge bg-<?= $isLow ? 'danger' : 'success' ?>"><?= $isLow ? 'Thiếu' : 'Đủ' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent 10 transactions -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Lịch sử 10 giao dịch gần nhất</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Ngày</th>
                        <th>Mã VT</th>
                        <th>Tên vật tư</th>
                        <th>Loại</th>
                        <th class="text-end">Số lượng</th>
                        <th>Số chứng từ</th>
                        <th>Người thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$recentTxns): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có giao dịch</td></tr>
                <?php endif; ?>
                <?php foreach ($recentTxns as $tx): ?>
                    <tr>
                        <td><?= e(formatDate($tx['transacted_at'], 'd/m/Y')) ?></td>
                        <td><?= e($tx['item_code']) ?></td>
                        <td><?= e($tx['item_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $tx['type'] === 'import' ? 'success' : 'warning' ?>">
                                <?= $tx['type'] === 'import' ? 'Nhập' : 'Xuất' ?>
                            </span>
                        </td>
                        <td class="text-end"><?= e(number_format((float)$tx['qty'], 2, ',', '.')) ?></td>
                        <td><?= e($tx['ref_no'] ?? '') ?></td>
                        <td><?= e($tx['full_name'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('load', function () {
    var chartData = <?= json_encode($chartData) ?>;
    var labels  = chartData.map(function(d) { return d.label; });
    var imports = chartData.map(function(d) { return d.import; });
    var exports = chartData.map(function(d) { return d.export; });
    var ctx = document.getElementById('txnChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Nhập kho',
                    data: imports,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Xuất kho',
                    data: exports,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
