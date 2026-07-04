<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once __DIR__ . '/common.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT u.id, u.employee_code, u.full_name, u.username, u.email, u.phone,
           r.display_name AS role_name, d.name AS department_name,
           ep.mobile_phone, ep.date_of_birth, ep.date_joined,
           ep.identity_no, ep.bank_account, ep.bank_name, ep.bank_branch
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN employee_profiles ep ON ep.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$changePasswordPath = $_SERVER['DOCUMENT_ROOT'] . '/erp/modules/users/change_password.php';

mobilePageStart('Tài khoản của tôi', $user);
?>

<div class="card mobile-card mb-3">
    <div class="card-body p-4 text-center">
        <div class="avatar-circle mx-auto mb-3"><?= e(mobileUserInitial($profile['full_name'] ?? $user['full_name'])) ?></div>
        <div class="fw-bold fs-5"><?= e($profile['full_name'] ?? $user['full_name']) ?></div>
        <div class="text-muted small mb-2"><?= e($profile['employee_code'] ?? $user['employee_code']) ?> • <?= e($profile['department_name'] ?? 'Chưa có phòng ban') ?></div>
        <span class="badge text-bg-primary"><?= e($profile['role_name'] ?? $user['role_name']) ?></span>
    </div>
</div>

<div class="list-compact mb-3">
    <div class="summary-item">
        <div class="label-muted">Tên đăng nhập</div>
        <div class="fw-semibold"><?= e($profile['username'] ?? $user['username']) ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">Email</div>
        <div class="fw-semibold"><?= e($profile['email'] ?? '—') ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">Số điện thoại</div>
        <div class="fw-semibold"><?= e($profile['mobile_phone'] ?? $profile['phone'] ?? '—') ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">Ngày sinh</div>
        <div class="fw-semibold"><?= e(!empty($profile['date_of_birth']) ? formatDate($profile['date_of_birth']) : '—') ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">Ngày vào làm</div>
        <div class="fw-semibold"><?= e(!empty($profile['date_joined']) ? formatDate($profile['date_joined']) : '—') ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">CMND/CCCD</div>
        <div class="fw-semibold"><?= e($profile['identity_no'] ?? '—') ?></div>
    </div>
    <div class="summary-item">
        <div class="label-muted">Ngân hàng</div>
        <div class="fw-semibold"><?= e(trim(($profile['bank_name'] ?? '') . ' ' . ($profile['bank_branch'] ?? '')) ?: '—') ?></div>
        <div class="small text-muted mt-1">STK: <?= e($profile['bank_account'] ?? '—') ?></div>
    </div>
</div>

<div class="d-grid gap-2">
    <?php if (is_file($changePasswordPath)): ?>
    <a href="/erp/modules/users/change_password.php?id=<?= (int)$user['id'] ?>" class="btn btn-outline-primary rounded-4 py-3 fw-semibold">
        <i class="fas fa-key me-2"></i>Đổi mật khẩu
    </a>
    <?php endif; ?>
    <a href="/erp/logout.php" class="btn btn-danger rounded-4 py-3 fw-semibold">
        <i class="fas fa-right-from-bracket me-2"></i>Đăng xuất
    </a>
</div>

<?php mobilePageEnd(); ?>
