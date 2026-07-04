<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once __DIR__ . '/common.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/leave.php');
        exit();
    }

    $type = $_POST['leave_type'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!in_array($type, ['annual', 'sick', 'unpaid', 'other'], true)) {
        $errors[] = 'Loại nghỉ phép không hợp lệ.';
    }
    if ($start === '' || $end === '') {
        $errors[] = 'Vui lòng chọn đầy đủ ngày bắt đầu và kết thúc.';
    }
    if ($reason === '') {
        $errors[] = 'Vui lòng nhập lý do.';
    }
    if ($start !== '' && $end !== '' && $start > $end) {
        $errors[] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
    }

    if (empty($errors)) {
        $days = (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $type, $start, $end, $days, $reason]);
        $requestId = (int)$pdo->lastInsertId();

        $managers = $pdo->query("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE name IN ('production','manager','director')) AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($managers as $mgr) {
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'leave_request', ?)");
            $notifStmt->execute([$mgr['id'], 'Đơn xin nghỉ phép mới', $user['full_name'] . ' xin nghỉ từ ' . formatDate($start) . ' đến ' . formatDate($end), $requestId]);
        }

        setFlash('success', 'Đã gửi đơn xin nghỉ phép thành công!');
        header('Location: /erp/mobile/leave.php');
        exit();
    }
}

$stmt = $pdo->prepare("SELECT lr.*, u.full_name AS approver_name FROM leave_requests lr LEFT JOIN users u ON lr.approved_by = u.id WHERE lr.user_id = ? ORDER BY lr.created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$myLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csrf = generateCSRF();

mobilePageStart('Xin nghỉ phép', $user);
showFlash();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card mobile-card mb-3">
    <div class="card-body p-4">
        <form method="POST" class="list-compact">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div>
                <label class="form-label fw-semibold">Loại nghỉ</label>
                <select name="leave_type" class="form-select" required>
                    <?php foreach (['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương', 'other' => 'Lý do khác'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($_POST['leave_type'] ?? 'annual') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" required value="<?= e($_POST['start_date'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" required value="<?= e($_POST['end_date'] ?? '') ?>">
                </div>
            </div>
            <div>
                <label class="form-label fw-semibold">Lý do</label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Mô tả lý do xin nghỉ..."><?= e($_POST['reason'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold rounded-4">
                <i class="fas fa-paper-plane me-2"></i>Gửi đơn
            </button>
        </form>
    </div>
</div>

<div class="fw-bold mb-2">5 đơn gần nhất</div>
<div class="list-compact">
    <?php foreach ($myLeaves as $leave): ?>
        <?php $badge = mobileStatusBadge($leave['status']); ?>
        <div class="summary-item">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <div class="fw-semibold"><?= e(mobileLeaveTypeLabel($leave['leave_type'])) ?></div>
                    <div class="small text-muted"><?= e(formatDate($leave['start_date'])) ?> → <?= e(formatDate($leave['end_date'])) ?></div>
                </div>
                <span class="badge bg-<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
            </div>
            <div class="small">Số ngày: <strong><?= e((string)$leave['total_days']) ?></strong></div>
            <div class="small text-muted mt-1"><?= nl2br(e($leave['reason'])) ?></div>
            <?php if ($leave['status'] === 'rejected' && !empty($leave['reject_reason'])): ?>
            <div class="small text-danger mt-2">Lý do từ chối: <?= e($leave['reject_reason']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($myLeaves)): ?>
    <div class="summary-item text-center text-muted">Chưa có đơn nghỉ phép nào.</div>
    <?php endif; ?>
</div>

<?php mobilePageEnd(); ?>
