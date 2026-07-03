<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant');

$pdo  = getDBConnection();
$user = currentUser();

$filterFrom   = $_GET['from']   ?? date('Y-m-01');
$filterTo     = $_GET['to']     ?? date('Y-m-d');
$filterCust   = trim($_GET['cust']   ?? '');
$filterStatus = $_GET['status'] ?? '';

// Khi lọc theo trạng thái nháp (draft), bỏ điều kiện ngày vì invoice_date = NULL
$where  = [];
$params = [];
if ($filterStatus !== 'draft') {
    $where[]  = 'i.invoice_date BETWEEN ? AND ?';
    $params[] = $filterFrom;
    $params[] = $filterTo;
}
if ($filterCust) { $where[] = 'c.customer_name LIKE ?'; $params[] = "%$filterCust%"; }
if ($filterStatus) { $where[] = 'i.status = ?'; $params[] = $filterStatus; }
if (empty($where)) { $where[] = '1=1'; }

$invoices = $pdo->prepare("
    SELECT i.*,
           i.bkav_invoice_no,
           i.bkav_status,
           i.is_locked, i.locked_bkav_no, i.locked_bkav_date,
           c.customer_name, c.customer_code,
           u.full_name AS created_by_name,
           COALESCE(SUM(p.amount),0) AS paid_amount
    FROM invoices i
    LEFT JOIN customers c  ON i.customer_id  = c.id
    LEFT JOIN users u      ON i.created_by   = u.id
    LEFT JOIN payments p   ON p.invoice_id   = i.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY i.id
    ORDER BY i.invoice_date DESC, i.id DESC
");
$invoices->execute($params);
$invoices = $invoices->fetchAll(PDO::FETCH_ASSOC);

$totalAmount = array_sum(array_column($invoices, 'total_amount'));
$totalPaid   = array_sum(array_column($invoices, 'paid_amount'));
$totalDebt   = $totalAmount - $totalPaid;

$customers = $pdo->query("
    SELECT id, customer_name, customer_code, COALESCE(vat_rate, 8) AS vat_rate
    FROM customers
    WHERE is_active = 1
    ORDER BY customer_name
")->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();

$draftCount = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'draft'")->fetchColumn();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-file-invoice me-2 text-primary"></i>Hoá đơn
                <?php if ($draftCount > 0): ?>
                <span class="badge bg-warning text-dark ms-2"><?= $draftCount ?> chờ xuất</span>
                <?php endif; ?>
            </h4>
            <p class="text-muted mb-0">Quản lý hoá đơn bán hàng</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInvoice">
            <i class="fas fa-plus me-1"></i> Tạo hoá đơn
        </button>
    </div>

    <?php showFlash(); ?>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-5 fw-bold text-primary"><?= number_format($totalAmount) ?> đ</div>
                <div class="text-muted small">Tổng hoá đơn</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-5 fw-bold text-success"><?= number_format($totalPaid) ?> đ</div>
                <div class="text-muted small">Đã thu</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-5 fw-bold text-danger"><?= number_format($totalDebt) ?> đ</div>
                <div class="text-muted small">Còn nợ</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-auto">
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= $filterFrom ?>">
                </div>
                <div class="col-auto"><span class="text-muted">→</span></div>
                <div class="col-auto">
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= $filterTo ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" name="cust" class="form-control form-control-sm"
                           placeholder="Khách hàng..." value="<?= htmlspecialchars($filterCust) ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Trạng thái --</option>
                        <option value="draft"    <?= $filterStatus==='draft'    ?'selected':'' ?>>Chờ xuất hoá đơn</option>
                        <option value="unpaid"   <?= $filterStatus==='unpaid'   ?'selected':'' ?>>Chưa thanh toán</option>
                        <option value="partial"  <?= $filterStatus==='partial'  ?'selected':'' ?>>Thanh toán 1 phần</option>
                        <option value="paid"     <?= $filterStatus==='paid'     ?'selected':'' ?>>Đã thanh toán</option>
                        <option value="cancelled"<?= $filterStatus==='cancelled'?'selected':'' ?>>Đã huỷ</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>Lọc
                    </button>
                    <a href="?" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng hoá đơn -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Số HĐ</th>
                            <th>Ngày HĐ</th>
                            <th>Khách hàng</th>
                            <th class="text-end">Tổng tiền</th>
                            <th class="text-end">Đã thu</th>
                            <th class="text-end">Còn nợ</th>
                            <th>Trạng thái</th>
                            <th class="text-center">BKAV</th>
                            <th>Hạn TT</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">Chưa có hoá đơn nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv):
                            $debt    = $inv['total_amount'] - $inv['paid_amount'];
                            $overdue = $inv['due_date'] && $inv['status'] !== 'paid' && $inv['status'] !== 'draft' && $inv['due_date'] < date('Y-m-d');
                        ?>
                        <tr class="<?= $overdue ? 'table-danger' : '' ?>"
                            style="cursor:pointer"
                            onclick="window.location='invoice_detail.php?id=<?= (int)$inv['id'] ?>'">
                            <td class="fw-semibold text-primary">
                                <?= htmlspecialchars($inv['invoice_no']) ?>
                                <?php if ($overdue): ?>
                                    <span class="badge bg-danger ms-1">Quá hạn</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $inv['invoice_date'] ? date('d/m/Y', strtotime($inv['invoice_date'])) : '<span class="text-muted">—</span>' ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($inv['customer_name'] ?? '—') ?></td>
                            <td class="text-end"><?= number_format($inv['total_amount']) ?> đ</td>
                            <td class="text-end text-success"><?= number_format($inv['paid_amount']) ?> đ</td>
                            <td class="text-end fw-bold <?= $debt > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($debt) ?> đ
                            </td>
                            <td>
                                <?php
                                $st = [
                                    'draft'     => ['secondary', 'Chờ xuất'],
                                    'unpaid'    => ['danger',    'Chưa TT'],
                                    'partial'   => ['warning',   '1 phần'],
                                    'paid'      => ['success',   'Đã TT'],
                                    'cancelled' => ['secondary', 'Huỷ'],
                                ];
                                $s = $st[$inv['status']] ?? ['secondary','?'];
                                echo "<span class='badge bg-{$s[0]}'>{$s[1]}</span>";
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $bkavIssued = !empty($inv['bkav_invoice_no']) || ($inv['bkav_status'] ?? '') === 'issued';
                                if ($bkavIssued): ?>
                                    <i class="fas fa-check-circle text-success"></i>
                                    <?php if (!empty($inv['bkav_invoice_no'])): ?>
                                    <small class="d-block text-success" style="font-size:9px;"><?= htmlspecialchars($inv['bkav_invoice_no']) ?></small>
                                    <?php else: ?>
                                    <small class="d-block text-warning" style="font-size:9px;">Chờ số</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-file-invoice-dollar text-muted"></i>
                                    <small class="d-block text-muted" style="font-size:9px;">Chưa xuất</small>
                                <?php endif; ?>
                            </td>
                            <td class="<?= $overdue ? 'text-danger fw-bold' : 'text-muted' ?> small">
                                <?= $inv['due_date'] ? date('d/m/Y', strtotime($inv['due_date'])) : '—' ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-warning btn-vat"
                                        data-id="<?= $inv['id'] ?>"
                                        data-no="<?= htmlspecialchars($inv['invoice_no']) ?>"
                                        data-bkav="<?= htmlspecialchars($inv['bkav_invoice_no'] ?? '') ?>"
                                        title="Xuất hoá đơn VAT BKAV"
                                        onclick="event.stopPropagation()">
                                    <?php if ($bkavIssued): ?>
                                        <i class="fas fa-check-circle text-success"></i>
                                        <?php if (!empty($inv['bkav_invoice_no'])): ?>
                                        <small class="d-block text-success" style="font-size:9px;"><?= htmlspecialchars($inv['bkav_invoice_no']) ?></small>
                                        <?php else: ?>
                                        <small class="d-block text-warning" style="font-size:9px;">Chờ số</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="fas fa-file-invoice-dollar text-muted"></i>
                                        <small class="d-block text-muted" style="font-size:9px;">Chưa xuất</small>
                                    <?php endif; ?>
                                </button>
                                <?php if ($inv['status'] !== 'paid' && $inv['status'] !== 'cancelled' && $inv['status'] !== 'draft'): ?>
                                <button class="btn btn-sm btn-outline-success btn-pay"
                                        data-id="<?= $inv['id'] ?>"
                                        data-no="<?= htmlspecialchars($inv['invoice_no']) ?>"
                                        data-debt="<?= $debt ?>"
                                        title="Ghi thu tiền"
                                        onclick="event.stopPropagation()">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                                <?php endif; ?>
                                <a href="/erp/api/invoice/print_invoice.php?id=<?= $inv['id'] ?>"
                                   target="_blank" class="btn btn-sm btn-outline-secondary"
                                   title="In hoá đơn"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if (empty($inv['is_locked'])): ?>
                                <button class="btn btn-sm btn-outline-warning btn-lock"
                                        data-id="<?= $inv['id'] ?>"
                                        data-no="<?= htmlspecialchars($inv['invoice_no']) ?>"
                                        data-bkav="<?= htmlspecialchars($inv['bkav_invoice_no'] ?? '') ?>"
                                        title="Khoá hoá đơn (nhập số HĐ BKAV)"
                                        onclick="event.stopPropagation()">
                                    <i class="fas fa-lock-open"></i>
                                </button>
                                <?php else: ?>
                                <span class="badge bg-danger ms-1"
                                      title="Đã khoá — Số BKAV: <?= htmlspecialchars($inv['locked_bkav_no'] ?? '') ?> | Ngày: <?= $inv['locked_bkav_date'] ? date('d/m/Y', strtotime($inv['locked_bkav_date'])) : '' ?>"
                                      style="cursor:help;">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            Tổng: <strong><?= count($invoices) ?></strong> hoá đơn
        </div>
    </div>
</div>
</div>

<!-- ============ MODAL TẠO HOÁ ĐƠN ============ -->
<div class="modal fade" id="modalInvoice" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice me-2"></i>Tạo hoá đơn
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- BƯỚC 1: Chọn điều kiện -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Khách hàng <span class="text-danger">*</span></label>
                                <select id="invCustomerSelect" class="form-select" required>
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            data-vat="<?= (int)($c['vat_rate'] ?? 8) ?>"
                                            data-name="<?= htmlspecialchars($c['customer_name']) ?>">
                                        [<?= htmlspecialchars($c['customer_code']) ?>]
                                        <?= htmlspecialchars($c['customer_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Từ ngày <span class="text-danger">*</span></label>
                                <input type="date" id="invFromDate" class="form-control"
                                       value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Đến ngày <span class="text-danger">*</span></label>
                                <input type="date" id="invToDate" class="form-control"
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Thuế VAT (%)</label>
                                <input type="number" id="invVat" class="form-control" value="8" min="0" max="100">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" id="btnLoadInvoiceData">
                                    <i class="fas fa-search me-1"></i>Tải dữ liệu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BƯỚC 2: Kết quả preview -->
                <div id="invPreviewArea" class="d-none">
                    <!-- Thông tin HĐ -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ngày HĐ <span class="text-danger">*</span></label>
                            <input type="date" id="invInvoiceDate" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hạn thanh toán</label>
                            <input type="date" id="invDueDate" class="form-control"
                                   value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ghi chú</label>
                            <input type="text" id="invNote" class="form-control" placeholder="Ghi chú hoá đơn...">
                        </div>
                    </div>

                    <!-- Thông tin biên bản -->
                    <div id="invDeliveryInfo" class="alert alert-info py-2 small mb-3"></div>

                    <!-- Bảng SP tổng hợp -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-2" id="invPreviewTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Mô tả</th>
                                    <th class="text-center">ĐVT</th>
                                    <th class="text-end">Tổng SL</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="invPreviewBody"></tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">Tạm tính:</td>
                                    <td class="fw-bold text-end" id="invSubtotal">0 đ</td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">
                                        VAT (<span id="vatPct">0</span>%):
                                    </td>
                                    <td class="fw-bold text-end text-warning" id="invVatAmount">0 đ</td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold fs-6">Tổng cộng:</td>
                                    <td class="fw-bold text-end text-success fs-6" id="invGrandTotal">0 đ</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Cảnh báo SP chưa có báo giá -->
                    <div id="invNoPriceWarning" class="d-none alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <span id="invNoPriceList"></span>
                    </div>
                    <div class="d-flex gap-2 mt-2 mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnExportBke">
                            <i class="fas fa-file-excel me-1"></i>Xuất bảng kê Excel (gửi KH)
                        </button>
                    </div>
                </div>

                <!-- Trạng thái loading / empty -->
                <div id="invLoadingMsg" class="text-center py-4 text-muted d-none">
                    <i class="fas fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...
                </div>
                <div id="invEmptyMsg" class="text-center py-4 text-muted d-none">
                    <i class="fas fa-inbox me-2"></i>Không có biên bản giao hàng nào trong khoảng thời gian này.
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary d-none" id="btnSaveInvoice">
                    <i class="fas fa-save me-1"></i>Xác nhận tạo hoá đơn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL THU TIỀN ============ -->
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave me-2"></i>Ghi nhận thanh toán
                </h5>
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
                        <input type="date" name="payment_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền thu <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="payAmount"
                               class="form-control" placeholder="0" min="1" required>
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
                <button type="button" class="btn btn-success" id="btnSavePay">
                    <i class="fas fa-save me-1"></i>Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL VAT BKAV ============ -->
<div class="modal fade" id="modalVAT" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Preview xuất hoá đơn VAT BKAV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin HĐ nội bộ -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <span class="text-muted small">Số HĐ nội bộ:</span>
                        <strong id="vatInvoiceNo" class="ms-1"></strong>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small">Ngày HĐ:</span>
                        <strong id="vatInvoiceDate" class="ms-1"></strong>
                    </div>
                </div>
                <!-- Seller -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body py-2">
                        <div class="fw-bold small text-uppercase text-muted mb-1">Người bán</div>
                        <div><strong id="vatSellerName"></strong></div>
                        <div class="small text-muted">MST: <span id="vatSellerTax"></span></div>
                        <div class="small text-muted"><span id="vatSellerAddress"></span></div>
                    </div>
                </div>
                <!-- Buyer -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body py-2">
                        <div class="fw-bold small text-uppercase text-muted mb-1">Người mua</div>
                        <div><strong id="vatBuyerName"></strong> – <span id="vatBuyerUnit"></span></div>
                        <div class="small text-muted"><span id="vatBuyerAddress"></span></div>
                        <div class="small text-muted">MST: <span id="vatBuyerTax"></span></div>
                        <div class="small text-muted">TK: <span id="vatBuyerBank"></span></div>
                    </div>
                </div>
                <!-- Items -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tên hàng hoá / dịch vụ</th>
                                <th class="text-center">ĐVT</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="vatItemsBody"></tbody>
                    </table>
                </div>
                <!-- Tổng -->
                <div class="d-flex flex-column align-items-end gap-1 mb-2">
                    <div class="small">Tạm tính: <strong id="vatSubtotal"></strong></div>
                    <div class="small">VAT (<span id="vatVatRate"></span>): <strong id="vatVatAmount"></strong></div>
                    <div class="fs-6 fw-bold text-success">Tổng cộng: <span id="vatTotal"></span></div>
                </div>
                <div class="alert alert-light border py-2 small">
                    <i class="fas fa-info-circle me-1 text-muted"></i>
                    Bằng chữ: <em id="vatWords"></em>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Đóng
                </button>
                <button type="button" class="btn btn-warning" id="btnConfirmVAT"
                        onclick="pushToBKAV(this.dataset.invId)">
                    <i class="fas fa-paper-plane me-1"></i>Xác nhận xuất BKAV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL KHOÁ HOÁ ĐƠN ============ -->
<div class="modal fade" id="modalLock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-lock me-2"></i>Khoá hoá đơn
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Sau khi khoá, hoá đơn <strong>chỉ giám đốc mới được sửa</strong>.
                    Thông tin này sẽ được dùng để chuyển sang công nợ.
                </div>
                <input type="hidden" id="lockInvoiceId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số hoá đơn nội bộ</label>
                    <input type="text" id="lockInvoiceNo" class="form-control bg-light" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số hoá đơn BKAV <span class="text-danger">*</span></label>
                    <input type="text" id="lockBkavNo" class="form-control" placeholder="Nhập số HĐ do BKAV cấp">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ngày hoá đơn BKAV <span class="text-danger">*</span></label>
                    <input type="date" id="lockBkavDate" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-danger" id="btnConfirmLock">
                    <i class="fas fa-lock me-1"></i>Xác nhận khoá
                </button>
            </div>
        </div>
    </div>
</div>


<script>
const CSRF_TOKEN = <?= json_encode($csrf) ?>;

// ── Biến lưu dữ liệu đã load ──
let loadedItems      = [];   // [{product_code_id, product_code, description, unit, quantity, unit_price, total_price}]
let loadedDeliveries = [];   // danh sách biên bản
let loadedCustomerId = null;

document.getElementById('invCustomerSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('invVat').value = opt?.dataset.vat ?? '8';
    updateTotals();
});

// ── Reset modal khi mở lại ──
document.getElementById('modalInvoice').addEventListener('show.bs.modal', () => {
    document.getElementById('invPreviewArea').classList.add('d-none');
    document.getElementById('invLoadingMsg').classList.add('d-none');
    document.getElementById('invEmptyMsg').classList.add('d-none');
    document.getElementById('btnSaveInvoice').classList.add('d-none');
    document.getElementById('invPreviewBody').innerHTML = '';
    document.getElementById('invDeliveryInfo').textContent = '';
    document.getElementById('invNoPriceWarning').classList.add('d-none');
    loadedItems = [];
    loadedDeliveries = [];
    loadedCustomerId = null;
});

// ── Tải dữ liệu khi nhấn nút ──
document.getElementById('btnLoadInvoiceData').addEventListener('click', () => {
    const customerId = document.getElementById('invCustomerSelect').value;
    const fromDate   = document.getElementById('invFromDate').value;
    const toDate     = document.getElementById('invToDate').value;

    if (!customerId) { alert('Vui lòng chọn khách hàng!'); return; }
    if (!fromDate || !toDate) { alert('Vui lòng chọn khoảng thời gian!'); return; }
    if (fromDate > toDate) { alert('Từ ngày không được lớn hơn đến ngày!'); return; }

    document.getElementById('invPreviewArea').classList.add('d-none');
    document.getElementById('invEmptyMsg').classList.add('d-none');
    document.getElementById('btnSaveInvoice').classList.add('d-none');
    document.getElementById('invLoadingMsg').classList.remove('d-none');

    fetch(`/erp/api/invoice/get_deliveries_by_customer.php?customer_id=${customerId}&from=${fromDate}&to=${toDate}`)
        .then(r => r.json())
        .then(res => {
            document.getElementById('invLoadingMsg').classList.add('d-none');

            if (!res.ok) { alert('Lỗi: ' + res.msg); return; }

            if (!res.deliveries || res.deliveries.length === 0) {
                document.getElementById('invEmptyMsg').classList.remove('d-none');
                return;
            }

            loadedDeliveries = res.deliveries;
            loadedCustomerId = customerId;

            // Tổng hợp items: gộp theo product_code_id, cộng dồn SL
            const merged = {};
            const noPriceList = [];
            (res.items_by_delivery ? Object.values(res.items_by_delivery).flat() : []).forEach(item => {
                const key = item.product_code_id;
                if (!merged[key]) {
                    merged[key] = {
                        product_code_id : item.product_code_id,
                        product_code    : item.product_code,
                        description     : item.description,
                        unit            : item.unit,
                        quantity        : 0,
                        unit_price      : parseFloat(item.unit_price) || 0,
                        total_price     : 0,
                    };
                    if (!parseFloat(item.unit_price)) noPriceList.push(item.product_code);
                }
                merged[key].quantity   += parseFloat(item.quantity) || 0;
                merged[key].total_price = Math.round(merged[key].quantity * merged[key].unit_price);
            });

            loadedItems = Object.values(merged);

            // Hiển thị bảng preview
            renderPreview(loadedItems, loadedDeliveries, noPriceList);
        })
        .catch(() => {
            document.getElementById('invLoadingMsg').classList.add('d-none');
            alert('Lỗi kết nối, vui lòng thử lại.');
        });
});

function renderPreview(items, deliveries, noPriceList) {
    // Thông tin biên bản
    const dlNos = deliveries.map(d =>
        `<strong>${esc(d.delivery_no)}</strong> (${fmtDate(d.delivery_date)})`
    ).join(', ');
    document.getElementById('invDeliveryInfo').innerHTML =
        `<i class="fas fa-truck me-1"></i> Tổng hợp từ <strong>${deliveries.length}</strong> biên bản giao hàng: ${dlNos}`;

    // Bảng SP
    const tbody = document.getElementById('invPreviewBody');
    tbody.innerHTML = '';

    let subtotal = 0;
    items.forEach(item => {
        subtotal += item.total_price;
        const priceClass = item.unit_price > 0 ? '' : 'text-danger';
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td><span class="badge bg-primary">${esc(item.product_code)}</span></td>
                <td>${esc(item.description)}</td>
                <td class="text-center">${esc(item.unit)}</td>
                <td class="text-end fw-semibold">${fmtNum(item.quantity)}</td>
                <td class="text-end ${priceClass}">
                    <input type="number" class="form-control form-control-sm text-end inv-unit-price"
                           style="width:130px;display:inline-block"
                           min="0" step="1"
                           value="${item.unit_price}"
                           data-idx="${items.indexOf(item)}">
                </td>
                <td class="text-end fw-bold inv-row-total">${fmtMoney(item.total_price)}</td>
            </tr>
        `);
    });

    updateTotals();

    // Cảnh báo SP chưa có báo giá
    if (noPriceList.length > 0) {
        document.getElementById('invNoPriceWarning').classList.remove('d-none');
        document.getElementById('invNoPriceList').textContent =
            'Các mã SP chưa có báo giá (đơn giá = 0): ' + noPriceList.join(', ') +
            '. Vui lòng nhập đơn giá thủ công trước khi tạo HĐ.';
    } else {
        document.getElementById('invNoPriceWarning').classList.add('d-none');
    }

    document.getElementById('invPreviewArea').classList.remove('d-none');
    document.getElementById('btnSaveInvoice').classList.remove('d-none');

    // Sự kiện thay đổi đơn giá
    document.querySelectorAll('.inv-unit-price').forEach(input => {
        input.addEventListener('input', () => {
            const idx = parseInt(input.dataset.idx);
            const qty = loadedItems[idx].quantity;
            const price = parseFloat(input.value) || 0;
            loadedItems[idx].unit_price  = price;
            loadedItems[idx].total_price = Math.round(qty * price);
            input.closest('tr').querySelector('.inv-row-total').textContent = fmtMoney(loadedItems[idx].total_price);
            updateTotals();
        });
    });
}

function updateTotals() {
    const vatRate = (parseFloat(document.getElementById('invVat').value) || 0) / 100;
    let subtotal = 0;
    loadedItems.forEach(item => subtotal += item.total_price);
    const vatAmt   = Math.round(subtotal * vatRate);
    const grandTotal = subtotal + vatAmt;

    document.getElementById('invSubtotal').textContent  = fmtMoney(subtotal);
    document.getElementById('vatPct').textContent        = document.getElementById('invVat').value || 0;
    document.getElementById('invVatAmount').textContent  = fmtMoney(vatAmt);
    document.getElementById('invGrandTotal').textContent = fmtMoney(grandTotal);
}

document.getElementById('invVat').addEventListener('input', updateTotals);

document.getElementById('btnExportBke')?.addEventListener('click', () => {
    const customerId = loadedCustomerId;
    const fromDate   = document.getElementById('invFromDate').value;
    const toDate     = document.getElementById('invToDate').value;
    if (!customerId || !fromDate || !toDate) { alert('Chưa có dữ liệu!'); return; }
    window.open(
        `/erp/api/invoice/export_bke_excel.php?customer_id=${encodeURIComponent(customerId)}&from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`,
        '_blank'
    );
});

// ── Lưu hoá đơn ──
document.getElementById('btnSaveInvoice').addEventListener('click', () => {
    if (!loadedCustomerId || loadedItems.length === 0) {
        alert('Không có dữ liệu để tạo hoá đơn!');
        return;
    }

    const invoiceDate = document.getElementById('invInvoiceDate').value;
    const dueDate     = document.getElementById('invDueDate').value;
    const note        = document.getElementById('invNote').value;
    const vatRate     = parseFloat(document.getElementById('invVat').value) || 0;

    if (!invoiceDate) { alert('Vui lòng nhập ngày hoá đơn!'); return; }

    // Kiểm tra còn SP đơn giá = 0
    const zeroPrices = loadedItems.filter(it => it.unit_price <= 0);
    if (zeroPrices.length > 0) {
        const codes = zeroPrices.map(it => it.product_code).join(', ');
        if (!confirm(`Các mã SP: ${codes} có đơn giá = 0. Vẫn tiếp tục tạo hoá đơn?`)) return;
    }

    const btn = document.getElementById('btnSaveInvoice');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('customer_id',   loadedCustomerId);
    fd.append('invoice_date',  invoiceDate);
    fd.append('due_date',      dueDate);
    fd.append('note',          note);
    fd.append('vat_rate',      vatRate);
    fd.append('delivery_ids',  JSON.stringify(loadedDeliveries.map(d => d.id)));
    loadedItems.forEach((item, i) => {
        fd.append(`items[${i}][product_code_id]`, item.product_code_id);
        fd.append(`items[${i}][description]`,     item.description);
        fd.append(`items[${i}][unit]`,            item.unit);
        fd.append(`items[${i}][quantity]`,        item.quantity);
        fd.append(`items[${i}][unit_price]`,      item.unit_price);
        fd.append(`items[${i}][total_price]`,     item.total_price);
    });

    fetch('/erp/api/invoice/save_invoice.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalInvoice')).hide();
                location.reload();
            } else {
                alert('Lỗi: ' + res.msg);
            }
        })
        .catch(() => alert('Lỗi kết nối!'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Xác nhận tạo hoá đơn';
        });
});

// ── Thu tiền ──
document.querySelectorAll('.btn-pay').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('payInvoiceId').value  = btn.dataset.id;
        document.getElementById('payInvoiceNo').value  = btn.dataset.no;
        document.getElementById('payAmount').value     = btn.dataset.debt;
        document.getElementById('payDebtInfo').textContent =
            'Còn nợ: ' + parseInt(btn.dataset.debt).toLocaleString('vi-VN') + ' đ';
        new bootstrap.Modal(document.getElementById('modalPay')).show();
    });
});

// ── Xuất VAT ──
document.querySelectorAll('.btn-vat').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const invId  = btn.dataset.id;
        const bkavNo = btn.dataset.bkav;
        if (bkavNo) {
            if (!confirm(`Hoá đơn này đã xuất BKAV: ${bkavNo}\nXuất lại?`)) return;
        }
        try {
            const res = await fetch(`/erp/api/invoice/push_invoice.php?preview=1&invoice_id=${invId}`).then(r => r.json());
            if (!res.success) { alert('Lỗi: ' + res.message); return; }
            renderVatPreview(res.preview, invId);
            new bootstrap.Modal(document.getElementById('modalVAT')).show();
        } catch (err) {
            alert('Lỗi kết nối khi tải preview.');
        }
    });
});

function renderVatPreview(p, invId) {
    document.getElementById('vatInvoiceNo').textContent     = p.invoice_no || '';
    document.getElementById('vatInvoiceDate').textContent   = fmtDate(p.invoice_date);
    document.getElementById('vatSellerName').textContent    = p.seller_name || '';
    document.getElementById('vatSellerTax').textContent     = p.seller_tax  || '';
    document.getElementById('vatSellerAddress').textContent = p.seller_address || '';
    document.getElementById('vatBuyerName').textContent     = p.contact_person || p.customer_name || '';
    document.getElementById('vatBuyerUnit').textContent     = p.customer_name  || '';
    document.getElementById('vatBuyerAddress').textContent  = p.address     || '';
    document.getElementById('vatBuyerTax').textContent      = p.tax_code    || '';
    document.getElementById('vatBuyerBank').textContent     = p.bank_account ? `${p.bank_account} – ${p.bank_name}` : '';

    let rowsHtml = '';
    (p.items || []).forEach((it, i) => {
        rowsHtml += `<tr>
            <td>${i + 1}</td>
            <td>${esc(it.description)}</td>
            <td class="text-center">${esc(it.unit)}</td>
            <td class="text-end">${fmtNum(it.quantity)}</td>
            <td class="text-end">${fmtMoney(it.unit_price)}</td>
            <td class="text-end fw-bold">${fmtMoney(it.total_price)}</td>
        </tr>`;
    });
    document.getElementById('vatItemsBody').innerHTML = rowsHtml;

    document.getElementById('vatSubtotal').textContent  = fmtMoney(p.subtotal);
    document.getElementById('vatVatRate').textContent   = (p.vat_rate || 0) + '%';
    document.getElementById('vatVatAmount').textContent = fmtMoney(p.vat_amount);
    document.getElementById('vatTotal').textContent     = fmtMoney(p.total_amount);
    document.getElementById('vatWords').textContent     = p.total_words || '';

    document.getElementById('btnConfirmVAT').dataset.invId = invId;
}

async function pushToBKAV(invId) {
    const btn = document.getElementById('btnConfirmVAT');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi BKAV...';
    try {
        const res = await fetch('/erp/api/invoice/push_invoice.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: parseInt(invId), csrf_token: CSRF_TOKEN })
        }).then(r => r.json());
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalVAT')).hide();
            alert('✅ Xuất hoá đơn thành công! Số HĐ BKAV: ' + res.invoiceNo);
            location.reload();
        } else {
            alert('❌ Lỗi: ' + res.message);
        }
    } catch (err) {
        alert('Lỗi kết nối khi gửi BKAV.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Xác nhận xuất BKAV';
    }
}

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
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu';
        });
});

// ── Helpers ──
function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}
function fmtNum(v) {
    return Number(v || 0).toLocaleString('vi-VN');
}
function fmtMoney(v) {
    return Number(v || 0).toLocaleString('vi-VN') + ' đ';
}
function fmtDate(d) {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}

// ── Khoá hoá đơn ──
document.querySelectorAll('.btn-lock').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('lockInvoiceId').value = btn.dataset.id;
        document.getElementById('lockInvoiceNo').value = btn.dataset.no;
        // Pre-fill số BKAV nếu đã có
        document.getElementById('lockBkavNo').value = btn.dataset.bkav || '';
        new bootstrap.Modal(document.getElementById('modalLock')).show();
    });
});

document.getElementById('btnConfirmLock')?.addEventListener('click', () => {
    const invoiceId = document.getElementById('lockInvoiceId').value;
    const bkavNo    = document.getElementById('lockBkavNo').value.trim();
    const bkavDate  = document.getElementById('lockBkavDate').value;
    if (!bkavNo)   { alert('Vui lòng nhập số hoá đơn BKAV!'); return; }
    if (!bkavDate) { alert('Vui lòng nhập ngày hoá đơn BKAV!'); return; }

    const btn = document.getElementById('btnConfirmLock');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang khoá...';

    fetch('/erp/api/invoice/lock_invoice.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            invoice_id: parseInt(invoiceId),
            bkav_no:    bkavNo,
            bkav_date:  bkavDate,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalLock')).hide();
            location.reload();
        } else {
            alert('Lỗi: ' + res.msg);
        }
    })
    .catch(() => alert('Lỗi kết nối!'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock me-1"></i>Xác nhận khoá';
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>