<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant');

$pdo    = getDBConnection();
$user   = currentUser();
$slipId = (int)($_GET['id'] ?? 0);

if (!$slipId) { header('Location: /erp/modules/payroll/index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT ps.*,
           u.full_name, u.employee_code,
           pp.period_year, pp.period_month,
           pp.period_from, pp.period_to,
           pp.status AS period_status
    FROM payroll_slips ps
    JOIN users u            ON ps.user_id   = u.id
    JOIN payroll_periods pp ON ps.period_id = pp.id
    WHERE ps.id = ?
");
$stmt->execute([$slipId]);
$slip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slip) { header('Location: /erp/modules/payroll/index.php'); exit; }

$periodName = 'Tháng ' . $slip['period_month'] . '/' . $slip['period_year'];

if ($slip['period_status'] === 'locked') {
    $_SESSION['flash_error'] = '🔒 Kỳ lương đã lock, không thể chỉnh sửa!';
    header("Location: /erp/modules/payroll/index.php?period_id={$slip['period_id']}"); exit;
}

// Lấy tất cả khoản lương hợp đồng của nhân viên
$stmtComp = $pdo->prepare("
    SELECT sc.component_name, sc.component_code, sc.component_type,
           es.amount
    FROM employee_salaries es
    JOIN salary_components sc ON es.component_id = sc.id
    WHERE es.user_id = ? AND es.is_active = 1
    ORDER BY sc.sort_order, sc.component_name
");
$stmtComp->execute([$slip['user_id']]);
$contractComponents = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCSRF();

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <?php showFlash(); ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="/erp/modules/payroll/index.php">Bảng lương</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/erp/modules/payroll/index.php?period_id=<?= $slip['period_id'] ?>">
                    <?= $periodName ?>
                </a>
            </li>
            <li class="breadcrumb-item active">
                Sửa phiếu lương — <?= htmlspecialchars($slip['full_name']) ?>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-file-invoice-dollar me-2 text-warning"></i>
                Sửa phiếu lương
            </h4>
            <small class="text-muted">
                <?= htmlspecialchars($slip['full_name']) ?>
                (<?= htmlspecialchars($slip['employee_code']) ?>) —
                <?= $periodName ?>
                (<?= date('d/m/Y', strtotime($slip['period_from'])) ?> –
                 <?= date('d/m/Y', strtotime($slip['period_to'])) ?>)
            </small>
        </div>
        <a href="/erp/modules/payroll/index.php?period_id=<?= $slip['period_id'] ?>"
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Quay lại
        </a>
    </div>

    <form id="formSlipEdit">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="slip_id"    value="<?= $slipId ?>">
        <input type="hidden" name="period_id"  value="<?= $slip['period_id'] ?>">

        <div class="row g-3">

            <!-- ===== CỘT TRÁI ===== -->
            <div class="col-xl-8">

                <!-- THÔNG TIN CƠ BẢN -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-secondary text-white fw-bold">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        <span class="badge bg-light text-dark ms-2 small">Tự động tính — chỉ đọc</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php
                            $readonlyFields = [
                                'basic_salary'          => 'Lương cơ bản',
                                'working_days_standard' => 'Ngày công chuẩn',
                                'actual_workdays'       => 'Ngày công thực tế',
                                'paid_leave_days'       => 'Nghỉ phép có lương',
                                'unpaid_leave_days'     => 'Nghỉ không lương',
                                'total_paid_days'       => 'Tổng ngày hưởng lương',
                                'salary_per_day'        => 'Lương/ngày (tổng)',
                                'basic_salary_received' => 'Lương cơ bản thực nhận',
                            ];
                            foreach ($readonlyFields as $field => $label): ?>
                            <div class="col-md-3">
                                <label class="form-label small text-muted"><?= $label ?></label>
                                <input type="text"
                                       class="form-control form-control-sm bg-light"
                                       value="<?= number_format((float)$slip[$field]) ?>"
                                       readonly>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- TRỢ CẤP -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-info text-white fw-bold">
                        <i class="fas fa-hand-holding-usd me-2"></i>Trợ cấp
                        <span class="badge bg-light text-dark ms-2 small">Tự động tính — chỉ đọc</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php
                            $allowFields = [
                                'meal_received'      => 'Ăn ca',
                                'clothes_received'   => 'Trang phục',
                                'phone_received'     => 'Điện thoại',
                                'transport_received' => 'Đi lại',
                                'housing_received'   => 'Nhà ở',   // ✅ Trợ cấp nhà ở
                                'attendance_bonus'   => 'Chuyên cần',
                                'total_ot_amount'    => 'OT (lương CB)',
                            ];
                            foreach ($allowFields as $field => $label): ?>
                            <div class="col-md-2">
                                <label class="form-label small text-muted"><?= $label ?></label>
                                <input type="text"
                                       class="form-control form-control-sm bg-light"
                                       value="<?= number_format((float)($slip[$field] ?? 0)) ?>"
                                       readonly>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- KPI -->
                <?php
                $kpiBonus     = (float)($slip['kpi_bonus']     ?? 0);
                $kpiOverDays  = (int)  ($slip['kpi_over_days']  ?? 0);
                $kpiDeduction = (float)($slip['kpi_deduction'] ?? 0);
                $kpiUnderDays = (int)  ($slip['kpi_under_days'] ?? 0);
                if ($kpiBonus > 0 || $kpiDeduction > 0):
                ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="fas fa-chart-line me-2"></i>KPI
                        <span class="badge bg-light text-dark ms-2 small">Tự động tính — chỉ đọc</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if ($kpiBonus > 0): ?>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    Thưởng vượt KPI
                                    <span class="badge bg-success ms-1"><?= $kpiOverDays ?> ngày</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-sm bg-light text-success fw-bold"
                                       value="+<?= number_format($kpiBonus) ?>"
                                       readonly>
                            </div>
                            <?php endif; ?>
                            <?php if ($kpiDeduction > 0): ?>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    Trừ KPI không đạt
                                    <span class="badge bg-danger ms-1"><?= $kpiUnderDays ?> ngày</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-sm bg-light text-danger fw-bold"
                                       value="-<?= number_format($kpiDeduction) ?>"
                                       readonly>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- KHẤU TRỪ -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-danger text-white fw-bold">
                        <i class="fas fa-minus-circle me-2"></i>Khấu trừ
                        <span class="badge bg-light text-dark ms-2 small">Tự động tính — chỉ đọc</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php
                            $deductFields = [
                                'si_employee'    => 'BHXH NV đóng',
                                'pit_amount'     => 'Thuế TNCN',
                                'late_deduction' => 'Trừ đi muộn/về sớm',
                                'kpi_deduction'  => 'Trừ KPI không đạt',
                            ];
                            foreach ($deductFields as $field => $label): ?>
                            <div class="col-md-3">
                                <label class="form-label small text-muted"><?= $label ?></label>
                                <input type="text"
                                       class="form-control form-control-sm bg-light
                                              <?= ($field === 'kpi_deduction' && $slip[$field] > 0) ? 'text-danger fw-bold' : '' ?>"
                                       value="<?= number_format((float)$slip[$field]) ?>"
                                       readonly>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ĐIỀU CHỈNH THỦ CÔNG -->
                <div class="card border-0 shadow-sm mb-3 border-warning border-2">
                    <div class="card-header bg-warning fw-bold">
                        <i class="fas fa-edit me-2"></i>Điều chỉnh thủ công
                        <span class="badge bg-dark ms-2 small">✏️ Có thể chỉnh sửa</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PC Trách nhiệm</label>
                                <div class="input-group">
                                    <input type="number" name="responsibility_allowance_received"
                                           class="form-control bg-light"
                                           value="<?= (int)($slip['responsibility_allowance_received'] ?? 0) ?>"
                                           min="0" step="1000" readonly>
                                    <span class="input-group-text small">đ</span>
                                </div>
                                <div class="form-text small">Từ cấu hình lương NV</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PC Thâm niên</label>
                                <div class="input-group">
                                    <input type="number" name="seniority_allowance_received"
                                           class="form-control bg-light"
                                           value="<?= (int)($slip['seniority_allowance_received'] ?? 0) ?>"
                                           min="0" step="1000" readonly>
                                    <span class="input-group-text small">đ</span>
                                </div>
                                <div class="form-text small">Từ cấu hình lương NV</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Thu nhập khác</label>
                                <div class="input-group">
                                    <input type="number" name="other_income"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['other_income'] ?>"
                                           min="0" step="1000">
                                    <span class="input-group-text small">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Thưởng hiệu suất</label>
                                <div class="input-group">
                                    <input type="number" name="performance_bonus"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['performance_bonus'] ?>"
                                           min="0" step="1000">
                                    <span class="input-group-text small">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Thưởng khác</label>
                                <div class="input-group">
                                    <input type="number" name="other_bonus"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['other_bonus'] ?>"
                                           min="0" step="1000">
                                    <span class="input-group-text small">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Điều chỉnh tăng/giảm</label>
                                <div class="input-group">
                                    <input type="number" name="adjustment"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['adjustment'] ?>">
                                    <span class="input-group-text small">đ</span>
                                </div>
                                <small class="text-muted">Âm (-) = giảm | Dương (+) = tăng</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tạm ứng</label>
                                <div class="input-group">
                                    <input type="number" name="advance_payment"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['advance_payment'] ?>"
                                           min="0" step="1000">
                                    <span class="input-group-text small">đ</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Điều chỉnh PIT</label>
                                <div class="input-group">
                                    <input type="number" name="pit_adjustment"
                                           class="form-control calc-field"
                                           value="<?= (int)$slip['pit_adjustment'] ?>">
                                    <span class="input-group-text small">đ</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Ghi chú</label>
                                <textarea name="remark" class="form-control" rows="2"
                                          placeholder="Ghi chú nội bộ..."><?= htmlspecialchars($slip['remark'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===== CỘT PHẢI ===== -->
            <div class="col-xl-4">

                <!-- Khoản lương hợp đồng -->
                <div class="card border-warning border-2 shadow-sm mb-3">
                    <div class="card-header bg-warning py-2 fw-bold small">
                        <i class="fas fa-file-contract me-1"></i>
                        Khoản lương hợp đồng — <?= htmlspecialchars($slip['full_name']) ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($contractComponents)): ?>
                        <div class="text-center text-muted py-2 small">Chưa có khoản lương nào</div>
                        <?php else: ?>
                        <table class="table table-sm table-borderless mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Khoản</th>
                                    <th class="text-end">Số tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $totalContract = 0;
                            foreach ($contractComponents as $c):
                                $totalContract += $c['amount'];
                                $typeLabel = match($c['component_type']) {
                                    'earning'   => '<span class="badge bg-success">Thu nhập</span>',
                                    'bonus'     => '<span class="badge bg-primary">Thưởng</span>',
                                    'deduction' => '<span class="badge bg-danger">Khấu trừ</span>',
                                    default     => '<span class="badge bg-secondary">' . htmlspecialchars($c['component_type']) . '</span>',
                                };
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($c['component_name']) ?>
                                    <?= $typeLabel ?>
                                </td>
                                <td class="text-end fw-semibold
                                    <?= $c['component_type'] === 'deduction' ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($c['amount']) ?> đ
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-warning">
                                <tr>
                                    <td class="fw-bold">Tổng hợp đồng</td>
                                    <td class="text-end fw-bold"><?= number_format($totalContract) ?> đ</td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tổng kết lương -->
                <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                    <div class="card-header bg-success text-white fw-bold">
                        <i class="fas fa-calculator me-2"></i>Tổng kết lương
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            <tr class="table-light">
                                <td colspan="2" class="fw-bold text-secondary small">THU NHẬP</td>
                            </tr>
                            <tr>
                                <td>Lương cơ bản thực nhận</td>
                                <td class="text-end fw-semibold">
                                    <?= number_format((float)$slip['basic_salary_received']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Trợ cấp</td>
                                <td class="text-end">
                                    <?= number_format(
                                        (float)$slip['meal_received'] +
                                        (float)$slip['clothes_received'] +
                                        (float)$slip['phone_received'] +
                                        (float)$slip['transport_received'] +
                                        (float)($slip['housing_received'] ?? 0)  // ✅ cộng nhà ở
                                    ) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Chuyên cần</td>
                                <td class="text-end">
                                    <?= number_format((float)$slip['attendance_bonus']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>PC Trách nhiệm</td>
                                <td class="text-end text-success" id="preview_responsibility_allowance">
                                    <?= number_format((float)($slip['responsibility_allowance_received'] ?? 0)) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>PC Thâm niên</td>
                                <td class="text-end text-success" id="preview_seniority_allowance">
                                    <?= number_format((float)($slip['seniority_allowance_received'] ?? 0)) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>OT</td>
                                <td class="text-end">
                                    <?= number_format((float)$slip['total_ot_amount']) ?> đ
                                </td>
                            </tr>
                            <?php if ($kpiBonus > 0): ?>
                            <tr class="table-success">
                                <td>
                                    Thưởng KPI
                                    <span class="badge bg-success ms-1 small"><?= $kpiOverDays ?> ngày</span>
                                </td>
                                <td class="text-end text-success fw-bold">
                                    +<?= number_format($kpiBonus) ?> đ
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Thu nhập khác</td>
                                <td class="text-end text-success" id="preview_other_income">
                                    <?= number_format((float)$slip['other_income']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Thưởng hiệu suất</td>
                                <td class="text-end text-success" id="preview_performance_bonus">
                                    <?= number_format((float)$slip['performance_bonus']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Thưởng khác</td>
                                <td class="text-end text-success" id="preview_other_bonus">
                                    <?= number_format((float)$slip['other_bonus']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Điều chỉnh</td>
                                <td class="text-end" id="preview_adjustment">
                                    <?= number_format((float)$slip['adjustment']) ?> đ
                                </td>
                            </tr>
                            <tr class="table-success fw-bold">
                                <td>Gross</td>
                                <td class="text-end" id="preview_gross">
                                    <?= number_format((float)$slip['gross_salary']) ?> đ
                                </td>
                            </tr>

                            <tr class="table-light">
                                <td colspan="2" class="fw-bold text-secondary small">KHẤU TRỪ</td>
                            </tr>
                            <tr>
                                <td>BHXH nhân viên</td>
                                <td class="text-end text-danger">
                                    - <?= number_format((float)$slip['si_employee']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Thuế TNCN</td>
                                <td class="text-end text-danger">
                                    - <?= number_format((float)$slip['pit_amount']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Điều chỉnh PIT</td>
                                <td class="text-end text-danger" id="preview_pit_adjustment">
                                    <?= number_format((float)$slip['pit_adjustment']) ?> đ
                                </td>
                            </tr>
                            <tr>
                                <td>Trừ đi muộn/về sớm</td>
                                <td class="text-end text-danger">
                                    - <?= number_format((float)$slip['late_deduction']) ?> đ
                                </td>
                            </tr>
                            <?php if ($kpiDeduction > 0): ?>
                            <tr class="table-danger">
                                <td>
                                    Trừ KPI
                                    <span class="badge bg-danger ms-1 small"><?= $kpiUnderDays ?> ngày</span>
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    - <?= number_format($kpiDeduction) ?> đ
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Tạm ứng</td>
                                <td class="text-end text-danger" id="preview_advance">
                                    - <?= number_format((float)$slip['advance_payment']) ?> đ
                                </td>
                            </tr>

                            <tr class="table-warning fw-bold fs-6">
                                <td>NET thực nhận</td>
                                <td class="text-end text-success" id="preview_net">
                                    <?= number_format((float)$slip['net_salary']) ?> đ
                                </td>
                            </tr>
                            <tr class="table-primary fw-bold">
                                <td>Chuyển khoản</td>
                                <td class="text-end" id="preview_bank">
                                    <?= number_format((float)$slip['bank_transfer']) ?> đ
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex gap-2">
                        <button type="submit" class="btn btn-warning fw-bold flex-fill">
                            <i class="fas fa-save me-1"></i>Lưu phiếu lương
                        </button>
                        <a href="/erp/modules/payroll/index.php?period_id=<?= $slip['period_id'] ?>"
                           class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>

            </div>
            <!-- ===== KẾT THÚC CỘT PHẢI ===== -->

        </div>
    </form>
</div>
</div>

<script>
const BASE = {
    basic_received   : <?= (float)$slip['basic_salary_received'] ?>,
    meal             : <?= (float)$slip['meal_received'] ?>,
    clothes          : <?= (float)$slip['clothes_received'] ?>,
    phone            : <?= (float)$slip['phone_received'] ?>,
    transport        : <?= (float)$slip['transport_received'] ?>,
    housing          : <?= (float)($slip['housing_received'] ?? 0) ?>,   // ✅ Nhà ở
    other_income     : <?= (float)$slip['other_income'] ?>,
    attendance_bonus : <?= (float)$slip['attendance_bonus'] ?>,
    ot               : <?= (float)$slip['total_ot_amount'] ?>,
    kpi_bonus        : <?= (float)($slip['kpi_bonus'] ?? 0) ?>,
    annual_leave     : <?= (float)$slip['annual_leave_payout'] ?>,
    si_employee      : <?= (float)$slip['si_employee'] ?>,
    pit              : <?= (float)$slip['pit_amount'] ?>,
    late_deduction   : <?= (float)$slip['late_deduction'] ?>,
    kpi_deduction    : <?= (float)($slip['kpi_deduction'] ?? 0) ?>,
};

function fmt(n) {
    return Math.round(n).toLocaleString('vi-VN') + ' đ';
}

function recalc() {
    const responsibility = parseFloat(document.querySelector('[name="responsibility_allowance_received"]').value) || 0;
    const seniority   = parseFloat(document.querySelector('[name="seniority_allowance_received"]').value)      || 0;
    const otherIncome = parseFloat(document.querySelector('[name="other_income"]').value)      || 0;
    const perfBonus   = parseFloat(document.querySelector('[name="performance_bonus"]').value) || 0;
    const otherBonus  = parseFloat(document.querySelector('[name="other_bonus"]').value)       || 0;
    const adjustment  = parseFloat(document.querySelector('[name="adjustment"]').value)        || 0;
    const advance     = parseFloat(document.querySelector('[name="advance_payment"]').value)   || 0;
    const pitAdj      = parseFloat(document.querySelector('[name="pit_adjustment"]').value)    || 0;

    const gross = BASE.basic_received
                + BASE.meal + BASE.clothes + BASE.phone + BASE.transport + BASE.housing  // ✅
                + responsibility + seniority
                + BASE.attendance_bonus + BASE.ot + BASE.kpi_bonus
                + BASE.annual_leave
                + otherIncome + perfBonus + otherBonus + adjustment;

    const net = gross
              - BASE.si_employee
              - BASE.pit
              - pitAdj
              - BASE.late_deduction
              - BASE.kpi_deduction
              - advance;

    document.getElementById('preview_responsibility_allowance').textContent = fmt(responsibility);
    document.getElementById('preview_seniority_allowance').textContent      = fmt(seniority);
    document.getElementById('preview_other_income').textContent      = fmt(otherIncome);
    document.getElementById('preview_performance_bonus').textContent = fmt(perfBonus);
    document.getElementById('preview_other_bonus').textContent       = fmt(otherBonus);
    document.getElementById('preview_adjustment').textContent        = fmt(adjustment);
    document.getElementById('preview_pit_adjustment').textContent    = fmt(pitAdj);
    document.getElementById('preview_advance').textContent           = '- ' + fmt(advance);
    document.getElementById('preview_gross').textContent             = fmt(gross);
    document.getElementById('preview_net').textContent               = fmt(Math.max(0, net));
    document.getElementById('preview_bank').textContent              = fmt(Math.max(0, net));
}

document.querySelectorAll('.calc-field').forEach(el => {
    el.addEventListener('input', recalc);
});
recalc();

document.getElementById('formSlipEdit').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

    fetch('/erp/api/payroll/save_slip_edit.php', {
        method: 'POST',
        body  : new FormData(this)
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            window.location.href =
                '/erp/modules/payroll/index.php?period_id=<?= $slip['period_id'] ?>&saved=1';
        } else {
            alert('Lỗi: ' + res.msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu phiếu lương';
        }
    })
    .catch(() => {
        alert('Có lỗi xảy ra!');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu phiếu lương';
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>