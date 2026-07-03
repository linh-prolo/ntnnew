<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
$delivery = fetchOneSafe($pdo, "SELECT d.*, c.customer_name, c.address, c.phone, c.tax_code
                              FROM oqc_deliveries d
                              LEFT JOIN customers c ON d.customer_id = c.id
                              WHERE d.id = ?", [$id]);

if (!$delivery) {
    echo 'Không tìm thấy phiếu giao hàng';
    exit;
}

$items = fetchAllSafe($pdo, "SELECT di.*, pi.qty_total, pi.qty_done, pi.qty_error,
                           pc.product_code, pc.description, iri.unit
                           FROM oqc_delivery_items di
                           JOIN production_items pi ON di.production_item_id = pi.id
                           JOIN iqc_receipt_items iri ON pi.iqc_item_id = iri.id
                           JOIN product_codes pc ON iri.product_code_id = pc.id
                           WHERE di.delivery_id = ?
                           ORDER BY pc.product_code, FIELD(di.type, 'done', 'error')", [$id]);

$totalQty = 0;
$totalDone = 0;
$totalError = 0;
foreach ($items as $it) {
    $qty = (float)$it['qty_deliver'];
    $totalQty += $qty;
    if ($it['type'] === 'done') { $totalDone += $qty; } else { $totalError += $qty; }
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Biên bản giao hàng</title>
<style>
body{font-family:DejaVu Sans,Arial,sans-serif;color:#222;font-size:14px}.wrap{max-width:1000px;margin:0 auto;padding:24px}.head{text-align:center;margin-bottom:16px}.head h2{margin:8px 0 0}.meta{display:flex;justify-content:space-between;gap:20px;margin-bottom:16px}.box{border:1px solid #ddd;padding:12px;border-radius:8px;flex:1}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ccc;padding:8px}th{background:#f5f5f5}.text-end{text-align:right}.type-done{color:#198754;font-weight:600}.type-error{color:#dc3545;font-weight:600}.sign{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:36px;text-align:center}.sign div{min-height:120px}.footer{text-align:center;margin-top:20px;color:#666}@media print{.no-print{display:none}.wrap{padding:0}}
</style>
</head><body>
<div class="wrap">
    <div class="head">
        <div><strong>CÔNG TY CỔ PHẦN SẢN XUẤT VÀ CUNG ỨNG NTN VIỆT NAM</strong></div>
        <div style="font-size:12px;color:#555;">NTN VIETNAM PRODUCE AND SUPPLY JOINT STOCK COMPANY</div>
        <div style="font-size:12px;color:#555;">Số 36, Xóm Trại, Quan Âm, Xã Phúc Thịnh, Thành phố Hà Nội | ĐT: 0966459663</div>
        <h2>BIÊN BẢN GIAO HÀNG</h2>
        <div>Số phiếu: <strong><?= e($delivery['delivery_no']) ?></strong></div>
    </div>

    <div class="meta">
        <div class="box">
            <div><strong>Khách hàng:</strong> <?= e($delivery['customer_name']) ?></div>
            <div><strong>Địa chỉ:</strong> <?= e($delivery['address']) ?></div>
            <div><strong>SĐT:</strong> <?= e($delivery['phone'] ?? '—') ?></div>
            <div><strong>MST:</strong> <?= e($delivery['tax_code'] ?? '—') ?></div>
        </div>
        <div class="box">
            <div><strong>Ngày giao:</strong> <?= e(formatDate($delivery['delivery_date'])) ?></div>
            <div><strong>Người nhận hàng:</strong> <?= e($delivery['sender_name']) ?></div>
            <div><strong>Phương tiện:</strong> <?= e($delivery['vehicle_plate']) ?> | <strong>Tài xế:</strong> <?= e($delivery['driver_name']) ?></div>
        </div>
    </div>

    <table>
        <thead><tr><th>STT</th><th>Mã hàng</th><th>Tên hàng</th><th>Loại</th><th class="text-end">Số lượng</th><th>ĐVT</th><th>Ghi chú</th></tr></thead>
        <tbody>
        <?php foreach($items as $i => $it): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($it['product_code']) ?></td>
                <td><?= e($it['description']) ?></td>
                <td class="<?= $it['type']==='error'?'type-error':'type-done' ?>"><?= $it['type']==='error' ? 'Lỗi-Trả lại' : 'Thành phẩm' ?></td>
                <td class="text-end"><?= e(number_format((float)$it['qty_deliver'], 2, ',', '.')) ?></td>
                <td><?= e($it['unit']) ?></td>
                <td><?= e($it['note']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php if ($totalDone > 0): ?>
            <tr><th colspan="4" class="text-end type-done">Tổng hoàn thành</th><th class="text-end type-done"><?= e(number_format($totalDone, 2, ',', '.')) ?></th><th colspan="2"></th></tr>
            <?php endif; ?>
            <?php if ($totalError > 0): ?>
            <tr><th colspan="4" class="text-end type-error">Tổng lỗi-trả lại</th><th class="text-end type-error"><?= e(number_format($totalError, 2, ',', '.')) ?></th><th colspan="2"></th></tr>
            <?php endif; ?>
            <tr><th colspan="4" class="text-end">Tổng số lượng</th><th class="text-end"><?= e(number_format($totalQty, 2, ',', '.')) ?></th><th colspan="2"></th></tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><strong>Người giao</strong><br><em>(Ký, ghi rõ họ tên)</em></div>
        <div><strong>Thủ kho</strong><br><em>(Ký, ghi rõ họ tên)</em></div>
        <div><strong>Kế toán</strong><br><em>(Ký, ghi rõ họ tên)</em></div>
        <div><strong>Khách hàng</strong><br><em>(Ký, ghi rõ họ tên)</em></div>
    </div>

    <div class="footer">Ghi chú: Biên bản giao hàng gia công không thể hiện đơn giá và thành tiền.</div>
    <div class="no-print" style="margin-top:16px;text-align:center"><button onclick="window.print()">In biên bản</button></div>
</div>
</body></html>
