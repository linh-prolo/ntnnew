<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
$pdo = getDBConnection();

$categories = fetchAllSafe($pdo, 'SELECT * FROM wa_categories ORDER BY id DESC');
$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="mb-1"><i class="fas fa-tags me-2 text-primary"></i>Nhóm vật tư</h4><p class="text-muted mb-0">Quản lý danh mục nhóm vật tư.</p></div><button class="btn btn-primary" id="btnNew"><i class="fas fa-plus me-1"></i>Thêm nhóm</button></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead class="table-dark"><tr><th>Tên nhóm</th><th>Mô tả</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
    <?php if(!$categories): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có nhóm vật tư</td></tr><?php endif; ?>
    <?php foreach($categories as $c): ?><tr><td class="fw-semibold"><?= e($c['name']) ?></td><td><?= e($c['description']) ?></td><td><span class="badge bg-<?= (int)$c['is_active']===1?'success':'secondary' ?>"><?= (int)$c['is_active']===1?'Active':'Inactive' ?></span></td><td><button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['name']) ?>" data-description="<?= e($c['description']) ?>" data-active="<?= (int)$c['is_active'] ?>"><i class="fas fa-edit"></i></button></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div></div>

<div class="modal fade" id="modalCategory" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Nhóm vật tư</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><form id="formCategory"><div class="modal-body">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="id" id="catId">
    <div class="mb-3"><label class="form-label">Tên nhóm</label><input type="text" class="form-control" name="name" id="catName" required></div>
    <div class="mb-3"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" id="catDescription"></textarea></div>
    <div class="mb-3"><label class="form-label">Trạng thái</label><select class="form-select" name="is_active" id="catActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Đóng</button><button class="btn btn-primary" type="submit">Lưu</button></div></form></div></div></div>
<script>
window.addEventListener('load', function() {
    const catId = document.getElementById('catId');
    const catName = document.getElementById('catName');
    const catDescription = document.getElementById('catDescription');
    const catActive = document.getElementById('catActive');

    function getCatModal() {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCategory'));
    }

    document.getElementById('btnNew').addEventListener('click', () => {
        catId.value = '';
        catName.value = '';
        catDescription.value = '';
        catActive.value = '1';
        getCatModal().show();
    });

    document.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', function() {
        catId.value = this.dataset.id;
        catName.value = this.dataset.name;
        catDescription.value = this.dataset.description || '';
        catActive.value = this.dataset.active !== undefined ? this.dataset.active : '1';
        getCatModal().show();
    }));

    document.getElementById('formCategory').addEventListener('submit', async function(e) {
        e.preventDefault();
        try {
            const res = await fetch('/erp/api/warehouse_admin/save_category.php', {method: 'POST', body: new FormData(this)});
            if (!res.ok) {
                alert('Lỗi server (HTTP ' + res.status + '). Vui lòng tải lại trang và thử lại.');
                return;
            }
            const data = await res.json();
            if (data.ok) location.reload();
            else alert(data.msg || 'Lỗi lưu nhóm vật tư');
        } catch (err) {
            alert('Không thể kết nối server. Vui lòng tải lại trang và thử lại.');
        }
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
