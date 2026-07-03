<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant','manager');

$pdo  = getDBConnection();
$user = currentUser();

// Lấy danh sách giá (mới nhất của từng SP)
$prices = $pdo->query("
    SELECT pp.*, pc.product_code, pc.description, pc.unit,
           u.full_name AS created_by_name
    FROM product_prices pp
    JOIN product_codes pc ON pp.product_code_id = pc.id
    LEFT JOIN users u ON pp.created_by = u.id
    ORDER BY pc.product_code, pp.effective_from DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách SP active cho dropdown
$productList = $pdo->query("
    SELECT id, product_code, description FROM product_codes
    WHERE is_active = 1 ORDER BY product_code
")->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';

?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-tags me-2 text-primary"></i>Bảng giá sản phẩm</h4>
            <p class="text-muted mb-0">Giá áp dụng theo từng thời điểm — giá cũ không thay đổi</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPrice">
            <i class="fas fa-plus me-1"></i> Thêm giá mới
        </button>
    </div>

    <?php showFlash(); ?>

    <!-- Alert giải thích -->
    <div class="alert alert-info border-0 shadow-sm mb-3">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Lưu ý:</strong> Mỗi lần setup giá mới sẽ áp dụng từ ngày <strong>effective_from</strong> trở đi.
        Các hoá đơn đã xuất trước đó <strong>giữ nguyên giá cũ</strong>.
    </div>

    <!-- Bảng giá -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th width="130">Mã SP</th>
                            <th>Mô tả</th>
                            <th width="80">Đơn vị</th>
                            <th width="150" class="text-end">Đơn giá</th>
                            <th width="130">Áp dụng từ</th>
                            <th width="150">Người tạo</th>
                            <th width="150">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($prices)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có bảng giá nào</td></tr>
                    <?php else: ?>
                        <?php
                        $prevCode = '';
                        foreach ($prices as $i => $p):
                            $isLatest = ($p['product_code'] !== $prevCode);
                            $prevCode = $p['product_code'];
                        ?>
                        <tr class="<?= $isLatest ? '' : 'text-muted' ?>">
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <span class="badge <?= $isLatest ? 'bg-primary' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($p['product_code']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($p['unit']) ?></td>
                            <td class="text-end fw-<?= $isLatest ? 'bold text-success' : 'normal' ?>">
                                <?= number_format($p['unit_price']) ?> đ
                            </td>
                            <td>
                                <?php if ($isLatest): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>
                                        <?= date('d/m/Y', strtotime($p['effective_from'])) ?>
                                    </span>
                                <?php else: ?>
                                    <?= date('d/m/Y', strtotime($p['effective_from'])) ?>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($p['created_by_name'] ?? '—') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($p['note'] ?? '—') ?></td>
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

<!-- Modal thêm giá -->
<div class="modal fade" id="modalPrice" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-tags me-2"></i>Thêm giá mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPrice">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                        <select name="product_code_id" id="priceProduct" class="form-select" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($productList as $pl): ?>
                            <option value="<?= $pl['id'] ?>">
                                [<?= htmlspecialchars($pl['product_code']) ?>] <?= htmlspecialchars($pl['description']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đơn giá <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="unit_price" id="priceAmount"
                                   class="form-control" placeholder="0" min="0" required>
                            <span class="input-group-text">VNĐ</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Áp dụng từ ngày <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" id="priceFrom"
                               class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Lý do thay đổi giá...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="btnSavePrice">
                    <i class="fas fa-save me-1"></i>Lưu giá
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btnSavePrice').addEventListener('click', () => {
    const form = document.getElementById('formPrice');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn = document.getElementById('btnSavePrice');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    fetch('/erp/api/master/save_price.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalPrice')).hide();
            location.reload();
        } else {
            alert('Lỗi: ' + res.msg);
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu giá';
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>