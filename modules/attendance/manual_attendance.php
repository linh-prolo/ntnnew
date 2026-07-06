<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
requireRole('director', 'accountant', 'manager', 'production');

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo     = getDBConnection();
$user    = currentUser();
$results = [];
$summary = null;
$errors  = [];
$skippedRows = [];

// ── Xử lý DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    ensurePostCsrf();
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        $pdo->prepare("DELETE FROM manual_attendance WHERE id = ?")->execute([$deleteId]);
        setFlash('success', 'Đã xóa bản ghi chấm công tay.');
    }
    $month = $_POST['filter_month'] ?? date('n');
    $year  = $_POST['filter_year']  ?? date('Y');
    header("Location: /erp/modules/attendance/manual_attendance.php?month={$month}&year={$year}");
    exit;
}

// ── Xử lý IMPORT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    ensurePostCsrf();

    $importMonth = (int)($_POST['import_month'] ?? 0);
    $importYear  = (int)($_POST['import_year']  ?? 0);

    if ($importMonth < 1 || $importMonth > 12 || $importYear < 2000 || $importYear > 2100) {
        $errors[] = 'Vui lòng chọn tháng và năm hợp lệ.';
    } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Vui lòng chọn file Excel hợp lệ.';
    } else {
        $file = $_FILES['excel_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $errors[] = 'Chỉ chấp nhận file .xlsx hoặc .xls.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File không được vượt quá 10MB.';
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet       = $spreadsheet->getActiveSheet();
                $rows        = $sheet->toArray(null, true, true, true);

                $payPeriod = sprintf('%04d-%02d', $importYear, $importMonth);
                $imported  = 0;
                $updated   = 0;
                $failed    = 0;

                // Cache users by employee_code
                $userMap = [];
                foreach ($pdo->query("SELECT id, employee_code, full_name FROM users WHERE is_active=1")->fetchAll() as $u) {
                    $userMap[strtoupper(trim($u['employee_code']))] = $u;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO manual_attendance
                        (user_id, pay_period,
                         actual_work_days, paid_leave_days, unpaid_leave_days,
                         holiday_days, insurance_leave_days, personal_leave_days,
                         hours_100, hours_130, hours_150, hours_200, hours_300,
                         hours_210, hours_270, hours_390,
                         imported_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        actual_work_days    = VALUES(actual_work_days),
                        paid_leave_days     = VALUES(paid_leave_days),
                        unpaid_leave_days   = VALUES(unpaid_leave_days),
                        holiday_days        = VALUES(holiday_days),
                        insurance_leave_days= VALUES(insurance_leave_days),
                        personal_leave_days = VALUES(personal_leave_days),
                        hours_100           = VALUES(hours_100),
                        hours_130           = VALUES(hours_130),
                        hours_150           = VALUES(hours_150),
                        hours_200           = VALUES(hours_200),
                        hours_300           = VALUES(hours_300),
                        hours_210           = VALUES(hours_210),
                        hours_270           = VALUES(hours_270),
                        hours_390           = VALUES(hours_390),
                        imported_by         = VALUES(imported_by),
                        updated_at          = CURRENT_TIMESTAMP
                ");

                // Check tồn tại để phân biệt insert vs update
                $checkExist = $pdo->prepare("
                    SELECT id FROM manual_attendance WHERE user_id = ? AND pay_period = ?
                ");

                foreach ($rows as $rowIdx => $row) {
                    if ($rowIdx == 1) continue; // bỏ header

                    $rawCode = strtoupper(trim($row['C'] ?? ''));

                    // Bỏ qua dòng hoàn toàn trống
                    $allEmpty = true;
                    foreach ($row as $cell) {
                        if (trim((string)$cell) !== '') { $allEmpty = false; break; }
                    }
                    if ($allEmpty) continue;

                    // Mã NV trống → ghi vào skippedRows
                    if ($rawCode === '') {
                        $skippedRows[] = $rowIdx;
                        continue;
                    }

                    $rowResult = [
                        'row'    => $rowIdx,
                        'code'   => $rawCode,
                        'name'   => '',
                        'status' => '',
                        'msg'    => '',
                    ];

                    // Lookup user
                    if (!isset($userMap[$rawCode])) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Mã NV ' . $rawCode . ' không tồn tại';
                        $results[] = $rowResult;
                        $failed++;
                        continue;
                    }

                    $targetUser = $userMap[$rawCode];
                    $userId     = (int)$targetUser['id'];
                    $rowResult['name'] = $targetUser['full_name'];

                    // Parse số liệu từ các cột D→Q
                    $actualWorkDays   = floatval($row['D'] ?? 0);
                    $paidLeaveDays    = floatval($row['E'] ?? 0);
                    $unpaidLeaveDays  = floatval($row['F'] ?? 0);
                    $holidayDays      = floatval($row['G'] ?? 0);
                    $insuranceDays    = floatval($row['H'] ?? 0);
                    $personalDays     = floatval($row['I'] ?? 0);
                    $hours100         = floatval($row['J'] ?? 0);
                    $hours130         = floatval($row['K'] ?? 0);
                    $hours150         = floatval($row['L'] ?? 0);
                    $hours200         = floatval($row['M'] ?? 0);
                    $hours300         = floatval($row['N'] ?? 0);
                    $hours210         = floatval($row['O'] ?? 0);
                    $hours270         = floatval($row['P'] ?? 0);
                    $hours390         = floatval($row['Q'] ?? 0);

                    // Kiểm tra bản ghi có tồn tại chưa
                    $checkExist->execute([$userId, $payPeriod]);
                    $existingId = $checkExist->fetchColumn();

                    try {
                        $stmt->execute([
                            $userId, $payPeriod,
                            $actualWorkDays, $paidLeaveDays, $unpaidLeaveDays,
                            $holidayDays, $insuranceDays, $personalDays,
                            $hours100, $hours130, $hours150, $hours200, $hours300,
                            $hours210, $hours270, $hours390,
                            $user['id']
                        ]);

                        if ($existingId) {
                            $rowResult['status'] = 'updated';
                            $rowResult['msg']    = '🔄 Cập nhật';
                            $updated++;
                        } else {
                            $rowResult['status'] = 'success';
                            $rowResult['msg']    = '✅ Thêm mới';
                            $imported++;
                        }
                    } catch (Exception $ex) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Lỗi DB: ' . $ex->getMessage();
                        $failed++;
                    }

                    $results[] = $rowResult;
                }

                $summary = [
                    'total'      => count($results) + count($skippedRows),
                    'imported'   => $imported,
                    'updated'    => $updated,
                    'failed'     => $failed + count($skippedRows),
                    'pay_period' => $payPeriod,
                ];

            } catch (Exception $e) {
                $errors[] = 'Lỗi đọc file Excel: ' . $e->getMessage();
            }
        }
    }
}

// ── Query xem dữ liệu theo filter ──
$filterMonth  = (int)($_GET['month'] ?? date('n'));
$filterYear   = (int)($_GET['year']  ?? date('Y'));
$filterPeriod = sprintf('%04d-%02d', $filterYear, $filterMonth);

$viewData = $pdo->prepare("
    SELECT ma.*,
           u.employee_code, u.full_name,
           ub.full_name AS importer_name
    FROM manual_attendance ma
    JOIN users u  ON ma.user_id    = u.id
    LEFT JOIN users ub ON ma.imported_by = ub.id
    WHERE ma.pay_period = ?
    ORDER BY u.employee_code
");
$viewData->execute([$filterPeriod]);
$viewRows = $viewData->fetchAll();

$currentYear = (int)date('Y');
$yearOptions = range($currentYear - 2, $currentYear + 1);

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

    <!-- Tiêu đề -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/erp/modules/attendance/all_attendance.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">📋 Chấm Công Tay</h4>
            <p class="text-muted small mb-0">Import dữ liệu chấm công tổng hợp từ Excel</p>
        </div>
        <div class="ms-auto">
            <a href="/erp/modules/attendance/download_manual_template.php"
               class="btn btn-outline-success btn-sm">
                <i class="fas fa-download me-1"></i>Tải file Excel mẫu
            </a>
        </div>
    </div>

    <?php showFlash(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>❌ Lỗi:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Phần A: Form Import -->
    <?php if (!$summary): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-upload me-2 text-primary"></i>Import dữ liệu
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="import">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tháng <span class="text-danger">*</span></label>
                        <select name="import_month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == (int)date('n') ? 'selected' : '' ?>>
                                Tháng <?= $m ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Năm <span class="text-danger">*</span></label>
                        <select name="import_year" class="form-select" required>
                            <?php foreach ($yearOptions as $yr): ?>
                            <option value="<?= $yr ?>" <?= $yr == $currentYear ? 'selected' : '' ?>>
                                <?= $yr ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control"
                               accept=".xlsx,.xls" required>
                        <div class="form-text">Chấp nhận .xlsx, .xls — Tối đa 10MB</div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload &amp; Xử lý
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hướng dẫn -->
    <div class="card border-info border-2 mb-4">
        <div class="card-header bg-info text-white fw-bold">
            <i class="fas fa-info-circle me-2"></i>Hướng dẫn cấu trúc file Excel
        </div>
        <div class="card-body">
            <p class="fw-semibold mb-2">Cấu trúc cột (dòng 1 là tiêu đề, dữ liệu từ dòng 2):</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered w-auto mb-3">
                    <thead class="table-warning">
                        <tr>
                            <th>A</th><th>B</th><th class="table-danger">C ⭐</th>
                            <th>D</th><th>E</th><th>F</th><th>G</th><th>H</th><th>I</th>
                            <th>J</th><th>K</th><th>L</th><th>M</th><th>N</th><th>O</th><th>P</th><th>Q</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>STT</td><td>Họ Tên</td><td class="fw-bold text-danger">Mã NV</td>
                            <td>Ng.công TT</td><td>Ng.phép</td><td>Ng.k.phép</td>
                            <td>Ng.lễ</td><td>Ng.BH</td><td>Ng.riêng</td>
                            <td>Giờ 100%</td><td>Giờ 130%</td><td>Giờ 150%</td>
                            <td>Giờ 200%</td><td>Giờ 300%</td><td>Giờ 210%</td>
                            <td>Giờ 270%</td><td>Giờ 390%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <ul class="small mb-0">
                <li>Cột <strong class="text-danger">C (Mã NV)</strong>: Bắt buộc. Dùng để tra cứu nhân viên trong DB</li>
                <li>Cột B (Họ và Tên): Chỉ tham khảo, không dùng để map dữ liệu</li>
                <li>Số ngày/giờ: Dạng số thập phân (VD: 26.0, 208.0, 8.5)</li>
                <li>Kỳ lương chọn trên giao diện — KHÔNG lấy từ file</li>
                <li>Nếu bản ghi đã tồn tại → tự động cập nhật (không tạo trùng)</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Phần B: Kết quả import -->
    <?php if ($summary): ?>

    <?php if (!empty($skippedRows)): ?>
    <div class="alert alert-warning d-flex gap-2">
        <span>⚠️</span>
        <div>
            <strong>Có <?= count($skippedRows) ?> dòng bỏ qua do thiếu mã nhân viên:
            dòng <?= implode(', ', $skippedRows) ?></strong><br>
            <small>Vui lòng mở lại file Excel và kiểm tra các dòng trên.</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= $summary['total'] ?></div>
                <div class="small text-muted">📄 Tổng dòng</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= $summary['imported'] ?></div>
                <div class="small text-muted">✅ Thêm mới</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-info"><?= $summary['updated'] ?></div>
                <div class="small text-muted">🔄 Cập nhật</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger"><?= $summary['failed'] ?></div>
                <div class="small text-muted">❌ Lỗi / Bỏ qua</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span>📋 Chi tiết kết quả — Kỳ <?= e($summary['pay_period']) ?></span>
            <a href="/erp/modules/attendance/manual_attendance.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Import thêm
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Dòng</th>
                            <th>Mã NV</th>
                            <th>Tên (từ DB)</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['status'] === 'error' ? 'table-danger' : ($r['status'] === 'updated' ? 'table-info bg-opacity-25' : 'table-success bg-opacity-10') ?>">
                        <td class="text-muted small"><?= $r['row'] ?></td>
                        <td><strong><?= e($r['code']) ?></strong></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= $r['msg'] ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($skippedRows as $sr): ?>
                    <tr class="table-warning bg-opacity-25">
                        <td class="text-muted small"><?= $sr ?></td>
                        <td><span class="text-muted fst-italic">(trống)</span></td>
                        <td>—</td>
                        <td>⚠️ Bỏ qua</td>
                        <td class="small text-muted">Thiếu mã nhân viên</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <a href="/erp/modules/attendance/manual_attendance.php?month=<?= (int)explode('-', $summary['pay_period'])[1] ?>&year=<?= (int)explode('-', $summary['pay_period'])[0] ?>"
           class="btn btn-success">
            <i class="fas fa-list me-2"></i>Xem dữ liệu kỳ này
        </a>
        <a href="/erp/modules/attendance/manual_attendance.php" class="btn btn-outline-primary">
            <i class="fas fa-upload me-2"></i>Import thêm
        </a>
    </div>
    <?php endif; ?>

    <!-- Phần C: Xem & tìm kiếm dữ liệu -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-search me-2 text-secondary"></i>Xem dữ liệu chấm công
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1">Tháng</label>
                    <select name="month" class="form-select form-select-sm">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $filterMonth ? 'selected' : '' ?>>
                            Tháng <?= $m ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1">Năm</label>
                    <select name="year" class="form-select form-select-sm">
                        <?php foreach ($yearOptions as $yr): ?>
                        <option value="<?= $yr ?>" <?= $yr == $filterYear ? 'selected' : '' ?>>
                            <?= $yr ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Tìm kiếm
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <span class="badge bg-secondary fs-6"><?= count($viewRows) ?> bản ghi</span>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <?php if (empty($viewRows)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                Chưa có dữ liệu chấm công cho kỳ <?= sprintf('%02d/%04d', $filterMonth, $filterYear) ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ Tên</th>
                            <th class="text-end">Ng.công</th>
                            <th class="text-end">Giờ 100%</th>
                            <th class="text-end">Giờ 130%</th>
                            <th class="text-end">Giờ 150%</th>
                            <th class="text-end">Giờ 200%</th>
                            <th class="text-end">Giờ 300%</th>
                            <th class="text-end">Giờ 210%</th>
                            <th class="text-end">Giờ 270%</th>
                            <th class="text-end">Giờ 390%</th>
                            <th>Import bởi</th>
                            <th>Thời gian</th>
                            <th>Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($viewRows as $vr): ?>
                    <tr>
                        <td><strong><?= e($vr['employee_code']) ?></strong></td>
                        <td><?= e($vr['full_name']) ?></td>
                        <td class="text-end"><?= $vr['actual_work_days'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_100'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_130'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_150'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_200'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_300'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_210'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_270'] + 0 ?></td>
                        <td class="text-end"><?= $vr['hours_390'] + 0 ?></td>
                        <td><small class="text-muted"><?= e($vr['importer_name'] ?? '—') ?></small></td>
                        <td><small class="text-muted"><?= e(substr($vr['imported_at'] ?? '', 0, 16)) ?></small></td>
                        <td>
                            <?php if (hasRole('director', 'accountant')): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $vr['id'] ?>">
                                <input type="hidden" name="filter_month" value="<?= $filterMonth ?>">
                                <input type="hidden" name="filter_year"  value="<?= $filterYear ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1"
                                        onclick="return confirm('Xác nhận xóa bản ghi này?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
