<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? '')) ? (string)$_GET['month'] : '';
$customerId = (int)($_GET['customer_id'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));

$where = ['1=1'];
$params = [];
if ($month !== '') { $where[] = "DATE_FORMAT(o.created_at, '%Y-%m') = ?"; $params[] = $month; }
if ($customerId > 0) { $where[] = 'r.customer_id = ?'; $params[] = $customerId; }
if (in_array($status, ['pending','in_progress','done'], true)) { $where[] = 'o.status = ?'; $params[] = $status; }

$customers = fetchAllSafe($pdo, 'SELECT id, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name');
$orders = fetchAllSafe($pdo, "SELECT o.id, o.order_no, o.status, o.created_at, r.receipt_no, c.customer_name,
                           COALESCE(SUM(pi.qty_total),0) AS qty_total,
                           COALESCE(SUM(pi.qty_done),0) AS qty_done,
                           COALESCE(SUM(pi.qty_error),0) AS qty_error
                           FROM production_orders o
                           JOIN iqc_receipts r ON r.id = o.iqc_receipt_id
                           JOIN customers c ON c.id = r.customer_id
                           LEFT JOIN production_items pi ON pi.order_id = o.id
                           WHERE " . implode(' AND ', $where) . "
                           GROUP BY o.id
                           ORDER BY o.id DESC", $params);

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content"><div class="container-fluid py-4">
    <div class="mb-4"><h4 class="mb-1"><i class="fas fa-industry me-2 text-primary"></i>Tiến độ gia công</h4><p class="text-muted mb-0">Danh sách lệnh sản xuất từ IQC.</p></div>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2"><form class="row g-2" method="get">
        <div class="col-md-2"><input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>"></div>
        <div class="col-md-3"><select name="customer_id" class="form-select form-select-sm"><option value="">-- Tất cả khách hàng --</option><?php foreach($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $customerId===(int)$c['id']?'selected':'' ?>><?= e($c['customer_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">-- Trạng thái --</option><?php foreach(['pending','in_progress','done'] as $st): ?><option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= e($st==='pending'?'Chờ':''.($st==='in_progress'?'Đang gia công':'Hoàn thành')) ?></option><?php endforeach; ?></select></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button></div>
        <div class="col-auto"><a href="/erp/modules/production/index.php" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    </form></div></div>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-dark"><tr><th>Số lệnh</th><th>Ngày tạo</th><th>Khách hàng</th><th>Số phiếu IQC</th><th class="text-end">Tổng SP</th><th class="text-end">Đã HT</th><th class="text-end">Lỗi</th><th class="text-end">Còn lại</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php if(!$orders): ?><tr><td colspan="9" class="text-center text-muted py-4">Không có lệnh sản xuất</td></tr><?php endif; ?>
        <?php foreach($orders as $row): $pending=(float)$row['qty_total']-(float)$row['qty_done']-(float)$row['qty_error']; $statusLabel = $pending > 0 ? 'Đang gia công' : 'Hoàn thành'; $statusClass = $pending > 0 ? 'warning text-dark' : 'success'; ?>
            <tr style="cursor:pointer" tabindex="0" onclick="window.location='/erp/modules/production/detail.php?id=<?= (int)$row['id'] ?>'" onkeydown="if(event.key==='Enter'){window.location='/erp/modules/production/detail.php?id=<?= (int)$row['id'] ?>'}">
                <td class="fw-semibold"><?= e($row['order_no']) ?></td>
                <td><?= e(formatDateTime($row['created_at'])) ?></td>
                <td><?= e($row['customer_name']) ?></td>
                <td><?= e($row['receipt_no']) ?></td>
                <td class="text-end"><?= e(number_format((float)$row['qty_total'],2,',','.')) ?></td>
                <td class="text-end text-success"><?= e(number_format((float)$row['qty_done'],2,',','.')) ?></td>
                <td class="text-end text-danger"><?= e(number_format((float)$row['qty_error'],2,',','.')) ?></td>
                <td class="text-end text-warning"><?= e(number_format(max($pending,0),2,',','.')) ?></td>
                <td><span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div></div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
