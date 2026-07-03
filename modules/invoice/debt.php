<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant');

$pdo = getDBConnection();
$csrf = generateCSRF();

$filterCustomer = (int)($_GET['customer_id'] ?? 0);
$filterStatus   = trim((string)($_GET['status'] ?? 'all'));
$showPaid       = isset($_GET['show_paid']) && $_GET['show_paid'] === '1';
$filterFrom     = trim((string)($_GET['from'] ?? date('Y-m-01')));
$filterTo       = trim((string)($_GET['to'] ?? date('Y-m-d')));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) $filterFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) $filterTo = date('Y-m-d');
if ($filterFrom > $filterTo) {
    [$filterFrom, $filterTo] = [$filterTo, $filterFrom];
}
if (!in_array($filterStatus, ['all', 'unpaid', 'partial'], true)) {
    $filterStatus = 'all';
}

$customers = $pdo->query("SELECT id, customer_name, customer_code FROM customers WHERE is_active = 1 ORDER BY customer_name")
    ->fetchAll(PDO::FETCH_ASSOC);

$where = [
    "i.status NOT IN ('draft', 'cancelled')",
    'i.invoice_date BETWEEN ? AND ?',
];
$params = [$filterFrom, $filterTo];

if ($filterCustomer > 0) {
    $where[] = 'i.customer_id = ?';
    $params[] = $filterCustomer;
}
if ($filterStatus === 'unpaid') {
    $where[] = "i.status = 'unpaid'";
} elseif ($filterStatus === 'partial') {
    $where[] = "i.status = 'partial'";
}

$sql = "
    SELECT i.id, i.invoice_no, i.invoice_date, i.due_date,
           i.total_amount, i.status,
           c.customer_name, c.customer_code,
           COALESCE(p.paid_amount, 0) AS paid_amount,
           (i.total_amount - COALESCE(p.paid_amount, 0)) AS remaining,
           CASE WHEN i.due_date IS NULL THEN 0 ELSE DATEDIFF(CURDATE(), i.due_date) END AS days_overdue
    FROM invoices i
    LEFT JOIN customers c ON c.id = i.customer_id
    LEFT JOIN (
        SELECT invoice_id, SUM(amount) AS paid_amount
        FROM payments
        GROUP BY invoice_id
    ) p ON p.invoice_id = i.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY days_overdue DESC, i.due_date ASC, i.invoice_date DESC, i.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$showPaid) {
    $rows = array_values(array_filter($rows, static fn($r) => (float)$r['remaining'] > 0.01));
}

$summary = [
    'total_debt' => 0,
    'bucket_0_30' => 0,
    'bucket_31_60' => 0,
    'bucket_60_plus' => 0,
];
$customerDebt = [];

foreach ($rows as $r) {
    $remaining = (float)$r['remaining'];
    if ($remaining <= 0.01) {
        continue;
    }
    $overdue = (int)$r['days_overdue'];
    $summary['total_debt'] += $remaining;

    if ($overdue >= 1 && $overdue <= 30) {
        $summary['bucket_0_30'] += $remaining;
    } elseif ($overdue >= 31 && $overdue <= 60) {
        $summary['bucket_31_60'] += $remaining;
    } elseif ($overdue > 60) {
        $summary['bucket_60_plus'] += $remaining;
    }

    $customerLabel = trim($r['customer_code'] ?? '');
    if (!isset($customerDebt[$customerLabel])) {
        $customerDebt[$customerLabel] = 0;
    }
    $customerDebt[$customerLabel] += $remaining;
}
arsort($customerDebt);

function debtRowClass(int $daysOverdue): string {
    if ($daysOverdue <= 0) return '';
    if ($daysOverdue <= 30) return 'bg-success bg-opacity-10';
    if ($daysOverdue <= 60) return 'bg-warning bg-opacity-25';
    return 'bg-danger bg-opacity-25';
}

function overdueBadge(int $daysOverdue): string {
    if ($daysOverdue <= 0) return '';
    if ($daysOverdue <= 30) return "<span class='badge bg-success'>Quá hạn {$daysOverdue} ngày</span>";
    if ($daysOverdue <= 60) return "<span class='badge bg-warning text-dark'>Quá hạn {$daysOverdue} ngày</span>";
    return "<span class='badge bg-danger'>Quá hạn {$daysOverdue} ngày ⚠️</span>";
}

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Công nợ phải thu</h4>
        <p class="text-muted mb-0">Theo dõi các hoá đơn chưa thanh toán đủ</p>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 small text-muted fw-semibold">Khách hàng</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="0">-- Tất cả khách hàng --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $filterCustomer === (int)$c['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($c['customer_code']) ?>] <?= htmlspecialchars($c['customer_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 small text-muted fw-semibold">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả</option>
                        <option value="unpaid" <?= $filterStatus === 'unpaid' ? 'selected' : '' ?>>Chưa TT</option>
                        <option value="partial" <?= $filterStatus === 'partial' ? 'selected' : '' ?>>Thanh toán 1 phần</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 small text-muted fw-semibold">Từ ngày</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filterFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 small text-muted fw-semibold">Đến ngày</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filterTo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 small text-muted fw-semibold d-block">Tùy chọn</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="showPaid" name="show_paid" <?= $showPaid ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="showPaid">Hiển thị đã TT</label>
                    </div>
                </div>
                <div class="col-md-1 d-grid pt-md-4">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="mb-1"><i class="fas fa-hand-holding-usd text-danger"></i></div>
                <div class="text-muted small">Tổng công nợ</div>
                <div class="fs-5 fw-bold text-danger"><?= number_format($summary['total_debt']) ?> đ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="mb-1"><i class="fas fa-clock text-warning"></i></div>
                <div class="text-muted small">Quá hạn 0-30 ngày</div>
                <div class="fs-5 fw-bold text-warning"><?= number_format($summary['bucket_0_30']) ?> đ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="mb-1"><i class="fas fa-exclamation-circle" style="color:#fd7e14"></i></div>
                <div class="text-muted small">Quá hạn 31-60 ngày</div>
                <div class="fs-5 fw-bold" style="color:#fd7e14"><?= number_format($summary['bucket_31_60']) ?> đ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="mb-1"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                <div class="text-muted small">Quá hạn &gt;60 ngày</div>
                <div class="fs-5 fw-bold text-danger"><?= number_format($summary['bucket_60_plus']) ?> đ</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Số HĐ</th>
                                    <th>Ngày HĐ</th>
                                    <th>Hạn TT</th>
                                    <th>Khách hàng</th>
                                    <th class="text-end">Tổng HĐ</th>
                                    <th class="text-end">Công nợ</th>
                                    <th>Số ngày quá hạn</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Không có công nợ phù hợp</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r):
                                    $remaining = (float)$r['remaining'];
                                    $daysOverdue = (int)$r['days_overdue'];
                                ?>
                                <tr class="<?= debtRowClass($daysOverdue) ?>"
                                    style="cursor:pointer"
                                    onclick="window.location='invoice_detail.php?id=<?= (int)$r['id'] ?>'">
                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($r['invoice_no']) ?></td>
                                    <td><?= $r['invoice_date'] ? date('d/m/Y', strtotime($r['invoice_date'])) : '—' ?></td>
                                    <td><?= $r['due_date'] ? date('d/m/Y', strtotime($r['due_date'])) : '—' ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($r['customer_name'] ?? '—') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['customer_code'] ?? '') ?></div>
                                    </td>
                                    <td class="text-end"><?= number_format($r['total_amount']) ?> đ</td>
                                    <td class="text-end">
                                        <div class="fw-bold <?= $remaining > 0.01 ? 'text-danger' : 'text-success' ?>">
                                            <?= number_format($remaining) ?> đ còn nợ
                                        </div>
                                        <div class="text-muted small">
                                            (đã thu: <?= number_format($r['paid_amount']) ?> đ / tổng: <?= number_format($r['total_amount']) ?> đ)
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($daysOverdue > 0): ?>
                                            <span class="fw-semibold text-danger"><?= $daysOverdue ?> ngày</span>
                                        <?php else: ?>
                                            <span class="text-muted small">Trong hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning btn-vat-placeholder" title="Xuất hóa đơn VAT (đang hoàn thiện)" onclick="event.stopPropagation()">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </button>
                                        <?php if ($remaining > 0.01): ?>
                                        <button class="btn btn-sm btn-outline-success btn-pay"
                                                data-id="<?= (int)$r['id'] ?>"
                                                data-no="<?= htmlspecialchars($r['invoice_no']) ?>"
                                                data-debt="<?= (float)$remaining ?>"
                                                title="Ghi thu"
                                                onclick="event.stopPropagation()">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                        <?php endif; ?>
                                        <a href="/erp/api/invoice/print_invoice.php?id=<?= (int)$r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="In HĐ" onclick="event.stopPropagation()">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>Tổng nợ theo khách hàng
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                            <?php if (empty($customerDebt)): ?>
                                <tr><td class="text-center text-muted py-3">Không có dữ liệu</td></tr>
                            <?php else: ?>
                                <?php foreach ($customerDebt as $customerLabel => $amount): ?>
                                <?php
                                    $percent = $summary['total_debt'] > 0 ? ($amount / $summary['total_debt']) * 100 : 0;
                                    $barClass = $percent >= 50 ? 'bg-danger' : ($percent >= 20 ? 'bg-warning' : 'bg-success');
                                ?>
                                <tr>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($customerLabel) ?></div>
                                        <div class="progress mt-1" style="height:4px">
                                            <div class="progress-bar <?= $barClass ?>" style="width:<?= min(100, round($percent, 2)) ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-end text-danger fw-semibold small">
                                        <?= number_format($amount) ?> đ<br>
                                        <span class="text-muted" style="font-size:11px"><?= round($percent, 1) ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Ghi nhận thanh toán</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPay">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="invoice_id" id="payInvoiceId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số hoá đơn</label>
                        <input type="text" id="payInvoiceNo" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày thu <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền thu <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="payAmount" class="form-control" min="1" required>
                        <div class="form-text text-danger" id="payDebtInfo"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hình thức</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Tiền mặt</option>
                            <option value="transfer">Chuyển khoản</option>
                            <option value="check">Séc</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Số chứng từ, ghi chú...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-success" id="btnSavePay"><i class="fas fa-save me-1"></i>Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    document.querySelectorAll('.btn-pay').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('payInvoiceId').value = btn.dataset.id;
            document.getElementById('payInvoiceNo').value = btn.dataset.no;
            document.getElementById('payAmount').value = btn.dataset.debt;
            document.getElementById('payDebtInfo').textContent = 'Còn nợ: ' + Number(btn.dataset.debt || 0).toLocaleString('vi-VN') + ' đ';
            new bootstrap.Modal(document.getElementById('modalPay')).show();
        });
    });

    document.querySelectorAll('.btn-vat-placeholder').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            alert('Tính năng xuất hóa đơn VAT đang được hoàn thiện. Vui lòng trở lại sau!');
        });
    });

    document.getElementById('btnSavePay').addEventListener('click', () => {
        const form = document.getElementById('formPay');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const btn = document.getElementById('btnSavePay');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

        fetch('/erp/api/invoice/save_payment.php', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalPay')).hide();
                    location.reload();
                } else {
                    alert('Lỗi: ' + res.msg);
                }
            })
            .catch(() => alert('Lỗi kết nối!'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu';
            });
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
