<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? '')) ? (string)$_GET['month'] : '';
$customerId = (int)($_GET['customer_id'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));
$isDefaultView = $month === '' && $customerId === 0 && $status === '';

$where = ['1=1'];
$params = [];
if ($month !== '') { $where[] = "DATE_FORMAT(r.received_date, '%Y-%m') = ?"; $params[] = $month; }
if ($customerId > 0) { $where[] = 'r.customer_id = ?'; $params[] = $customerId; }
if (in_array($status, ['open','in_production','done'], true)) { $where[] = 'r.status = ?'; $params[] = $status; }
if ($isDefaultView) { $where[] = "(r.status != 'done' OR r.received_date = CURDATE())"; }

$customers = fetchAllSafe($pdo, 'SELECT id, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name');
$receivers = fetchAllSafe($pdo, 'SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name');

$receipts = fetchAllSafe($pdo, "SELECT r.id, r.receipt_no, r.received_date, r.status, r.note,
                               c.customer_name, u.full_name AS received_by_name,
                               COUNT(ri.id) AS item_count
                               FROM iqc_receipts r
                               LEFT JOIN customers c ON c.id = r.customer_id
                               LEFT JOIN users u ON u.id = r.received_by
                               LEFT JOIN iqc_receipt_items ri ON ri.receipt_id = r.id
                               WHERE " . implode(' AND ', $where) . "
                               GROUP BY r.id
                               ORDER BY r.id DESC", $params);

$statusMap = [
    'open'          => ['warning', 'Chờ sản xuất'],
    'in_production' => ['info',    'Đang sản xuất'],
    'done'          => ['success', 'Hoàn thành'],
];
$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-file-import me-2 text-primary"></i>Nhập kho IQC</h4>
            <p class="text-muted mb-0">Danh sách phiếu nhập IQC và tạo mới.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateIqc">
            <i class="fas fa-plus me-1"></i>Tạo phiếu IQC
        </button>
    </div>

    <?php showFlash(); ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
        <form class="row g-2 align-items-center" method="get">
            <div class="col-md-2">
                <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>">
            </div>
            <div class="col-md-3">
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">-- Khách hàng --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $customerId === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['customer_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Trạng thái --</option>
                    <?php foreach ($statusMap as $st => $info): ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e($info[1]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button>
            </div>
            <div class="col-auto">
                <a href="/erp/modules/warehouse/iqc_list.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div></div>

    <?php if ($isDefaultView): ?>
    <div class="alert alert-info py-2 small mb-3 border-0 bg-info bg-opacity-10">
        <i class="fas fa-eye-slash me-1"></i>
        Đang hiển thị phiếu <strong>chưa hoàn thành</strong> và hoàn thành <strong>hôm nay</strong>.
        Dùng bộ lọc bên trên để xem lịch sử.
    </div>
    <?php endif; ?>

    <!-- Danh sách -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Số phiếu</th>
                        <th>Ngày nhận</th>
                        <th>Khách hàng</th>
                        <th class="text-center">Số mặt hàng</th>
                        <th>Trạng thái</th>
                        <th>Người nhận</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$receipts): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có phiếu IQC</td></tr>
                <?php endif; ?>
                <?php foreach ($receipts as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($row['receipt_no']) ?></td>
                        <td><?= e(formatDate($row['received_date'])) ?></td>
                        <td><?= e($row['customer_name'] ?? '—') ?></td>
                        <td class="text-center"><?= e((string)$row['item_count']) ?></td>
                        <td>
                            <span class="badge bg-<?= e($statusMap[$row['status']][0] ?? 'secondary') ?>">
                                <?= e($statusMap[$row['status']][1] ?? $row['status']) ?>
                            </span>
                        </td>
                        <td><?= e($row['received_by_name'] ?? '—') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-view-items"
                                data-id="<?= (int)$row['id'] ?>"
                                data-no="<?= e($row['receipt_no']) ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div>

<!-- Modal tạo phiếu IQC -->
<div class="modal fade" id="modalCreateIqc" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Tạo phiếu nhập IQC</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCreateIqc">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Khách hàng <span class="text-danger">*</span></label>
                            <select class="form-select" name="customer_id" id="iqcCustomer" required>
                                <option value="">-- Chọn khách hàng --</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['customer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Ngày nhận <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Người nhận <span class="text-danger">*</span></label>
                            <select class="form-select" name="received_by" id="iqcReceivedBy" required>
                                <option value="0">-- Chọn người nhận --</option>
                                <?php foreach ($receivers as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Ghi chú</label>
                            <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú về lô hàng..."></textarea>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-list me-1 text-primary"></i>Danh sách hàng hoá</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRow">
                            <i class="fas fa-plus me-1"></i>Thêm dòng
                        </button>
                    </div>
                    <div id="noCustomerAlert" class="alert alert-warning py-2 small mb-2" style="display:none">
                        <i class="fas fa-exclamation-triangle me-1"></i>Vui lòng chọn khách hàng trước khi thêm hàng hoá.
                    </div>
                    <div id="noProductAlert" class="alert alert-info py-2 small mb-2" style="display:none">
                        <i class="fas fa-info-circle me-1"></i>Khách hàng này chưa có mã sản phẩm nào. Vui lòng vào <strong>Danh mục → Khách hàng</strong> để thêm mã SP.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle border" id="iqcItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:35%">Mã hàng</th>
                                    <th>Tên hàng</th>
                                    <th style="width:12%">SL <span class="text-danger">*</span></th>
                                    <th style="width:10%">ĐVT</th>
                                    <th>Ghi chú</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="iqcItemsBody">
                                <!-- Rows added by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button class="btn btn-primary" type="submit" id="btnSubmitIqc">
                        <i class="fas fa-save me-1"></i>Lưu phiếu IQC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết items -->
<div class="modal fade" id="modalIqcItems" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết phiếu <span id="iqcDetailNo" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã hàng</th>
                                <th>Tên hàng</th>
                                <th class="text-end">Số lượng</th>
                                <th>ĐVT</th>
                            </tr>
                        </thead>
                        <tbody id="iqcDetailBody">
                            <tr><td colspan="4" class="text-center text-muted">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const escHtml = (val) => String(val ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

// Cache sản phẩm theo khách hàng
let cachedCustomerId = null;
let cachedProducts   = [];

async function fetchProducts(customerId) {
    if (!customerId) return [];
    if (String(customerId) === String(cachedCustomerId)) return cachedProducts;
    try {
        const res  = await fetch(`/erp/api/warehouse/get_customer_products.php?customer_id=${encodeURIComponent(customerId)}`);
        const data = await res.json();
        if (data.ok) {
            cachedCustomerId = customerId;
            cachedProducts   = data.products || [];
        }
        return cachedProducts;
    } catch (err) {
        console.error('Lỗi tải sản phẩm:', err);
        return [];
    }
}

function buildProductOptions(products, selectedId = '') {
    if (!products.length) {
        return '<option value="">-- Chưa có sản phẩm --</option>';
    }
    const opts = products.map(pc => {
        const sel = String(selectedId) === String(pc.id) ? 'selected' : '';
        return `<option value="${escHtml(pc.id)}"
                        data-unit="${escHtml(pc.unit || 'cái')}"
                        data-desc="${escHtml(pc.description || '')}"
                        ${sel}>${escHtml(pc.product_code)} — ${escHtml(pc.description)}</option>`;
    }).join('');
    return `<option value="">-- Chọn mã hàng --</option>${opts}`;
}

// Thêm dòng hàng hoá — dùng index cụ thể để PHP parse đúng
async function addItemRow() {
    const customerId = document.getElementById('iqcCustomer').value;
    if (!customerId) {
        document.getElementById('noCustomerAlert').style.display = '';
        document.getElementById('noProductAlert').style.display  = 'none';
        return;
    }
    document.getElementById('noCustomerAlert').style.display = 'none';

    const products = await fetchProducts(customerId);
    if (!products.length) {
        document.getElementById('noProductAlert').style.display = '';
        return;
    }
    document.getElementById('noProductAlert').style.display = 'none';

    const tbody = document.getElementById('iqcItemsBody');
    // Dùng index = số dòng hiện tại để PHP parse items[0][...], items[1][...] đúng
    const idx = tbody.querySelectorAll('tr').length;
    const tr  = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select class="form-select form-select-sm" name="items[${idx}][product_code_id]" required>
                ${buildProductOptions(products)}
            </select>
        </td>
        <td><span class="item-desc text-muted small fst-italic"></span></td>
        <td><input type="number" class="form-control form-control-sm item-qty"
                   name="items[${idx}][qty]" min="0.01" step="0.01" required
                   placeholder="0"></td>
        <td><input type="text" class="form-control form-control-sm"
                   name="items[${idx}][unit]" value="cái" readonly></td>
        <td><input type="text" class="form-control form-control-sm"
                   name="items[${idx}][note]" placeholder="Ghi chú"></td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

// Khi đổi khách hàng → xoá tất cả dòng cũ, reset cache, thêm 1 dòng mới
document.getElementById('iqcCustomer').addEventListener('change', async function () {
    cachedCustomerId = null;
    cachedProducts   = [];
    document.getElementById('iqcItemsBody').innerHTML = '';
    document.getElementById('noCustomerAlert').style.display = 'none';
    document.getElementById('noProductAlert').style.display  = 'none';
    if (this.value) {
        await addItemRow();
    }
});

document.getElementById('btnAddRow').addEventListener('click', addItemRow);

// Xoá dòng — sau khi xoá phải re-index tất cả name
document.getElementById('iqcItemsBody').addEventListener('click', function (e) {
    if (e.target.closest('.btn-remove-row')) {
        e.target.closest('tr').remove();
        // Re-index để PHP nhận đúng
        reindexRows();
    }
});

function reindexRows() {
    document.querySelectorAll('#iqcItemsBody tr').forEach((tr, idx) => {
        tr.querySelector('select').name               = `items[${idx}][product_code_id]`;
        tr.querySelector('.item-qty').name            = `items[${idx}][qty]`;
        tr.querySelectorAll('input[type="text"]')[0].name = `items[${idx}][unit]`;
        tr.querySelectorAll('input[type="text"]')[1].name = `items[${idx}][note]`;
    });
}

// Chọn mã hàng → điền tên + đơn vị
document.getElementById('iqcItemsBody').addEventListener('change', function (e) {
    if (e.target.tagName === 'SELECT' && e.target.name.includes('product_code_id')) {
        const opt  = e.target.options[e.target.selectedIndex];
        const tr   = e.target.closest('tr');
        tr.querySelector('.item-desc').textContent = opt?.dataset.desc || '';
        const unitEl = tr.querySelector('input[name*="[unit]"]');
        if (unitEl) unitEl.value = opt?.dataset.unit || 'cái';
    }
});

// Reset khi đóng modal
document.getElementById('modalCreateIqc').addEventListener('hidden.bs.modal', function () {
    document.getElementById('iqcItemsBody').innerHTML = '';
    document.getElementById('formCreateIqc').reset();
    document.getElementById('noCustomerAlert').style.display = 'none';
    document.getElementById('noProductAlert').style.display  = 'none';
    cachedCustomerId = null;
    cachedProducts   = [];
});

// Submit
document.getElementById('formCreateIqc').addEventListener('submit', async function (e) {
    e.preventDefault();

    const customerId = document.getElementById('iqcCustomer').value;
    const receivedBy = document.getElementById('iqcReceivedBy').value;
    if (!customerId) { alert('Vui lòng chọn khách hàng.'); return; }
    if (!receivedBy || receivedBy === '0') { alert('Vui lòng chọn người nhận hàng.'); return; }

    const rows = document.querySelectorAll('#iqcItemsBody tr');
    if (!rows.length) { alert('Vui lòng thêm ít nhất 1 mặt hàng.'); return; }

    // Validate: mỗi dòng phải chọn mã hàng và nhập số lượng
    let valid = true;
    rows.forEach((tr, i) => {
        const sel = tr.querySelector('select');
        const qty = tr.querySelector('.item-qty');
        if (!sel.value) { sel.classList.add('is-invalid'); valid = false; }
        else              sel.classList.remove('is-invalid');
        if (!qty.value || parseFloat(qty.value) <= 0) { qty.classList.add('is-invalid'); valid = false; }
        else                                             qty.classList.remove('is-invalid');
    });
    if (!valid) { alert('Vui lòng chọn mã hàng và nhập số lượng cho tất cả các dòng.'); return; }

    const btn = document.getElementById('btnSubmitIqc');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    try {
        const fd  = new FormData(this);
        const res  = await fetch('/erp/api/warehouse/save_iqc.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalCreateIqc')).hide();
            // Toast thành công
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow';
            toast.style.zIndex = 9999;
            toast.innerHTML = `✅ Đã lưu phiếu <strong>${escHtml(data.receipt_no)}</strong>
                               — Lệnh SX: <strong>${escHtml(data.order_no)}</strong>
                               <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(toast);
            setTimeout(() => location.reload(), 2000);
        } else {
            alert('Lỗi: ' + (data.msg || 'Không thể lưu phiếu IQC'));
        }
    } catch (err) {
        alert('Lỗi kết nối: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu phiếu IQC';
    }
});

// Xem chi tiết phiếu
document.querySelectorAll('.btn-view-items').forEach(btn => btn.addEventListener('click', async function () {
    document.getElementById('iqcDetailNo').textContent = this.dataset.no;
    const body = document.getElementById('iqcDetailBody');
    body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-1"></i>Đang tải...</td></tr>';
    new bootstrap.Modal(document.getElementById('modalIqcItems')).show();
    try {
        const res  = await fetch('/erp/api/warehouse/get_iqc_items.php?receipt_id=' + this.dataset.id);
        const data = await res.json();
        if (data.ok && data.items.length) {
            body.innerHTML = data.items.map((it, i) => `
                <tr>
                    <td><span class="badge bg-primary">${escHtml(it.product_code)}</span></td>
                    <td>${escHtml(it.description)}</td>
                    <td class="text-end fw-semibold">${Number(it.qty).toLocaleString('vi-VN')}</td>
                    <td>${escHtml(it.unit)}</td>
                </tr>`).join('');
        } else {
            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Không có dữ liệu</td></tr>';
        }
    } catch (err) {
        body.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
    }
}));
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
