<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once __DIR__ . '/common.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();

$slipsStmt = $pdo->prepare("
    SELECT ps.id, ps.period_id, ps.basic_salary_received, ps.meal_received, ps.clothes_received,
           ps.phone_received, ps.transport_received, ps.housing_received,
           ps.responsibility_allowance_received, ps.seniority_allowance_received,
           ps.performance_bonus, ps.attendance_bonus, ps.total_ot_amount,
           ps.si_employee, ps.pit_amount, ps.net_salary,
           pp.period_month, pp.period_year, pp.period_from, pp.period_to
    FROM payroll_slips ps
    JOIN payroll_periods pp ON ps.period_id = pp.id
    WHERE ps.user_id = ?
      AND pp.status IN ('approved', 'locked')
    ORDER BY pp.period_year DESC, pp.period_month DESC
");
$slipsStmt->execute([$user['id']]);
$slips = $slipsStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedPeriodId = (int)($_GET['period_id'] ?? ($slips[0]['period_id'] ?? 0));
$selectedSlip = null;
foreach ($slips as $slip) {
    if ((int)$slip['period_id'] === $selectedPeriodId) {
        $selectedSlip = $slip;
        break;
    }
}
if (!$selectedSlip && !empty($slips)) {
    $selectedSlip = $slips[0];
    $selectedPeriodId = (int)$selectedSlip['period_id'];
}

mobilePageStart('Phiếu lương', $user);
?>

<?php if (empty($slips)): ?>
<div class="summary-item text-center text-muted">
    Chưa có phiếu lương nào được duyệt.
</div>
<?php else: ?>
<div class="card mobile-card mb-3">
    <div class="card-body p-4">
        <form method="GET">
            <label class="form-label fw-semibold">Chọn kỳ lương</label>
            <select name="period_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($slips as $slip): ?>
                <option value="<?= (int)$slip['period_id'] ?>" <?= (int)$slip['period_id'] === $selectedPeriodId ? 'selected' : '' ?>>
                    Tháng <?= e((string)$slip['period_month']) ?>/<?= e((string)$slip['period_year']) ?>
                    (<?= e(formatDate($slip['period_from'])) ?> – <?= e(formatDate($slip['period_to'])) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($selectedSlip): ?>
<?php
$allowanceTotal = (float)$selectedSlip['meal_received']
    + (float)$selectedSlip['clothes_received']
    + (float)$selectedSlip['phone_received']
    + (float)$selectedSlip['transport_received']
    + (float)($selectedSlip['housing_received'] ?? 0)
    + (float)($selectedSlip['responsibility_allowance_received'] ?? 0)
    + (float)($selectedSlip['seniority_allowance_received'] ?? 0)
    + (float)($selectedSlip['performance_bonus'] ?? 0)
    + (float)($selectedSlip['attendance_bonus'] ?? 0);
$deductionTotal = (float)$selectedSlip['si_employee'] + (float)$selectedSlip['pit_amount'];
?>
<div class="list-compact">
    <div class="summary-item">
        <div class="label-muted">Lương cơ bản thực nhận</div>
        <div class="fw-bold fs-4"><?= e(formatCurrency($selectedSlip['basic_salary_received'])) ?></div>
    </div>
    <div class="summary-item d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold">Trợ cấp (tổng)</div>
            <div class="small text-muted">Ăn uống, điện thoại, xăng xe, thưởng...</div>
        </div>
        <div class="fw-bold text-primary"><?= e(formatCurrency($allowanceTotal)) ?></div>
    </div>
    <div class="summary-item d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold">OT</div>
            <div class="small text-muted">Tổng tiền làm thêm</div>
        </div>
        <div class="fw-bold text-primary"><?= e(formatCurrency($selectedSlip['total_ot_amount'])) ?></div>
    </div>
    <div class="summary-item d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold">Các khoản trừ</div>
            <div class="small text-muted">BHXH: <?= e(formatCurrency($selectedSlip['si_employee'])) ?> • Thuế: <?= e(formatCurrency($selectedSlip['pit_amount'])) ?></div>
        </div>
        <div class="fw-bold text-danger"><?= e(formatCurrency($deductionTotal)) ?></div>
    </div>
    <div class="summary-item border border-success-subtle bg-success-subtle">
        <div class="label-muted">THỰC NHẬN (NET)</div>
        <div class="payslip-amount"><?= e(formatCurrency($selectedSlip['net_salary'])) ?></div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php mobilePageEnd(); ?>
