<?php

function mobileNavItems(): array {
    return [
        'index.php' => ['href' => '/erp/mobile/index.php', 'icon' => 'fa-clock', 'label' => 'Chấm công'],
        'leave.php' => ['href' => '/erp/mobile/leave.php', 'icon' => 'fa-file-signature', 'label' => 'Xin phép'],
        'ot.php' => ['href' => '/erp/mobile/ot.php', 'icon' => 'fa-business-time', 'label' => 'Đăng ký OT'],
        'payslip.php' => ['href' => '/erp/mobile/payslip.php', 'icon' => 'fa-wallet', 'label' => 'Lương'],
        'me.php' => ['href' => '/erp/mobile/me.php', 'icon' => 'fa-user', 'label' => 'Tôi'],
    ];
}

function mobileCurrentPage(): string {
    return basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

function mobileUserInitial(string $fullName): string {
    $trimmed = trim($fullName);
    if ($trimmed === '') {
        return 'U';
    }

    return mb_strtoupper(mb_substr($trimmed, 0, 1));
}

function mobileStatusBadge(string $status): array {
    return match ($status) {
        'approved' => ['class' => 'success', 'label' => 'Đã duyệt'],
        'rejected' => ['class' => 'danger', 'label' => 'Từ chối'],
        default => ['class' => 'warning text-dark', 'label' => 'Chờ duyệt'],
    };
}

function mobileLeaveTypeLabel(string $type): string {
    return [
        'annual' => 'Phép năm',
        'sick' => 'Nghỉ ốm',
        'unpaid' => 'Không lương',
        'other' => 'Khác',
    ][$type] ?? $type;
}

function mobileOtTypeLabel(string $type): string {
    return [
        'weekday' => 'Ngày thường',
        'weekend' => 'Cuối tuần',
        'holiday' => 'Ngày lễ',
        'night_weekday' => 'Đêm thường',
        'night_weekend' => 'Đêm cuối tuần',
        'night_holiday' => 'Đêm ngày lễ',
    ][$type] ?? $type;
}

function mobilePageStart(string $title, array $user): void {
    $currentPage = mobileCurrentPage();
    $menuItems = mobileNavItems();
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0d6efd">
    <title><?= e($title) ?> - ERP Mobile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
            color: #1f2937;
        }
        body {
            min-height: 100vh;
        }
        .mobile-shell {
            max-width: 480px;
            min-height: 100vh;
            margin: 0 auto;
            background: #f5f7fa;
            position: relative;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.04);
        }
        .mobile-topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e5e7eb;
        }
        .mobile-topbar .btn {
            border-radius: 12px;
        }
        .mobile-user-label {
            min-width: 0;
        }
        .mobile-user-label .name {
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mobile-content {
            padding: 16px 16px 88px;
        }
        .page-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .mobile-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,.08);
        }
        .attendance-card {
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
        }
        .check-btn {
            height: 64px;
            font-size: 20px;
            font-weight: 700;
            border-radius: 16px;
        }
        .clock-panel {
            text-align: center;
            padding: 20px 16px;
        }
        .clock-panel .date-text {
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .clock-panel .time-text {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .stat-tile {
            background: #fff;
            border-radius: 18px;
            padding: 12px 8px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .stat-tile .icon {
            display: block;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }
        .stat-tile .value {
            font-weight: 700;
            font-size: 1rem;
        }
        .stat-tile .label {
            font-size: 12px;
            color: #64748b;
        }
        .history-item,
        .summary-item {
            background: #fff;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }
        .bottom-nav {
            position: fixed;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-top: 1px solid #dee2e6;
            z-index: 1000;
        }
        .bottom-nav .nav-link {
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            padding: 10px 4px calc(10px + env(safe-area-inset-bottom, 0px));
        }
        .bottom-nav .nav-link i {
            display: block;
            font-size: 1rem;
            margin-bottom: 4px;
        }
        .bottom-nav .nav-link.active {
            color: #0d6efd;
        }
        .avatar-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .list-compact > * + * {
            margin-top: 12px;
        }
        .label-muted {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .payslip-amount {
            font-size: 1.65rem;
            font-weight: 800;
            color: #198754;
        }
        @media (max-width: 380px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body>
<div class="mobile-shell">
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header">
            <div>
                <div class="fw-bold" id="mobileMenuLabel">ERP Mobile</div>
                <div class="small text-muted"><?= e($user['full_name']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="list-group list-group-flush mb-3">
                <?php foreach ($menuItems as $file => $item): ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= $currentPage === $file ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                    <i class="fas <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
                <?php endforeach; ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2" href="/erp/dashboard.php">
                    <i class="fas fa-table-columns"></i>
                    <span>Dashboard desktop</span>
                </a>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 text-danger" href="/erp/logout.php">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </div>
    </div>

    <div class="mobile-topbar px-3 py-2">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <button class="btn btn-light border" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                <i class="fas fa-bars"></i>
                <span class="small ms-1">Menu</span>
            </button>
            <div class="mobile-user-label text-center flex-grow-1">
                <div class="small text-muted">Nhân viên</div>
                <div class="name"><?= e($user['full_name']) ?></div>
            </div>
            <a class="btn btn-light border" href="/erp/dashboard.php" aria-label="Thông báo">
                <i class="fas fa-bell"></i>
            </a>
        </div>
    </div>

    <main class="mobile-content">
        <div class="page-title"><?= e($title) ?></div>
<?php
}

function mobilePageEnd(): void {
    $currentPage = mobileCurrentPage();
    $menuItems = mobileNavItems();
    ?>
    </main>

    <nav class="bottom-nav">
        <div class="nav nav-justified">
            <?php foreach ($menuItems as $file => $item): ?>
            <a class="nav-link <?= $currentPage === $file ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                <i class="fas <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
