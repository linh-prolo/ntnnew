<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('production', 'manager', 'director', 'accountant');

$user = currentUser();
$pdo = getDBConnection();

// Xử lý duyệt/từ chối
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $id = (int)$_POST['request_id'];
    $action = $_POST['action']; // approved / rejected
    $reason = trim($_POST['reject_reason'] ?? '');

    $stmt = $pdo->prepare("UPDATE leave_requests SET status=?, approved_by=?, approved_at=NOW(), reject_reason=? WHERE id=? AND status='pending'");
    $stmt->execute([$action, $user['id'], $reason, $id]);

    if ($stmt->rowCount()) {
        // Thông báo cho nhân viên
        $req = $pdo->prepare("SELECT user_id FROM leave_requests WHERE id=?");
        $req->execute([$id]);
        $lr = $req->fetch();
        $msg = $action==='approved' ? '✅ Đơn xin nghỉ phép của bạn đã được duyệt.' : '❌ Đơn xin nghỉ phép bị từ chối: '.$reason;
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, 'Kết quả đơn nghỉ phép', ?, 'leave_request', ?)");
        $notif->execute([$lr['user_id'], $msg, $id]);
        setFlash('success', 'Đã xử lý đơn nghỉ phép.');
    }
    header('Location: /erp/modules/attendance/leave_manage.php');
    exit();
}

$filter = $_GET['filter'] ?? 'pending';
$stmt = $pdo->prepare("
    SELECT lr.*, u.full_name, u.employee_code,
           d.name as department_name,
           a.full_name as approver_name
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN users a ON lr.approved_by = a.id
    WHERE (? = 'all' OR lr.status = ?)
    ORDER BY lr.created_at DESC
    LIMIT 50
");
$stmt->execute([$filter, $filter]);
$requests = $stmt->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">
    <h4 class="mb-4">📋 Duyệt đơn nghỉ phép</h4>
    <?php showFlash(); ?>

    <!-- Filter -->
    <div class="btn-group mb-3">
        <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending'?'btn-warning':'btn-outline-warning' ?>">⌛ Chờ duyệt</a>
        <a href="?filter=approved" class="btn btn-sm <?= $filter==='approved'?'btn-success':'btn-outline-success' ?>">✅ Đã duyệt</a>
        <a href="?filter=rejected" class="btn btn-sm <?= $filter==='rejected'?'btn-danger':'btn-outline-danger' ?>">❌ Từ chối</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-secondary':'btn-outline-secondary' ?>">Tất cả</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr><th>Nhân viên</th><th>Loại</th><th>Từ Ngày</th><th>Đến Ngày</th><th>Số Ngày Nghỉ</th><th>Lý do</th><th>Ngày tạo</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($r['full_name']) ?></div>
                            <small class="text-muted"><?= $r['employee_code'] ?> &bull; <?= htmlspecialchars($r['department_name'] ?? '') ?></small>
                        </td>
                        <td><?= ['annual'=>'Phép năm','sick'=>'Ốm','unpaid'=>'KL','other'=>'Khác'][$r['leave_type']] ?></td>
                        <td><?= formatDate($r['start_date']) ?></td>
                        <td><?= formatDate($r['end_date']) ?></td>
                        <td><?= $r['total_days'] ?></td>
                        <td><small><?= htmlspecialchars($r['reason']) ?></small></td>
                        <td><small><?= formatDate($r['created_at'], 'd/m H:i') ?></small></td>
                        <td>
                            <?php $badges=['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                                  $labels=['pending'=>'Chờ','approved'=>'Duyệt','rejected'=>'Từ chối']; ?>
                            <span class="badge bg-<?= $badges[$r['status']] ?>"><?= $labels[$r['status']] ?></span>
                        </td>
                        <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <div class="d-flex gap-1">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="approved">
                                    <button class="btn btn-success btn-sm" onclick="return confirm('Duyệt đơn này?')">✅</button>
                                </form>
                                <button class="btn btn-danger btn-sm" onclick="showRejectForm(<?= $r['id'] ?>)">❌</button>
                            </div>
                        <?php else: ?>
                            <small class="text-muted"><?= $r['approver_name'] ? htmlspecialchars($r['approver_name']) : '-' ?></small>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Không có đơn nào</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal từ chối -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="request_id" id="rejectId">
                <input type="hidden" name="action" value="rejected">
                <div class="modal-header">
                    <h6 class="modal-title">❌ Từ chối đơn nghỉ phép</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Lý do từ chối</label>
                    <textarea name="reject_reason" class="form-control" rows="3" required placeholder="Nhập lý do..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function showRejectForm(id) {
    document.getElementById('rejectId').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>