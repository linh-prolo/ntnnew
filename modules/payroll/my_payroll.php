<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$pdo  = getDBConnection();
$user = currentUser();

// Lấy tất cả phiếu lương đã duyệt của nhân viên
$slips = $pdo->prepare("
    SELECT ps.*, pp.period_month, pp.period_year,
           pp.period_from, pp.period_to,
           pp.working_days AS period_working_days,
           pp.status AS period_status
    FROM payroll_slips ps
    JOIN payroll_periods pp ON ps.period_id = pp.id
    WHERE ps.user_id = ?
      AND pp.status IN ('approved','locked')
    ORDER BY pp.period_year DESC, pp.period_month DESC
");
$slips->execute([$user['id']]);
$slips = $slips->fetchAll();

// Phiếu đang xem chi tiết
$viewId     = (int)($_GET['id'] ?? 0);
$slipDetail = null;

if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT ps.*, pp.period_month, pp.period_year,
               pp.period_from, pp.period_to,
               pp.working_days AS period_working_days,
               pp.status AS period_status,
               pp.approved_at, pp.locked_at,
               u.full_name, u.email,
               d.name AS department_name,
               ep.bank_account, ep.bank_name, ep.bank_branch
        FROM payroll_slips ps
        JOIN payroll_periods pp ON ps.period_id = pp.id
        JOIN users u ON ps.user_id = u.id
        LEFT JOIN employee_profiles ep ON ep.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE ps.id = ? AND ps.user_id = ?
          AND pp.status IN ('approved','locked')
    ");
    $stmt->execute([$viewId, $user['id']]);
    $slipDetail = $stmt->fetch();
    if (!$slipDetail) {
        setFlash('danger', 'Không tìm thấy phiếu lương hoặc chưa được duyệt.');
        header('Location: /erp/modules/payroll/my_payroll.php'); exit;
    }
} elseif (!empty($slips)) {
    header('Location: /erp/modules/payroll/my_payroll.php?id=' . $slips[0]['id']); exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">💰 Phiếu lương của tôi</h4>
            <p class="text-muted small mb-0">Xem lịch sử phiếu lương đã được Giám đốc duyệt</p>
        </div>
        <?php if ($slipDetail): ?>
        <a href="/erp/modules/payroll/slip_print.php?id=<?= $slipDetail['id'] ?>"
           target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i>In / Xuất PDF
        </a>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <?php if (empty($slips)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-invoice-dollar fa-4x mb-3 opacity-25"></i>
            <h5 class="text-muted">Chưa có phiếu lương nào</h5>
            <p class="text-muted small">Phiếu lương sẽ hiển thị sau khi Giám đốc duyệt.</p>
        </div>
    </div>
    <?php else: ?>

    <div class="row g-4">

        <!-- ── Cột trái: Danh sách kỳ lương ── -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold border-0 pt-3 small">
                    <i class="fas fa-history me-2 text-primary"></i>Lịch sử phiếu lương
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($slips as $s): ?>
                    <a href="/erp/modules/payroll/my_payroll.php?id=<?= $s['id'] ?>"
                       class="list-group-item list-group-item-action
                              <?= ($slipDetail && $s['id'] == $slipDetail['id']) ? 'active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">
                                    Tháng <?= $s['period_month'] ?>/<?= $s['period_year'] ?>
                                </div>
                                <div style="font-size:11px"
                                     class="<?= ($slipDetail && $s['id'] == $slipDetail['id']) ? 'text-white opacity-75' : 'text-muted' ?>">
                                    <?= number_format($s['net_salary'], 0, '.', ',') ?>₫
                                </div>
                            </div>
                            <?php if ($s['period_status'] === 'locked'): ?>
                            <i class="fas fa-lock text-muted small"></i>
                            <?php else: ?>
                            <i class="fas fa-check-circle text-success small"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Cột phải: Chi tiết phiếu lương ── -->
        <?php if ($slipDetail): ?>
        <div class="col-md-9">
            <div class="card border-0 shadow-sm" id="payslipCard">

                <!-- Header phiếu -->
                <div class="card-body border-bottom text-center pb-3">
                    <div class="fw-bold fs-5 text-uppercase">PHIẾU THANH TOÁN LƯƠNG</div>
                    <div class="small text-muted">PAYROLL SLIP</div>
                    <div class="small mt-1">
                        Tháng <?= $slipDetail['period_month'] ?>/<?= $slipDetail['period_year'] ?>
                        (<?= date('d/m/Y', strtotime($slipDetail['period_from'])) ?>
                        – <?= date('d/m/Y', strtotime($slipDetail['period_to'])) ?>)
                    </div>
                </div>

                <div class="card-body">

                    <!-- Thông tin nhân viên -->
                    <table class="table table-bordered table-sm mb-4">
                        <tr>
                            <td class="fw-semibold bg-light" width="35%">Họ tên / Full name</td>
                            <td class="fw-bold"><?= htmlspecialchars($slipDetail['full_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold bg-light">Bộ phận / Department</td>
                            <td><?= htmlspecialchars($slipDetail['department_name'] ?? '—') ?></td>
                        </tr>
                    </table>

                    <!-- I. Thông tin chung -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        I. Thông tin chung / General information
                    </div>
                    <table class="table table-sm table-bordered mb-4">
                        <tr class="table-dark">
                            <td class="fw-bold">Lương Tổng / Gross salary (=1+2+3+4+5+6+7)</td>
                            <td class="text-end fw-bold">
                                <?= number_format(
                                    $slipDetail['basic_salary'] +
                                    $slipDetail['meal_allowance'] +
                                    $slipDetail['clothes_allowance'] +
                                    $slipDetail['phone_allowance'] +
                                    $slipDetail['transport_allowance'] +
									(float)($slipDetail['housing_allowance'] ?? 0) +
                                    $slipDetail['performance_bonus'] +
									
                                    (float)$slipDetail['attendance_bonus']
                                , 0, '.', ',') ?>
                            </td>
                        </tr>
                        <?php
                        $items = [
                            ['(1) Lương cơ bản / Basic salary',                    'basic_salary'],
                            ['(2) Trợ cấp ăn uống / Meal allowance',               'meal_allowance'],
                            ['(3) Trợ cấp trang phục / Clothes allowance',         'clothes_allowance'],
                            ['(4) Trợ cấp điện thoại / Mobile allowance',          'phone_allowance'],
                            ['(5) Trợ cấp xăng xe / Gas-travelling allowance',     'transport_allowance'],
							['(5b) Trợ cấp nhà ở / Housing allowance',             'housing_allowance'],
                            ['(6) Thưởng hiệu quả / Job effectiveness bonus',      'performance_bonus'],
                        ];
                        foreach ($items as [$label, $key]): ?>
                        <tr>
                            <td class="text-muted small">- <?= $label ?></td>
                            <td class="text-end"><?= number_format($slipDetail[$key], 0, '.', ',') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Chuyên cần -->
                        <tr>
                            <td class="text-muted small">
                                - (7) Thưởng chuyên cần / Attendance bonus
                                <?php if (!(int)$slipDetail['attendance_bonus_eligible']): ?>
                                <span class="badge bg-danger ms-1" style="font-size:10px">Không đủ điều kiện</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end <?= (float)$slipDetail['attendance_bonus'] > 0 ? 'text-success fw-semibold' : 'text-muted' ?>">
                                <?= number_format((float)$slipDetail['attendance_bonus'], 0, '.', ',') ?>
                            </td>
                        </tr>
                    </table>

                    <!-- II. Tiền lương thực tế -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        II. Tiền lương và các khoản trợ cấp thực nhận / Real payment
                    </div>
                    <table class="table table-sm table-bordered mb-4">
                        <tr class="bg-light">
                            <td class="fw-semibold">(7) Số ngày công chuẩn / Total standard working days</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['working_days_standard'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(8) Số ngày làm việc thực tế / Actual workdays</td>
                            <td class="text-end"><?= number_format($slipDetail['actual_workdays'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(9) Số ngày nghỉ phép hưởng lương / Paid annual leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['paid_leave_days'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(10) Số ngày nghỉ hưởng lương khác / Other paid leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['other_paid_leave_days'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(11) Số ngày nghỉ không lương / Unpaid leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['unpaid_leave_days'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(12) Số giờ đi muộn về sớm / Late & early hours</td>
                            <td class="text-end"><?= number_format($slipDetail['late_early_hours'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(13) Số tiền đi muộn về sớm / Late-early deduction</td>
                            <td class="text-end text-danger"><?= number_format($slipDetail['late_early_deduction'], 0, '.', ',') ?></td>
                        </tr>
                        <tr class="bg-light">
                            <td class="fw-semibold">Tổng số ngày hưởng lương / Total paid days (=8+9+10)</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['total_paid_days'], 2) ?></td>
                        </tr>
                        <?php
                        $items2 = [
                            ['(15) Lương CB thực nhận / Basic salary received',       'basic_salary_received'],
                            ['(16) Trợ cấp ăn uống thực nhận / Meal received',        'meal_received'],
                            ['(17) Trợ cấp trang phục thực nhận / Clothes received',  'clothes_received'],
                            ['(18) Trợ cấp điện thoại thực nhận / Mobile received',   'phone_received'],
                            ['(19) Trợ cấp xăng xe thực nhận / Transport received',   'transport_received'],
							['(19b) Trợ cấp nhà ở thực nhận / Housing received',      'housing_received'],
                            ['(20) Thưởng hiệu quả thực nhận / Performance received', 'performance_bonus'],
                        ];
                        foreach ($items2 as [$label, $key]): ?>
                        <tr>
                            <td class="text-muted small"><?= $label ?></td>
                            <td class="text-end"><?= number_format($slipDetail[$key], 0, '.', ',') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ((float)($slipDetail['night_shift_bonus'] ?? 0) > 0): ?>
                        <tr class="table-dark">
                            <td class="text-white small">
                                (15b) 🌙 Phụ trội làm đêm / Night shift bonus (30%)
                            </td>
                            <td class="text-end text-white fw-bold">
                                +<?= number_format((float)$slipDetail['night_shift_bonus'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <!-- Chuyên cần thực nhận -->
                        <tr>
                            <td class="text-muted small">
                                (21) Thưởng chuyên cần thực nhận / Attendance bonus received
                                <?php if (!(int)$slipDetail['attendance_bonus_eligible']): ?>
                                <span class="badge bg-danger ms-1" style="font-size:10px">Không đủ điều kiện</span>
                                <?php else: ?>
                                <span class="badge bg-success ms-1" style="font-size:10px">Đủ điều kiện ✓</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end <?= (float)$slipDetail['attendance_bonus'] > 0 ? 'text-success fw-semibold' : 'text-muted' ?>">
                                <?= number_format((float)$slipDetail['attendance_bonus'], 0, '.', ',') ?>
                            </td>
                        </tr>
                    </table>

                    <!-- III. OT & Cộng thêm -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        III. Các khoản cộng thêm / Additional income
                    </div>
                    <table class="table table-sm table-bordered mb-4">
                        <tr class="bg-light">
                            <td class="fw-semibold small">Lương cơ bản 1h / Basic salary 1h</td>
                            <td class="text-end"><?= number_format($slipDetail['salary_per_hour'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">
                                (22) Làm thêm ngày thường / OT weekdays (150%)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_weekday_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_weekday_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">
                                (23) Làm thêm ngày nghỉ / OT weekend (200%)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_weekend_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_weekend_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">
                                (24) Làm thêm ngày lễ / OT holiday (300%)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_holiday_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_holiday_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <?php if ((float)($slipDetail['ot_night_hours'] ?? 0) > 0): ?>
                        <tr>
                            <td class="text-muted small">
                                (24b) 🌙 Làm thêm ban đêm / OT night (130%)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_night_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_night_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)($slipDetail['ot_night_weekday_hours'] ?? 0) > 0): ?>
                        <tr>
                            <td class="text-muted small">
                                (24c) 🌙 OT đêm ngày thường / Night weekday (×2.1)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_night_weekday_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_night_weekday_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)($slipDetail['ot_night_weekend_hours'] ?? 0) > 0): ?>
                        <tr>
                            <td class="text-muted small">
                                (24d) 🌙 OT đêm cuối tuần / Night weekend (×2.7)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_night_weekend_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_night_weekend_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)($slipDetail['ot_night_holiday_hours'] ?? 0) > 0): ?>
                        <tr>
                            <td class="text-muted small">
                                (24e) 🌙 OT đêm ngày lễ / Night holiday (×3.9)
                                <span class="text-muted">[<?= number_format($slipDetail['ot_night_holiday_hours'], 2) ?>h]</span>
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['ot_night_holiday_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="bg-light">
                            <td class="fw-semibold small">(25) Tổng tiền làm thêm / Total OT salary</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['total_ot_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <!-- ✅ THÊM: Thưởng KPI -->
                        <?php if ((float)($slipDetail['kpi_bonus'] ?? 0) > 0): ?>
                        <tr class="table-success">
                            <td class="fw-semibold small">
                                (25b) Thưởng vượt KPI / KPI bonus
                                <span class="badge bg-success ms-1" style="font-size:10px">
                                    <?= (int)($slipDetail['kpi_over_days'] ?? 0) ?> ngày vượt
                                </span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                +<?= number_format((float)$slipDetail['kpi_bonus'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted small">Phép tồn / Redundant annual leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['annual_leave_remaining'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(26) Tất toán phép tồn / Annual leave payout</td>
                            <td class="text-end"><?= number_format($slipDetail['annual_leave_payout'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(27) Thu nhập khác / Other income</td>
                            <td class="text-end"><?= number_format($slipDetail['other_income'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(28) Điều chỉnh / Adjustment</td>
                            <td class="text-end <?= $slipDetail['adjustment'] < 0 ? 'text-danger' : '' ?>">
                                <?= number_format($slipDetail['adjustment'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(29) Tổng các khoản thưởng khác / Other bonuses</td>
                            <td class="text-end"><?= number_format($slipDetail['other_bonus'], 0, '.', ',') ?></td>
                        </tr>
                    </table>

                    <!-- IV. Khấu trừ -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        IV. Các khoản giảm trừ / Deductions
                    </div>
                    <table class="table table-sm table-bordered mb-4">
                        <tr>
                            <td class="text-muted small">
                                (30) Trích nộp BHXH, BHYT, BHTN / SI & MI & UI: 10.5%
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['si_employee'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Số người giảm trừ gia cảnh / Number of dependants</td>
                            <td class="text-end"><?= $slipDetail['dependants'] ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Số tiền giảm trừ gia cảnh / Dependable deduction</td>
                            <td class="text-end">
                                <?= number_format($slipDetail['personal_deduction'] + $slipDetail['dependant_deduction'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Thu nhập tính thuế / Taxable income</td>
                            <td class="text-end"><?= number_format($slipDetail['taxable_income'], 0, '.', ',') ?></td>
                        </tr>
                        <tr class="table-warning">
                            <td class="fw-bold">(31) Thuế TNCN / PIT payment</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['pit_amount'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">(32) Trừ tiền đi muộn về sớm / Late-early deduction</td>
                            <td class="text-end text-danger"><?= number_format($slipDetail['late_deduction'], 0, '.', ',') ?></td>
                        </tr>
                        <?php if ((float)$slipDetail['kpi_deduction'] > 0): ?>
                        <tr>
                            <td class="text-muted small">
                                (33) Trừ KPI không đạt / KPI deduction
                                <span class="badge bg-danger ms-1" style="font-size:10px">KPI</span>
                                <?php if ((int)($slipDetail['kpi_under_days'] ?? 0) > 0): ?>
                                <span class="badge bg-secondary ms-1" style="font-size:10px">
                                    <?= (int)$slipDetail['kpi_under_days'] ?> ngày
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-danger fw-bold">
                                -<?= number_format($slipDetail['kpi_deduction'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <!-- V. Thực nhận -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        V. Số tiền thực nhận / Net payment
                    </div>
                    <table class="table table-sm table-bordered mb-4">
                        <tr>
                            <td class="fw-semibold small">TỔNG LƯƠNG / GROSS SALARY</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['gross_salary'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tạm ứng / Advance</td>
                            <td class="text-end"><?= number_format($slipDetail['advance_payment'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Điều chỉnh PIT / Adjustment on PIT</td>
                            <td class="text-end"><?= number_format($slipDetail['pit_adjustment'], 0, '.', ',') ?></td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">THU NHẬP THỰC NHẬN / NET SALARY</td>
                            <td class="text-end fw-bold fs-5 text-success">
                                <?= number_format($slipDetail['net_salary'], 0, '.', ',') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold small">Nhận chuyển khoản / Bank transfer</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['bank_transfer'], 0, '.', ',') ?></td>
                        </tr>
                        <?php if ($slipDetail['remark']): ?>
                        <tr>
                            <td class="text-muted small">Ghi chú / Remark</td>
                            <td><?= htmlspecialchars($slipDetail['remark']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <!-- VI. Thông tin tham khảo -->
                    <div class="fw-bold mb-2 border-bottom pb-1">
                        VI. Thông tin tham khảo / Additional information
                    </div>
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <td class="text-muted small">
                                Phần trích nộp BHXH, BHYT, BHTN do công ty đóng / SI, MI, UI by Company: 21.5%
                            </td>
                            <td class="text-end"><?= number_format($slipDetail['si_company'], 0, '.', ',') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tổng số ngày phép / Total annual leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['annual_leave_total'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Số phép đã sử dụng / Used annual leaves</td>
                            <td class="text-end"><?= number_format($slipDetail['annual_leave_used'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Số phép còn tồn / Remaining annual leaves</td>
                            <td class="text-end fw-bold"><?= number_format($slipDetail['annual_leave_remaining'], 2) ?></td>
                        </tr>
                    </table>

                </div><!-- card-body -->
            </div><!-- card -->
        </div><!-- col -->
        <?php endif; ?>

    </div><!-- row -->
    <?php endif; ?>

</div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>