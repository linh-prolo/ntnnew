<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$stats = [
    'iqc_open' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM iqc_receipts WHERE status = 'open'", [], 0),
    'orders_running' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM production_orders WHERE status IN ('pending','in_progress')", [], 0),
    'oqc_waiting' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM production_items WHERE (qty_done > 0 OR qty_error > 0)", [], 0),
    'delivery_today' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM oqc_deliveries WHERE delivery_date = CURDATE()", [], 0),
];

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$dailyStats = [
    'iqc' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM iqc_receipts WHERE DATE(received_date) = CURDATE()", [], 0),
    'orders' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM production_orders WHERE DATE(created_at) = CURDATE()", [], 0),
    'done' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_done),0) FROM production_items WHERE DATE(updated_at) = CURDATE() AND qty_done > 0", [], 0),
    'error' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_error),0) FROM production_items WHERE DATE(updated_at) = CURDATE() AND qty_error > 0", [], 0),
    'delivery_slips' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM oqc_deliveries WHERE delivery_date = CURDATE()", [], 0),
    'delivery_qty' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(di.qty_deliver),0) FROM oqc_delivery_items di JOIN oqc_deliveries d ON d.id = di.delivery_id WHERE d.delivery_date = CURDATE()", [], 0),
    'pending' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(GREATEST(qty_total - qty_done - qty_error, 0)),0) FROM production_items WHERE DATE(updated_at) = CURDATE()", [], 0),
];

$weeklyStats = [
    'iqc' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM iqc_receipts WHERE YEARWEEK(received_date, 1) = YEARWEEK(CURDATE(), 1)", [], 0),
    'orders' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM production_orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)", [], 0),
    'done' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_done),0) FROM production_items WHERE YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1) AND qty_done > 0", [], 0),
    'error' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_error),0) FROM production_items WHERE YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1) AND qty_error > 0", [], 0),
    'delivery_slips' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM oqc_deliveries WHERE YEARWEEK(delivery_date, 1) = YEARWEEK(CURDATE(), 1)", [], 0),
    'delivery_qty' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(di.qty_deliver),0) FROM oqc_delivery_items di JOIN oqc_deliveries d ON d.id = di.delivery_id WHERE YEARWEEK(d.delivery_date, 1) = YEARWEEK(CURDATE(), 1)", [], 0),
];

$monthlyStats = [
    'iqc' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM iqc_receipts WHERE DATE(received_date) BETWEEN :monthStart AND :monthEnd", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
    'orders' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM production_orders WHERE DATE(created_at) BETWEEN :monthStart AND :monthEnd", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
    'done' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_done),0) FROM production_items WHERE DATE(updated_at) BETWEEN :monthStart AND :monthEnd AND qty_done > 0", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
    'error' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(qty_error),0) FROM production_items WHERE DATE(updated_at) BETWEEN :monthStart AND :monthEnd AND qty_error > 0", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
    'delivery_slips' => (int)fetchScalarSafe($pdo, "SELECT COUNT(*) FROM oqc_deliveries WHERE delivery_date BETWEEN :monthStart AND :monthEnd", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
    'delivery_qty' => (float)fetchScalarSafe($pdo, "SELECT COALESCE(SUM(di.qty_deliver),0) FROM oqc_delivery_items di JOIN oqc_deliveries d ON d.id = di.delivery_id WHERE d.delivery_date BETWEEN :monthStart AND :monthEnd", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd], 0),
];

$weekStartDate = (new DateTimeImmutable('today'))->modify('monday this week');
$weekStart = $weekStartDate->format('Y-m-d');
$weekEnd = $weekStartDate->modify('+6 days')->format('Y-m-d');
$monthLabel = date('m/Y');

$dailyProgressTotal = (float)$dailyStats['done'] + (float)$dailyStats['error'] + (float)$dailyStats['pending'];
$dailyProgressPercent = $dailyProgressTotal > 0 ? ((float)$dailyStats['done'] / $dailyProgressTotal) * 100 : 0;
$dailyProgressPercentText = number_format($dailyProgressPercent, 1, ',', '.') . '%';
$dailyProgressAriaText = number_format($dailyProgressPercent, 1, '.', '') . ' percent';

$delivery7DaysRaw = fetchAllSafe($pdo, "SELECT DATE(d.delivery_date) AS day,
                                        SUM(CASE WHEN di.type='done' THEN di.qty_deliver ELSE 0 END) AS done,
                                        SUM(CASE WHEN di.type='error' THEN di.qty_deliver ELSE 0 END) AS error_qty
                                        FROM oqc_deliveries d
                                        LEFT JOIN oqc_delivery_items di ON di.delivery_id = d.id
                                        WHERE d.delivery_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                        GROUP BY DATE(d.delivery_date)
                                        ORDER BY day ASC");
$delivery7DaysMap = [];
foreach ($delivery7DaysRaw as $row) {
    $dayKey = (string)($row['day'] ?? '');
    if ($dayKey === '') {
        continue;
    }
    $delivery7DaysMap[$dayKey] = [
        'done' => (float)($row['done'] ?? 0),
        'error' => (float)($row['error_qty'] ?? 0),
    ];
}

$delivery7Labels = [];
$delivery7Done = [];
$delivery7Error = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} day"));
    $delivery7Labels[] = date('d/m', strtotime($day));
    $delivery7Done[] = $delivery7DaysMap[$day]['done'] ?? 0;
    $delivery7Error[] = $delivery7DaysMap[$day]['error'] ?? 0;
}

$monthlyCustomerDistribution = fetchAllSafe($pdo, "SELECT COALESCE(NULLIF(TRIM(c.customer_name), ''), CONCAT('KH #', c.id)) AS customer_name_display,
                                                    COALESCE(SUM(di.qty_deliver),0) AS total_qty
                                                    FROM oqc_deliveries d
                                                    JOIN customers c ON c.id = d.customer_id
                                                    LEFT JOIN oqc_delivery_items di ON di.delivery_id = d.id
                                                    WHERE d.delivery_date BETWEEN :monthStart AND :monthEnd
                                                    GROUP BY c.id, COALESCE(NULLIF(TRIM(c.customer_name), ''), CONCAT('KH #', c.id))
                                                    ORDER BY total_qty DESC
                                                    LIMIT 6", ['monthStart' => $monthStart, 'monthEnd' => $monthEnd]);
$customerLabels = [];
$customerQty = [];
foreach ($monthlyCustomerDistribution as $row) {
    $customerLabels[] = (string)($row['customer_name_display'] ?? 'Khách hàng');
    $customerQty[] = (float)($row['total_qty'] ?? 0);
}

$recentIqc = fetchAllSafe($pdo, "SELECT r.id, r.receipt_no, r.received_date, c.customer_name, r.status,
                                 (SELECT COUNT(*) FROM iqc_receipt_items i WHERE i.receipt_id = r.id) AS item_count
                                 FROM iqc_receipts r
                                 LEFT JOIN customers c ON c.id = r.customer_id
                                 ORDER BY r.id DESC LIMIT 10");

$runningOrders = fetchAllSafe($pdo, "SELECT o.id, o.order_no, o.status, c.customer_name,
                                    SUM(pi.qty_total) AS qty_total,
                                    SUM(pi.qty_done) AS qty_done,
                                    SUM(pi.qty_error) AS qty_error
                                    FROM production_orders o
                                    JOIN iqc_receipts r ON r.id = o.iqc_receipt_id
                                    JOIN customers c ON c.id = r.customer_id
                                    LEFT JOIN production_items pi ON pi.order_id = o.id
                                    WHERE o.status = 'in_progress'
                                    GROUP BY o.id
                                    ORDER BY o.id DESC");

$statusMap = ['open' => 'warning', 'in_production' => 'info', 'done' => 'success'];
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-boxes me-2 text-primary"></i>Tổng quan kho sản xuất</h4>
                <p class="text-muted mb-0">Theo dõi IQC, lệnh sản xuất và OQC.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/erp/modules/warehouse/iqc_list.php" class="btn btn-primary"><i class="fas fa-file-import me-1"></i>Nhập IQC mới</a>
                <a href="/erp/modules/production/index.php" class="btn btn-outline-primary"><i class="fas fa-tasks me-1"></i>Xem lệnh SX</a>
                <a href="/erp/modules/warehouse/oqc_list.php" class="btn btn-outline-secondary"><i class="fas fa-boxes me-1"></i>Kho OQC</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">IQC đang mở</div><div class="fs-4 fw-bold text-warning"><?= e((string)$stats['iqc_open']) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Lệnh SX đang chạy</div><div class="fs-4 fw-bold text-info"><?= e((string)$stats['orders_running']) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Hàng chờ xuất OQC</div><div class="fs-4 fw-bold text-primary"><?= e((string)$stats['oqc_waiting']) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Phiếu xuất hôm nay</div><div class="fs-4 fw-bold text-success"><?= e((string)$stats['delivery_today']) ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary bg-opacity-10 border-start border-primary border-3">
                <h6 class="mb-1">Thống kê hôm nay</h6>
                <div class="small text-muted"><?= e(date('d/m/Y')) ?></div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">IQC nhập</div><div class="fs-5 fw-bold text-primary"><?= e((string)$dailyStats['iqc']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Lệnh SX tạo</div><div class="fs-5 fw-bold text-primary"><?= e((string)$dailyStats['orders']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL hoàn thành</div><div class="fs-5 fw-bold text-success"><?= e(number_format((float)$dailyStats['done'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL lỗi</div><div class="fs-5 fw-bold text-danger"><?= e(number_format((float)$dailyStats['error'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Phiếu xuất</div><div class="fs-5 fw-bold text-primary"><?= e((string)$dailyStats['delivery_slips']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Tổng SL giao</div><div class="fs-5 fw-bold text-primary"><?= e(number_format((float)$dailyStats['delivery_qty'], 2, ',', '.')) ?></div></div></div>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Tiến độ hôm nay: <?= e(number_format((float)$dailyStats['done'], 2, ',', '.')) ?> / <?= e(number_format($dailyProgressTotal, 2, ',', '.')) ?></span>
                    <span><?= e($dailyProgressPercentText) ?></span>
                </div>
                <div class="progress" role="progressbar" aria-label="Tiến độ hôm nay" aria-valuenow="<?= e((string)round($dailyProgressPercent)) ?>" aria-valuetext="<?= e($dailyProgressAriaText) ?>" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?= e((string)min(100, max(0, $dailyProgressPercent))) ?>%"></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success bg-opacity-10 border-start border-success border-3">
                <h6 class="mb-1">Thống kê tuần này</h6>
                <div class="small text-muted">Thứ 2 <?= e(date('d/m', strtotime((string)$weekStart))) ?> - CN <?= e(date('d/m', strtotime((string)$weekEnd))) ?></div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">IQC nhập</div><div class="fs-5 fw-bold text-primary"><?= e((string)$weeklyStats['iqc']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Lệnh SX tạo</div><div class="fs-5 fw-bold text-primary"><?= e((string)$weeklyStats['orders']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL hoàn thành</div><div class="fs-5 fw-bold text-success"><?= e(number_format((float)$weeklyStats['done'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL lỗi</div><div class="fs-5 fw-bold text-danger"><?= e(number_format((float)$weeklyStats['error'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Phiếu xuất</div><div class="fs-5 fw-bold text-primary"><?= e((string)$weeklyStats['delivery_slips']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Tổng SL giao</div><div class="fs-5 fw-bold text-primary"><?= e(number_format((float)$weeklyStats['delivery_qty'], 2, ',', '.')) ?></div></div></div>
                </div>
                <h6 class="mb-3">Số lượng giao 7 ngày gần đây</h6>
                <div style="height:280px;"><canvas id="weeklyDeliveryChart"></canvas></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning bg-opacity-10 border-start border-warning border-3">
                <h6 class="mb-1">Thống kê tháng này</h6>
                <div class="small text-muted">Tháng <?= e($monthLabel) ?></div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">IQC nhập</div><div class="fs-5 fw-bold text-primary"><?= e((string)$monthlyStats['iqc']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Lệnh SX tạo</div><div class="fs-5 fw-bold text-primary"><?= e((string)$monthlyStats['orders']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL hoàn thành</div><div class="fs-5 fw-bold text-success"><?= e(number_format((float)$monthlyStats['done'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">SL lỗi</div><div class="fs-5 fw-bold text-danger"><?= e(number_format((float)$monthlyStats['error'], 2, ',', '.')) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Phiếu xuất</div><div class="fs-5 fw-bold text-primary"><?= e((string)$monthlyStats['delivery_slips']) ?></div></div></div>
                    <div class="col-md-2 col-sm-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Tổng SL giao</div><div class="fs-5 fw-bold text-primary"><?= e(number_format((float)$monthlyStats['delivery_qty'], 2, ',', '.')) ?></div></div></div>
                </div>
                <h6 class="mb-3">Phân bổ SL giao theo khách hàng</h6>
                <div style="height:280px;"><canvas id="monthlyCustomerChart"></canvas></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h6 class="mb-0">Phiếu IQC gần đây</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark"><tr><th>Số phiếu</th><th>Ngày nhận</th><th>Khách hàng</th><th class="text-center">Mặt hàng</th><th>Trạng thái</th></tr></thead>
                            <tbody>
                            <?php if (!$recentIqc): ?><tr><td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr><?php endif; ?>
                            <?php foreach ($recentIqc as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($row['receipt_no']) ?></td>
                                    <td><?= e(formatDate($row['received_date'])) ?></td>
                                    <td><?= e($row['customer_name'] ?? '—') ?></td>
                                    <td class="text-center"><?= e((string)$row['item_count']) ?></td>
                                    <td><span class="badge bg-<?= e($statusMap[$row['status']] ?? 'secondary') ?>"><?= e($row['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h6 class="mb-0">Lệnh SX đang chạy</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark"><tr><th>Lệnh SX</th><th>Khách hàng</th><th class="text-end">Còn lại</th></tr></thead>
                            <tbody>
                            <?php if (!$runningOrders): ?><tr><td colspan="3" class="text-center text-muted py-4">Không có lệnh in_progress</td></tr><?php endif; ?>
                            <?php foreach ($runningOrders as $row): $pending = (float)$row['qty_total'] - (float)$row['qty_done'] - (float)$row['qty_error']; ?>
                                <tr>
                                    <td><a class="text-decoration-none" href="/erp/modules/production/detail.php?id=<?= (int)$row['id'] ?>"><?= e($row['order_no']) ?></a></td>
                                    <td><?= e($row['customer_name'] ?? '—') ?></td>
                                    <td class="text-end text-warning fw-semibold"><?= e(number_format(max($pending, 0), 2, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(() => {
    const weeklyCtx = document.getElementById('weeklyDeliveryChart');
    if (weeklyCtx) {
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($delivery7Labels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label: 'Thành phẩm',
                        data: <?= json_encode($delivery7Done) ?>,
                        backgroundColor: '#198754'
                    },
                    {
                        label: 'Lỗi',
                        data: <?= json_encode($delivery7Error) ?>,
                        backgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const monthlyCtx = document.getElementById('monthlyCustomerChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($customerLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($customerQty) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1', '#20c997']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
