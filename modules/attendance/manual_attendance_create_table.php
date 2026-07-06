<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo     = getDBConnection();
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_table') {
    ensurePostCsrf();
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS manual_attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pay_period VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
                actual_work_days DECIMAL(5,2) DEFAULT 0,
                paid_leave_days DECIMAL(5,2) DEFAULT 0,
                unpaid_leave_days DECIMAL(5,2) DEFAULT 0,
                holiday_days DECIMAL(5,2) DEFAULT 0,
                insurance_leave_days DECIMAL(5,2) DEFAULT 0,
                personal_leave_days DECIMAL(5,2) DEFAULT 0,
                hours_100 DECIMAL(7,2) DEFAULT 0,
                hours_130 DECIMAL(7,2) DEFAULT 0,
                hours_150 DECIMAL(7,2) DEFAULT 0,
                hours_200 DECIMAL(7,2) DEFAULT 0,
                hours_300 DECIMAL(7,2) DEFAULT 0,
                hours_210 DECIMAL(7,2) DEFAULT 0,
                hours_270 DECIMAL(7,2) DEFAULT 0,
                hours_390 DECIMAL(7,2) DEFAULT 0,
                imported_by INT DEFAULT NULL,
                imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_period (user_id, pay_period),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $success = true;
        $message = 'Bảng <code>manual_attendance</code> đã được tạo thành công!';
    } catch (Exception $e) {
        $message = 'Lỗi: ' . htmlspecialchars($e->getMessage());
    }
}

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/modules/attendance/manual_attendance.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">🛠️ Tạo bảng manual_attendance</h4>
            <p class="text-muted small mb-0">Chạy script này một lần để khởi tạo bảng trong cơ sở dữ liệu</p>
        </div>
    </div>

    <?php showFlash(); ?>

    <?php if ($message): ?>
    <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?> d-flex align-items-center gap-2">
        <?= $success ? '✅' : '❌' ?> <?= $message ?>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-database me-2 text-primary"></i>Cấu trúc bảng sẽ được tạo
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-4">
                        <thead class="table-light">
                            <tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Mô tả</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>id</td><td>INT AUTO_INCREMENT</td><td>Khóa chính</td></tr>
                            <tr><td>user_id</td><td>INT NOT NULL</td><td>FK → users.id</td></tr>
                            <tr><td>pay_period</td><td>VARCHAR(7)</td><td>Kỳ lương (YYYY-MM)</td></tr>
                            <tr><td>actual_work_days</td><td>DECIMAL(5,2)</td><td>Ngày công thực tế</td></tr>
                            <tr><td>paid_leave_days</td><td>DECIMAL(5,2)</td><td>Nghỉ phép tính lương</td></tr>
                            <tr><td>unpaid_leave_days</td><td>DECIMAL(5,2)</td><td>Nghỉ không phép</td></tr>
                            <tr><td>holiday_days</td><td>DECIMAL(5,2)</td><td>Ngày lễ</td></tr>
                            <tr><td>insurance_leave_days</td><td>DECIMAL(5,2)</td><td>Nghỉ bảo hiểm</td></tr>
                            <tr><td>personal_leave_days</td><td>DECIMAL(5,2)</td><td>Nghỉ việc riêng hưởng lương</td></tr>
                            <tr><td>hours_100</td><td>DECIMAL(7,2)</td><td>Giờ ca ngày 100%</td></tr>
                            <tr><td>hours_130</td><td>DECIMAL(7,2)</td><td>Giờ ca đêm 130%</td></tr>
                            <tr><td>hours_150</td><td>DECIMAL(7,2)</td><td>Làm thêm ban ngày 150%</td></tr>
                            <tr><td>hours_200</td><td>DECIMAL(7,2)</td><td>Làm thêm ban ngày lễ 200%</td></tr>
                            <tr><td>hours_300</td><td>DECIMAL(7,2)</td><td>Làm thêm ban ngày nghỉ 300%</td></tr>
                            <tr><td>hours_210</td><td>DECIMAL(7,2)</td><td>Làm thêm ban đêm 210%</td></tr>
                            <tr><td>hours_270</td><td>DECIMAL(7,2)</td><td>Làm thêm ban đêm nghỉ 270%</td></tr>
                            <tr><td>hours_390</td><td>DECIMAL(7,2)</td><td>Làm thêm ban đêm nghỉ lễ 390%</td></tr>
                            <tr><td>imported_by</td><td>INT</td><td>User thực hiện import</td></tr>
                            <tr><td>imported_at</td><td>DATETIME</td><td>Thời gian import</td></tr>
                            <tr><td>updated_at</td><td>DATETIME</td><td>Cập nhật lần cuối</td></tr>
                        </tbody>
                    </table>

                    <?php if (!$success): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="create_table">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database me-2"></i>Tạo bảng ngay
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="/erp/modules/attendance/manual_attendance.php" class="btn btn-success">
                        <i class="fas fa-arrow-right me-2"></i>Đến trang Chấm Công Tay
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
