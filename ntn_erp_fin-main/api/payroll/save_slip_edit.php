<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']); exit;
}
if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF không hợp lệ']); exit;
}

$pdo    = getDBConnection();
$user   = currentUser();
$slipId = (int)($_POST['slip_id'] ?? 0);

if (!$slipId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu slip_id']); exit;
}

// Kiểm tra slip + period status
$stmt = $pdo->prepare("
    SELECT ps.*, pp.status AS period_status
    FROM payroll_slips ps
    JOIN payroll_periods pp ON ps.period_id = pp.id
    WHERE ps.id = ?
");
$stmt->execute([$slipId]);
$slip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slip) {
    echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy phiếu lương']); exit;
}
if ($slip['period_status'] === 'locked') {
    echo json_encode(['ok' => false, 'msg' => '🔒 Kỳ lương đã lock!']); exit;
}

// Lấy các giá trị chỉnh sửa
$otherIncome     = (float)($_POST['other_income']      ?? $slip['other_income']);
$perfBonus       = (float)($_POST['performance_bonus'] ?? $slip['performance_bonus']);
$otherBonus      = (float)($_POST['other_bonus']       ?? $slip['other_bonus']);
$adjustment      = (float)($_POST['adjustment']        ?? $slip['adjustment']);
$advancePayment  = (float)($_POST['advance_payment']   ?? $slip['advance_payment']);
$pitAdjustment   = (float)($_POST['pit_adjustment']    ?? $slip['pit_adjustment']);
$remark          = trim($_POST['remark']               ?? $slip['remark']);

// Tính lại gross & net
$gross = (float)$slip['basic_salary_received']
       + (float)$slip['meal_received']
       + (float)$slip['clothes_received']
       + (float)$slip['phone_received']
       + (float)$slip['transport_received']
       + (float)$slip['attendance_bonus']
       + (float)$slip['total_ot_amount']
       + (float)$slip['annual_leave_payout']
       + $otherIncome
       + $perfBonus
       + $otherBonus
       + $adjustment;

$net = $gross
     - (float)$slip['si_employee']
     - (float)$slip['pit_amount']
     - $pitAdjustment
     - (float)$slip['late_deduction']
     - (float)$slip['kpi_deduction']
     - $advancePayment;

$net  = max(0, round($net));
$bank = $net;

try {
    $pdo->prepare("
        UPDATE payroll_slips SET
            other_income       = ?,
            performance_bonus  = ?,
            other_bonus        = ?,
            adjustment         = ?,
            advance_payment    = ?,
            pit_adjustment     = ?,
            remark             = ?,
            gross_salary       = ?,
            net_salary         = ?,
            bank_transfer      = ?,
            manually_adjusted  = 1,
            updated_at         = NOW()
        WHERE id = ?
    ")->execute([
        $otherIncome, $perfBonus, $otherBonus,
        $adjustment, $advancePayment, $pitAdjustment,
        $remark,
        round($gross), $net, $bank,
        $slipId
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}