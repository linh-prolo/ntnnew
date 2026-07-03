<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo    = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $employee_code = trim($_POST['employee_code'] ?? '');
    $full_name     = trim($_POST['full_name']     ?? '');
    $username      = trim($_POST['username']      ?? '');
    $password      = $_POST['password']           ?? '';
    $password2     = $_POST['password2']          ?? '';
    $email         = trim($_POST['email']         ?? '');
    $phone         = trim($_POST['phone']         ?? '');
    $role_id       = (int)($_POST['role_id']      ?? 0);
    $dept_id       = (int)($_POST['department_id'] ?? 0) ?: null;

    // Nếu username để trống → dùng mã nhân viên làm username
    if (empty($username)) {
        $username = $employee_code;
    }

    if (empty($employee_code)) $errors[] = 'Mã nhân viên không được để trống.';
    if (empty($full_name))     $errors[] = 'Họ tên không được để trống.';
    if (empty($username))      $errors[] = 'Tên đăng nhập không được để trống.';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($password !== $password2) $errors[] = 'Xác nhận mật khẩu không khớp.';
    if (!$role_id)             $errors[] = 'Vui lòng chọn phân quyền.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetchColumn() > 0) $errors[] = 'Tên đăng nhập đã tồn tại.';

        $chk2 = $pdo->prepare("SELECT COUNT(*) FROM users WHERE employee_code = ?");
        $chk2->execute([$employee_code]);
        if ($chk2->fetchColumn() > 0) $errors[] = 'Mã nhân viên đã tồn tại.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (employee_code, full_name, username, password_hash, email, phone, role_id, department_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$employee_code, $full_name, $username, $hash, $email, $phone, $role_id, $dept_id]);

        setFlash('success', "✅ Tạo tài khoản <strong>" . htmlspecialchars($full_name) . "</strong> thành công! Đăng nhập bằng mã <code>$employee_code</code>");
        header('Location: /erp/modules/users/index.php');
        exit();
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$lastCode    = $pdo->query("SELECT employee_code FROM users ORDER BY id DESC LIMIT 1")->fetchColumn();
$nextNum     = $lastCode ? (intval(substr($lastCode, 2)) + 1) : 1;
$suggestCode = 'NV' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">➕ Tạo tài khoản mới</h4>
            <p class="text-muted mb-0 small">Điền đầy đủ thông tin để tạo tài khoản</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>❌ Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" id="createForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <!-- Thông tin cơ bản -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-user me-2 text-primary"></i>Thông tin cơ bản
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mã nhân viên <span class="text-danger">*</span></label>
                                <input type="text" name="employee_code" class="form-control" id="employeeCodeInput"
                                       value="<?= htmlspecialchars($_POST['employee_code'] ?? $suggestCode) ?>"
                                       placeholder="VD: NV008" required
                                       oninput="syncUsername(this.value)">
                                <div class="form-text text-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Mã NV sẽ là tên đăng nhập mặc định
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" id="fullNameInput"
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                       placeholder="Nguyễn Văn A" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       placeholder="email@company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       placeholder="0901234567">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phân quyền & Phòng ban -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-shield-alt me-2 text-success"></i>Phân quyền & Phòng ban
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phân quyền <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select" required id="roleSelect">
                                    <option value="">-- Chọn quyền --</option>
                                    <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"
                                        <?= (($_POST['role_id'] ?? '') == $r['id']) ? 'selected' : '' ?>
                                        data-role="<?= $r['name'] ?>">
                                        <?= htmlspecialchars($r['display_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2" id="roleBadgePreview"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phòng ban</label>
                                <select name="department_id" class="form-select">
                                    <option value="">-- Chọn phòng ban --</option>
                                    <?php foreach ($depts as $d): ?>
                                    <option value="<?= $d['id'] ?>"
                                        <?= (($_POST['department_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin đăng nhập -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-lock me-2 text-warning"></i>Thông tin đăng nhập
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Tên đăng nhập mặc định = <strong>Mã nhân viên</strong>.
                            Có thể đổi nếu muốn dùng tên khác.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="username" class="form-control" id="usernameInput"
                                           value="<?= htmlspecialchars($_POST['username'] ?? $suggestCode) ?>"
                                           placeholder="vd: NV008" required>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                            onclick="resetUsername()" title="Đặt lại = mã NV">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="form-text">Chỉ dùng chữ không dấu, số, gạch dưới</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="pw1"
                                           placeholder="Tối thiểu 6 ký tự" required minlength="6">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw1','eye1')">
                                        <i class="fas fa-eye" id="eye1"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password2" class="form-control" id="pw2"
                                           placeholder="Nhập lại mật khẩu" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw2','eye2')">
                                        <i class="fas fa-eye" id="eye2"></i>
                                    </button>
                                </div>
                                <div class="form-text text-danger d-none" id="pwMismatch">⚠️ Mật khẩu không khớp</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-user-plus me-2"></i>Tạo tài khoản
                    </button>
                    <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary px-4">Huỷ</a>
                </div>
            </form>
        </div>

        <!-- Hướng dẫn -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📋 Mô tả phân quyền</h6>
                    <div class="role-item mb-2"><span class="badge bg-danger me-1">👑 Giám đốc</span><small class="text-muted d-block mt-1">Xem toàn bộ dữ liệu, duyệt bảng lương</small></div>
                    <div class="role-item mb-2"><span class="badge bg-warning text-dark me-1">💰 Kế toán</span><small class="text-muted d-block mt-1">Hoá đơn, lương, bảo hiểm, xuất nhập tồn</small></div>
                    <div class="role-item mb-2"><span class="badge bg-primary me-1">🏢 Quản lý</span><small class="text-muted d-block mt-1">Quản lý chung, duyệt đơn từ cấp dưới</small></div>
                    <div class="role-item mb-2"><span class="badge bg-info me-1">📦 Quản lý Kho</span><small class="text-muted d-block mt-1">Nhập/xuất kho, phiếu xuất kho</small></div>
                    <div class="role-item mb-2"><span class="badge bg-success me-1">🏭 Quản lý SX</span><small class="text-muted d-block mt-1">Duyệt OT và nghỉ phép nhân viên</small></div>
                    <div class="role-item"><span class="badge bg-secondary me-1">👤 Nhân viên</span><small class="text-muted d-block mt-1">Chấm công, xin OT/phép, xem bảng lương</small></div>
                </div>
            </div>
            <div class="card border-0 shadow-sm border-info">
                <div class="card-body small text-muted">
                    <h6 class="fw-bold text-info mb-2"><i class="fas fa-lightbulb me-1"></i>Lưu ý đăng nhập</h6>
                    <p class="mb-1">Nhân viên có thể đăng nhập bằng:</p>
                    <ul class="mb-0">
                        <li><strong>Mã nhân viên</strong> (VD: <code>NV008</code>)</li>
                        <li>Hoặc <strong>tên đăng nhập</strong> tuỳ chỉnh</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Đồng bộ username khi thay đổi mã NV (chỉ khi username chưa bị sửa tay)
let usernameTouched = false;
document.getElementById('usernameInput').addEventListener('input', function() {
    usernameTouched = true;
});

function syncUsername(code) {
    if (!usernameTouched) {
        document.getElementById('usernameInput').value = code;
    }
}

function resetUsername() {
    const code = document.getElementById('employeeCodeInput').value;
    document.getElementById('usernameInput').value = code;
    usernameTouched = false;
}

function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.getElementById('pw2').addEventListener('input', function() {
    const pw1 = document.getElementById('pw1').value;
    const msg = document.getElementById('pwMismatch');
    msg.classList.toggle('d-none', this.value === pw1 || this.value === '');
});

const roleColors = { 'director':'danger','accountant':'warning','manager':'primary','warehouse':'info','production':'success','employee':'secondary' };
const roleIcons  = { 'director':'👑','accountant':'💰','manager':'🏢','warehouse':'📦','production':'🏭','employee':'👤' };
document.getElementById('roleSelect').addEventListener('change', function() {
    const opt      = this.options[this.selectedIndex];
    const roleName = opt.dataset.role;
    const preview  = document.getElementById('roleBadgePreview');
    preview.innerHTML = roleName
        ? `<span class="badge bg-${roleColors[roleName]||'secondary'} fs-6">${roleIcons[roleName]||''} ${opt.text}</span>`
        : '';
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>