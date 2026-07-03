<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
}
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/erp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="z-index:1030;">
    <div class="container-fluid">
        <button class="btn btn-sm text-white me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand fw-bold" href="/erp/dashboard.php">🏢 ERP System</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- Thông báo -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount" style="display:none;">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:300px;" id="notifDropdown">
                    <li><div class="dropdown-header">Thông báo</div></li>
                    <li><div class="dropdown-item text-muted small text-center py-3">Không có thông báo mới</div></li>
                </ul>
            </div>
            <!-- User menu -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= htmlspecialchars($user['full_name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><div class="dropdown-header">
                        <?php $badge = getRoleBadge($user['role']); ?>
                        <span class="badge bg-<?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
                        <div class="text-muted small mt-1"><?= htmlspecialchars($user['employee_code']) ?></div>
                    </div></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/erp/logout.php"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>