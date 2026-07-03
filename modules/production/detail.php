<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
$order = fetchOneSafe($pdo, "SELECT o.*, r.receipt_no, c.customer_name
                            FROM production_orders o
                            JOIN iqc_receipts r ON r.id = o.iqc_receipt_id
                            JOIN customers c ON c.id = r.customer_id
                            WHERE o.id = ?", [$id]);
if (!$order) { redirect('/erp/modules/production/index.php'); }

$items = fetchAllSafe($pdo, "SELECT pi.*, iri.unit, pc.product_code, pc.description
                           FROM production_items pi
                           JOIN iqc_receipt_items iri ON iri.id = pi.iqc_item_id
                           JOIN product_codes pc ON pc.id = iri.product_code_id
                           WHERE pi.order_id = ?
                           ORDER BY pi.id", [$id]);
$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="mb-1"><i class="fas fa-tasks me-2 text-primary"></i>Chi tiết tiến độ gia công</h4><p class="text-muted mb-0">Lệnh <?= e($order['order_no']) ?> | Khách hàng: <?= e($order['customer_name']) ?> | IQC: <?= e($order['receipt_no']) ?></p></div></div>

    <div class="card border-0 shadow-sm"><div class="card-body">
        <form id="formProgress">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$id ?>">
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead class="table-dark"><tr>
                    <th><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="checkAll"><label class="form-check-label small" for="checkAll">Hoàn<br>thành<br>100%</label></div></th>
                    <th>Mã hàng</th><th>Tên hàng</th><th>ĐVT</th>
                    <th class="text-end">SL Tổng</th>
                    <th class="text-end text-success">SL Hoàn thành</th>
                    <th class="text-end text-danger">SL Lỗi</th>
                    <th class="text-end text-warning">SL Chưa HT</th>
                    <th>Công đoạn</th><th>Ghi chú</th><th>Trạng thái</th>
                </tr></thead>
                <tbody>
                <?php foreach($items as $idx => $it): ?>
                    <?php
                    $pending = max((float)$it['qty_total'] - (float)$it['qty_done'] - (float)$it['qty_error'], 0);
                    $statusLabel = 'Đang gia công';
                    $statusClass = 'warning';
                    if ($pending <= 0) {
                        if ((float)$it['qty_error'] > 0) {
                            $statusLabel = 'Có lỗi';
                            $statusClass = 'danger';
                        } else {
                            $statusLabel = 'Hoàn thành';
                            $statusClass = 'success';
                        }
                    }
                    ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input check-done"></td>
                        <td><?= e($it['product_code']) ?><input type="hidden" name="items[<?= $idx ?>][id]" value="<?= (int)$it['id'] ?>"></td>
                        <td><?= e($it['description']) ?></td>
                        <td><?= e($it['unit']) ?></td>
                        <td class="text-end qty-total"><?= e(number_format((float)$it['qty_total'],2,'.','')) ?></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end qty-done" name="items[<?= $idx ?>][qty_done]" value="<?= e(number_format((float)$it['qty_done'],2,'.','')) ?>"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end qty-error" name="items[<?= $idx ?>][qty_error]" value="<?= e(number_format((float)$it['qty_error'],2,'.','')) ?>"></td>
                        <td class="text-end text-warning fw-semibold qty-pending"><?= e(number_format(max($pending,0),2,'.','')) ?></td>
                        <td><input type="text" class="form-control form-control-sm" name="items[<?= $idx ?>][stage]" value="<?= e($it['stage']) ?>"></td>
                        <td><input type="text" class="form-control form-control-sm" name="items[<?= $idx ?>][note]" value="<?= e($it['note']) ?>"></td>
                        <td><span class="badge bg-<?= $statusClass ?> status-badge"><?= $statusLabel ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>Lưu tiến độ</button>
            <a href="/erp/modules/production/index.php" class="btn btn-outline-secondary">Quay lại</a>
        </form>
    </div></div>
</div></div>
<script>
const rows = document.querySelectorAll('#formProgress tbody tr');
const checkAll = document.getElementById('checkAll');
const FLOAT_TOLERANCE = 0.001;

// Cập nhật trạng thái checkAll (indeterminate / checked / unchecked)
function updateCheckAllState(){
  const rowChecks = document.querySelectorAll('.check-done');
  const checked = document.querySelectorAll('.check-done:checked');
  checkAll.checked = rowChecks.length > 0 && checked.length === rowChecks.length;
  checkAll.indeterminate = checked.length > 0 && checked.length < rowChecks.length;
}

// Cập nhật SL chưa HT, badge trạng thái và màu dòng — KHÔNG chạm vào checkbox
function updateRowPending(tr){
  const total = Number(tr.querySelector('.qty-total').textContent || 0);
  const done  = Number(tr.querySelector('.qty-done').value  || 0);
  const err   = Number(tr.querySelector('.qty-error').value || 0);
  const pending = Math.max(total - done - err, 0);

  tr.querySelector('.qty-pending').textContent = pending.toFixed(2);

  const statusBadge = tr.querySelector('.status-badge');
  let statusClass = 'warning';
  let statusLabel = 'Đang gia công';
  if (pending <= FLOAT_TOLERANCE) {
    if (err > 0) {
      statusClass = 'danger';
      statusLabel = 'Có lỗi';
    } else {
      statusClass = 'success';
      statusLabel = 'Hoàn thành';
    }
  }
  statusBadge.className = `badge bg-${statusClass} status-badge`;
  statusBadge.textContent = statusLabel;
  tr.classList.toggle('table-danger', err > 0);
}

// Sync checkbox của 1 dòng dựa vào giá trị nhập tay (chỉ gọi khi user tự gõ số)
function syncRowCheckFromInput(tr){
  const total = Number(tr.querySelector('.qty-total').textContent || 0);
  const done  = Number(tr.querySelector('.qty-done').value  || 0);
  const err   = Number(tr.querySelector('.qty-error').value || 0);
  const cb    = tr.querySelector('.check-done');
  cb.checked  = total > 0 && Math.abs(done - total) < FLOAT_TOLERANCE && Math.abs(err) < FLOAT_TOLERANCE;
  updateCheckAllState();
}

rows.forEach(tr => {
  const doneInput = tr.querySelector('.qty-done');
  const errInput  = tr.querySelector('.qty-error');
  const rowCheck  = tr.querySelector('.check-done');

  // Khi user gõ số → cập nhật pending + sync checkbox
  doneInput.addEventListener('input', () => { updateRowPending(tr); syncRowCheckFromInput(tr); });
  errInput.addEventListener('input',  () => { updateRowPending(tr); syncRowCheckFromInput(tr); });

  // Khi user tick checkbox → điền / reset số lượng
  rowCheck.addEventListener('change', () => {
    const total = Number(tr.querySelector('.qty-total').textContent || 0);
    doneInput.value = rowCheck.checked ? total.toFixed(2) : '0.00';
    errInput.value  = '0.00';
    updateRowPending(tr);
    updateCheckAllState();
  });

  // Khởi tạo ban đầu
  updateRowPending(tr);
  syncRowCheckFromInput(tr);
});

// Checkbox "tích tất cả"
checkAll.addEventListener('change', () => {
  rows.forEach(tr => {
    const cb    = tr.querySelector('.check-done');
    const total = Number(tr.querySelector('.qty-total').textContent || 0);
    const done  = tr.querySelector('.qty-done');
    const err   = tr.querySelector('.qty-error');
    cb.checked  = checkAll.checked;
    done.value  = checkAll.checked ? total.toFixed(2) : '0.00';
    err.value   = '0.00';
    updateRowPending(tr);
  });
  updateCheckAllState();
});

document.getElementById('formProgress').addEventListener('submit', async function(e){
  e.preventDefault();
  let valid = true;
  rows.forEach(tr=>{
    const total = Number(tr.querySelector('.qty-total').textContent || 0);
    const done  = Number(tr.querySelector('.qty-done').value  || 0);
    const err   = Number(tr.querySelector('.qty-error').value || 0);
    if (done + err > total + FLOAT_TOLERANCE) valid = false;
  });
  if(!valid){ alert('SL hoàn thành + lỗi không được vượt quá SL tổng'); return; }
  const res  = await fetch('/erp/api/production/save_progress.php', {method:'POST', body:new FormData(this)});
  const data = await res.json();
  if(data.ok){ alert('Đã lưu tiến độ'); location.reload(); }
  else alert(data.msg || 'Không thể lưu');
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
