<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant','warehouse','production','manager');

$pdo  = getDBConnection();
$user = currentUser();

// Lấy danh sách mã SP
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = '(product_code LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $where[]  = 'category = ?';
    $params[] = $category;
}

$sql  = 'SELECT * FROM product_codes WHERE ' . implode(' AND ', $where) . ' ORDER BY product_code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách category
$categories = $pdo->query("SELECT DISTINCT category FROM product_codes WHERE category IS NOT NULL ORDER BY category")
                  ->fetchAll(PDO::FETCH_COLUMN);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';

?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-barcode me-2 text-primary"></i>Danh mục mã sản phẩm</h4>
            <p class="text-muted mb-0">Quản lý mã SP & mô tả tự động</p>
        </div>
        <?php if (hasRole('director','accountant','warehouse','manager')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus me-1"></i> Thêm mã SP
        </button>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Tìm mã hoặc mô tả..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">-- Tất cả nhóm --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                        <?php endforeach; ?>
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

    <!-- Bảng danh sách -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th width="150">Mã SP</th>
                            <th>Mô tả hàng hoá</th>
                            <th width="80">Đơn vị</th>
                            <th width="150">Nhóm</th>
                            <th width="80">Trạng thái</th>
                            <?php if (hasRole('director','accountant','warehouse','manager')): ?>
                            <th width="100">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Chưa có mã sản phẩm nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $i => $p): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td><span class="badge bg-primary fs-6"><?= htmlspecialchars($p['product_code']) ?></span></td>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($p['unit']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($p['category'] ?? '—') ?></span></td>
                            <td class="text-center">
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success">Đang dùng</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Ngừng</span>
                                <?php endif; ?>
                            </td>
                            <?php if (hasRole('director','accountant','warehouse','manager')): ?>
                            <td>
                                <button class="btn btn-sm btn-outline-warning btn-edit"
                                    data-id="<?= $p['id'] ?>"
                                    data-code="<?= htmlspecialchars($p['product_code']) ?>"
                                    data-desc="<?= htmlspecialchars($p['description']) ?>"
                                    data-unit="<?= htmlspecialchars($p['unit']) ?>"
                                    data-category="<?= htmlspecialchars($p['category'] ?? '') ?>"
                                    data-active="<?= $p['is_active'] ?>">
                                    <i class="fas fa-edit"></i>
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
            Tổng: <strong><?= count($products) ?></strong> mã sản phẩm
        </div>
    </div>
</div>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-barcode me-2"></i>Thêm mã sản phẩm
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formProduct">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="id" id="fieldId" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="product_code" id="fieldCode"
                               class="form-control text-uppercase"
                               placeholder="VD: SP-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả hàng hoá <span class="text-danger">*</span></label>
                        <textarea name="description" id="fieldDesc"
                                  class="form-control" rows="3"
                                  placeholder="Mô tả chi tiết sản phẩm..." required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Đơn vị</label>
                            <select name="unit" id="fieldUnit" class="form-select">
                                <option value="cái">Cái</option>
                                <option value="chiếc">Chiếc</option>
                                <option value="bộ">Bộ</option>
                                <option value="kg">Kg</option>
                                <option value="tấn">Tấn</option>
                                <option value="m">Mét</option>
                                <option value="m2">M²</option>
                                <option value="thùng">Thùng</option>
                                <option value="hộp">Hộp</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Nhóm / Category</label>
                            <input type="text" name="category" id="fieldCategory"
                                   class="form-control" placeholder="VD: Thành phẩm">
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="is_active" id="fieldActive" value="1" checked>
                            <label class="form-check-label" for="fieldActive">Đang sử dụng</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="btnSave">
                    <i class="fas fa-save me-1"></i>Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Nút Sửa → đổ data vào modal
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Sửa mã sản phẩm';
        document.getElementById('fieldId').value       = btn.dataset.id;
        document.getElementById('fieldCode').value     = btn.dataset.code;
        document.getElementById('fieldDesc').value     = btn.dataset.desc;
        document.getElementById('fieldUnit').value     = btn.dataset.unit;
        document.getElementById('fieldCategory').value = btn.dataset.category;
        document.getElementById('fieldActive').checked = btn.dataset.active == '1';
        new bootstrap.Modal(document.getElementById('modalAdd')).show();
    });
});

// Reset modal khi đóng
document.getElementById('modalAdd').addEventListener('hidden.bs.modal', () => {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-barcode me-2"></i>Thêm mã sản phẩm';
    document.getElementById('formProduct').reset();
    document.getElementById('fieldId').value = '';
});

// Lưu
document.getElementById('btnSave').addEventListener('click', () => {
    const form = document.getElementById('formProduct');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn  = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    const data = new FormData(form);
    if (!document.getElementById('fieldActive').checked) data.delete('is_active');

    fetch('/erp/api/master/save_product_code.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalAdd')).hide();
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
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>