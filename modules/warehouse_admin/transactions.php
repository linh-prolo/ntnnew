<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
$pdo = getDBConnection();

$month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? '')) ? (string)$_GET['month'] : date('Y-m');
$itemId = (int)($_GET['item_id'] ?? 0);
$type = trim((string)($_GET['type'] ?? ''));

$items = fetchAllSafe($pdo, 'SELECT i.id, i.item_code, i.item_name, i.unit, c.name AS category_name
                           FROM wa_items i LEFT JOIN wa_categories c ON c.id = i.category_id
                           WHERE i.is_active = 1 ORDER BY i.item_name');
$stockByItem = [];
foreach ($items as $it) {
    $stockByItem[(int)$it['id']] = (float)fetchScalarSafe($pdo,
        "SELECT COALESCE(SUM(CASE WHEN type='import' THEN qty ELSE -qty END), 0) FROM wa_transactions WHERE item_id = ?",
        [(int)$it['id']],
        0
    );
}

$where = ["DATE_FORMAT(t.transacted_at, '%Y-%m') = ?"]; $params = [$month];
if ($itemId > 0) { $where[] = 't.item_id = ?'; $params[] = $itemId; }
if (in_array($type, ['import', 'export'], true)) { $where[] = 't.type = ?'; $params[] = $type; }

$history = fetchAllSafe($pdo, "SELECT t.*, i.item_code, i.item_name, i.unit, u.full_name
                             FROM wa_transactions t
                             JOIN wa_items i ON i.id = t.item_id
                             LEFT JOIN users u ON u.id = t.transacted_by
                             WHERE " . implode(' AND ', $where) . "
                             ORDER BY t.transacted_at DESC, t.id DESC", $params);
$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <h4 class="mb-3"><i class="fas fa-exchange-alt me-2 text-primary"></i>Nhập / Xuất vật tư</h4>
    <ul class="nav nav-tabs mb-3" id="tabWrap"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRecord">Ghi nhận</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHistory">Lịch sử</button></li></ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabRecord">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <form id="formTxn" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <div class="col-md-4"><label class="form-label">Vật tư</label><select class="form-select" name="item_id" id="txnItem" required><option value="">-- Chọn --</option><?php foreach($items as $it): ?><option value="<?= (int)$it['id'] ?>" data-stock="<?= e((string)$stockByItem[(int)$it['id']]) ?>"><?= e($it['item_code'].' - '.$it['item_name']) ?></option><?php endforeach; ?></select><div class="form-text">Tồn hiện tại: <strong id="currentStock">0</strong></div></div>
                    <div class="col-md-2"><label class="form-label">Loại</label><select class="form-select" name="type" required><option value="import">Nhập</option><option value="export">Xuất</option></select></div>
                    <div class="col-md-2"><label class="form-label">Số lượng</label><input type="number" min="0.01" step="0.01" class="form-control" name="qty" required></div>
                    <div class="col-md-2"><label class="form-label">Ngày</label><input type="date" class="form-control" name="transacted_at" value="<?= e(date('Y-m-d')) ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Số chứng từ</label><input type="text" class="form-control" name="ref_no"></div>
                    <div class="col-md-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="2"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Lưu giao dịch</button></div>
                </form>
            </div></div>
        </div>
        <div class="tab-pane fade" id="tabHistory">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2"><form class="row g-2" method="get">
                <div class="col-md-2"><input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>"></div>
                <div class="col-md-3"><select name="item_id" class="form-select form-select-sm"><option value="">-- Vật tư --</option><?php foreach($items as $it): ?><option value="<?= (int)$it['id'] ?>" <?= $itemId===(int)$it['id']?'selected':'' ?>><?= e($it['item_code'].' - '.$it['item_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><select name="type" class="form-select form-select-sm"><option value="">-- Loại --</option><option value="import" <?= $type==='import'?'selected':'' ?>>Nhập</option><option value="export" <?= $type==='export'?'selected':'' ?>>Xuất</option></select></div>
                <div class="col-auto"><button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button></div>
            </form></div></div>
            <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-dark"><tr><th>Ngày</th><th>Mã vật tư</th><th>Tên vật tư</th><th>Loại</th><th class="text-end">SL</th><th>Số chứng từ</th><th>Người thực hiện</th><th>Ghi chú</th></tr></thead><tbody>
            <?php if(!$history): ?><tr><td colspan="8" class="text-center text-muted py-4">Chưa có dữ liệu giao dịch</td></tr><?php endif; ?>
            <?php foreach($history as $h): ?><tr><td><?= e(formatDate($h['transacted_at'])) ?></td><td><?= e($h['item_code']) ?></td><td><?= e($h['item_name']) ?></td><td><span class="badge bg-<?= $h['type']==='import'?'success':'danger' ?>"><?= $h['type']==='import'?'Nhập':'Xuất' ?></span></td><td class="text-end"><?= e(number_format((float)$h['qty'],2,',','.')) ?></td><td><?= e($h['ref_no']) ?></td><td><?= e($h['full_name']) ?></td><td><?= e($h['note']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div></div>
        </div>
    </div>
</div></div>
<script>
window.addEventListener('load', function() {
    const selectItem = document.getElementById('txnItem');
    const currentStock = document.getElementById('currentStock');

    if (selectItem) {
        selectItem.addEventListener('change', function() {
            if (currentStock) {
                currentStock.textContent = Number(this.selectedOptions[0]?.dataset.stock || 0).toLocaleString('vi-VN');
            }
        });
    }

    const formTxn = document.getElementById('formTxn');
    if (formTxn) {
        formTxn.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const res = await fetch('/erp/api/warehouse_admin/save_transaction.php', {method: 'POST', body: new FormData(this)});
                if (!res.ok) {
                    alert('Lỗi server (HTTP ' + res.status + '). Vui lòng tải lại trang và thử lại.');
                    return;
                }
                const data = await res.json();
                if (data.ok) {
                    alert('Đã ghi nhận giao dịch');
                    location.reload();
                } else {
                    alert(data.msg || 'Không thể lưu giao dịch');
                }
            } catch (err) {
                alert('Không thể kết nối server. Vui lòng tải lại trang và thử lại.');
            }
        });
    }
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
