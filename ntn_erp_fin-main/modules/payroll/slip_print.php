<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$pdo  = getDBConnection();
$user = currentUser();

$slipId = (int)($_GET['id'] ?? 0);
if (!$slipId) {
    header('Location: /erp/modules/payroll/index.php'); exit;
}

// Quyền: GĐ/KT xem tất cả, nhân viên chỉ xem của mình
$isManager = hasRole('director', 'accountant', 'manager');

$sql = "
    SELECT ps.*,
           pp.period_month, pp.period_year,
           pp.period_from,  pp.period_to,
           pp.working_days  AS period_working_days,
           pp.status        AS period_status,
           pp.approved_at,  pp.locked_at,
           u.full_name,     u.email,          u.employee_code,
           d.name           AS department_name,
           ep.bank_account, ep.bank_name,     ep.bank_branch,
           ep.identity_no,  ep.personal_tax_code,
           ep.social_book_no,ep.dependants    AS ep_dependants,
           ep.date_joined
    FROM payroll_slips ps
    JOIN payroll_periods pp ON ps.period_id = pp.id
    JOIN users u            ON ps.user_id   = u.id
    LEFT JOIN employee_profiles ep ON ep.user_id = u.id
    LEFT JOIN departments d        ON u.department_id = d.id
    WHERE ps.id = ?
";

if (!$isManager) {
    $sql .= " AND ps.user_id = ? AND pp.status IN ('approved','locked')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slipId, $user['id']]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slipId]);
}

$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#dc3545;">
         ❌ Không tìm thấy phiếu lương hoặc bạn không có quyền xem.</div>');
}

// ── Công ty (lấy từ settings nếu có, fallback hardcode) ──────────────────
$companyName    = 'CÔNG TY CỔ PHẦN SẢN XUẤT VÀ CUNG ỨNG NTN VIỆT NAM';
$companyAddress = 'Số 36, Xóm Trại, Quan Âm, Xã Phúc Thịnh, Thành phố Hà Nội, Việt Nam';
$companyTax     = '';
try {
    $cfg = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('company_name','company_address','company_tax')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $companyName    = $cfg['company_name']    ?? $companyName;
    $companyAddress = $cfg['company_address'] ?? $companyAddress;
    $companyTax     = $cfg['company_tax']     ?? $companyTax;
} catch (\Throwable $e) { /* bỏ qua nếu bảng chưa có */ }

// ── Helper: số tiền thành chữ (tiếng Việt) ───────────────────────────────
function numberToWords(int $n): string {
    if ($n === 0) return 'Không đồng';
    $ones  = ['','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'];
    $teens = ['mười','mười một','mười hai','mười ba','mười bốn','mười lăm',
              'mười sáu','mười bảy','mười tám','mười chín'];
    function readHundreds(int $n, array $ones, array $teens): string {
        $result = '';
        if ($n >= 100) { $result .= $ones[intdiv($n,100)].' trăm '; $n %= 100; }
        if ($n >= 20)  { $result .= $ones[intdiv($n,10)].' mươi ';  $n %= 10; if ($n) $result .= ($n===5?'lăm':$ones[$n]).' '; }
        elseif ($n >= 10) $result .= $teens[$n-10].' ';
        elseif ($n >  0)  $result .= ($result ? 'lẻ ' : '').$ones[$n].' ';
        return $result;
    }
    $parts = []; $units = ['','nghìn','triệu','tỷ']; $i = 0;
    while ($n > 0) { $rem = $n % 1000; if ($rem) $parts[] = trim(readHundreds($rem,$ones,$teens)).' '.$units[$i]; $n = intdiv($n,1000); $i++; }
    return ucfirst(trim(implode(' ',array_reverse($parts)))).' đồng chẵn';
}

$netWords = numberToWords((int)$s['net_salary']);
$periodName = 'Tháng '.$s['period_month'].'/'.$s['period_year'];
$kpiBonus     = (float)($s['kpi_bonus']     ?? 0);
$kpiDeduction = (float)($s['kpi_deduction'] ?? 0);
$kpiOverDays  = (int)  ($s['kpi_over_days']  ?? 0);
$kpiUnderDays = (int)  ($s['kpi_under_days'] ?? 0);
$otMealBonus  = (float)($s['ot_meal_bonus']  ?? 0);
$otMealDays   = (int)  ($s['ot_meal_days']   ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Phiếu lương – <?= htmlspecialchars($s['full_name']) ?> – <?= $periodName ?></title>
<style>
/* ── Reset & base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 13px;
    color: #111;
    background: #f0f0f0;
    padding: 20px;
}

/* ── Trang in ── */
.page {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 0 auto 30px;
    padding: 15mm 15mm 12mm;
    box-shadow: 0 2px 20px rgba(0,0,0,.15);
    position: relative;
}

/* ── Toolbar (không in) ── */
.toolbar {
    width: 210mm;
    margin: 0 auto 12px;
    display: flex;
    gap: 10px;
    align-items: center;
}
.toolbar button {
    padding: 8px 22px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-print  { background: #0d6efd; color: #fff; }
.btn-print:hover  { background: #0b5ed7; }
.btn-close  { background: #6c757d; color: #fff; }
.btn-close:hover  { background: #5a6268; }

/* ── Header công ty ── */
.header-wrap {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.company-info .company-name {
    font-size: 15px;
    font-weight: 700;
    text-transform: uppercase;
}
.company-info .company-sub {
    font-size: 11px;
    color: #555;
    margin-top: 2px;
}
.slip-title {
    text-align: center;
    flex: 1;
    padding: 0 20px;
}
.slip-title .title-main {
    font-size: 18px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.slip-title .title-en {
    font-size: 12px;
    color: #555;
    font-style: italic;
}
.slip-title .title-period {
    font-size: 13px;
    font-weight: 600;
    margin-top: 4px;
}
.confidential {
    font-size: 10px;
    color: #dc3545;
    border: 1px solid #dc3545;
    padding: 2px 8px;
    border-radius: 4px;
    text-align: center;
    white-space: nowrap;
    align-self: center;
}

/* ── Thông tin nhân viên ── */
.emp-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: 12.5px;
}
.emp-info .item { display: flex; gap: 6px; }
.emp-info .lbl  { color: #555; min-width: 120px; }
.emp-info .val  { font-weight: 600; }

/* ── Section title ── */
.section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    background: #343a40;
    color: #fff;
    padding: 5px 10px;
    margin: 12px 0 0;
    letter-spacing: .5px;
}
.section-title.green  { background: #198754; }
.section-title.red    { background: #dc3545; }
.section-title.blue   { background: #0d6efd; }
.section-title.orange { background: #fd7e14; }

/* ── Bảng dữ liệu ── */
table.data {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
table.data td, table.data th {
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    vertical-align: middle;
}
table.data thead th {
    background: #e9ecef;
    font-weight: 700;
    text-align: center;
    font-size: 11px;
    text-transform: uppercase;
}
table.data .lbl  { color: #333; width: 60%; }
table.data .amt  { text-align: right; font-weight: 600; width: 40%; }
table.data .sub  { color: #555; padding-left: 20px; font-style: italic; }
table.data .pos  { color: #198754; }
table.data .neg  { color: #dc3545; }
table.data .bold-row td { font-weight: 700; background: #f1f3f5; }
table.data .gross-row td { font-weight: 700; background: #d1ecf1; font-size: 13px; }
table.data .net-row   td { font-weight: 700; background: #d4edda; font-size: 14px; }
table.data .kpi-bonus-row td { background: #d1e7dd; }
table.data .kpi-deduct-row td { background: #f8d7da; }

/* ── Tổng kết ── */
.summary-box {
    border: 2px solid #198754;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 14px;
    background: #f0fff4;
}
.summary-box .net-label {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    color: #155724;
}
.summary-box .net-amount {
    font-size: 22px;
    font-weight: 700;
    color: #155724;
    text-align: right;
}
.summary-box .net-words {
    font-style: italic;
    color: #555;
    font-size: 12px;
    margin-top: 4px;
}

/* ── Ký tên ── */
.signature-row {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    gap: 10px;
}
.signature-box {
    flex: 1;
    text-align: center;
    border-top: 1px dashed #aaa;
    padding-top: 8px;
}
.signature-box .sig-title {
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
}
.signature-box .sig-note {
    font-size: 11px;
    color: #777;
    font-style: italic;
}
.signature-box .sig-space { height: 50px; }

/* ── Footer ── */
.page-footer {
    position: absolute;
    bottom: 8mm;
    left: 15mm;
    right: 15mm;
    border-top: 1px solid #dee2e6;
    padding-top: 5px;
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    color: #aaa;
}

/* ── Badge ── */
.badge {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
}
.badge-success { background: #198754; }
.badge-danger  { background: #dc3545; }
.badge-info    { background: #0dcaf0; color: #000; }

/* ── Print ── */
@media print {
    body { background: none; padding: 0; }
    .toolbar { display: none !important; }
    .page {
        width: 100%;
        min-height: unset;
        padding: 10mm 12mm 15mm;
        box-shadow: none;
        margin: 0;
    }
}
</style>
</head>
<body>

<!-- ── Toolbar ── -->
<div class="toolbar no-print">
    <button class="btn-print" onclick="window.print()">
        🖨️ In phiếu lương
    </button>
    <button class="btn-close" onclick="window.close()">
        ✕ Đóng
    </button>
    <span style="color:#666;font-size:13px;font-family:sans-serif;">
        Phiếu lương: <strong><?= htmlspecialchars($s['full_name']) ?></strong>
        — <?= $periodName ?>
    </span>
</div>

<!-- ══════════════════════════════════════════════════════
     TRANG IN
══════════════════════════════════════════════════════ -->
<div class="page">

    <!-- ── Header ── -->
    <div class="header-wrap">
        <div class="company-info">
            <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
            <?php if ($companyAddress): ?>
            <div class="company-sub"><?= htmlspecialchars($companyAddress) ?></div>
            <?php endif; ?>
            <?php if ($companyTax): ?>
            <div class="company-sub">MST: <?= htmlspecialchars($companyTax) ?></div>
            <?php endif; ?>
        </div>

        <div class="slip-title">
            <div class="title-main">Phiếu thanh toán lương</div>
            <div class="title-en">Payroll Slip</div>
            <div class="title-period">
                <?= $periodName ?>
                (<?= date('d/m/Y', strtotime($s['period_from'])) ?>
                 – <?= date('d/m/Y', strtotime($s['period_to'])) ?>)
            </div>
        </div>

        <div class="confidential">🔒 BÍ MẬT<br>CONFIDENTIAL</div>
    </div>

    <!-- ── Thông tin nhân viên ── -->
    <div class="emp-info">
        <div class="item">
            <span class="lbl">Họ tên / Full name:</span>
            <span class="val"><?= htmlspecialchars($s['full_name']) ?></span>
        </div>
        <div class="item">
            <span class="lbl">Mã NV / Emp. Code:</span>
            <span class="val"><?= htmlspecialchars($s['employee_code'] ?? '—') ?></span>
        </div>
        <div class="item">
            <span class="lbl">Phòng ban / Dept:</span>
            <span class="val"><?= htmlspecialchars($s['department_name'] ?? '—') ?></span>
        </div>
        <div class="item">
            <span class="lbl">Ngày vào làm / Joined:</span>
            <span class="val"><?= $s['date_joined'] ? date('d/m/Y', strtotime($s['date_joined'])) : '—' ?></span>
        </div>
        <div class="item">
            <span class="lbl">CCCD / ID No.:</span>
            <span class="val"><?= htmlspecialchars($s['identity_no'] ?? '—') ?></span>
        </div>
        <div class="item">
            <span class="lbl">MST cá nhân / Tax Code:</span>
            <span class="val"><?= htmlspecialchars($s['personal_tax_code'] ?? '—') ?></span>
        </div>
        <div class="item">
            <span class="lbl">Số sổ BHXH:</span>
            <span class="val"><?= htmlspecialchars($s['social_book_no'] ?? '—') ?></span>
        </div>
        <div class="item">
            <span class="lbl">Số TK / Bank Account:</span>
            <span class="val">
                <?= htmlspecialchars($s['bank_account'] ?? '—') ?>
                <?php if ($s['bank_name']): ?>
                — <?= htmlspecialchars($s['bank_name']) ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- ══════════════════════════════
         I. THÔNG TIN NGÀY CÔNG
    ══════════════════════════════ -->
    <div class="section-title blue">I. Thông tin ngày công / Attendance</div>
    <table class="data">
        <tr>
            <td class="lbl">(1) Số ngày công chuẩn / Standard working days</td>
            <td class="amt"><?= number_format($s['working_days_standard'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">(2) Số ngày làm việc thực tế / Actual workdays</td>
            <td class="amt"><?= number_format($s['actual_workdays'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">(3) Nghỉ phép năm có lương / Paid annual leave</td>
            <td class="amt"><?= number_format($s['paid_leave_days'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">(4) Nghỉ hưởng lương khác / Other paid leave</td>
            <td class="amt"><?= number_format($s['other_paid_leave_days'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">(5) Nghỉ không lương / Unpaid leave</td>
            <td class="amt <?= $s['unpaid_leave_days'] > 0 ? 'neg' : '' ?>">
                <?= number_format($s['unpaid_leave_days'], 1) ?> ngày
            </td>
        </tr>
        <tr class="bold-row">
            <td class="lbl">(6) Tổng ngày hưởng lương / Total paid days (=2+3+4)</td>
            <td class="amt"><?= number_format($s['total_paid_days'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">(7) Giờ đi muộn / về sớm / Late & early hours</td>
            <td class="amt <?= $s['late_early_hours'] > 0 ? 'neg' : '' ?>">
                <?= number_format($s['late_early_hours'], 2) ?> giờ
            </td>
        </tr>
    </table>

    <!-- ══════════════════════════════
         II. THU NHẬP
    ══════════════════════════════ -->
    <div class="section-title green">II. Thu nhập / Earnings</div>
    <table class="data">
        <tr>
            <td class="lbl sub">(8) Lương cơ bản hợp đồng / Basic salary</td>
            <td class="amt"><?= number_format($s['basic_salary']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">(9) Lương cơ bản thực nhận / Basic salary received</td>
            <td class="amt pos"><?= number_format($s['basic_salary_received']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">
                (10) Trợ cấp ăn ca / Meal allowance
                <?php if ($otMealDays > 0): ?>
                <span class="badge badge-info"><?= $otMealDays ?> ngày OT ≥ 3h + <?= number_format($otMealBonus) ?>đ</span>
                <?php endif; ?>
            </td>
            <td class="amt pos"><?= number_format($s['meal_received']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">(11) Trợ cấp trang phục / Clothes allowance</td>
            <td class="amt pos"><?= number_format($s['clothes_received']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">(12) Trợ cấp điện thoại / Mobile allowance</td>
            <td class="amt pos"><?= number_format($s['phone_received']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">(13) Trợ cấp đi lại / Transport allowance</td>
            <td class="amt pos"><?= number_format($s['transport_received']) ?> đ</td>
        </tr>
		<tr>
			<td class="lbl sub">(13b) Trợ cấp nhà ở / Housing allowance</td>
			<td class="amt pos"><?= number_format($s['housing_received'] ?? 0) ?> đ</td>
		</tr>
        <tr>
            <td class="lbl sub">(14) Thưởng hiệu quả / Performance bonus</td>
            <td class="amt pos"><?= number_format($s['performance_bonus']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">
                (15) Thưởng chuyên cần / Attendance bonus
                <?php if (!(int)($s['attendance_bonus_eligible'] ?? 1)): ?>
                <span class="badge badge-danger">Không đủ điều kiện</span>
                <?php else: ?>
                <span class="badge badge-success">Đủ điều kiện ✓</span>
                <?php endif; ?>
            </td>
            <td class="amt pos"><?= number_format($s['attendance_bonus']) ?> đ</td>
        </tr>
        <!-- OT -->
        <tr>
            <td class="lbl sub">
                (16) Làm thêm ngày thường / OT weekday (×1.5)
                <span style="color:#888;font-size:11px;">[<?= number_format($s['ot_weekday_hours'],2) ?>h]</span>
            </td>
            <td class="amt pos"><?= number_format($s['ot_weekday_amount']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">
                (17) Làm thêm ngày nghỉ / OT weekend (×2.0)
                <span style="color:#888;font-size:11px;">[<?= number_format($s['ot_weekend_hours'],2) ?>h]</span>
            </td>
            <td class="amt pos"><?= number_format($s['ot_weekend_amount']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">
                (18) Làm thêm ngày lễ / OT holiday (×3.0)
                <span style="color:#888;font-size:11px;">[<?= number_format($s['ot_holiday_hours'],2) ?>h]</span>
            </td>
            <td class="amt pos"><?= number_format($s['ot_holiday_amount']) ?> đ</td>
        </tr>
        <tr class="bold-row">
            <td class="lbl">(19) Tổng tiền làm thêm / Total OT</td>
            <td class="amt pos"><?= number_format($s['total_ot_amount']) ?> đ</td>
        </tr>
        <!-- KPI bonus -->
        <?php if ($kpiBonus > 0): ?>
        <tr class="kpi-bonus-row">
            <td class="lbl sub">
                (20) Thưởng vượt KPI / KPI bonus
                <span class="badge badge-success"><?= $kpiOverDays ?> ngày vượt</span>
            </td>
            <td class="amt pos">+<?= number_format($kpiBonus) ?> đ</td>
        </tr>
        <?php endif; ?>
        <!-- Other income / adjustment -->
        <?php if ((float)$s['other_income'] != 0): ?>
        <tr>
            <td class="lbl sub">(21) Thu nhập khác / Other income</td>
            <td class="amt pos"><?= number_format($s['other_income']) ?> đ</td>
        </tr>
        <?php endif; ?>
        <?php if ((float)$s['other_bonus'] != 0): ?>
        <tr>
            <td class="lbl sub">(22) Thưởng khác / Other bonus</td>
            <td class="amt pos"><?= number_format($s['other_bonus']) ?> đ</td>
        </tr>
        <?php endif; ?>
        <?php if ((float)$s['performance_bonus'] != 0 && isset($s['adjustment'])): ?>
        <?php endif; ?>
        <?php if ((float)$s['adjustment'] != 0): ?>
        <tr>
            <td class="lbl sub">(23) Điều chỉnh / Adjustment</td>
            <td class="amt <?= $s['adjustment'] < 0 ? 'neg' : 'pos' ?>">
                <?= number_format($s['adjustment']) ?> đ
            </td>
        </tr>
        <?php endif; ?>
        <?php if ((float)($s['annual_leave_payout'] ?? 0) > 0): ?>
        <tr>
            <td class="lbl sub">(24) Tất toán phép tồn / Annual leave payout</td>
            <td class="amt pos"><?= number_format($s['annual_leave_payout']) ?> đ</td>
        </tr>
        <?php endif; ?>
        <tr class="gross-row">
            <td class="lbl">TỔNG LƯƠNG / GROSS SALARY</td>
            <td class="amt pos"><?= number_format($s['gross_salary']) ?> đ</td>
        </tr>
    </table>

    <!-- ══════════════════════════════
         III. KHẤU TRỪ
    ══════════════════════════════ -->
    <div class="section-title red">III. Khấu trừ / Deductions</div>
    <table class="data">
        <tr>
            <td class="lbl sub">
                (25) BHXH, BHYT, BHTN nhân viên đóng / SI employee (10.5%)
                <?php if (!(int)($s['has_social_insurance'] ?? 0)): ?>
                <span class="badge badge-danger">Không đóng BH</span>
                <?php endif; ?>
            </td>
            <td class="amt neg">- <?= number_format($s['si_employee']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">(26) Số người phụ thuộc / Dependants</td>
            <td class="amt"><?= (int)$s['dependants'] ?> người</td>
        </tr>
        <tr>
            <td class="lbl sub">(27) Giảm trừ gia cảnh / Personal + dependant deduction</td>
            <td class="amt">
                <?= number_format((float)$s['personal_deduction'] + (float)$s['dependant_deduction']) ?> đ
            </td>
        </tr>
        <tr>
            <td class="lbl sub">(28) Thu nhập tính thuế / Taxable income</td>
            <td class="amt"><?= number_format($s['taxable_income']) ?> đ</td>
        </tr>
        <tr class="bold-row">
            <td class="lbl">(29) Thuế thu nhập cá nhân / PIT</td>
            <td class="amt neg">- <?= number_format($s['pit_amount']) ?> đ</td>
        </tr>
        <?php if ((float)$s['pit_adjustment'] != 0): ?>
        <tr>
            <td class="lbl sub">(30) Điều chỉnh thuế TNCN / PIT adjustment</td>
            <td class="amt neg"><?= number_format($s['pit_adjustment']) ?> đ</td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="lbl sub">(31) Trừ đi muộn / về sớm / Late-early deduction</td>
            <td class="amt neg">- <?= number_format($s['late_deduction']) ?> đ</td>
        </tr>
        <?php if ($kpiDeduction > 0): ?>
        <tr class="kpi-deduct-row">
            <td class="lbl sub">
                (32) Trừ KPI không đạt / KPI deduction
                <span class="badge badge-danger"><?= $kpiUnderDays ?> ngày</span>
            </td>
            <td class="amt neg">- <?= number_format($kpiDeduction) ?> đ</td>
        </tr>
        <?php endif; ?>
        <?php if ((float)$s['advance_payment'] > 0): ?>
        <tr>
            <td class="lbl sub">(33) Tạm ứng / Advance payment</td>
            <td class="amt neg">- <?= number_format($s['advance_payment']) ?> đ</td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ══════════════════════════════
         IV. THỰC NHẬN
    ══════════════════════════════ -->
    <div class="summary-box">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div class="net-label">Thu nhập thực nhận / Net Salary</div>
            <div class="net-amount"><?= number_format($s['net_salary']) ?> đ</div>
        </div>
        <div class="net-words">Bằng chữ: <?= $netWords ?></div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;">
            <div>
                <strong>Nhận chuyển khoản / Bank Transfer:</strong>
                <span style="color:#155724;font-weight:700;">
                    <?= number_format($s['bank_transfer']) ?> đ
                </span>
                <?php if ($s['bank_account']): ?>
                — <?= htmlspecialchars($s['bank_name'] ?? '') ?>:
                <strong><?= htmlspecialchars($s['bank_account']) ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════
         V. THÔNG TIN THAM KHẢO
    ══════════════════════════════ -->
    <div class="section-title orange">V. Thông tin tham khảo / Reference</div>
    <table class="data">
        <tr>
            <td class="lbl sub">BHXH, BHYT, BHTN công ty đóng / SI company (21.5%)</td>
            <td class="amt"><?= number_format($s['si_company']) ?> đ</td>
        </tr>
        <tr>
            <td class="lbl sub">Tổng ngày phép năm / Annual leave total</td>
            <td class="amt"><?= number_format($s['annual_leave_total'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">Đã sử dụng / Used leaves</td>
            <td class="amt"><?= number_format($s['annual_leave_used'], 1) ?> ngày</td>
        </tr>
        <tr>
            <td class="lbl sub">Còn tồn / Remaining leaves</td>
            <td class="amt"><?= number_format($s['annual_leave_remaining'], 1) ?> ngày</td>
        </tr>
        <?php if (!empty($s['remark'])): ?>
        <tr>
            <td class="lbl sub">Ghi chú / Remark</td>
            <td class="amt" style="text-align:left;font-style:italic;color:#555;">
                <?= htmlspecialchars($s['remark']) ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ══════════════════════════════
         Ký tên
    ══════════════════════════════ -->
    <div class="signature-row">
        <div class="signature-box">
            <div class="sig-title">Người lập bảng</div>
            <div class="sig-note">Accountant</div>
            <div class="sig-space"></div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="signature-box">
            <div class="sig-title">Kế toán trưởng</div>
            <div class="sig-note">Chief Accountant</div>
            <div class="sig-space"></div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="signature-box">
            <div class="sig-title">Giám đốc</div>
            <div class="sig-note">Director</div>
            <div class="sig-space"></div>
            <div class="sig-note">(Ký, đóng dấu)</div>
        </div>
        <div class="signature-box">
            <div class="sig-title">Người nhận</div>
            <div class="sig-note">Employee</div>
            <div class="sig-space"></div>
            <div class="sig-note"><?= htmlspecialchars($s['full_name']) ?></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="page-footer">
        <span>In ngày: <?= date('d/m/Y H:i') ?></span>
        <span>Phiếu lương #<?= $slipId ?> — <?= $periodName ?></span>
        <span><?= htmlspecialchars($companyName) ?></span>
    </div>

</div><!-- .page -->

<script>
// Tự động mở hộp thoại in nếu có tham số ?print=1
if (new URLSearchParams(location.search).get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>
</body>
</html>