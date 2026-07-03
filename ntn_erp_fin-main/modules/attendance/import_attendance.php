<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
requireRole('director', 'accountant', 'manager', 'production');

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo     = getDBConnection();
$results = [];
$summary = null;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
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

                $inserted = 0; $updated = 0; $skipped = 0;

                // Cache users by employee_code
                $userMap = [];
                foreach ($pdo->query("SELECT id, employee_code FROM users WHERE is_active=1")->fetchAll() as $u) {
                    $userMap[strtoupper(trim($u['employee_code']))] = (int)$u['id'];
                }

                // Cache shifts by user_id
                $shiftMap = [];
                $shiftRows = $pdo->query("
                    SELECT es.user_id, ws.start_time, ws.end_time, ws.work_hours, ws.late_threshold
                    FROM employee_shifts es
                    JOIN work_shifts ws ON es.shift_id = ws.id
                    WHERE es.end_date IS NULL OR es.end_date >= CURDATE()
                ")->fetchAll();
                foreach ($shiftRows as $s) {
                    $shiftMap[(int)$s['user_id']] = $s;
                }

                $insertStmt = $pdo->prepare("
                    INSERT INTO attendance_logs
                        (user_id, work_date, check_in, check_out, work_hours,
                         is_late, late_minutes, early_leave, early_leave_minutes, source)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'import')
                    ON DUPLICATE KEY UPDATE
                        check_in            = VALUES(check_in),
                        check_out           = VALUES(check_out),
                        work_hours          = VALUES(work_hours),
                        is_late             = VALUES(is_late),
                        late_minutes        = VALUES(late_minutes),
                        early_leave         = VALUES(early_leave),
                        early_leave_minutes = VALUES(early_leave_minutes),
                        source              = 'import'
                ");

                foreach ($rows as $rowIdx => $row) {
                    if ($rowIdx == 1) continue; // Skip header

                    $rawDate = trim($row['A'] ?? '');
                    $rawCode = strtoupper(trim($row['B'] ?? ''));
                    $rawIn   = trim($row['C'] ?? '');
                    $rawOut  = trim($row['D'] ?? '');

                    if ($rawDate === '' && $rawCode === '') continue; // Blank row

                    $rowResult = [
                        'row'    => $rowIdx,
                        'code'   => $rawCode,
                        'date'   => $rawDate,
                        'in'     => $rawIn,
                        'out'    => $rawOut,
                        'status' => '',
                        'msg'    => '',
                    ];

                    // Parse date
                    $workDate = null;
                    foreach (['Y-m-d', 'd/m/Y', 'n/j/Y'] as $fmt) {
                        $dt = DateTime::createFromFormat($fmt, $rawDate);
                        if ($dt) { $workDate = $dt->format('Y-m-d'); break; }
                    }
                    if (!$workDate && strtotime($rawDate)) {
                        $workDate = date('Y-m-d', strtotime($rawDate));
                    }
                    if (!$workDate) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Ngày không hợp lệ: ' . $rawDate;
                        $results[] = $rowResult; $skipped++; continue;
                    }

                    // Lookup user
                    if (!isset($userMap[$rawCode])) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Mã NV không tồn tại: ' . $rawCode;
                        $results[] = $rowResult; $skipped++; continue;
                    }
                    $userId = $userMap[$rawCode];

                    // Validate check_in
                    if ($rawIn === '') {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Giờ vào không được để trống';
                        $results[] = $rowResult; $skipped++; continue;
                    }
                    $checkIn  = substr($rawIn,  0, 5); // HH:MM
                    $checkOut = $rawOut !== '' ? substr($rawOut, 0, 5) : null;

                    // Calculate work_hours
                    $workHours = 0;
                    if ($checkOut) {
                        $inTs  = strtotime("$workDate $checkIn");
                        $outTs = strtotime("$workDate $checkOut");
                        if ($outTs > $inTs) $workHours = round(($outTs - $inTs) / 3600, 2);
                    }

                    // Calculate late/early from shift
                    $isLate = 0; $lateMin = 0; $earlyLeave = 0; $earlyMin = 0;
                    if (isset($shiftMap[$userId])) {
                        $shift     = $shiftMap[$userId];
                        $threshold = (int)($shift['late_threshold'] ?? 0);
                        $shiftInTs = strtotime("$workDate {$shift['start_time']}");
                        $checkInTs = strtotime("$workDate $checkIn");
                        if ($checkInTs > $shiftInTs + $threshold * 60) {
                            $isLate  = 1;
                            $lateMin = (int)(($checkInTs - $shiftInTs) / 60);
                        }
                        if ($checkOut) {
                            $shiftOutTs = strtotime("$workDate {$shift['end_time']}");
                            $checkOutTs = strtotime("$workDate $checkOut");
                            if ($checkOutTs < $shiftOutTs) {
                                $earlyLeave = 1;
                                $earlyMin   = (int)(($shiftOutTs - $checkOutTs) / 60);
                            }
                        }
                    }

                    try {
                        $insertStmt->execute([
                            $userId,
                            $workDate,
                            "$workDate $checkIn:00",
                            $checkOut ? "$workDate $checkOut:00" : null,
                            $workHours,
                            $isLate, $lateMin,
                            $earlyLeave, $earlyMin,
                        ]);
                        $affected = $insertStmt->rowCount();
                        if ($affected == 1) {
                            $inserted++;
                            $rowResult['status'] = 'inserted';
                            $rowResult['msg']    = '✅ Thêm mới';
                        } else {
                            $updated++;
                            $rowResult['status'] = 'updated';
                            $rowResult['msg']    = '🔄 Cập nhật';
                        }
                    } catch (Exception $ex) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Lỗi DB: ' . $ex->getMessage();
                        $skipped++;
                    }
                    $results[] = $rowResult;
                }

                $summary = [
                    'total'    => count($results),
                    'inserted' => $inserted,
                    'updated'  => $updated,
                    'skipped'  => $skipped,
                ];

            } catch (Exception $e) {
                $errors[] = 'Lỗi đọc file Excel: ' . $e->getMessage();
            }
        }
    }
}

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
            <h4 class="mb-0">📥 Import Chấm Công từ Excel</h4>
            <p class="text-muted small mb-0">Tải lên file Excel để cập nhật dữ liệu chấm công hàng loạt</p>
        </div>
    </div>

    <?php showFlash(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>❌ Lỗi:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Hướng dẫn -->
    <div class="card border-info border-2 mb-4">
        <div class="card-header bg-info text-white fw-bold">
            <i class="fas fa-info-circle me-2"></i>Hướng dẫn nhập Excel
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <p class="mb-2 fw-semibold">Cấu trúc file Excel (dòng 1 là tiêu đề, dữ liệu từ dòng 2):</p>
                    <table class="table table-sm table-bordered w-auto mb-3">
                        <thead class="table-warning">
                            <tr><th>A - Ngày</th><th>B - Mã Nhân Viên</th><th>C - Giờ vào</th><th>D - Giờ ra</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>2026-01-02</td><td>NV001</td><td>07:30</td><td>16:30</td></tr>
                            <tr><td>2026-01-02</td><td>NV002</td><td>08:05</td><td>17:00</td></tr>
                        </tbody>
                    </table>
                    <ul class="small mb-0">
                        <li>Định dạng ngày: <code>YYYY-MM-DD</code> hoặc <code>DD/MM/YYYY</code></li>
                        <li>Định dạng giờ: <code>HH:MM</code> (VD: 07:30)</li>
                        <li>Giờ ra <strong>có thể để trống</strong> nếu chưa ra</li>
                        <li>Nếu đã có dữ liệu ngày đó → hệ thống sẽ <strong class="text-info">cập nhật</strong>, không tạo trùng</li>
                    </ul>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <a href="/erp/modules/attendance/download_att_template.php"
                       class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-download fa-lg d-block mb-1"></i>
                        Tải file Excel mẫu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form upload -->
    <?php if (!$summary): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-upload me-2 text-primary"></i>Upload file Excel
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Chọn file Excel <span class="text-danger">*</span>
                        </label>
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
    <?php endif; ?>

    <!-- Kết quả -->
    <?php if ($summary): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= $summary['total'] ?></div>
                <div class="small text-muted">📄 Tổng dòng xử lý</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success"><?= $summary['inserted'] ?></div>
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
                <div class="fs-2 fw-bold text-danger"><?= $summary['skipped'] ?></div>
                <div class="small text-muted">❌ Lỗi/Bỏ qua</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span>📋 Chi tiết kết quả</span>
            <a href="/erp/modules/attendance/import_attendance.php"
               class="btn btn-outline-primary btn-sm">
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
                            <th>Ngày</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['status'] === 'error' ? 'table-danger' : ($r['status'] === 'updated' ? 'table-info bg-opacity-50' : '') ?>">
                        <td class="text-muted small"><?= $r['row'] ?></td>
                        <td><strong><?= htmlspecialchars($r['code']) ?></strong></td>
                        <td><?= htmlspecialchars($r['date']) ?></td>
                        <td><?= htmlspecialchars($r['in']) ?></td>
                        <td><?= $r['out'] !== '' ? htmlspecialchars($r['out']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $r['msg'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="/erp/modules/attendance/all_attendance.php" class="btn btn-success">
            <i class="fas fa-table me-2"></i>Xem bảng chấm c��ng
        </a>
        <a href="/erp/modules/attendance/import_attendance.php" class="btn btn-outline-primary">
            <i class="fas fa-upload me-2"></i>Import thêm
        </a>
    </div>
    <?php endif; ?>

</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>