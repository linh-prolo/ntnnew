<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant','manager');

$pdo        = getDBConnection();
$user       = currentUser();
$deliveryId = (int)($_GET['delivery_id'] ?? 0);

// Load biên bản giao hàng
$delivery = null;
$dnItems  = [];
if ($deliveryId) {
    $stmt = $pdo->prepare("
        SELECT dn.*, c.customer_name, c.customer_code, c.address, c.phone
        FROM delivery_notes dn
        LEFT JOIN customers c ON dn.customer_id = c.id
        WHERE dn.id = ?
    ");
    $stmt->execute([$deliveryId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($delivery) {
        $stmtItems = $pdo->prepare("
            SELECT dni.*,
                   pc.product_code, pc.unit,
                   COALESCE(pp.unit_price, 0) AS unit_price
            FROM delivery_note_items dni
            JOIN product_codes pc  ON dni.product_code_id  = pc.id
            LEFT JOIN product_prices pp ON pp.product_code_id = pc.id
            WHERE dni.delivery_note_id = ?
            ORDER BY dni.id
        ");
        $stmtItems->execute([$deliveryId]);
        $dnItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Danh sách khách hàng
$customers = $pdo->query("
    SELECT id, customer_name, customer_code
    FROM customers WHERE is_active = 1 ORDER BY customer_name
")->fetchAll(PDO::FETCH_ASSOC);

// Danh sách SP
$productList = $pdo->query("
    SELECT pc.id, pc.product_code, pc.description, pc.unit,
           COALESCE(pp.unit_price, 0) AS unit_price
    FROM product_codes pc
    LEFT JOIN product_prices pp ON pp.product_code_id = pc.id
    WHERE pc.is_active = 1
    ORDER BY pc.product_code
")->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <a href="index.php" class="btn btn-sm btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-1">
                <i class="fas fa-file-invoice me-2 text-primary"></i>Tạo hoá đơn
                <?php if ($delivery): ?>
                    <span class="text-muted fs-6 fw-normal ms-2">
                        từ biên bản <?= htmlspecialchars($delivery['delivery_no']) ?>
                    </span>
                <?php endif; ?>
            </h4>
        </div>
    </div>

    <?php if ($delivery): ?>
    <!-- Thông tin biên bản -->
    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle me-2"></i>
        Tạo hoá đơn từ biên bản <strong><?= htmlspecialchars($delivery['delivery_no']) ?></strong>
        — Khách: <strong><?= htmlspecialchars($delivery['customer_name']) ?></strong>
        — Ngày giao: <strong><?= date('d/m/Y', strtotime($delivery['delivery_date'])) ?></strong>
    </div>
    <?php endif; ?>

    <form id="formCreateInvoice">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <?php if ($deliveryId): ?>
        <input type="hidden" name="delivery_id" value="<?= $deliveryId ?>">
        <?php endif; ?>

        <div class="row g-3">
            <!-- Cột trái: thông tin HĐ -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Thông tin hoá đơn
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Ngày HĐ <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="invoice_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hạn thanh toán</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Khách hàng <span class="text-danger">*</span>
                            </label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">-- Chọn KH --</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ($delivery && $delivery['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($c['customer_code']) ?>]
                                    <?= htmlspecialchars($c['customer_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Thuế VAT (%)</label>
                            <input type="number" name="vat_rate" id="vatRate"
                                   class="form-control" value="0" min="0" max="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="2"
                                      placeholder="Ghi chú hoá đơn..."></textarea>
                        </div>

                        <!-- Tổng tiền -->
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Tạm tính:</span>
                                    <span id="showSubtotal" class="fw-semibold">0 đ</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">VAT (<span id="showVatPct">0</span>%):</span>
                                    <span id="showVatAmt" class="text-warning">0 đ</span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Tổng cộng:</span>
                                    <span id="showTotal" class="fw-bold text-success fs-6">0 đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: chi tiết SP -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            <i class="fas fa-list me-2 text-warning"></i>Chi tiết sản phẩm
                        </span>
                        <button type="button" class="btn btn-sm btn-success" id="btnAddRow">
                            <i class="fas fa-plus me-1"></i>Thêm dòng
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="220">Mã sản phẩm</th>
                                        <th>Mô tả</th>
                                        <th width="65">ĐVT</th>
                                        <th width="95">Số lượng</th>
                                        <th width="120">Đơn giá</th>
                                        <th width="130">Thành tiền</th>
                                        <th width="36"></th>
                                    </tr>
                                </thead>
                                <tbody id="invBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Nút lưu -->
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Huỷ
                    </a>
                    <button type="button" class="btn btn-primary" id="btnSaveInvoice">
                        <i class="fas fa-save me-1"></i>Tạo hoá đơn
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>

<script>
const PRODUCTS = <?= json_encode($productList) ?>;
// Dữ liệu từ biên bản (nếu có)
const DN_ITEMS = <?= json_encode($dnItems) ?>;

let rowIdx = 0;

function makeRow(idx, prefill = {}) {
    const opts = PRODUCTS.map(p =>
        `<option value="${p.id}"
            data-desc="${p.description}"
            data-unit="${p.unit}"
            data-price="${p.unit_price}"
            ${prefill.product_code_id == p.id ? 'selected' : ''}>
            [${p.product_code}] ${p.description}
        </option>`
    ).join('');

    const qty   = prefill.quantity   || 0;
    const price = prefill.unit_price || 0;
    const total = Math.round(qty * price);

    return `
    <tr data-idx="${idx}">
        <td>
            <select name="items[${idx}][product_code_id]"
                    class="form-select form-select-sm sel-prod" required>
                <option value="">-- Chọn SP --</option>
                ${opts}
            </select>
        </td>
        <td>
            <input type="text" name="items[${idx}][description]"
                   class="form-control form-control-sm inp-desc"
                   value="${prefill.description || ''}" readonly>
        </td>
        <td>
            <input type="text" name="items[${idx}][unit]"
                   class="form-control form-control-sm inp-unit"
                   value="${prefill.unit || ''}" readonly>
        </td>
        <td>
            <input type="number" name="items[${idx}][quantity]"
                   class="form-control form-control-sm inp-qty"
                   value="${qty}" min="1" required>
        </td>
        <td>
            <input type="number" name="items[${idx}][unit_price]"
                   class="form-control form-control-sm inp-price"
                   value="${price}" min="0">
        </td>
        <td>
            <input type="number" name="items[${idx}][total_price]"
                   class="form-control form-control-sm inp-total fw-bold text-success"
                   value="${total}" readonly>
        </td>
        <td>
            <button type="button"
                    class="btn btn-sm btn-outline-danger btn-rm">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>`;
}

function addRow(prefill = {}) {
    document.getElementById('invBody').insertAdjacentHTML('beforeend', makeRow(rowIdx++, prefill));
    // Nếu prefill có product_code_id thì autofill desc/unit
    if (prefill.product_code_id) {
        const row = document.querySelector(`#invBody tr:last-child`);
        const sel = row.querySelector('.sel-prod');
        if (!prefill.description) {
            const opt = sel.options[sel.selectedIndex];
            row.querySelector('.inp-desc').value  = opt.dataset.desc  || '';
            row.querySelector('.inp-unit').value  = opt.dataset.unit  || '';
            if (!prefill.unit_price)
                row.querySelector('.inp-price').value = opt.dataset.price || 0;
        }
        calcRow(row);
    }
}

// Nếu có biên bản → prefill các dòng SP
if (DN_ITEMS.length > 0) {
    DN_ITEMS.forEach(it => addRow({
        product_code_id : it.product_code_id,
        description     : it.description,
        unit            : it.unit,
        quantity        : it.quantity,
        unit_price      : it.unit_price,
    }));
} else {
    addRow();
}
updateTotals();

document.getElementById('btnAddRow').addEventListener('click', () => { addRow(); });

// Event delegation
document.getElementById('invBody').addEventListener('change', function(e) {
    const row = e.target.closest('tr'); if (!row) return;
    if (e.target.classList.contains('sel-prod')) {
        const opt = e.target.options[e.target.selectedIndex];
        row.querySelector('.inp-desc').value  = opt.dataset.desc  || '';
        row.querySelector('.inp-unit').value  = opt.dataset.unit  || '';
        row.querySelector('.inp-price').value = opt.dataset.price || 0;
        calcRow(row);
    }
    if (e.target.classList.contains('inp-qty') ||
        e.target.classList.contains('inp-price')) calcRow(row);
});

document.getElementById('invBody').addEventListener('input', function(e) {
    const row = e.target.closest('tr'); if (!row) return;
    if (e.target.classList.contains('inp-qty') ||
        e.target.classList.contains('inp-price')) calcRow(row);
});

document.getElementById('invBody').addEventListener('click', function(e) {
    if (e.target.closest('.btn-rm')) {
        if (document.querySelectorAll('#invBody tr').length <= 1) {
            alert('Cần ít nhất 1 dòng!'); return;
        }
        e.target.closest('tr').remove();
        updateTotals();
    }
});

function calcRow(row) {
    const qty   = parseFloat(row.querySelector('.inp-qty').value)   || 0;
    const price = parseFloat(row.querySelector('.inp-price').value) || 0;
    row.querySelector('.inp-total').value = Math.round(qty * price);
    updateTotals();
}

function updateTotals() {
    let sub = 0;
    document.querySelectorAll('.inp-total').forEach(el => sub += parseFloat(el.value) || 0);
    const vatPct = parseFloat(document.getElementById('vatRate').value) || 0;
    const vatAmt = Math.round(sub * vatPct / 100);
    document.getElementById('showSubtotal').textContent = sub.toLocaleString('vi-VN') + ' đ';
    document.getElementById('showVatPct').textContent   = vatPct;
    document.getElementById('showVatAmt').textContent   = vatAmt.toLocaleString('vi-VN') + ' đ';
    document.getElementById('showTotal').textContent    = (sub + vatAmt).toLocaleString('vi-VN') + ' đ';
}

document.getElementById('vatRate').addEventListener('input', updateTotals);

// Lưu hoá đơn
document.getElementById('btnSaveInvoice').addEventListener('click', () => {
    const form = document.getElementById('formCreateInvoice');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    let valid = false;
    document.querySelectorAll('#invBody tr').forEach(r => {
        if (r.querySelector('.sel-prod').value &&
            parseFloat(r.querySelector('.inp-qty').value) > 0) valid = true;
    });
    if (!valid) { alert('Cần ít nhất 1 dòng sản phẩm hợp lệ!'); return; }

    const btn = document.getElementById('btnSaveInvoice');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    fetch('/erp/api/invoice/save_invoice.php', {
        method: 'POST',
        body  : new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            window.location.href = 'invoice_detail.php?id=' + res.id;
        } else {
            alert('Lỗi: ' + res.msg);
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Tạo hoá đơn';
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>