<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>403 - Không có quyền truy cập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="text-center">
        <div class="mb-4" style="font-size:5rem;">🚫</div>
        <h1 class="display-1 fw-bold text-danger">403</h1>
        <h4 class="mb-3">Bạn không có quyền truy cập trang này!</h4>
        <p class="text-muted">Tài khoản của bạn không được phép xem nội dung này.<br>
        Vui lòng liên hệ quản trị viên nếu cần hỗ trợ.</p>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <a href="/erp/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i>Về trang chủ
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>
</div>
</body>
</html>