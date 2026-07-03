<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant','warehouse','production','manager');

$pdo  = getDBConnection();
$user = currentUser();

$filterCust = (int)($_GET['customer_id'] ?? 0);
$search     = trim($_GET['search'] ?? '');

// Lấy danh sách khách hàng
$customers = $pdo->query("
    SELECT id, customer_code, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách mã SP active
$productList = $pdo->query("
    SELECT id, product_code, description, unit FROM product_codes WHERE is_active = 1 ORDER BY product_code
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy bảng giá theo khách đã chọn
$prices = [];
if ($filterCust) {
    $where  = ['cp.customer_id = ?'];
    $params = [$filterCust];
    if ($search) {
        $where[]  = '(pc.product_code LIKE ? OR pc.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $stmt = $pdo->prepare("
        SELECT cp.*, pc.product_code, pc.description, pc.unit,
               c.customer_name
        FROM customer_prices cp
        JOIN product_codes pc ON cp.product_code_id = pc.id
        JOIN customers c ON cp.customer_id = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY pc.product_code
    ");
    $stmt->execute($params);
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-dollar-sign me-2 text-primary"></i>Bảng giá theo khách hàng</h4>
            <p class="text-muted mb-0">Mỗi khách hàng có đơn giá gia công riêng cho từng mã sản phẩm</p>
        </div>
        <?php if (hasRole('director','accountant','manager')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPrice">
            <i class="fas fa-plus me-1"></i> Thêm đơn giá
        </button>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <select name="customer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Chọn khách hàng --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterCust == $c['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($c['customer_code'] ?? '') ?>] <?= htmlspecialchars($c['customer_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Tìm mã SP..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>Lọc
                    </button>
                    <a href="?customer_id=<?= $filterCust ?>" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$filterCust): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <i class="fas fa-info-circle me-2"></i>
        Vui lòng chọn khách hàng để xem bảng giá.
    </div>
    <?php else: ?>

    <!-- Bảng giá -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th width="130">Mã SP</th>
                            <th>Mô tả sản phẩm</th>
                            <th width="80">Đơn vị</th>
                            <th width="160" class="text-end">Đơn giá gia công</th>
                            <th width="80" class="text-center">Kích hoạt</th>
                            <th>Ghi chú</th>
                            <?php if (hasRole('director','accountant','manager')): ?>
                            <th width="100">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($prices)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có đơn giá nào cho khách hàng này</td></tr>
                    <?php else: ?>
                        <?php foreach ($prices as $i => $p): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($p['product_code']) ?></span></td>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($p['unit'] ?? '') ?></td>
                            <td class="text-end fw-bold text-success">
                                <?= number_format($p['unit_price'], 0, ',', '.') ?> đ
                            </td>
                            <td class="text-center">
                                <?= $p['is_active']
                                    ? '<span class="badge bg-success">Đang dùng</span>'
                                    : '<span class="badge bg-secondary">Tắt</span>' ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($p['note'] ?? '—') ?></td>
                            <?php if (hasRole('director','accountant','manager')): ?>
                            <td>
                                <button class="btn btn-sm btn-outline-warning btn-edit-price"
                                        data-id="<?= $p['id'] ?>"
                                        data-customer="<?= $p['customer_id'] ?>"
                                        data-product="<?= $p['product_code_id'] ?>"
                                        data-price="<?= $p['unit_price'] ?>"
                                        data-active="<?= $p['is_active'] ?>"
                                        data-note="<?= htmlspecialchars($p['note'] ?? '') ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-price ms-1"
                                        data-id="<?= $p['id'] ?>"
                                        data-name="<?= htmlspecialchars($p['product_code'] . ' - ' . $p['customer_name']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            Tổng: <strong><?= count($prices) ?></strong> mã hàng có đơn giá
        </div>
    </div>

    <?php endif; ?>
</div>
</div>

<!-- Modal Thêm/Sửa đơn giá -->
<?php if (hasRole('director','accountant','manager')): ?>
<div class="modal fade" id="modalPrice" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalPriceTitle">
                    <i class="fas fa-dollar-sign me-2"></i>Thêm đơn giá
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPrice">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="id" id="priceId" value="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Khách hàng <span class="text-danger">*</span></label>
                        <select name="customer_id" id="priceCustomer" class="form-select" required>
                            <option value="">-- Chọn khách hàng --</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                    <?= $filterCust == $c['id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($c['customer_code'] ?? '') ?>] <?= htmlspecialchars($c['customer_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã sản phẩm <span class="text-danger">*</span></label>
                        <select name="product_code_id" id="priceProduct" class="form-select" required>
                            <option value="">-- Chọn mã SP --</option>
                            <?php foreach ($productList as $pc): ?>
                            <option value="<?= $pc['id'] ?>">
                                [<?= htmlspecialchars($pc['product_code']) ?>] <?= htmlspecialchars($pc['description']) ?>
                                (<?= htmlspecialchars($pc['unit'] ?? '') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đơn giá gia công <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="unit_price" id="priceValue"
                                   class="form-control" placeholder="0" min="0" step="1" required>
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <input type="text" name="note" id="priceNote" class="form-control"
                               placeholder="Ghi chú (tuỳ chọn)">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="btnSavePrice">
                    <i class="fas fa-save me-1"></i>Lưu đơn giá
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const csrf = '<?= $csrf ?>';

// Mở modal thêm mới
document.querySelector('[data-bs-target="#modalPrice"]')?.addEventListener('click', () => {
    document.getElementById('modalPriceTitle').innerHTML = '<i class="fas fa-dollar-sign me-2"></i>Thêm đơn giá';
    document.getElementById('formPrice').reset();
    document.getElementById('priceId').value = '';
    const custSel = document.getElementById('priceCustomer');
    if (custSel && '<?= $filterCust ?>') custSel.value = '<?= $filterCust ?>';
});

// Sửa
document.querySelectorAll('.btn-edit-price').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modalPriceTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Sửa đơn giá';
        document.getElementById('priceId').value      = btn.dataset.id;
        document.getElementById('priceCustomer').value = btn.dataset.customer;
        document.getElementById('priceProduct').value  = btn.dataset.product;
        document.getElementById('priceValue').value    = btn.dataset.price;
        document.getElementById('priceNote').value     = btn.dataset.note;
        new bootstrap.Modal(document.getElementById('modalPrice')).show();
    });
});

// Lưu
document.getElementById('btnSavePrice')?.addEventListener('click', () => {
    const form = document.getElementById('formPrice');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const fd = new FormData(form);
    fetch('/erp/api/master/save_customer_price.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                location.href = '?customer_id=<?= $filterCust ?>';
            } else {
                alert('Lỗi: ' + d.msg);
            }
        });
});

// Xoá
document.querySelectorAll('.btn-delete-price').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm(`Xoá đơn giá cho "${btn.dataset.name}"?`)) return;
        const fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('action', 'delete');
        fd.append('id', btn.dataset.id);
        fetch('/erp/api/master/save_customer_price.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) location.href = '?customer_id=<?= $filterCust ?>';
                else alert('Lỗi: ' + d.msg);
            });
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
