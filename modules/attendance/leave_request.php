<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('employee', 'production', 'warehouse');

$user = currentUser();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $type = $_POST['leave_type'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    // Tính số ngày
    $days = (strtotime($end) - strtotime($start)) / 86400 + 1;

    $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user['id'], $type, $start, $end, $days, $reason]);

    // Thông báo cho quản lý sản xuất
    $managers = $pdo->query("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE name IN ('production','manager','director'))")->fetchAll();
    foreach ($managers as $mgr) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'leave_request', ?)");
        $notifStmt->execute([$mgr['id'], 'Đơn xin nghỉ phép mới', $user['full_name'] . ' xin nghỉ từ ' . formatDate($start) . ' đến ' . formatDate($end), $pdo->lastInsertId()]);
    }

    setFlash('success', 'Đã gửi đơn xin nghỉ phép thành công!');
    header('Location: /erp/modules/attendance/leave_request.php');
    exit();
}

// Lịch sử đơn của user
$stmt = $pdo->prepare("SELECT lr.*, u.full_name as approver_name FROM leave_requests lr LEFT JOIN users u ON lr.approved_by = u.id WHERE lr.user_id = ? ORDER BY lr.created_at DESC LIMIT 20");
$stmt->execute([$user['id']]);
$myLeaves = $stmt->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">
    <h4 class="mb-4">📝 Xin nghỉ phép</h4>
    <?php showFlash(); ?>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0">Tạo đơn xin nghỉ phép</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Loại nghỉ phép</label>
                            <select name="leave_type" class="form-select" required>
                                <option value="annual">Nghỉ phép năm</option>
                                <option value="sick">Nghỉ ốm</option>
                                <option value="unpaid">Nghỉ không lương</option>
                                <option value="other">Lý do khác</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Từ ngày</label>
                                <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Đến ngày</label>
                                <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lý do</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Mô tả lý do xin nghỉ..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i>Gửi đơn
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">📋 Lịch sử đơn của tôi</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Loại</th><th>Từ ngày</th><th>Đến ngày</th><th>Số ngày</th><th>Trạng thái</th></tr></thead>
                            <tbody>
                            <?php foreach ($myLeaves as $leave): ?>
                            <tr>
                                <td><?= ['annual'=>'Phép năm','sick'=>'Ốm','unpaid'=>'KL','other'=>'Khác'][$leave['leave_type']] ?></td>
                                <td><?= formatDate($leave['start_date']) ?></td>
                                <td><?= formatDate($leave['end_date']) ?></td>
                                <td><?= $leave['total_days'] ?></td>
                                <td>
                                    <?php
                                    $badges = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                                    $labels = ['pending'=>'⌛ Chờ duyệt','approved'=>'✅ Đã duyệt','rejected'=>'❌ Từ chối'];
                                    ?>
                                    <span class="badge bg-<?= $badges[$leave['status']] ?> text-<?= $leave['status']==='pending'?'dark':'white' ?>">
                                        <?= $labels[$leave['status']] ?>
                                    </span>
                                    <?php if ($leave['status']==='rejected' && $leave['reject_reason']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($leave['reject_reason']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($myLeaves)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Chưa có đơn nào</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>