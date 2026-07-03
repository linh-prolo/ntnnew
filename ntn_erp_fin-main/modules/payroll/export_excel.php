<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo      = getDBConnection();
$periodId = (int)($_GET['period_id'] ?? 0);
if (!$periodId) {
    header('Location: /erp/modules/payroll/index.php'); exit;
}

// ── Thông tin kỳ lương ──────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM payroll_periods WHERE id = ?");
$stmt->execute([$periodId]);
$period = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$period) {
    die('Kỳ lương không tồn tại.');
}

// ── Danh sách phiếu lương ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        ps.*,
        u.full_name,
        u.email,
        u.employee_code,
        d.name        AS department_name,
        ep.identity_no,
        ep.personal_tax_code,
        ep.social_book_no,
        ep.bank_account,
        ep.bank_name,
        ep.date_joined,
        ep.dependants AS ep_dependants
    FROM payroll_slips ps
    JOIN users u              ON ps.user_id   = u.id
    LEFT JOIN employee_profiles ep ON ep.user_id = u.id
    LEFT JOIN departments d        ON u.department_id = d.id
    WHERE ps.period_id = ?
    ORDER BY d.name, u.full_name
");
$stmt->execute([$periodId]);
$slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Tên file ────────────────────────────────────────────────────
$fileName = 'BangLuong_T' . $period['period_month'] . '_' . $period['period_year'] . '.xls';

// ── Header xuất Excel ───────────────────────────────────────────
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// ── Helper ──────────────────────────────────────────────────────
function n(float $val, int $dec = 0): string {
    return number_format($val, $dec, '.', ',');
}
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml><x:ExcelWorkbook><x:ExcelWorksheets>
<x:ExcelWorksheet>
<x:Name>Bảng lương T<?= $period['period_month'] ?>-<?= $period['period_year'] ?></x:Name>
<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
</x:ExcelWorksheet>
</x:ExcelWorksheets></x:ExcelWorkbook></xml>
<![endif]-->
<style>
    body  { font-family: Times New Roman; font-size: 11pt; }
    table { border-collapse: collapse; }
    td, th {
        border: 1px solid #000;
        padding: 3px 6px;
        font-size: 10pt;
        vertical-align: middle;
        mso-number-format: "\@";   /* mặc định: text */
    }
    .num  { mso-number-format: "\#\,\#\#0";   text-align: right; }
    .num2 { mso-number-format: "\#\,\#\#0\.00"; text-align: right; }
    .ctr  { text-align: center; }
    .hdr1 { background: #1e3a5f; color: #fff; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr2 { background: #2e5f9e; color: #fff; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-ot        { background: #bdd7ee; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-allow     { background: #c6efce; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-bonus     { background: #ffeb9c; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-kpi       { background: #b8e0f7; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-deduct    { background: #ffc7ce; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-manual    { background: #fce4d6; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-ref       { background: #d9d9d9; font-weight: bold; text-align: center; font-size: 9pt; }
    .hdr-net       { background: #00b050; color: #fff; font-weight: bold; text-align: center; }
    .foot          { background: #f2f2f2; font-weight: bold; }
    .foot-net      { background: #00b050; color: #fff; font-weight: bold; text-align: right; }
    .neg           { color: #c00000; }
    .pos           { color: #00602a; }
    .title-row td  { border: none; }
</style>
</head>
<body>

<table>
    <!-- ── Tiêu đề ── -->
    <tr class="title-row">
        <td colspan="50" style="font-size:14pt;font-weight:bold;text-align:center;border:none;">
            BẢNG THANH TOÁN LƯƠNG THÁNG <?= $period['period_month'] ?>/<?= $period['period_year'] ?>
        </td>
    </tr>
    <tr class="title-row">
        <td colspan="50" style="text-align:center;color:#555;border:none;font-size:10pt;">
            Kỳ: <?= date('d/m/Y', strtotime($period['period_from'])) ?>
            – <?= date('d/m/Y', strtotime($period['period_to'])) ?>
            &nbsp;·&nbsp; <?= $period['working_days'] ?> ngày công chuẩn
            &nbsp;·&nbsp; <?= count($slips) ?> nhân viên
            &nbsp;·&nbsp; Xuất ngày: <?= date('d/m/Y H:i') ?>
        </td>
    </tr>
    <tr class="title-row"><td colspan="50" style="border:none;">&nbsp;</td></tr>

    <!-- ══════════════════════════════════════════════════════════
         HEADER ROW 1 – nhóm cột
    ══════════════════════════════════════════════════════════ -->
    <tr>
        <th class="hdr1" rowspan="2">#</th>
        <th class="hdr1" rowspan="2">Mã NV</th>
        <th class="hdr1" rowspan="2">Họ và tên</th>
        <th class="hdr1" rowspan="2">Phòng ban</th>
        <th class="hdr1" rowspan="2">CCCD</th>
        <th class="hdr1" rowspan="2">MST cá nhân</th>
        <th class="hdr1" rowspan="2">Sổ BHXH</th>
        <th class="hdr1" rowspan="2">Ngày vào làm</th>
        <th class="hdr1" rowspan="2">Số TK ngân hàng</th>
        <th class="hdr1" rowspan="2">Ngân hàng</th>

        <!-- Ngày công -->
        <th class="hdr2" rowspan="2">Ngày công chuẩn</th>
        <th class="hdr2" rowspan="2">Ngày làm thực tế</th>
        <th class="hdr2" rowspan="2">Nghỉ phép có lương</th>
        <th class="hdr2" rowspan="2">Nghỉ không lương</th>
        <th class="hdr2" rowspan="2">Tổng ngày hưởng lương</th>
        <th class="hdr2" rowspan="2">Giờ đi muộn/về sớm</th>

        <!-- Lương cơ bản -->
        <th class="hdr2" rowspan="2">Lương CB hợp đồng</th>
        <th class="hdr2" rowspan="2">Lương CB thực nhận</th>

        <!-- OT -->
        <th class="hdr-ot" colspan="4">Làm thêm giờ (OT)</th>

        <!-- Trợ cấp -->
        <th class="hdr-allow" colspan="6">Trợ cấp thực nhận</th>

        <!-- Thưởng -->
        <th class="hdr-bonus" colspan="3">Phụ cấp & Thưởng</th>

        <!-- KPI -->
        <th class="hdr-kpi" colspan="2">KPI</th>

        <!-- Gross -->
        <th class="hdr-net" rowspan="2">GROSS SALARY</th>

        <!-- Khấu trừ -->
        <th class="hdr-deduct" colspan="7">Khấu trừ</th>

        <!-- Điều chỉnh -->
        <th class="hdr-manual" colspan="4">Điều chỉnh / Khác</th>

        <!-- NET -->
        <th class="hdr-net" rowspan="2">THU NHẬP THỰC NHẬN (NET)</th>
        <th class="hdr-net" rowspan="2">CHUYỂN KHOẢN</th>

        <!-- Tham khảo -->
        <th class="hdr-ref" colspan="4">Thông tin tham khảo</th>
    </tr>

    <!-- ══════════════════════════════════════════════════════════
         HEADER ROW 2 – chi tiết cột
    ══════════════════════════════════════════════════════════ -->
    <tr>
        <!-- OT chi tiết -->
        <th class="hdr-ot">Ngày thường (×1.5)<br>Số giờ</th>
        <th class="hdr-ot">Ngày thường (×1.5)<br>Số tiền</th>
        <th class="hdr-ot">Ngày nghỉ (×2.0)<br>Số giờ</th>
        <th class="hdr-ot">Ngày nghỉ (×2.0)<br>Số tiền</th>

        <!-- Trợ cấp chi tiết -->
        <th class="hdr-allow">Ăn ca</th>
        <th class="hdr-allow">Trang phục</th>
        <th class="hdr-allow">Điện thoại</th>
        <th class="hdr-allow">Đi lại</th>
        <th class="hdr-allow">Nhà ở</th>
        <th class="hdr-allow">Tổng trợ cấp</th>

        <!-- Thưởng chi tiết -->
        <th class="hdr-bonus">Hiệu quả</th>
        <th class="hdr-bonus">Chuyên cần</th>
        <th class="hdr-bonus">Thưởng khác</th>

        <!-- KPI chi tiết -->
        <th class="hdr-kpi">Thưởng KPI</th>
        <th class="hdr-kpi">Trừ KPI</th>

        <!-- Khấu trừ chi tiết -->
        <th class="hdr-deduct">BHXH NV (10.5%)</th>
        <th class="hdr-deduct">Số người PT</th>
        <th class="hdr-deduct">Giảm trừ GC</th>
        <th class="hdr-deduct">TN tính thuế</th>
        <th class="hdr-deduct">Thuế TNCN</th>
        <th class="hdr-deduct">Trừ đi muộn</th>
        <th class="hdr-deduct">Tạm ứng</th>

        <!-- Điều chỉnh chi tiết -->
        <th class="hdr-manual">Thu nhập khác</th>
        <th class="hdr-manual">Thưởng HS</th>
        <th class="hdr-manual">Điều chỉnh</th>
        <th class="hdr-manual">Tất toán phép tồn</th>

        <!-- Tham khảo chi tiết -->
        <th class="hdr-ref">BHXH Công ty (21.5%)</th>
        <th class="hdr-ref">Phép tồn (ngày)</th>
        <th class="hdr-ref">Đã dùng (ngày)</th>
        <th class="hdr-ref">Ghi chú</th>
    </tr>

    <!-- ══════════════════════════════════════════════════════════
         DATA ROWS
    ══════════════════════════════════════════════════════════ -->
    <?php
    // Biến tổng
    $totals = array_fill_keys([
        'basic_salary','basic_salary_received',
        'ot_weekday_hours','ot_weekday_amount',
        'ot_weekend_hours','ot_weekend_amount',
        'meal_received','clothes_received','phone_received',
        'transport_received','housing_received',
        'performance_bonus','attendance_bonus','other_bonus',
        'kpi_bonus','kpi_deduction',
        'gross_salary',
        'si_employee','pit_amount','late_deduction','advance_payment',
        'other_income','other_bonus2','adjustment','annual_leave_payout',
        'net_salary','bank_transfer','si_company',
    ], 0);

    foreach ($slips as $i => $s):
        $paidLeave   = (float)$s['paid_leave_days'] + (float)$s['other_paid_leave_days'];
        $gcDeduct    = (float)$s['personal_deduction'] + (float)$s['dependant_deduction'];
        $housing     = (float)($s['housing_received'] ?? 0);
        $totalAllow  = (float)$s['meal_received']
                     + (float)$s['clothes_received']
                     + (float)$s['phone_received']
                     + (float)$s['transport_received']
                     + $housing;
        $kpiBonus    = (float)($s['kpi_bonus']     ?? 0);
        $kpiDeduct   = (float)($s['kpi_deduction'] ?? 0);

        // Tích lũy tổng
        $totals['basic_salary']          += (float)$s['basic_salary'];
        $totals['basic_salary_received'] += (float)$s['basic_salary_received'];
        $totals['ot_weekday_hours']      += (float)$s['ot_weekday_hours'];
        $totals['ot_weekday_amount']     += (float)$s['ot_weekday_amount'];
        $totals['ot_weekend_hours']      += (float)$s['ot_weekend_hours'];
        $totals['ot_weekend_amount']     += (float)$s['ot_weekend_amount'];
        $totals['meal_received']         += (float)$s['meal_received'];
        $totals['clothes_received']      += (float)$s['clothes_received'];
        $totals['phone_received']        += (float)$s['phone_received'];
        $totals['transport_received']    += (float)$s['transport_received'];
        $totals['housing_received']      += $housing;
        $totals['performance_bonus']     += (float)$s['performance_bonus'];
        $totals['attendance_bonus']      += (float)$s['attendance_bonus'];
        $totals['other_bonus']           += (float)$s['other_bonus'];
        $totals['kpi_bonus']             += $kpiBonus;
        $totals['kpi_deduction']         += $kpiDeduct;
        $totals['gross_salary']          += (float)$s['gross_salary'];
        $totals['si_employee']           += (float)$s['si_employee'];
        $totals['pit_amount']            += (float)$s['pit_amount'];
        $totals['late_deduction']        += (float)$s['late_deduction'];
        $totals['advance_payment']       += (float)$s['advance_payment'];
        $totals['other_income']          += (float)$s['other_income'];
        $totals['adjustment']            += (float)$s['adjustment'];
        $totals['annual_leave_payout']   += (float)($s['annual_leave_payout'] ?? 0);
        $totals['net_salary']            += (float)$s['net_salary'];
        $totals['bank_transfer']         += (float)$s['bank_transfer'];
        $totals['si_company']            += (float)$s['si_company'];
    ?>
    <tr>
        <!-- Thông tin cơ bản -->
        <td class="ctr"><?= $i + 1 ?></td>
        <td class="ctr"><?= e($s['employee_code'] ?? '—') ?></td>
        <td><?= e($s['full_name']) ?></td>
        <td><?= e($s['department_name'] ?? '—') ?></td>
        <td class="ctr"><?= e($s['identity_no'] ?? '—') ?></td>
        <td class="ctr"><?= e($s['personal_tax_code'] ?? '—') ?></td>
        <td class="ctr"><?= e($s['social_book_no'] ?? '—') ?></td>
        <td class="ctr"><?= $s['date_joined'] ? date('d/m/Y', strtotime($s['date_joined'])) : '—' ?></td>
        <td class="ctr"><?= e($s['bank_account'] ?? '—') ?></td>
        <td><?= e($s['bank_name'] ?? '—') ?></td>

        <!-- Ngày công -->
        <td class="num2"><?= n($s['working_days_standard'], 1) ?></td>
        <td class="num2"><?= n($s['actual_workdays'], 1) ?></td>
        <td class="num2"><?= n($paidLeave, 1) ?></td>
        <td class="num2 neg"><?= n($s['unpaid_leave_days'], 1) ?></td>
        <td class="num2"><?= n($s['total_paid_days'], 1) ?></td>
        <td class="num2 neg"><?= n($s['late_early_hours'], 2) ?></td>

        <!-- Lương CB -->
        <td class="num"><?= n($s['basic_salary']) ?></td>
        <td class="num"><?= n($s['basic_salary_received']) ?></td>

        <!-- OT -->
        <td class="num2"><?= n($s['ot_weekday_hours'], 2) ?></td>
        <td class="num"><?= n($s['ot_weekday_amount']) ?></td>
        <td class="num2"><?= n($s['ot_weekend_hours'], 2) ?></td>
        <td class="num"><?= n($s['ot_weekend_amount']) ?></td>

        <!-- Trợ cấp -->
        <td class="num"><?= n($s['meal_received']) ?></td>
        <td class="num"><?= n($s['clothes_received']) ?></td>
        <td class="num"><?= n($s['phone_received']) ?></td>
        <td class="num"><?= n($s['transport_received']) ?></td>
        <td class="num pos"><?= n($housing) ?></td>
        <td class="num"><?= n($totalAllow) ?></td>

        <!-- Thưởng -->
        <td class="num"><?= n($s['performance_bonus']) ?></td>
        <td class="num pos"><?= n($s['attendance_bonus']) ?></td>
        <td class="num"><?= n($s['other_bonus']) ?></td>

        <!-- KPI -->
        <td class="num pos"><?= $kpiBonus > 0 ? n($kpiBonus) : '—' ?></td>
        <td class="num neg"><?= $kpiDeduct > 0 ? n($kpiDeduct) : '—' ?></td>

        <!-- GROSS -->
        <td class="num" style="font-weight:bold;"><?= n($s['gross_salary']) ?></td>

        <!-- Khấu trừ -->
        <td class="num neg"><?= n($s['si_employee']) ?></td>
        <td class="ctr"><?= (int)$s['dependants'] ?></td>
        <td class="num"><?= n($gcDeduct) ?></td>
        <td class="num"><?= n($s['taxable_income']) ?></td>
        <td class="num neg"><?= n($s['pit_amount']) ?></td>
        <td class="num neg"><?= n($s['late_deduction']) ?></td>
        <td class="num neg"><?= (float)$s['advance_payment'] > 0 ? n($s['advance_payment']) : '—' ?></td>

        <!-- Điều chỉnh -->
        <td class="num"><?= n($s['other_income']) ?></td>
        <td class="num"><?= n($s['performance_bonus']) ?></td>
        <td class="num <?= (float)$s['adjustment'] < 0 ? 'neg' : '' ?>"><?= n($s['adjustment']) ?></td>
        <td class="num"><?= n($s['annual_leave_payout'] ?? 0) ?></td>

        <!-- NET -->
        <td class="num" style="font-weight:bold;color:#00602a;"><?= n($s['net_salary']) ?></td>
        <td class="num" style="font-weight:bold;"><?= n($s['bank_transfer']) ?></td>

        <!-- Tham khảo -->
        <td class="num"><?= n($s['si_company']) ?></td>
        <td class="num2"><?= n($s['annual_leave_remaining'], 1) ?></td>
        <td class="num2"><?= n($s['annual_leave_used'], 1) ?></td>
        <td><?= e($s['remark'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- ══════════════════════════════════════════════════════════
         FOOTER – TỔNG CỘNG
    ══════════════════════════════════════════════════════════ -->
    <tr>
        <td class="foot ctr" colspan="10">TỔNG CỘNG (<?= count($slips) ?> nhân viên)</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot num"><?= n($totals['basic_salary']) ?></td>
        <td class="foot num"><?= n($totals['basic_salary_received']) ?></td>
        <td class="foot num2"><?= n($totals['ot_weekday_hours'], 2) ?></td>
        <td class="foot num"><?= n($totals['ot_weekday_amount']) ?></td>
        <td class="foot num2"><?= n($totals['ot_weekend_hours'], 2) ?></td>
        <td class="foot num"><?= n($totals['ot_weekend_amount']) ?></td>
        <td class="foot num"><?= n($totals['meal_received']) ?></td>
        <td class="foot num"><?= n($totals['clothes_received']) ?></td>
        <td class="foot num"><?= n($totals['phone_received']) ?></td>
        <td class="foot num"><?= n($totals['transport_received']) ?></td>
        <td class="foot num pos"><?= n($totals['housing_received']) ?></td>
        <td class="foot num"><?= n($totals['meal_received']+$totals['clothes_received']+$totals['phone_received']+$totals['transport_received']+$totals['housing_received']) ?></td>
        <td class="foot num"><?= n($totals['performance_bonus']) ?></td>
        <td class="foot num"><?= n($totals['attendance_bonus']) ?></td>
        <td class="foot num"><?= n($totals['other_bonus']) ?></td>
        <td class="foot num pos"><?= n($totals['kpi_bonus']) ?></td>
        <td class="foot num neg"><?= n($totals['kpi_deduction']) ?></td>
        <td class="foot-net"><?= n($totals['gross_salary']) ?></td>
        <td class="foot num neg"><?= n($totals['si_employee']) ?></td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot num neg"><?= n($totals['pit_amount']) ?></td>
        <td class="foot num neg"><?= n($totals['late_deduction']) ?></td>
        <td class="foot num neg"><?= n($totals['advance_payment']) ?></td>
        <td class="foot num"><?= n($totals['other_income']) ?></td>
        <td class="foot num">—</td>
        <td class="foot num"><?= n($totals['adjustment']) ?></td>
        <td class="foot num"><?= n($totals['annual_leave_payout']) ?></td>
        <td class="foot-net"><?= n($totals['net_salary']) ?></td>
        <td class="foot-net"><?= n($totals['bank_transfer']) ?></td>
        <td class="foot num"><?= n($totals['si_company']) ?></td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
        <td class="foot ctr">—</td>
    </tr>

    <!-- Dòng trống -->
    <tr><td colspan="50" style="border:none;">&nbsp;</td></tr>

    <!-- ── Ký tên ── -->
    <tr>
        <td colspan="50" style="border:none;font-size:10pt;text-align:right;color:#555;">
            Ngày xuất: <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border:none;text-align:center;font-weight:bold;">Người lập bảng<br><br><br><br>...................</td>
        <td colspan="13" style="border:none;text-align:center;font-weight:bold;">Kế toán trưởng<br><br><br><br>...................</td>
        <td colspan="13" style="border:none;text-align:center;font-weight:bold;">Giám đốc<br>(Ký, đóng dấu)<br><br><br>...................</td>
        <td colspan="12" style="border:none;"></td>
    </tr>
</table>

</body>
</html>