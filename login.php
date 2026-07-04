<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

if (isLoggedIn()) {
    header('Location: /erp/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
        } else {
            $pdo  = getDBConnection();
            // FIX: tìm theo username HOẶC employee_code
            $stmt = $pdo->prepare("
                SELECT u.id, u.employee_code, u.full_name, u.username,
                       u.password_hash, u.is_active, u.department_id,
                       r.name AS role, r.display_name AS role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.username = ? OR u.employee_code = ?
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['employee_code'] = $user['employee_code'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['role_name']     = $user['role_name'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['login_time']    = time();

                $redirectRaw = $_GET['redirect'] ?? '';
                $redirect = (str_starts_with($redirectRaw, '/erp/') && !str_contains($redirectRaw, '//'))
                    ? $redirectRaw
                    : ($user['role'] === 'employee' ? '/erp/mobile/index.php' : '/erp/dashboard.php');
                header("Location: " . $redirect);
                exit();
            } elseif ($user && !$user['is_active']) {
                $error = 'Tài khoản của bạn đã bị khóa. Liên hệ quản trị viên.';
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
            }
        }
    }
}

$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo .logo-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #0f3460, #533483);
            border-radius: 20px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 32px; margin-bottom: 12px;
        }
        .login-logo h4 { color: #1a1a2e; font-weight: 700; margin: 0; }
        .login-logo p  { color: #666; font-size: 13px; margin: 4px 0 0; }
        .form-label { font-weight: 600; color: #333; font-size: 14px; }
        .form-control {
            border-radius: 10px; padding: 12px 15px;
            border: 2px solid #e9ecef; transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 0.2rem rgba(15,52,96,.15);
        }
        .input-group .form-control { border-right: none; }
        .input-group .btn-outline-secondary {
            border: 2px solid #e9ecef; border-left: none;
            border-radius: 0 10px 10px 0; background: white;
        }
        .btn-login {
            background: linear-gradient(135deg, #0f3460, #533483);
            border: none; border-radius: 10px; padding: 12px;
            font-size: 16px; font-weight: 600;
            letter-spacing: 0.5px; transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15,52,96,.4);
        }
        .hint-box {
            background: #f0f7ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 16px;
            font-size: 12px;
            color: #1e40af;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="logo-icon">🏢</div>
        <h4>ERP System</h4>
        <p>Hệ thống Quản lý Doanh nghiệp</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="mb-3">
            <label class="form-label">
                <i class="fas fa-id-badge me-1"></i>Mã nhân viên / Tên đăng nhập
            </label>
            <input type="text" class="form-control" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="VD: NV01 hoặc tên đăng nhập"
                   autocomplete="username" required>
        </div>

        <div class="mb-4">
            <label class="form-label"><i class="fas fa-lock me-1"></i>Mật khẩu</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="passwordInput"
                       placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-login btn-primary w-100 text-white">
            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
        </button>
    </form>

    <div class="hint-box">
        <i class="fas fa-info-circle me-1"></i>
        Đăng nhập bằng <strong>Mã nhân viên</strong> (VD: <strong>NV001</strong>)
        hoặc <strong>tên đăng nhập</strong> đã được cấp.
        Mật khẩu mặc định: <strong>123456</strong>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>