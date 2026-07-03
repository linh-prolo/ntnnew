<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = getDBConnection();
$csrf = generateCSRF();

$results    = [];   // kết quả từng dòng
$totalRows  = 0;
$successCnt = 0;
$errorCnt   = 0;
$processed  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'CSRF token không hợp lệ.');
        header('Location: /erp/modules/users/import_excel.php');
        exit();
    }

    // Validate file
    $uploadError = $_FILES['excel_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($uploadError !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Vui lòng chọn file Excel để tải lên.');
        header('Location: /erp/modules/users/import_excel.php');
        exit();
    }

    $tmpPath  = $_FILES['excel_file']['tmp_name'];
    $origName = $_FILES['excel_file']['name'];
    $fileSize = $_FILES['excel_file']['size'];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls'])) {
        setFlash('danger', 'Chỉ chấp nhận file .xlsx hoặc .xls.');
        header('Location: /erp/modules/users/import_excel.php');
        exit();
    }

    if ($fileSize > 5 * 1024 * 1024) {
        setFlash('danger', 'File vượt quá giới hạn 5MB.');
        header('Location: /erp/modules/users/import_excel.php');
        exit();
    }

    // Load spreadsheet
    try {
        $spreadsheet = IOFactory::load($tmpPath);
    } catch (\Exception $e) {
        setFlash('danger', 'Không thể đọc file Excel: ' . htmlspecialchars($e->getMessage()));
        header('Location: /erp/modules/users/import_excel.php');
        exit();
    }

    $sheet    = $spreadsheet->getActiveSheet();
    $highRow  = $sheet->getHighestRow();

    // Chuẩn bị lookup roles
    $roleMap = [];
    foreach ($pdo->query("SELECT id, name FROM roles")->fetchAll() as $r) {
        $roleMap[strtolower(trim($r['name']))] = (int)$r['id'];
    }

    // Chuẩn bị lookup departments
    $deptMap = [];
    foreach ($pdo->query("SELECT id, name FROM departments")->fetchAll() as $d) {
        $deptMap[mb_strtolower(trim($d['name']))] = (int)$d['id'];
    }

    // Chuẩn bị lookup salary_components theo component_code
    $salaryCompMap = [];
    foreach ($pdo->query("SELECT id, component_code FROM salary_components")->fetchAll() as $sc) {
        $salaryCompMap[strtolower(trim($sc['component_code']))] = (int)$sc['id'];
    }

    // Mapping cột lương: cột Excel => component_code
    $salaryColMap = [
        'I' => 'basic_salary',
        'J' => 'meal',
        'K' => 'clothes',
        'L' => 'phone',
        'M' => 'transport',
        'N' => 'attendance_bonus',
    ];

    $processed = true;

    // Xử lý từng dòng (bỏ qua 2 dòng đầu)
    for ($rowNum = 3; $rowNum <= $highRow; $rowNum++) {
        // Đọc các cột A-N
        $employee_code = trim((string)($sheet->getCell('A' . $rowNum)->getValue() ?? ''));
        $full_name     = trim((string)($sheet->getCell('B' . $rowNum)->getValue() ?? ''));
        $username      = trim((string)($sheet->getCell('C' . $rowNum)->getValue() ?? ''));
        $password      = trim((string)($sheet->getCell('D' . $rowNum)->getValue() ?? ''));
        $email         = trim((string)($sheet->getCell('E' . $rowNum)->getValue() ?? ''));
        $phone         = trim((string)($sheet->getCell('F' . $rowNum)->getValue() ?? ''));
        $role_name     = strtolower(trim((string)($sheet->getCell('G' . $rowNum)->getValue() ?? '')));
        $dept_name     = mb_strtolower(trim((string)($sheet->getCell('H' . $rowNum)->getValue() ?? '')));

        // Bỏ qua dòng hoàn toàn trống
        if ($employee_code === '' && $full_name === '' && $username === '') {
            continue;
        }

        $totalRows++;
        $rowErrors = [];

        // Validate bắt buộc
        if ($employee_code === '') {
            $rowErrors[] = 'Mã nhân viên không được để trống';
        }
        if ($full_name === '') {
            $rowErrors[] = 'Họ và tên không được để trống';
        }
        if ($username === '') {
            $rowErrors[] = 'Tên đăng nhập không được để trống';
        }
        if ($password === '') {
            $rowErrors[] = 'Mật khẩu không được để trống';
        } elseif (strlen($password) < 6) {
            $rowErrors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }

        // Validate role
        $role_id = null;
        if ($role_name === '') {
            $rowErrors[] = 'Phân quyền không được để trống';
        } elseif (!isset($roleMap[$role_name])) {
            $rowErrors[] = "Phân quyền \"$role_name\" không hợp lệ (dùng: director/accountant/manager/warehouse/production/employee)";
        } else {
            $role_id = $roleMap[$role_name];
        }

        if (empty($rowErrors)) {
            // Kiểm tra trùng username
            $chkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $chkUser->execute([$username]);
            if ($chkUser->fetchColumn() > 0) {
                $rowErrors[] = "Tên đăng nhập \"$username\" đã tồn tại";
            }

            // Kiểm tra trùng employee_code
            $chkCode = $pdo->prepare("SELECT COUNT(*) FROM users WHERE employee_code = ?");
            $chkCode->execute([$employee_code]);
            if ($chkCode->fetchColumn() > 0) {
                $rowErrors[] = "Mã nhân viên \"$employee_code\" đã tồn tại";
            }
        }

        if (!empty($rowErrors)) {
            $results[] = [
                'row'     => $rowNum,
                'name'    => $full_name ?: $employee_code,
                'success' => false,
                'errors'  => $rowErrors,
            ];
            $errorCnt++;
            continue;
        }

        // Lookup department
        $dept_id = null;
        if ($dept_name !== '') {
            $dept_id = $deptMap[$dept_name] ?? null;
        }

        // INSERT user
        try {
            $pdo->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (employee_code, full_name, username, password_hash, email, phone, role_id, department_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$employee_code, $full_name, $username, $hash, $email ?: null, $phone ?: null, $role_id, $dept_id]);
            $newUserId = (int)$pdo->lastInsertId();

            // INSERT lương nếu có
            foreach ($salaryColMap as $col => $compCode) {
                $rawVal = $sheet->getCell($col . $rowNum)->getValue();
                $amount = (float)($rawVal ?? 0);
                if ($amount > 0 && isset($salaryCompMap[strtolower($compCode)])) {
                    $compId = $salaryCompMap[strtolower($compCode)];
                    $salStmt = $pdo->prepare("
                        INSERT INTO employee_salaries (user_id, component_id, amount, is_active)
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE amount = VALUES(amount), is_active = 1
                    ");
                    $salStmt->execute([$newUserId, $compId, $amount]);
                }
            }

            $pdo->commit();

            $results[] = [
                'row'     => $rowNum,
                'name'    => $full_name,
                'success' => true,
                'errors'  => [],
            ];
            $successCnt++;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[] = [
                'row'     => $rowNum,
                'name'    => $full_name ?: $employee_code,
                'success' => false,
                'errors'  => ['Lỗi DB: ' . $e->getMessage()],
            ];
            $errorCnt++;
        }
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- Tiêu đề -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">📥 Import Nhân viên từ Excel</h4>
            <p class="text-muted mb-0 small">Tải lên file Excel để thêm hàng loạt tài khoản nhân viên</p>
        </div>
    </div>

    <?php showFlash(); ?>

    <!-- Hướng dẫn -->
    <div class="card border-info mb-4">
        <div class="card-header bg-info text-white fw-bold">
            📋 Hướng dẫn nhập Excel
        </div>
        <div class="card-body">
            <ul class="mb-3">
                <li>Tải file mẫu trước, điền dữ liệu vào từ dòng 3 trở đi (dòng 1 là tiêu đề tổng, dòng 2 là tên cột)</li>
                <li>Các cột có dấu <strong>*</strong> là bắt buộc: Mã nhân viên, Họ và tên, Tên đăng nhập, Mật khẩu, Phân quyền</li>
                <li>Cột <strong>Phân quyền</strong> nhập đúng một trong các giá trị: <code>director</code>, <code>accountant</code>, <code>manager</code>, <code>warehouse</code>, <code>production</code>, <code>employee</code></li>
                <li>Cột <strong>Phòng ban</strong>: nhập tên phòng ban chính xác, để trống nếu chưa xác định</li>
                <li>Mật khẩu tối thiểu 6 ký tự</li>
                <li>File tối đa 5MB, định dạng <code>.xlsx</code> hoặc <code>.xls</code></li>
            </ul>
            <a href="/erp/modules/users/download_template.php" class="btn btn-outline-success">
                <i class="fas fa-download me-2"></i>Tải file Excel mẫu
            </a>
        </div>
    </div>

    <!-- Form upload -->
    <?php if (!$processed): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-upload me-2 text-primary"></i>Tải lên file Excel
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Chọn file Excel <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required id="fileInput">
                        <div class="form-text">Chấp nhận file .xlsx, .xls — tối đa 5MB</div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="fas fa-file-import me-2"></i>Xử lý Import
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kết quả import -->
    <?php if ($processed): ?>

    <!-- Bảng tổng kết -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= $totalRows ?></div>
                <div class="text-muted small">Tổng dòng xử lý</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= $successCnt ?></div>
                <div class="text-muted small">Thành công</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger"><?= $errorCnt ?></div>
                <div class="text-muted small">Lỗi / Bỏ qua</div>
            </div>
        </div>
    </div>

    <?php if ($successCnt > 0): ?>
    <div class="alert alert-success">
        ✅ Đã import thành công <strong><?= $successCnt ?></strong> nhân viên.
        <a href="/erp/modules/users/index.php" class="alert-link ms-2">Xem danh sách →</a>
    </div>
    <?php endif; ?>

    <!-- Bảng chi tiết -->
    <?php if (!empty($results)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-list me-2"></i>Chi tiết kết quả từng dòng
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:80px">Dòng</th>
                            <th>Tên / Mã</th>
                            <th style="width:140px" class="text-center">Trạng thái</th>
                            <th>Chi tiết lỗi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['success'] ? '' : 'table-danger' ?>">
                        <td class="text-center fw-semibold"><?= $r['row'] ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td class="text-center">
                            <?php if ($r['success']): ?>
                                <span class="badge bg-success">✅ Thành công</span>
                            <?php else: ?>
                                <span class="badge bg-danger">❌ Lỗi</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['errors'])): ?>
                                <ul class="mb-0 small text-danger">
                                    <?php foreach ($r['errors'] as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4 d-flex gap-2">
        <a href="/erp/modules/users/import_excel.php" class="btn btn-outline-primary">
            <i class="fas fa-redo me-2"></i>Import thêm
        </a>
        <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary">
            <i class="fas fa-list me-2"></i>Về danh sách
        </a>
    </div>

    <?php endif; // end $processed ?>

</div>
</div>

<script>
document.getElementById('importForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
