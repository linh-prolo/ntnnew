<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();
$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');

// --- Lấy số liệu theo role ---
$stats = [];

// Chấm công hôm nay (tất cả role đều thấy của mình)
$stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND work_date = ?");
$stmt->execute([$user['id'], $today]);
$todayAttendance = $stmt->fetch();

// Đơn chờ duyệt (production/manager/director)
if (hasRole('production', 'manager', 'director')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_leaves'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM overtime_requests WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_ot'] = $stmt->fetchColumn();
}

// Tổng nhân viên (director/manager)
if (hasRole('director', 'manager', 'accountant')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1 AND role_id = (SELECT id FROM roles WHERE name = 'employee')");
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetchColumn();

    // Chấm công hôm nay tổng
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE work_date = ?");
    $stmt->execute([$today]);
    $stats['checked_today'] = $stmt->fetchColumn();
}

// Ngày công tháng này của nhân viên
if (hasRole('employee')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE user_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? AND check_in IS NOT NULL");
    $stmt->execute([$user['id'], $currentMonth, $currentYear]);
    $stats['my_working_days'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user['id']]);
    $stats['my_pending'] = $stmt->fetchColumn();
}

// ── Dữ liệu bảng lương ───────────────────────────────────────────────────
$latestPeriod    = null;
$myLatestSlip    = null;
$pendingPayrolls = 0;
$adminWarnings = [
    'expiring_30' => 0,
    'expiring_60' => 0,
    'pending_expenses' => 0,
];

// Kỳ lương mới nhất
$latestPeriod = $pdo->query("
    SELECT * FROM payroll_periods
    ORDER BY period_year DESC, period_month DESC
    LIMIT 1
")->fetch();

// Phiếu lương mới nhất của tôi (đã được duyệt)
$stmtSlip = $pdo->prepare("
    SELECT ps.*, pp.period_month, pp.period_year, pp.status AS period_status
    FROM payroll_slips ps
    JOIN payroll_periods pp ON ps.period_id = pp.id
    WHERE ps.user_id = ? AND pp.status IN ('approved','locked')
    ORDER BY pp.period_year DESC, pp.period_month DESC
    LIMIT 1
");
$stmtSlip->execute([$user['id']]);
$myLatestSlip = $stmtSlip->fetch();

// Số kỳ chờ duyệt (GĐ)
if (hasRole('director')) {
    $pendingPayrolls = (int)$pdo->query("
        SELECT COUNT(*) FROM payroll_periods WHERE status = 'submitted'
    ")->fetchColumn();
}

if (hasRole('director', 'accountant', 'manager')) {
    $adminWarnings['expiring_30'] = (int)$pdo->query("
        SELECT COUNT(DISTINCT vehicle_id)
        FROM vehicle_documents
        WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();

    $adminWarnings['expiring_60'] = (int)$pdo->query("
        SELECT COUNT(DISTINCT vehicle_id)
        FROM vehicle_documents
        WHERE end_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND end_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ")->fetchColumn();

    $adminWarnings['pending_expenses'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM expense_requests
        WHERE status = 'submitted'
    ")->fetchColumn();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <!-- Tiêu đề -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Xin chào, <strong><?= htmlspecialchars($user['full_name']) ?></strong> 👋</h4>
                <p class="text-muted mb-0">
                    <?php $badge = getRoleBadge($user['role']); ?>
                    <span class="badge bg-<?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
                    &nbsp; <?= date('l, d/m/Y') ?>
                </p>
            </div>
        </div>

        <?php showFlash(); ?>

        <!-- Chấm công hôm nay + Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Chấm công hôm nay</p>
                                <?php if ($todayAttendance): ?>
                                    <h5 class="text-success mb-1">✅ Đã chấm công</h5>
                                    <small class="text-muted">
                                        Vào: <?= $todayAttendance['check_in'] ? date('H:i', strtotime($todayAttendance['check_in'])) : '--:--' ?>
                                        &nbsp;|&nbsp;
                                        Ra: <?= $todayAttendance['check_out'] ? date('H:i', strtotime($todayAttendance['check_out'])) : '--:--' ?>
                                    </small>
                                <?php else: ?>
                                    <h5 class="text-danger mb-1">❌ Chưa chấm công</h5>
                                    <small class="text-muted"><?= date('d/m/Y') ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="fs-2">⏰</div>
                        </div>
                        <a href="/erp/modules/attendance/index.php" class="btn btn-sm btn-outline-primary mt-2">Xem chi tiết</a>
                    </div>
                </div>
            </div>

            <?php if (hasRole('director', 'manager', 'accountant')): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body">
                        <p class="mb-1 opacity-75 small">Tổng nhân viên</p>
                        <h2 class="mb-1"><?= $stats['total_employees'] ?? 0 ?></h2>
                        <small class="opacity-75">Đang hoạt động</small>
                        <div class="fs-2 float-end mt-n3">👥</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-success text-white">
                    <div class="card-body">
                        <p class="mb-1 opacity-75 small">Có mặt hôm nay</p>
                        <h2 class="mb-1"><?= $stats['checked_today'] ?? 0 ?></h2>
                        <small class="opacity-75"><?= date('d/m/Y') ?></small>
                        <div class="fs-2 float-end mt-n3">📋</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasRole('production', 'manager', 'director')): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-warning">
                    <div class="card-body">
                        <p class="mb-1 small">Đơn nghỉ phép chờ duyệt</p>
                        <h2 class="mb-1"><?= $stats['pending_leaves'] ?? 0 ?></h2>
                        <a href="/erp/modules/attendance/leave_manage.php" class="btn btn-sm btn-dark mt-1">Xem & Duyệt</a>
                        <div class="fs-2 float-end mt-n3">📝</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-info text-white">
                    <div class="card-body">
                        <p class="mb-1 opacity-75 small">Đơn OT chờ duyệt</p>
                        <h2 class="mb-1"><?= $stats['pending_ot'] ?? 0 ?></h2>
                        <a href="/erp/modules/attendance/ot_manage.php" class="btn btn-sm btn-light mt-1">Xem & Duyệt</a>
                        <div class="fs-2 float-end mt-n3">⏱️</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasRole('employee')): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-success text-white">
                    <div class="card-body">
                        <p class="mb-1 opacity-75 small">Ngày công tháng <?= $currentMonth ?></p>
                        <h2 class="mb-1"><?= $stats['my_working_days'] ?? 0 ?></h2>
                        <small class="opacity-75">ngày</small>
                        <div class="fs-2 float-end mt-n3">📅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-warning">
                    <div class="card-body">
                        <p class="mb-1 small">Đơn đang chờ duyệt</p>
                        <h2 class="mb-1"><?= $stats['my_pending'] ?? 0 ?></h2>
                        <small>đơn nghỉ phép/OT</small>
                        <div class="fs-2 float-end mt-n3">⌛</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Widget Bảng lương ── -->
            <?php if (hasRole('director', 'accountant', 'manager')): ?>
            <div class="col-md-4">
                <a href="/erp/modules/payroll/index.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 <?= $pendingPayrolls > 0 ? 'border border-warning border-2' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-semibold">BẢNG LƯƠNG</p>
                                    <?php if ($latestPeriod): ?>
                                        <?php
                                        $statusMap = [
                                            'draft'     => ['secondary', '📝 Nháp'],
                                            'submitted' => ['warning',   '📤 Chờ duyệt'],
                                            'approved'  => ['success',   '✅ Đã duyệt'],
                                            'locked'    => ['dark',      '🔒 Đã lock'],
                                        ];
                                        [$sc, $sl] = $statusMap[$latestPeriod['status']] ?? ['secondary', '?'];
                                        ?>
                                        <h5 class="mb-1 fw-bold">
                                            Tháng <?= $latestPeriod['period_month'] ?>/<?= $latestPeriod['period_year'] ?>
                                        </h5>
                                        <span class="badge bg-<?= $sc ?>"><?= $sl ?></span>
                                        <div class="small text-muted mt-1">
                                            <?= $latestPeriod['working_days'] ?> ngày công chuẩn
                                        </div>
                                    <?php else: ?>
                                        <h5 class="mb-1 text-muted">Chưa có kỳ lương</h5>
                                    <?php endif; ?>

                                    <?php if ($pendingPayrolls > 0): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-bell me-1"></i>
                                            <?= $pendingPayrolls ?> kỳ chờ duyệt
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-3 p-2 ms-2">
                                    <i class="fas fa-money-check-alt fa-2x text-success"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-primary">
                                <i class="fas fa-arrow-right me-1"></i>Quản lý kỳ lương →
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <?php else: ?>
            <!-- Widget phiếu lương cho nhân viên thường -->
            <div class="col-md-4">
                <a href="/erp/modules/payroll/my_payroll.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-semibold">PHIẾU LƯƠNG</p>
                                    <?php if ($myLatestSlip): ?>
                                        <h5 class="mb-1 fw-bold text-success">
                                            <?= number_format($myLatestSlip['net_salary'], 0, '.', ',') ?> ₫
                                        </h5>
                                        <div class="small text-muted">
                                            Tháng <?= $myLatestSlip['period_month'] ?>/<?= $myLatestSlip['period_year'] ?>
                                        </div>
                                    <?php else: ?>
                                        <h5 class="mb-1 text-muted">Chưa có phiếu lương</h5>
                                        <div class="small text-muted">Liên hệ Kế toán nếu cần</div>
                                    <?php endif; ?>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-3 p-2 ms-2">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-success"></i>
                                </div>
                            </div>
                            <div class="mt-2 small text-primary">
                                <i class="fas fa-arrow-right me-1"></i>Xem phiếu lương →
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

        </div>

        <?php if (hasRole('director', 'accountant', 'manager') && array_sum($adminWarnings) > 0): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a href="/erp/modules/admin/vehicles.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">🔴 Xe sắp hết hạn trong 30 ngày</p>
                            <h3 class="mb-1 text-danger"><?= $adminWarnings['expiring_30'] ?></h3>
                            <small class="text-muted">Đăng kiểm / bảo hiểm / bảo dưỡng</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="/erp/modules/admin/vehicles.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">🟡 Xe sắp hết hạn 31-60 ngày</p>
                            <h3 class="mb-1 text-warning"><?= $adminWarnings['expiring_60'] ?></h3>
                            <small class="text-muted">Chủ động gia hạn hồ sơ xe</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="/erp/modules/admin/expenses.php?status=submitted&month=<?= date('Y-m') ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">🔵 Đề xuất chi phí chờ duyệt</p>
                            <h3 class="mb-1 text-primary"><?= $adminWarnings['pending_expenses'] ?></h3>
                            <small class="text-muted">Cần xử lý trong tháng hiện tại</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu chức năng nhanh -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-bold mb-0">⚡ Chức năng nhanh</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/attendance/index.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">⏰</div>
                                <div class="small fw-semibold">Chấm công</div>
                            </div>
                        </a>
                    </div>
                    <?php if (hasRole('employee', 'production')): ?>
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/attendance/leave_request.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">📝</div>
                                <div class="small fw-semibold">Xin nghỉ phép</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/attendance/ot_request.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">⏱️</div>
                                <div class="small fw-semibold">Đăng ký OT</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (hasRole('director', 'accountant', 'manager')): ?>
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/attendance/all_attendance.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">📊</div>
                                <div class="small fw-semibold">Bảng chấm công</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/payroll/index.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">💰</div>
                                <div class="small fw-semibold">Bảng lương</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!hasRole('director', 'accountant', 'manager')): ?>
                    <div class="col-6 col-md-3">
                        <a href="/erp/modules/payroll/my_payroll.php" class="text-decoration-none">
                            <div class="border rounded-3 p-3 text-center hover-card">
                                <div class="fs-2 mb-2">💰</div>
                                <div class="small fw-semibold">Phiếu lương</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.hover-card { transition: all 0.2s; cursor: pointer; }
.hover-card:hover { background: #f0f4ff; border-color: #0f3460 !important; transform: translateY(-2px); }
</style>

<?php include 'includes/footer.php'; ?>