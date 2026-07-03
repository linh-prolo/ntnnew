<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// ---- Kiểm tra đăng nhập ----
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $current = urlencode($_SERVER['REQUEST_URI']);
        header("Location: /erp/login.php?redirect=$current");
        exit();
    }
}

// ---- Kiểm tra role ----
function hasRole(...$roles) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles);
}

function requireRole(...$roles) {
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit();
    }
}

// ---- Lấy thông tin user hiện tại ----
function currentUser() {
    return [
        'id'          => $_SESSION['user_id']   ?? null,
        'full_name'   => $_SESSION['full_name'] ?? '',
        'username'    => $_SESSION['username']  ?? '',
        'role'        => $_SESSION['role']      ?? '',
        'role_name'   => $_SESSION['role_name'] ?? '',
        'employee_code' => $_SESSION['employee_code'] ?? '',
        'department_id' => $_SESSION['department_id'] ?? null,
    ];
}

// ---- Lấy màu badge theo role ----
function getRoleBadge($role) {
    $map = [
        'director'   => ['class' => 'danger',   'icon' => '👑', 'label' => 'Giám đốc'],
        'accountant' => ['class' => 'warning',   'icon' => '💰', 'label' => 'Kế toán'],
        'manager'    => ['class' => 'primary',   'icon' => '🏢', 'label' => 'Quản lý'],
        'warehouse'  => ['class' => 'info',      'icon' => '📦', 'label' => 'Quản lý Kho'],
        'production' => ['class' => 'success',   'icon' => '🏭', 'label' => 'Quản lý SX'],
        'employee'   => ['class' => 'secondary', 'icon' => '👤', 'label' => 'Nhân viên'],
    ];
    return $map[$role] ?? ['class' => 'secondary', 'icon' => '❓', 'label' => $role];
}
?>