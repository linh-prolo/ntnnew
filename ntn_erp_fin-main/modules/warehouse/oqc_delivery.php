<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$customerId = (int)($_GET['customer_id'] ?? 0);
$customers = fetchAllSafe($pdo, 'SELECT id, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name');

$items = [];
if ($customerId > 0) {
    $items = fetchAllSafe($pdo, "SELECT pi.id, po.order_no, pc.product_code, pc.description,
                               pi.qty_done, pi.qty_error,
                               COALESCE(SUM(CASE WHEN di.type = 'done' THEN di.qty_deliver ELSE 0 END), 0) AS delivered_done,
                               COALESCE(SUM(CASE WHEN di.type = 'error' THEN di.qty_deliver ELSE 0 END), 0) AS delivered_error
                               FROM production_items pi
                               JOIN production_orders po ON po.id = pi.order_id
                               JOIN iqc_receipt_items iri ON iri.id = pi.iqc_item_id
                               JOIN iqc_receipts ir ON ir.id = po.iqc_receipt_id
                               JOIN product_codes pc ON pc.id = iri.product_code_id
                               LEFT JOIN oqc_delivery_items di ON di.production_item_id = pi.id
                               WHERE ir.customer_id = ? AND (pi.qty_done > 0 OR pi.qty_error > 0)
                               GROUP BY pi.id
                               ORDER BY pi.id DESC", [$customerId]);
}

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="mb-1"><i class="fas fa-truck me-2 text-primary"></i>Xuất kho / Giao hàng OQC</h4><p class="text-muted mb-0">Tạo phiếu xuất và in biên bản giao hàng.</p></div></div>

    <div class="card border-0 shadow-sm"><div class="card-body">
        <form id="formDelivery">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Khách hàng</label><select name="customer_id" id="customerId" class="form-select" required><option value="">-- Chọn --</option><?php foreach($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $customerId===(int)$c['id']?'selected':'' ?>><?= e($c['customer_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Ngày giao</label><input type="date" class="form-control" name="delivery_date" value="<?= e(date('Y-m-d')) ?>" required></div>
                <div class="col-md-2"><label class="form-label">Người nhận hàng</label><input type="text" class="form-control" name="sender_name"></div>
                <div class="col-md-2"><label class="form-label">Phương tiện</label><input type="text" class="form-control" name="vehicle_plate"></div>
                <div class="col-md-2"><label class="form-label">Tài xế</label><input type="text" class="form-control" name="driver_name"></div>
                <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" rows="2" name="note"></textarea></div>
            </div>
            <hr>
            <h6>Danh sách hàng có thể giao</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-dark"><tr><th><input type="checkbox" id="checkAll" class="form-check-input" title="Tích tất cả"></th><th>Lệnh SX</th><th>Mã hàng</th><th>Tên hàng</th><th>Loại</th><th class="text-end">SL tối đa</th><th class="text-end">SL giao</th></tr></thead>
                    <tbody>
                    <?php if(!$items): ?><tr><td colspan="7" class="text-center text-muted py-4">Vui lòng chọn khách hàng để tải dữ liệu</td></tr><?php endif; ?>
                    <?php
                    // Each product may yield up to 2 rows (done + error); $rowIdx tracks the
                    // absolute row index so every items[N][...] group maps correctly in PHP.
                    $rowIdx = 0;
                    foreach($items as $it):
                        $availableDone = max((float)$it['qty_done'] - (float)$it['delivered_done'], 0);
                        $availableError = max((float)$it['qty_error'] - (float)$it['delivered_error'], 0);
                        if ($availableDone <= 0 && $availableError <= 0) continue;
                    ?>
                    <?php if ($availableDone > 0): ?>
                    <tr>
                        <td><input class="form-check-input pick-row" type="checkbox"></td>
                        <td><?= e($it['order_no']) ?></td>
                        <td><?= e($it['product_code']) ?></td>
                        <td><?= e($it['description']) ?></td>
                        <td><span class="badge bg-success">Thành phẩm</span></td>
                        <td class="text-end text-success"><?= e(number_format($availableDone,2,',','.')) ?></td>
                        <td class="text-end">
                            <input type="hidden" name="items[<?= $rowIdx ?>][production_item_id]" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="items[<?= $rowIdx ?>][type]" value="done">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm row-qty" data-max="<?= e((string)$availableDone) ?>" name="items[<?= $rowIdx ?>][qty_deliver]" value="0">
                        </td>
                    </tr>
                    <?php $rowIdx++; endif; ?>
                    <?php if ($availableError > 0): ?>
                    <tr class="table-danger bg-opacity-25">
                        <td><input class="form-check-input pick-row" type="checkbox"></td>
                        <td><?= e($it['order_no']) ?></td>
                        <td><?= e($it['product_code']) ?></td>
                        <td><?= e($it['description']) ?></td>
                        <td><span class="badge bg-danger">Lỗi-Trả lại</span></td>
                        <td class="text-end text-danger"><?= e(number_format($availableError,2,',','.')) ?></td>
                        <td class="text-end">
                            <input type="hidden" name="items[<?= $rowIdx ?>][production_item_id]" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="items[<?= $rowIdx ?>][type]" value="error">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm row-qty" data-max="<?= e((string)$availableError) ?>" name="items[<?= $rowIdx ?>][qty_deliver]" value="0">
                        </td>
                    </tr>
                    <?php $rowIdx++; endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex gap-2 mt-3"><button class="btn btn-primary" type="submit">Lưu phiếu</button><a id="printLink" class="btn btn-outline-secondary d-none" target="_blank">In biên bản giao hàng</a></div>
        </form>
    </div></div>
</div></div>

<script>
document.getElementById('customerId').addEventListener('change', function(){
  location.href = '/erp/modules/warehouse/oqc_delivery.php?customer_id='+this.value;
});

document.querySelectorAll('.pick-row').forEach(chk=>chk.addEventListener('change', function(){
  const tr = this.closest('tr');
  const qtyInput = tr.querySelector('.row-qty');
  if (this.checked) {
    qtyInput.value = qtyInput.dataset.max || '0';
  } else {
    qtyInput.value = '0';
  }
}));

const checkAll = document.getElementById('checkAll');
if (checkAll) {
  checkAll.addEventListener('change', function(){
    document.querySelectorAll('.pick-row').forEach(chk => {
      chk.checked = this.checked;
      chk.dispatchEvent(new Event('change'));
    });
  });
}

document.getElementById('formDelivery').addEventListener('submit', async function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const valid = [...document.querySelectorAll('.pick-row')].some(chk => chk.checked && Number(chk.closest('tr').querySelector('.row-qty').value) > 0);
  if(!valid){ alert('Vui lòng chọn ít nhất 1 dòng hàng hợp lệ'); return; }
  const res = await fetch('/erp/api/warehouse/save_delivery.php', {method:'POST', body:fd});
  const data = await res.json();
  if(data.ok){
    alert('Đã tạo phiếu ' + data.delivery_no);
    const printLink = document.getElementById('printLink');
    printLink.href = '/erp/modules/warehouse/print_delivery.php?id=' + data.id;
    printLink.classList.remove('d-none');
  } else {
    alert(data.msg || 'Không thể lưu phiếu xuất');
  }
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
