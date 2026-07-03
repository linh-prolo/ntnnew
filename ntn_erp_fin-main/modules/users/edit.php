<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo    = getDBConnection();
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Lấy thông tin user cần sửa
$stmt = $pdo->prepare("SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$id]);
$editUser = $stmt->fetch();
if (!$editUser) { setFlash('danger', 'Không tìm thấy tài khoản.'); header('Location: /erp/modules/users/index.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $full_name  = trim($_POST['full_name']     ?? '');
    $email      = trim($_POST['email']         ?? '');
    $phone      = trim($_POST['phone']         ?? '');
    $role_id    = (int)($_POST['role_id']      ?? 0);
    $dept_id    = (int)($_POST['department_id'] ?? 0) ?: null;
    $employee_code = trim($_POST['employee_code'] ?? '');

    if (empty($full_name))     $errors[] = 'Họ tên không được để trống.';
    if (!$role_id)             $errors[] = 'Vui lòng chọn phân quyền.';
    if (empty($employee_code)) $errors[] = 'Mã nhân viên không được để trống.';

    // Kiểm tra trùng employee_code với user khác
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE employee_code = ? AND id != ?");
        $chk->execute([$employee_code, $id]);
        if ($chk->fetchColumn() > 0) $errors[] = 'Mã nhân viên đã tồn tại.';
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role_id=?, department_id=?, employee_code=? WHERE id=?")
            ->execute([$full_name, $email, $phone, $role_id, $dept_id, $employee_code, $id]);
        setFlash('success', 'Cập nhật tài khoản thành công!');
        header('Location: /erp/modules/users/index.php');
        exit();
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$csrf  = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">✏️ Chỉnh sửa tài khoản: <strong><?= htmlspecialchars($editUser['full_name']) ?></strong></h4>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="col-lg-8">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-bold"><i class="fas fa-user me-2 text-primary"></i>Thông tin cơ bản</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mã nhân viên <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control"
                                   value="<?= htmlspecialchars($_POST['employee_code'] ?? $editUser['employee_code']) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? $editUser['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($_POST['email'] ?? $editUser['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? $editUser['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-bold"><i class="fas fa-shield-alt me-2 text-success"></i>Phân quyền & Phòng ban</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phân quyền <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"
                                    <?= (($_POST['role_id'] ?? $editUser['role_id']) == $r['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['display_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phòng ban</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- Chọn phòng ban --</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= (($_POST['department_id'] ?? $editUser['department_id']) == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 p-2 bg-light rounded">
                        <small><i class="fas fa-info-circle text-info me-1"></i>
                        Username: <code><?= htmlspecialchars($editUser['username']) ?></code>
                        — Để đổi mật khẩu, dùng nút <strong>🔑 Đổi mật khẩu</strong> ở trang danh sách.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
                <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary px-4">Huỷ</a>
            </div>
        </form>
    </div>
</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>