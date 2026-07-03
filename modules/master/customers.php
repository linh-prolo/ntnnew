<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director','accountant','manager');

$pdo  = getDBConnection();
$user = currentUser();

$search = trim($_GET['search'] ?? '');
$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = '(customer_name LIKE ? OR customer_code LIKE ? OR phone LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare('SELECT * FROM customers WHERE ' . implode(' AND ', $where) . ' ORDER BY customer_name');
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<style>
/* Dòng bảng có thể click */
.table-clickable tbody tr { cursor: pointer; }
.table-clickable tbody tr:hover td { background-color: #f0f4ff; }
/* Cột thao tác không trigger click dòng */
.table-clickable tbody tr td.col-actions { cursor: default; }
</style>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Danh mục khách hàng</h4>
            <p class="text-muted mb-0">Nhấn vào dòng khách hàng để xem hồ sơ chi tiết</p>
        </div>
        <?php if (hasRole('director','accountant','warehouse','manager')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCust">
            <i class="fas fa-plus me-1"></i> Thêm khách hàng
        </button>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Tên, mã KH, số điện thoại..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
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

    <!-- Bảng -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-clickable">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th width="120">Mã KH</th>
                            <th>Tên khách hàng</th>
                            <th width="100" class="text-center">Trạng thái</th>
                            <?php if (hasRole('director','accountant','warehouse','manager')): ?>
                            <th width="110" class="text-center">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="<?= hasRole('director','accountant','warehouse','manager') ? 5 : 4 ?>" class="text-center text-muted py-4">Chưa có khách hàng nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $i => $c): ?>
                        <tr data-href="customer_profile.php?id=<?= (int)$c['id'] ?>">
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td><span class="badge bg-secondary fs-6"><?= htmlspecialchars($c['customer_code'] ?? '—') ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($c['customer_name']) ?></td>
                            <td class="text-center">
                                <?= $c['is_active']
                                    ? '<span class="badge bg-success">Đang dùng</span>'
                                    : '<span class="badge bg-secondary">Ngừng</span>' ?>
                            </td>
                            <?php if (hasRole('director','accountant','warehouse','manager')): ?>
                            <td class="text-center col-actions" onclick="event.stopPropagation()">
                                <button class="btn btn-sm btn-outline-warning btn-edit-cust"
                                    title="Sửa"
                                    data-id="<?= $c['id'] ?>"
                                    data-code="<?= htmlspecialchars($c['customer_code'] ?? '') ?>"
                                    data-name="<?= htmlspecialchars($c['customer_name']) ?>"
                                    data-address="<?= htmlspecialchars($c['address'] ?? '') ?>"
                                    data-tax="<?= htmlspecialchars($c['tax_code'] ?? '') ?>"
                                    data-contact="<?= htmlspecialchars($c['contact_person'] ?? '') ?>"
                                    data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>"
                                    data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                    data-vat="<?= (int)($c['vat_rate'] ?? 8) ?>"
                                    data-active="<?= $c['is_active'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (hasRole('director','accountant')): ?>
                                <button class="btn btn-sm btn-outline-danger btn-delete-cust ms-1"
                                    title="Xoá"
                                    data-id="<?= $c['id'] ?>"
                                    data-name="<?= htmlspecialchars($c['customer_name']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
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
            Tổng: <strong><?= count($customers) ?></strong> khách hàng
            <span class="ms-2 text-info"><i class="fas fa-info-circle me-1"></i>Nhấn vào dòng để xem hồ sơ chi tiết</span>
        </div>
    </div>
</div>
</div>

<!-- Modal Thêm/Sửa KH -->
<div class="modal fade" id="modalCust" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCustTitle">
                    <i class="fas fa-user-plus me-2"></i>Thêm khách hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCust">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="id" id="custId" value="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Mã khách hàng</label>
                            <input type="text" name="customer_code" id="custCode"
                                   class="form-control text-uppercase" placeholder="VD: KH-001">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Tên công ty <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" id="custName"
                                   class="form-control" placeholder="Tên công ty hoặc cá nhân" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Địa chỉ</label>
                            <input type="text" name="address" id="custAddress"
                                   class="form-control" placeholder="Địa chỉ đầy đủ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mã số thuế</label>
                            <input type="text" name="tax_code" id="custTaxCode"
                                   class="form-control" placeholder="VD: 0123456789">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Thuế VAT mặc định</label>
                            <select name="vat_rate" id="custVat" class="form-select">
                                <option value="0">0% (Không chịu thuế)</option>
                                <option value="5">5%</option>
                                <option value="8" selected>8%</option>
                                <option value="10">10%</option>
                            </select>
                        </div>
                        <div class="col-md-4"></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Người liên hệ</label>
                            <input type="text" name="contact_person" id="custContact"
                                   class="form-control" placeholder="Họ tên người liên hệ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Điện thoại <span class="text-muted small">(hiện trên biên bản giao hàng)</span></label>
                            <input type="text" name="phone" id="custPhone"
                                   class="form-control" placeholder="0909...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Email nhận hoá đơn
                                <span class="text-muted small">(nhiều email ngăn cách bằng dấu <code>;</code>)</span>
                            </label>
                            <textarea name="email" id="custEmail" class="form-control" rows="2"
                                      placeholder="email1@example.com; email2@example.com"></textarea>
                            <div class="form-text">Hệ thống sẽ gửi hoá đơn đến tất cả email này.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       name="is_active" id="custActive" value="1" checked>
                                <label class="form-check-label" for="custActive">Đang giao dịch</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="btnSaveCust">
                    <i class="fas fa-save me-1"></i>Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Click dòng → vào hồ sơ
document.querySelectorAll('.table-clickable tbody tr[data-href]').forEach(tr => {
    tr.addEventListener('click', function(e) {
        if (e.target.closest('.col-actions')) return;
        location.href = this.dataset.href;
    });
});

// Sửa KH
document.querySelectorAll('.btn-edit-cust').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modalCustTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Sửa khách hàng';
        document.getElementById('custId').value      = btn.dataset.id;
        document.getElementById('custCode').value    = btn.dataset.code;
        document.getElementById('custName').value    = btn.dataset.name;
        document.getElementById('custAddress').value = btn.dataset.address;
        document.getElementById('custTaxCode').value = btn.dataset.tax || '';
        document.getElementById('custContact').value = btn.dataset.contact;
        document.getElementById('custPhone').value   = btn.dataset.phone;
        document.getElementById('custEmail').value   = btn.dataset.email;
        document.getElementById('custVat').value     = btn.dataset.vat || '8';
        document.getElementById('custActive').checked = btn.dataset.active == '1';
        new bootstrap.Modal(document.getElementById('modalCust')).show();
    });
});

// Xoá KH
document.querySelectorAll('.btn-delete-cust').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm(`Xác nhận xoá khách hàng "${btn.dataset.name}"?\nNếu đã có giao dịch, khách hàng sẽ chuyển sang trạng thái Ngừng.`)) return;
        const fd = new FormData();
        fd.append('csrf_token', '<?= $csrf ?>');
        fd.append('action', 'delete');
        fd.append('id', btn.dataset.id);
        fetch('/erp/api/master/save_customer.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) location.reload();
                else alert('Lỗi: ' + d.msg);
            });
    });
});

document.getElementById('modalCust').addEventListener('hidden.bs.modal', () => {
    document.getElementById('modalCustTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Thêm khách hàng';
    document.getElementById('formCust').reset();
    document.getElementById('custId').value  = '';
    document.getElementById('custVat').value = '8';
});

document.getElementById('btnSaveCust').addEventListener('click', () => {
    const form = document.getElementById('formCust');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn = document.getElementById('btnSaveCust');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    const data = new FormData(form);
    if (!document.getElementById('custActive').checked) data.delete('is_active');

    fetch('/erp/api/master/save_customer.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalCust')).hide();
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
