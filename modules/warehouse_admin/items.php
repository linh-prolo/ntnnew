<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
$pdo = getDBConnection();

$categoryId = (int)($_GET['category_id'] ?? 0);
$keyword = trim((string)($_GET['keyword'] ?? ''));
$where = ['1=1'];
$params = [];
if($categoryId>0){ $where[]='i.category_id=?'; $params[]=$categoryId; }
if($keyword!==''){ $where[]='(i.item_code LIKE ? OR i.item_name LIKE ?)'; $params[]="%$keyword%"; $params[]="%$keyword%"; }

$categories = fetchAllSafe($pdo, 'SELECT * FROM wa_categories ORDER BY name');
$items = fetchAllSafe($pdo, "SELECT i.*, c.name AS category_name
                           FROM wa_items i
                           LEFT JOIN wa_categories c ON c.id = i.category_id
                           WHERE " . implode(' AND ', $where) . "
                           ORDER BY i.id DESC", $params);
$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="mb-1"><i class="fas fa-list-alt me-2 text-primary"></i>Danh mục vật tư</h4><p class="text-muted mb-0">Quản lý danh mục vật tư kho hành chính.</p></div><button class="btn btn-primary" id="btnNewItem"><i class="fas fa-plus me-1"></i>Thêm vật tư</button></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2"><form class="row g-2" method="get">
        <div class="col-md-3"><select name="category_id" class="form-select form-select-sm"><option value="">-- Nhóm vật tư --</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $categoryId===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><input type="text" name="keyword" class="form-control form-control-sm" value="<?= e($keyword) ?>" placeholder="Tìm mã / tên vật tư"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Lọc</button></div>
        <div class="col-auto"><a href="/erp/modules/warehouse_admin/items.php" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    </form></div></div>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-dark"><tr><th>Mã vật tư</th><th>Tên</th><th>Nhóm</th><th>ĐVT</th><th class="text-end">Tồn tối thiểu</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
    <?php if(!$items): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có vật tư</td></tr><?php endif; ?>
    <?php foreach($items as $it): ?><tr><td class="fw-semibold"><?= e($it['item_code']) ?></td><td><?= e($it['item_name']) ?></td><td><?= e($it['category_name']) ?></td><td><?= e($it['unit']) ?></td><td class="text-end"><?= e(number_format((float)$it['min_stock'],2,',','.')) ?></td><td><span class="badge bg-<?= (int)$it['is_active']===1?'success':'secondary' ?>"><?= (int)$it['is_active']===1?'Active':'Inactive' ?></span></td><td><button class="btn btn-sm btn-outline-primary btn-edit-item" data-json='<?= e(json_encode($it, JSON_UNESCAPED_UNICODE)) ?>'><i class="fas fa-edit"></i></button></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div></div>

<div class="modal fade" id="modalItem" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Vật tư</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><form id="formItem"><div class="modal-body">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="id" id="itemId">
    <div class="mb-2"><label class="form-label">Mã vật tư</label><input type="text" class="form-control" name="item_code" id="itemCode" required></div>
    <div class="mb-2"><label class="form-label">Tên vật tư</label><input type="text" class="form-control" name="item_name" id="itemName" required></div>
    <div class="mb-2"><label class="form-label">Nhóm</label><select class="form-select" name="category_id" id="itemCategory" required><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-2"><label class="form-label">ĐVT</label><input type="text" class="form-control" name="unit" id="itemUnit" value="cái"></div>
    <div class="mb-2"><label class="form-label">Tồn tối thiểu</label><input type="number" min="0" step="0.01" class="form-control" name="min_stock" id="itemMinStock" value="0"></div>
    <div class="mb-2"><label class="form-label">Trạng thái</label><select class="form-select" name="is_active" id="itemActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary" type="submit">Lưu</button></div></form></div></div></div>
<script>
window.addEventListener('load', function() {
    const itemId = document.getElementById('itemId');
    const itemCode = document.getElementById('itemCode');
    const itemName = document.getElementById('itemName');
    const itemCategory = document.getElementById('itemCategory');
    const itemUnit = document.getElementById('itemUnit');
    const itemMinStock = document.getElementById('itemMinStock');
    const itemActive = document.getElementById('itemActive');

    function getItemModal() {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById('modalItem'));
    }

    document.getElementById('btnNewItem').addEventListener('click', () => {
        itemId.value = '';
        itemCode.value = '';
        itemName.value = '';
        itemCategory.value = '';
        itemUnit.value = 'cái';
        itemMinStock.value = '0';
        itemActive.value = '1';
        getItemModal().show();
    });

    document.querySelectorAll('.btn-edit-item').forEach(btn => btn.addEventListener('click', () => {
        const d = JSON.parse(btn.dataset.json);
        itemId.value = d.id || '';
        itemCode.value = d.item_code || '';
        itemName.value = d.item_name || '';
        itemCategory.value = d.category_id || '';
        itemUnit.value = d.unit || 'cái';
        itemMinStock.value = d.min_stock || '0';
        itemActive.value = d.is_active !== undefined ? d.is_active : 1;
        getItemModal().show();
    }));

    document.getElementById('formItem').addEventListener('submit', async function(e) {
        e.preventDefault();
        try {
            const res = await fetch('/erp/api/warehouse_admin/save_item.php', {method: 'POST', body: new FormData(this)});
            if (!res.ok) {
                alert('Lỗi server (HTTP ' + res.status + '). Vui lòng tải lại trang và thử lại.');
                return;
            }
            const data = await res.json();
            if (data.ok) location.reload();
            else alert(data.msg || 'Lỗi lưu vật tư');
        } catch (err) {
            alert('Không thể kết nối server. Vui lòng tải lại trang và thử lại.');
        }
    });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
