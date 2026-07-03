<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/bkav.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$pdo = getDBConnection();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) die('Không tìm thấy hoá đơn');

$inv = $pdo->prepare("
    SELECT i.*, c.customer_name, c.customer_code, c.address, c.phone, c.tax_code
    FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?
");
$inv->execute([$id]);
$inv = $inv->fetch(PDO::FETCH_ASSOC);
if (!$inv) die('Không tìm thấy hoá đơn');

$items = $pdo->prepare("
    SELECT ii.*, pc.product_code FROM invoice_items ii
    JOIN product_codes pc ON ii.product_code_id = pc.id
    WHERE ii.invoice_id = ? ORDER BY ii.id
");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hoá đơn - <?= htmlspecialchars($inv['invoice_no']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Times New Roman',serif; font-size:13px; padding:15mm 20mm; }
        h2 { text-align:center; font-size:20px; text-transform:uppercase; margin-bottom:4px; }
        h2 .company-topline { display:block; font-size:12px; font-weight:normal; text-transform:none; margin-bottom:2px; }
        h2 .brand-dot { display:inline-block; width:8px; height:8px; background:#c00; margin-right:6px; vertical-align:middle; }
        .subtitle { text-align:center; margin-bottom:16px; }
        .info-table { width:100%; margin-bottom:16px; }
        .info-table td { padding:3px 6px; }
        .label { font-weight:bold; width:130px; }
        table.items { width:100%; border-collapse:collapse; margin-bottom:12px; }
        table.items th, table.items td { border:1px solid #444; padding:5px 8px; }
        table.items th { background:#f0f0f0; text-align:center; }
        .tr { text-align:right; } .tc { text-align:center; }
        .total-section { width:50%; margin-left:auto; border-collapse:collapse; }
        .total-section td { padding:4px 8px; }
        .total-section .lbl { text-align:right; font-weight:bold; }
        .total-section .grand { font-size:15px; font-weight:bold; color:#c00; }
        .sign-area { display:flex; justify-content:space-between; margin-top:30px; }
        .sign-box { text-align:center; width:30%; }
        .sign-box .title { font-weight:bold; margin-bottom:50px; }
        hr { border: none; border-top: 1px solid #999; margin: 6px 0; }
        @media print { body { padding:10mm 15mm; } }
    </style>
</head>
<body onload="window.print()">
    <!-- THÔNG TIN NGƯỜI BÁN -->
    <div style="text-align:center; margin-bottom:8px;">
        <strong style="font-size:14px;"><?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : '' ?></strong><br>
        <span style="font-size:12px;">MST: <?= defined('COMPANY_TAX') ? htmlspecialchars(COMPANY_TAX) : '' ?></span>
        <?php if (defined('COMPANY_ADDRESS') && COMPANY_ADDRESS): ?>
        <br><span style="font-size:12px;"><?= htmlspecialchars(COMPANY_ADDRESS) ?></span>
        <?php endif; ?>
        <?php if (defined('COMPANY_BANK') && COMPANY_BANK): ?>
        <br><span style="font-size:12px;">TK: <?= htmlspecialchars(COMPANY_ACCOUNT ?? '') ?> — <?= htmlspecialchars(COMPANY_BANK) ?></span>
        <?php endif; ?>
    </div>
    <hr style="margin-bottom:8px;">
    <h2>
        <span class="company-topline">
            <span class="brand-dot"></span><?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : '' ?>
        </span>
        Hoá đơn bán hàng
    </h2>
    <div class="subtitle">
        Số: <strong><?= htmlspecialchars($inv['invoice_no']) ?></strong>
        &nbsp;|&nbsp; Ngày: <strong><?= date('d/m/Y', strtotime($inv['invoice_date'])) ?></strong>
        &nbsp;|&nbsp; Hạn TT: <strong><?= $inv['due_date'] ? date('d/m/Y', strtotime($inv['due_date'])) : '—' ?></strong>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Khách hàng:</td>
            <td><strong><?= htmlspecialchars($inv['customer_name'] ?? '—') ?></strong></td>
            <td class="label">Mã KH:</td>
            <td><?= htmlspecialchars($inv['customer_code'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Địa chỉ:</td>
            <td colspan="3"><?= htmlspecialchars($inv['address'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">SĐT:</td>
            <td><?= htmlspecialchars($inv['phone'] ?? '—') ?></td>
            <td class="label">MST:</td>
            <td><?= htmlspecialchars($inv['tax_code'] ?? '—') ?></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="30">STT</th>
                <th width="90">Mã SP</th>
                <th>Tên hàng hoá / dịch vụ</th>
                <th width="55">ĐVT</th>
                <th width="80">SL</th>
                <th width="110">Đơn giá</th>
                <th width="120">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; foreach($items as $it): ?>
        <tr>
            <td class="tc"><?= $i++ ?></td>
            <td class="tc"><?= htmlspecialchars($it['product_code']) ?></td>
            <td><?= htmlspecialchars($it['description']) ?></td>
            <td class="tc"><?= htmlspecialchars($it['unit']) ?></td>
            <td class="tr"><?= number_format($it['quantity']) ?></td>
            <td class="tr"><?= number_format($it['unit_price']) ?></td>
            <td class="tr"><?= number_format($it['total_price']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="total-section">
        <tr><td class="lbl">Tạm tính:</td>
            <td class="tr"><?= number_format($inv['subtotal']) ?> đ</td></tr>
        <?php if ($inv['vat_rate'] > 0): ?>
        <tr><td class="lbl">VAT (<?= $inv['vat_rate'] ?>%):</td>
            <td class="tr"><?= number_format($inv['vat_amount']) ?> đ</td></tr>
        <?php endif; ?>
        <tr><td class="lbl grand">Tổng cộng:</td>
            <td class="tr grand"><?= number_format($inv['total_amount']) ?> đ</td></tr>
    </table>

    <?php if ($inv['note']): ?>
    <p style="margin-top:12px;"><em>Ghi chú: <?= htmlspecialchars($inv['note']) ?></em></p>
    <?php endif; ?>

    <div class="sign-area">
        <div class="sign-box">
            <div class="title">Người mua hàng</div>
            <div>(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="sign-box">
            <div class="title">Kế toán</div>
            <div>(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="sign-box">
            <div class="title">Giám đốc</div>
            <div>(Ký, đóng dấu)</div>
        </div>
    </div>
</body>
</html>