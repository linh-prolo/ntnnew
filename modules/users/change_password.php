<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin(); // ← Tất cả đều phải đăng nhập, không requireRole

$pdo         = getDBConnection();
$currentUser = currentUser();

// Xác định target: GĐ/KT đổi cho ai cũng được, NV chỉ đổi của mình
$targetId = (int)($_GET['id'] ?? $currentUser['id']);

if (!hasRole('director', 'accountant') && $targetId !== (int)$currentUser['id']) {
    setFlash('danger', 'Bạn không có quyền đổi mật khẩu tài khoản khác.');
    header("Location: /erp/modules/users/change_password.php?id={$currentUser['id']}");
    exit();
}

$isOwnAccount = ($targetId === (int)$currentUser['id']);

$stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$targetUser = $stmt->fetch();
if (!$targetUser) {
    setFlash('danger', 'Không tìm thấy tài khoản.');
    header('Location: /erp/dashboard.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $newPw  = $_POST['new_password']     ?? '';
    $newPw2 = $_POST['confirm_password'] ?? '';

    // Nếu đổi mật khẩu của chính mình → yêu cầu nhập mật khẩu cũ
    if ($isOwnAccount) {
        $oldPw = $_POST['old_password'] ?? '';
        $chk   = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $chk->execute([$targetId]);
        $hash  = $chk->fetchColumn();
        if (!password_verify($oldPw, $hash)) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }
    }

    if (strlen($newPw) < 6) $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    if ($newPw !== $newPw2) $errors[] = 'Xác nhận mật khẩu không khớp.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($newPw, PASSWORD_DEFAULT), $targetId]);
        setFlash('success', '✅ Đổi mật khẩu thành công!');
        // Sau khi đổi → về dashboard hoặc danh sách
        $redirect = hasRole('director', 'accountant') && !$isOwnAccount
                    ? '/erp/modules/users/index.php'
                    : '/erp/dashboard.php';
        header("Location: $redirect");
        exit();
    }
}

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">🔑 Đổi mật khẩu</h4>
            <p class="text-muted small mb-0">
                Tài khoản: <strong><?= htmlspecialchars($targetUser['full_name']) ?></strong>
                (<?= htmlspecialchars($targetUser['username']) ?>)
                <?php if ($isOwnAccount): ?>
                <span class="badge bg-success ms-1">Của tôi</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php showFlash(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <?php if ($isOwnAccount): ?>
                    <!-- Mật khẩu hiện tại (chỉ khi đổi của mình) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Mật khẩu hiện tại <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="old_password" id="oldPw"
                                   class="form-control" required
                                   placeholder="Nhập mật khẩu hiện tại">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePw('oldPw','eye0')">
                                <i class="fas fa-eye" id="eye0"></i>
                            </button>
                        </div>
                    </div>
                    <hr>
                    <?php else: ?>
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Đặt lại mật khẩu cho
                            <strong><?= htmlspecialchars($targetUser['full_name']) ?></strong>
                        </small>
                    </div>
                    <?php endif; ?>

                    <!-- Mật khẩu mới -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Mật khẩu mới <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPw"
                                   class="form-control" required minlength="6"
                                   placeholder="Tối thiểu 6 ký tự">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePw('newPw','eye1')">
                                <i class="fas fa-eye" id="eye1"></i>
                            </button>
                        </div>
                        <!-- Thanh độ mạnh -->
                        <div class="progress mt-2" style="height:5px;">
                            <div class="progress-bar" id="pwStrengthBar" style="width:0%"></div>
                        </div>
                        <small id="pwStrengthText" class="text-muted"></small>
                    </div>

                    <!-- Xác nhận mật khẩu mới -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Xác nhận mật khẩu mới <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="cfPw"
                                   class="form-control" required
                                   placeholder="Nhập lại mật khẩu mới">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePw('cfPw','eye2')">
                                <i class="fas fa-eye" id="eye2"></i>
                            </button>
                        </div>
                        <small class="text-danger d-none" id="pwMismatch">
                            ⚠️ Mật khẩu không khớp
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="fas fa-key me-2"></i>Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}

// Kiểm tra match
document.getElementById('cfPw').addEventListener('input', function() {
    const match = document.getElementById('pwMismatch');
    match.classList.toggle('d-none', this.value === document.getElementById('newPw').value);
});

// Độ mạnh mật khẩu
document.getElementById('newPw').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('pwStrengthBar');
    const txt = document.getElementById('pwStrengthText');
    let score = 0;
    if (val.length >= 6)             score++;
    if (val.length >= 10)            score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^A-Za-z0-9]/.test(val))    score++;
    const levels = [
        { pct:  0, cls: '',            lbl: '' },
        { pct: 20, cls: 'bg-danger',   lbl: 'Rất yếu' },
        { pct: 40, cls: 'bg-warning',  lbl: 'Yếu' },
        { pct: 60, cls: 'bg-info',     lbl: 'Trung bình' },
        { pct: 80, cls: 'bg-primary',  lbl: 'Mạnh' },
        { pct:100, cls: 'bg-success',  lbl: 'Rất mạnh' },
    ];
    const lvl = levels[score] || levels[0];
    bar.style.width = lvl.pct + '%';
    bar.className   = 'progress-bar ' + lvl.cls;
    txt.textContent = lvl.lbl;
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>