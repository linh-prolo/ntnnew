<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';
require_once 'config/audit.php';
try {
    if (isLoggedIn()) {
        $pdo = getDBConnection();
        auditLog($pdo, 'logout', 'auth', 'success', "Đăng xuất: " . ($_SESSION['full_name'] ?? 'unknown'));
    }
} catch (Throwable $e) {
}
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: /erp/login.php');
exit();
?>