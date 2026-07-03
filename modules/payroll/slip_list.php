<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo  = getDBConnection();
$user = currentUser();

$periodId = (int)($_GET['period_id'] ?? 0);
if (!$periodId) {
    setFlash('danger', 'Không tìm thấy kỳ lương.');
    header('Location: /erp/modules/payroll/index.php'); exit;
}

$stmt = $pdo->prepare("
    SELECT pp.*,
           u1.full_name AS created_name,
           u2.full_name AS submitted_name,
           u3.full_name AS approved_name,
           u4.full_name AS locked_name
    FROM payroll_periods pp
    LEFT JOIN users u1 ON pp.created_by   = u1.id
    LEFT JOIN users u2 ON pp.submitted_by = u2.id
    LEFT JOIN users u3 ON pp.approved_by  = u3.id
    LEFT JOIN users u4 ON pp.locked_by    = u4.id
    WHERE pp.id = ?
");
$stmt->execute([$periodId]);
$period = $stmt->fetch();
if (!$period) {
    setFlash('danger', 'Kỳ lương không tồn tại.');
    header('Location: /erp/modules/payroll/index.php'); exit;
}

$stmtSlips = $pdo->prepare("
    SELECT ps.*,
           u.full_name, u.email, u.employee_code,
           d.name AS department_name
    FROM payroll_slips ps
    JOIN users u ON ps.user_id = u.id
    LEFT JOIN employee_profiles ep ON ep.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE ps.period_id = ?
    ORDER BY d.name, u.full_name
");
$stmtSlips->execute([$periodId]);
$slips = $stmtSlips->fetchAll();

$totalGross    = array_sum(array_column($slips, 'gross_salary'));
$totalNet      = array_sum(array_column($slips, 'net_salary'));
$totalSI       = array_sum(array_column($slips, 'si_employee'));
$totalPIT      = array_sum(array_column($slips, 'pit_amount'));
$totalOT       = array_sum(array_column($slips, 'total_ot_amount'));
$totalKpiBonus = array_sum(array_column($slips, 'kpi_bonus'));
$totalKpiDeduct= array_sum(array_column($slips, 'kpi_deduction'));
$totalHousing  = array_sum(array_column($slips, 'housing_received')); // ✅ Nhà ở
$totalResponsibility = array_sum(array_map(fn($s) => (float)($s['responsibility_allowance_received'] ?? 0), $slips));
$totalSeniority      = array_sum(array_map(fn($s) => (float)($s['seniority_allowance_received'] ?? 0), $slips));
$totalNightWD  = array_sum(array_column($slips, 'ot_night_weekday_amount'));
$totalNightWE  = array_sum(array_column($slips, 'ot_night_weekend_amount'));
$totalNightHL  = array_sum(array_column($slips, 'ot_night_holiday_amount'));
$totalNightBonus = array_sum(array_column($slips, 'night_shift_bonus'));
$warningCount  = count(array_filter($slips, fn($s) => $s['is_late_warning']));

$statusMap = [
    'draft'     => ['secondary', '📝 Nháp'],
    'submitted' => ['warning',   '📤 Chờ duyệt'],
    'approved'  => ['success',   '✅ Đã duyệt'],
    'locked'    => ['dark',      '🔒 Đã lock'],
];
[$sCls, $sLbl] = $statusMap[$period['status']] ?? ['secondary', '?'];
$isLocked = $period['status'] === 'locked';

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- ── Tiêu đề ── -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="/erp/modules/payroll/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h4 class="mb-0">
                    💰 Bảng lương Tháng <?= $period['period_month'] ?>/<?= $period['period_year'] ?>
                </h4>
                <span class="badge bg-<?= $sCls ?> fs-6"><?= $sLbl ?></span>
            </div>
            <div class="ms-5 text-muted small">
                <?= date('d/m/Y', strtotime($period['period_from'])) ?>
                → <?= date('d/m/Y', strtotime($period['period_to'])) ?>
                &nbsp;·&nbsp; <?= $period['working_days'] ?> ngày công chuẩn
                &nbsp;·&nbsp; <?= count($slips) ?> phiếu lương
                <?php if ($warningCount > 0): ?>
                &nbsp;·&nbsp;
                <span class="text-warning fw-bold">
                    <i class="fas fa-exclamation-triangle"></i> <?= $warningCount ?> cảnh báo về sớm
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <?php if (!$isLocked): ?>
            <button class="btn btn-outline-info btn-sm" onclick="recalcAll(<?= $periodId ?>, this)">
                <i class="fas fa-calculator me-1"></i>Tính lại lương
            </button>
            <?php endif; ?>
            <a href="/erp/api/payroll/export_excel.php?period_id=<?= $periodId ?>"
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </a>
            <button class="btn btn-outline-secondary btn-sm" onclick="printTable()">
                <i class="fas fa-print me-1"></i>In bảng lương
            </button>
            <?php if ($period['status'] === 'draft' && hasRole('accountant','director')): ?>
            <button class="btn btn-warning btn-sm" onclick="doWorkflow(<?= $periodId ?>, 'submit', this)">
                <i class="fas fa-paper-plane me-1"></i>Trình GĐ duyệt
            </button>
            <?php endif; ?>
            <?php if ($period['status'] === 'submitted' && hasRole('director')): ?>
            <button class="btn btn-success btn-sm" onclick="doWorkflow(<?= $periodId ?>, 'approve', this)">
                <i class="fas fa-check me-1"></i>Duyệt
            </button>
            <?php endif; ?>
            <?php if ($period['status'] === 'approved' && hasRole('director')): ?>
            <button class="btn btn-dark btn-sm" onclick="doWorkflow(<?= $periodId ?>, 'lock', this)">
                <i class="fas fa-lock me-1"></i>Lock
            </button>
            <?php endif; ?>
            <?php if ($period['status'] === 'locked' && hasRole('director')): ?>
            <button class="btn btn-outline-secondary btn-sm" onclick="doWorkflow(<?= $periodId ?>, 'reopen', this)">
                <i class="fas fa-lock-open me-1"></i>Mở lại
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php showFlash(); ?>

    <!-- ── Thống kê tổng ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Tổng Gross</div>
                <div class="fw-bold fs-6 text-primary"><?= number_format($totalGross) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Tổng Net</div>
                <div class="fw-bold fs-6 text-success"><?= number_format($totalNet) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Tổng OT</div>
                <div class="fw-bold fs-6 text-info"><?= number_format($totalOT) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Thưởng KPI</div>
                <div class="fw-bold fs-6 text-success">+<?= number_format($totalKpiBonus) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Trừ KPI</div>
                <div class="fw-bold fs-6 text-danger">-<?= number_format($totalKpiDeduct) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Tổng BHXH NV</div>
                <div class="fw-bold fs-6 text-warning"><?= number_format($totalSI) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Tổng Thuế TNCN</div>
                <div class="fw-bold fs-6 text-danger"><?= number_format($totalPIT) ?>₫</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Số phiếu</div>
                <div class="fw-bold fs-6 text-secondary"><?= count($slips) ?> phiếu</div>
            </div>
        </div>
    </div>

    <!-- ── Bảng ── -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">📋 Danh sách phiếu lương</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm"
                   placeholder="🔍 Tìm tên / mã nhân viên..." style="width:250px"
                   onkeyup="filterTable()">
        </div>
        <div class="card-body p-0">
        <?php if (empty($slips)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3 d-block opacity-25"></i>
                <p class="mb-2">Chưa có phiếu lương nào.</p>
                <?php if (!$isLocked): ?>
                <button class="btn btn-primary btn-sm" onclick="recalcAll(<?= $periodId ?>, this)">
                    <i class="fas fa-calculator me-1"></i>Tính lương ngay
                </button>
                <?php endif; ?>
            </div>
        <?php else: ?>

        <div class="slip-scroll-wrap" id="printArea">
        <table class="slip-table" id="slipTable">
            <thead>
                <tr class="group-row">
                    <th class="sticky-col col-stt"   rowspan="2">#</th>
                    <th class="sticky-col col-code"  rowspan="2">Mã NV</th>
                    <th class="sticky-col col-name"  rowspan="2">Nhân viên</th>
                    <th class="sticky-col col-dept"  rowspan="2">Phòng ban</th>
                    <th class="sticky-col col-days"  rowspan="2">Ngày công</th>
                    <th class="sticky-col col-basic" rowspan="2">Lương CB</th>
                    <th colspan="6" class="grp-ot">OT</th>
                    <th colspan="5" class="grp-allowance">Trợ cấp</th><!-- ✅ colspan 4→5 -->
                    <th colspan="6" class="grp-bonus">Phụ cấp & Thưởng</th>
                    <th colspan="2" class="grp-kpi">KPI</th>
                    <th colspan="2" class="grp-leave">Nghỉ phép</th>
                    <th colspan="3" class="grp-deduct">Khấu trừ tự động</th>
                    <th colspan="4" class="grp-manual">Điều chỉnh tay</th>
                    <th class="sticky-col-right col-net" rowspan="2">Thực nhận</th>
                    <th class="sticky-col-right col-act no-print" rowspan="2">Thao tác</th>
                </tr>
                <tr class="detail-row">
                    <th class="grp-ot">Thường</th>
                    <th class="grp-ot">CN</th>
                    <th class="grp-ot">Lễ</th>
                    <th class="grp-ot">🌙 Đêm TT</th>
                    <th class="grp-ot">🌙 Đêm CN</th>
                    <th class="grp-ot">🌙 Đêm Lễ</th>
                    <th class="grp-allowance">Ăn ca</th>
                    <th class="grp-allowance">Trang phục</th>
                    <th class="grp-allowance">Điện thoại</th>
                    <th class="grp-allowance">Đi lại</th>
                    <th class="grp-allowance">Nhà ở</th><!-- ✅ thêm cột Nhà ở -->
                    <th class="grp-bonus" title="Phụ cấp trách nhiệm thực nhận">PC Trách nhiệm</th>
                    <th class="grp-bonus" title="Phụ cấp thâm niên thực nhận">PC Thâm niên</th>
                    <th class="grp-bonus">Hiệu quả</th>
                    <th class="grp-bonus">Chuyên cần</th>
                    <th class="grp-bonus">Thưởng khác</th>
                    <th class="grp-bonus">🌙 Phụ trội đêm</th>
                    <th class="grp-kpi text-success">Thưởng KPI</th>
                    <th class="grp-kpi text-danger">Trừ KPI</th>
                    <th class="grp-leave">Có lương</th>
                    <th class="grp-leave">Không lương</th>
                    <th class="grp-deduct">BHXH</th>
                    <th class="grp-deduct">Thuế TNCN</th>
                    <th class="grp-deduct">Trừ muộn</th>
                    <th class="grp-manual">Thu nhập khác</th>
                    <th class="grp-manual">Thưởng HS</th>
                    <th class="grp-manual">Thưởng khác</th>
                    <th class="grp-manual">Tạm ứng</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($slips as $i => $s):
                $paidLeave    = (float)$s['paid_leave_days'] + (float)$s['other_paid_leave_days'];
                $rowKpiBonus  = (float)($s['kpi_bonus']     ?? 0);
                $rowKpiDeduct = (float)($s['kpi_deduction'] ?? 0);
                $rowClass     = $s['is_late_warning'] ? 'row-warning' : ($i % 2 === 0 ? '' : 'row-alt');
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="sticky-col col-stt text-center c-muted"><?= $i + 1 ?></td>
                <td class="sticky-col col-code text-center"><?= htmlspecialchars($s['employee_code'] ?? '—') ?></td>
                <td class="sticky-col col-name">
                    <a href="/erp/modules/payroll/slip_edit.php?id=<?= $s['id'] ?>"
                       class="fw-semibold text-decoration-none text-dark link-hover">
                        <?= htmlspecialchars($s['full_name']) ?>
                    </a>
                    <?php if ($s['manually_adjusted']): ?>
                    <span class="badge-mini bi-info" title="Đã chỉnh tay"><i class="fas fa-pen"></i></span>
                    <?php endif; ?>
                    <?php if ($s['is_late_warning']): ?>
                    <span class="badge-mini bi-warn" title="<?= htmlspecialchars($s['late_warning_note'] ?? '') ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                    <?php endif; ?>
                </td>
                <td class="sticky-col col-dept c-muted"><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
                <td class="sticky-col col-days text-center fw-semibold">
                    <?= number_format($s['actual_workdays'], 1) ?>
                    <span class="c-muted">/<?= $s['working_days_standard'] ?></span>
                </td>
                <td class="sticky-col col-basic text-end"><?= number_format($s['basic_salary_received']) ?></td>

                <!-- OT -->
                <td class="text-end"><?= $s['ot_weekday_amount'] > 0 ? number_format($s['ot_weekday_amount']) : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end"><?= $s['ot_weekend_amount'] > 0 ? number_format($s['ot_weekend_amount']) : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end"><?= $s['ot_holiday_amount'] > 0 ? number_format($s['ot_holiday_amount']) : '<span class="c-muted">—</span>' ?></td>
                <?php
                $nightWD = (float)($s['ot_night_weekday_amount'] ?? 0);
                $nightWE = (float)($s['ot_night_weekend_amount'] ?? 0);
                $nightHL = (float)($s['ot_night_holiday_amount'] ?? 0);
                ?>
                <td class="text-end"><?= $nightWD > 0 ? number_format($nightWD) : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end"><?= $nightWE > 0 ? number_format($nightWE) : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end"><?= $nightHL > 0 ? number_format($nightHL) : '<span class="c-muted">—</span>' ?></td>

                <!-- Trợ cấp — ✅ thêm housing_received -->
                <?php foreach (['meal_received','clothes_received','phone_received','transport_received','housing_received'] as $f):
                    $val = (float)($s[$f] ?? 0); ?>
                <td class="text-end"><?= $val > 0 ? number_format($val) : '<span class="c-muted">—</span>' ?></td>
                <?php endforeach; ?>

                <!-- Phụ cấp & Thưởng -->
                <?php foreach ([
                    'responsibility_allowance_received',
                    'seniority_allowance_received',
                    'performance_bonus',
                    'attendance_bonus',
                    'other_bonus',
                ] as $f):
                    $val = (float)($s[$f] ?? 0); ?>
                <td class="text-end"><?= $val != 0 ? number_format($val) : '<span class="c-muted">—</span>' ?></td>
                <?php endforeach; ?>
                <?php $nightBonus = (float)($s['night_shift_bonus'] ?? 0); ?>
                <td class="text-end <?= $nightBonus > 0 ? 'text-info fw-bold' : '' ?>">
                    <?= $nightBonus > 0 ? number_format($nightBonus) : '<span class="c-muted">—</span>' ?>
                </td>

                <!-- KPI -->
                <td class="text-end <?= $rowKpiBonus > 0 ? 'text-success fw-bold' : '' ?>">
                    <?= $rowKpiBonus > 0
                        ? '+' . number_format($rowKpiBonus)
                        : '<span class="c-muted">—</span>' ?>
                </td>
                <td class="text-end <?= $rowKpiDeduct > 0 ? 'c-danger fw-bold' : '' ?>">
                    <?= $rowKpiDeduct > 0
                        ? '-' . number_format($rowKpiDeduct)
                        : '<span class="c-muted">—</span>' ?>
                </td>

                <!-- Nghỉ phép -->
                <td class="text-center"><?= $paidLeave > 0 ? number_format($paidLeave, 1) : '<span class="c-muted">—</span>' ?></td>
                <td class="text-center">
                    <?= $s['unpaid_leave_days'] > 0
                        ? '<span class="badge-danger">' . $s['unpaid_leave_days'] . '</span>'
                        : '<span class="c-muted">—</span>' ?>
                </td>

                <!-- Khấu trừ -->
                <td class="text-end c-danger"><?= $s['si_employee']    > 0 ? number_format($s['si_employee'])    : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end c-danger"><?= $s['pit_amount']     > 0 ? number_format($s['pit_amount'])     : '<span class="c-muted">—</span>' ?></td>
                <td class="text-end c-danger"><?= $s['late_deduction'] > 0 ? number_format($s['late_deduction']) : '<span class="c-muted">—</span>' ?></td>

                <!-- Điều chỉnh tay -->
                <?php foreach (['other_income','performance_bonus','other_bonus','advance_payment'] as $f):
                    $val = (float)$s[$f]; $isAdv = ($f === 'advance_payment'); ?>
                <td class="text-end <?= $val != 0 ? ($isAdv ? 'c-danger' : '') : '' ?>">
                    <?= $val != 0 ? number_format($val) : '<span class="c-muted">—</span>' ?>
                </td>
                <?php endforeach; ?>

                <!-- Thực nhận -->
                <td class="sticky-col-right col-net text-end fw-bold"><?= number_format($s['net_salary']) ?></td>
                <td class="sticky-col-right col-act text-center no-print">
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="/erp/modules/payroll/slip_edit.php?id=<?= $s['id'] ?>"
                           class="btn btn-xs btn-outline-primary" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="/erp/modules/payroll/slip_print.php?id=<?= $s['id'] ?>"
                           target="_blank" class="btn btn-xs btn-outline-secondary" title="In">
                            <i class="fas fa-print"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="foot-row">
                    <td class="sticky-col col-stt"></td>
                    <td class="sticky-col col-code"></td>
                    <td class="sticky-col col-name">TỔNG CỘNG</td>
                    <td class="sticky-col col-dept"><?= count($slips) ?> NV</td>
                    <td class="sticky-col col-days text-center">—</td>
                    <td class="sticky-col col-basic text-end"><?= number_format(array_sum(array_column($slips,'basic_salary_received'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'ot_weekday_amount'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'ot_weekend_amount'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'ot_holiday_amount'))) ?></td>
                    <td class="text-end"><?= number_format($totalNightWD) ?></td>
                    <td class="text-end"><?= number_format($totalNightWE) ?></td>
                    <td class="text-end"><?= number_format($totalNightHL) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'meal_received'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'clothes_received'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'phone_received'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'transport_received'))) ?></td>
                    <td class="text-end"><?= number_format($totalHousing) ?></td><!-- ✅ Tổng nhà ở -->
                    <td class="text-end"><?= number_format($totalResponsibility) ?></td>
                    <td class="text-end"><?= number_format($totalSeniority) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'performance_bonus'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'attendance_bonus'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'other_bonus'))) ?></td>
                    <td class="text-end <?= $totalNightBonus > 0 ? 'text-info fw-bold' : '' ?>"><?= number_format($totalNightBonus) ?></td>
                    <td class="text-end text-success fw-bold">+<?= number_format($totalKpiBonus) ?></td>
                    <td class="text-end c-danger fw-bold">-<?= number_format($totalKpiDeduct) ?></td>
                    <td class="text-center">—</td>
                    <td class="text-center">—</td>
                    <td class="text-end"><?= number_format($totalSI) ?></td>
                    <td class="text-end"><?= number_format($totalPIT) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'late_deduction'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'other_income'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'performance_bonus'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'other_bonus'))) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($slips,'advance_payment'))) ?></td>
                    <td class="sticky-col-right col-net text-end"><?= number_format($totalNet) ?></td>
                    <td class="sticky-col-right col-act no-print"></td>
                </tr>
            </tfoot>
        </table>
        </div>

        <?php endif; ?>
        </div>
    </div>

    <!-- ── Timeline ── -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0">📌 Lịch sử xử lý</h6>
        </div>
        <div class="card-body">
            <div class="d-flex gap-4 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white"
                         style="width:32px;height:32px;font-size:12px"><i class="fas fa-plus"></i></div>
                    <div>
                        <div class="fw-semibold small">Tạo kỳ lương</div>
                        <div class="text-muted" style="font-size:11px">
                            <?= date('d/m/Y H:i', strtotime($period['created_at'])) ?>
                            · <?= htmlspecialchars($period['created_name'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <?php if ($period['submitted_at']): ?>
                <div class="text-muted align-self-center">→</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center"
                         style="width:32px;height:32px;font-size:12px"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <div class="fw-semibold small">Trình duyệt</div>
                        <div class="text-muted" style="font-size:11px">
                            <?= date('d/m/Y H:i', strtotime($period['submitted_at'])) ?>
                            · <?= htmlspecialchars($period['submitted_name'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($period['approved_at']): ?>
                <div class="text-muted align-self-center">→</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white"
                         style="width:32px;height:32px;font-size:12px"><i class="fas fa-check"></i></div>
                    <div>
                        <div class="fw-semibold small">Đã duyệt</div>
                        <div class="text-muted" style="font-size:11px">
                            <?= date('d/m/Y H:i', strtotime($period['approved_at'])) ?>
                            · <?= htmlspecialchars($period['approved_name'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($period['locked_at']): ?>
                <div class="text-muted align-self-center">→</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center text-white"
                         style="width:32px;height:32px;font-size:12px"><i class="fas fa-lock"></i></div>
                    <div>
                        <div class="fw-semibold small">Đã lock</div>
                        <div class="text-muted" style="font-size:11px">
                            <?= date('d/m/Y H:i', strtotime($period['locked_at'])) ?>
                            · <?= htmlspecialchars($period['locked_name'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div>

<style>
.slip-scroll-wrap {
    overflow-x: auto; overflow-y: visible; position: relative;
    max-width: 100%; scrollbar-width: thin; scrollbar-color: #ced4da #f8f9fa;
}
.slip-scroll-wrap::-webkit-scrollbar       { height: 7px; }
.slip-scroll-wrap::-webkit-scrollbar-track { background: #f8f9fa; }
.slip-scroll-wrap::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }
.slip-table {
    border-collapse: separate; border-spacing: 0; font-size: 11px;
    white-space: nowrap; width: max-content; min-width: 100%; color: #212529;
}
.slip-table th, .slip-table td {
    padding: 5px 8px; border-right: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef; background: #fff; color: #212529;
}
.slip-table thead tr:first-child th { border-top: 1px solid #e9ecef; }
.slip-table thead .group-row th { text-align: center; font-size: 11px; font-weight: 600; color: #212529; }
.slip-table thead .detail-row th { text-align: center; font-size: 10px; font-weight: 500; color: #495057; }
.grp-ot        { background: #dbeafe !important; }
.grp-allowance { background: #dcfce7 !important; }
.grp-bonus     { background: #fef9c3 !important; }
.grp-kpi       { background: #e0f2fe !important; }
.grp-leave     { background: #ede9fe !important; }
.grp-deduct    { background: #fee2e2 !important; }
.grp-manual    { background: #ffedd5 !important; }
.slip-table thead .sticky-col {
    background: #f1f5f9 !important; color: #212529 !important;
    font-weight: 600; text-align: center; z-index: 3;
}
.slip-table thead .sticky-col-right {
    background: #f0fdf4 !important; color: #212529 !important; font-weight: 600; z-index: 3;
}
.sticky-col { position: sticky; z-index: 2; background: #fff; border-right: 1px solid #dee2e6 !important; }
.col-stt   { left: 0;    min-width: 36px;  max-width: 36px;  }
.col-code  { left: 36px; min-width: 72px;  max-width: 72px;  }
.col-name  { left: 108px;min-width: 150px; max-width: 150px; }
.col-dept  { left: 258px;min-width: 110px; max-width: 110px; }
.col-days  { left: 368px;min-width: 80px;  max-width: 80px;  }
.col-basic { left: 448px; min-width: 100px; max-width: 100px;
    border-right: 2px solid #cbd5e1 !important; box-shadow: 3px 0 5px -2px rgba(0,0,0,.08); }
.sticky-col-right { position: sticky; z-index: 2; background: #fff; }
.col-net {
    right: 82px; min-width: 110px;
    border-left: 2px solid #86efac !important; box-shadow: -3px 0 5px -2px rgba(0,0,0,.08);
    font-weight: 700; color: #166534 !important;
}
.col-act  { right: 0; min-width: 82px; border-left: 1px solid #e9ecef !important; }
.row-alt td, .row-alt .sticky-col, .row-alt .sticky-col-right { background: #f8fafc !important; }
.row-warning td, .row-warning .sticky-col, .row-warning .sticky-col-right { background: #fffbeb !important; }
.foot-row td {
    background: #f1f5f9 !important; color: #1e293b !important;
    font-weight: 700; font-size: 11px; border-top: 2px solid #cbd5e1 !important;
}
.foot-row .sticky-col, .foot-row .sticky-col-right { background: #f1f5f9 !important; color: #1e293b !important; }
.foot-row .col-net { color: #166534 !important; }
.c-muted  { color: #94a3b8 !important; }
.c-danger { color: #dc2626 !important; }
.badge-mini { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-left: 3px; }
.badge-mini.bi-info { background: #dbeafe; color: #1e40af; }
.badge-mini.bi-warn { background: #fef9c3; color: #92400e; }
.badge-danger { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 10px;
    background: #fee2e2; color: #dc2626; font-weight: 600; }
.btn-xs { padding: 2px 7px; font-size: 11px; }
.link-hover:hover { color: #0d6efd !important; text-decoration: underline !important; }
@media print {
    .no-print { display: none !important; }
    .slip-scroll-wrap { overflow: visible !important; }
    .slip-table { font-size: 8px !important; }
    .sticky-col, .sticky-col-right { position: static !important; }
}
</style>

<script>
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#slipTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

function printTable() {
    const content = document.getElementById('printArea').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <title>Bảng lương Tháng <?= $period['period_month'] ?>/<?= $period['period_year'] ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 8px; margin: 8px; color:#212529; }
            h2 { text-align:center; font-size:12px; margin-bottom:3px; }
            p  { text-align:center; font-size:9px; color:#555; margin-bottom:6px; }
            .slip-scroll-wrap { overflow: visible; }
            .slip-table { border-collapse:collapse; width:100%; font-size:8px; white-space:normal; color:#212529; }
            .slip-table th,.slip-table td { border:1px solid #dee2e6; padding:2px 4px; color:#212529; }
            .sticky-col,.sticky-col-right { position:static !important; }
            .group-row th { background:#f1f5f9 !important; color:#212529 !important; font-weight:600;
                -webkit-print-color-adjust:exact; print-color-adjust:exact; text-align:center; }
            .detail-row th { background:#e2e8f0 !important; color:#374151 !important;
                -webkit-print-color-adjust:exact; print-color-adjust:exact; text-align:center; }
            .foot-row td { background:#f1f5f9 !important; color:#1e293b !important; font-weight:bold;
                -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-ot        { background:#dbeafe !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-allowance { background:#dcfce7 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-bonus     { background:#fef9c3 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-kpi       { background:#e0f2fe !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-leave     { background:#ede9fe !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-deduct    { background:#fee2e2 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .grp-manual    { background:#ffedd5 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print      { display:none !important; }
            .c-muted  { color:#94a3b8; }
            .c-danger { color:#dc2626; }
            .col-net  { color:#166534 !important; font-weight:bold; }
            .text-end    { text-align:right; }
            .text-center { text-align:center; }
            .fw-bold     { font-weight:bold; }
            a { color:inherit; text-decoration:none; }
            .badge-mini   { padding:1px 3px; border-radius:2px; font-size:7px; }
            .badge-danger { padding:1px 3px; border-radius:2px; font-size:7px;
                            background:#fee2e2; color:#dc2626; }
            .row-alt td   { background:#f8fafc !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .text-success { color:#166534 !important; }
        </style></head><body>
        <h2>BẢNG THANH TOÁN LƯƠNG — THÁNG <?= $period['period_month'] ?>/<?= $period['period_year'] ?></h2>
        <p>Từ <?= date('d/m/Y', strtotime($period['period_from'])) ?>
           đến <?= date('d/m/Y', strtotime($period['period_to'])) ?>
           · <?= $period['working_days'] ?> ngày công chuẩn
           · <?= count($slips) ?> nhân viên · In: <?= date('d/m/Y H:i') ?></p>
        ${content}
        </body></html>`);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 500);
}

async function recalcAll(periodId, btn) {
    if (!confirm('Tính lại lương cho tất cả nhân viên?\n⚠️ Phiếu đã điều chỉnh tay sẽ giữ nguyên phần nhập tay.')) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang tính...';
    try {
        const res  = await fetch('/erp/api/payroll/calculate.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ period_id: periodId })
        });
        const data = await res.json();
        alert(data.msg + (data.errors?.length ? '\n\nLỗi:\n' + data.errors.join('\n') : ''));
        if (data.ok) location.reload();
        else { btn.disabled = false; btn.innerHTML = orig; }
    } catch(e) { alert('Lỗi kết nối!'); btn.disabled = false; btn.innerHTML = orig; }
}

async function doWorkflow(periodId, action, btn) {
    const labels = {
        submit:  'Trình Giám đốc duyệt kỳ lương này?',
        approve: '✅ Duyệt kỳ lương?\nNhân viên sẽ thấy phiếu lương sau khi duyệt.',
        lock:    '🔒 Lock kỳ lương?\nSau khi lock sẽ không thể chỉnh sửa.',
        reopen:  '🔓 Mở lại kỳ lương để chỉnh sửa?'
    };
    if (!confirm(labels[action] || 'Xác nhận?')) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch('/erp/api/payroll/workflow.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ period_id: periodId, action })
        });
        const data = await res.json();
        if (data.ok) { alert(data.msg); location.reload(); }
        else { alert('❌ ' + data.msg); btn.disabled = false; btn.innerHTML = orig; }
    } catch(e) { alert('Lỗi kết nối!'); btn.disabled = false; btn.innerHTML = orig; }
}
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>