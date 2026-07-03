<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
requireRole('production', 'manager', 'director', 'accountant');

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo     = getDBConnection();
$user    = currentUser();
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
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File không được vượt quá 5MB.';
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet       = $spreadsheet->getActiveSheet();
                $rows        = $sheet->toArray(null, true, true, true);

                $inserted = 0;
                $skipped  = 0;
                $failed   = 0;

                // Cache users by employee_code
                $userMap = [];
                foreach ($pdo->query("SELECT id, employee_code, department_id FROM users WHERE is_active=1")->fetchAll() as $u) {
                    $userMap[strtoupper(trim($u['employee_code']))] = $u;
                }

                // Cache shifts by user_id (ca làm việc hiện tại)
                $shiftMap = [];
                $shiftRows = $pdo->query("
                    SELECT es.user_id, ws.id AS shift_id, ws.end_time,
                           ws.ot_multiplier, ws.weekend_multiplier, ws.holiday_multiplier
                    FROM employee_shifts es
                    JOIN work_shifts ws ON es.shift_id = ws.id
                    WHERE (es.end_date IS NULL OR es.end_date >= CURDATE())
                ")->fetchAll();
                foreach ($shiftRows as $s) {
                    $shiftMap[(int)$s['user_id']] = $s;
                }

                // Cache holidays
                $holidaySet = [];
                foreach ($pdo->query("SELECT holiday_date FROM holidays")->fetchAll() as $h) {
                    $holidaySet[$h['holiday_date']] = true;
                }

                // Prepare insert
                $insertStmt = $pdo->prepare("
                    INSERT INTO overtime_requests
                        (user_id, ot_date, start_time, end_time, hours, reason, ot_type, shift_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                // Check duplicate
                $checkDup = $pdo->prepare("
                    SELECT COUNT(*) FROM overtime_requests
                    WHERE user_id = ? AND ot_date = ? AND status != 'rejected'
                ");

                // Notify managers
                $managers = $pdo->query("
                    SELECT id FROM users
                    WHERE role_id IN (SELECT id FROM roles WHERE name IN ('production','manager','director'))
                    AND is_active = 1
                ")->fetchAll();

                foreach ($rows as $rowIdx => $row) {
                    if ($rowIdx == 1) continue; // bỏ header

                    $rawDate = trim($row['A'] ?? '');
                    $rawCode = strtoupper(trim($row['B'] ?? ''));
                    $rawHrs  = trim($row['C'] ?? '');
                    $rawNote = trim($row['D'] ?? '');

                    if ($rawDate === '' && $rawCode === '' && $rawHrs === '') continue;

                    $rowResult = [
                        'row'    => $rowIdx,
                        'code'   => $rawCode,
                        'date'   => $rawDate,
                        'hours'  => $rawHrs,
                        'reason' => $rawNote,
                        'status' => '',
                        'msg'    => '',
                    ];

                    // Parse date
                    $workDate = null;
                    foreach (['Y-m-d', 'd/m/Y', 'n/j/Y', 'd-m-Y'] as $fmt) {
                        $dt = DateTime::createFromFormat($fmt, $rawDate);
                        if ($dt && $dt->format($fmt) == $rawDate) {
                            $workDate = $dt->format('Y-m-d');
                            break;
                        }
                    }
                    if (!$workDate && is_numeric($rawDate)) {
                        // Excel serial date
                        $unixDate = ($rawDate - 25569) * 86400;
                        $workDate = date('Y-m-d', $unixDate);
                    }
                    if (!$workDate) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Ngày không hợp lệ: ' . $rawDate;
                        $results[] = $rowResult; $failed++; continue;
                    }

                    

                    // Lookup user
                    if ($rawCode === '') {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Mã nhân viên không được để trống';
                        $results[] = $rowResult; $failed++; continue;
                    }
                    if (!isset($userMap[$rawCode])) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Mã NV không tồn tại: ' . $rawCode;
                        $results[] = $rowResult; $failed++; continue;
                    }
                    $targetUser = $userMap[$rawCode];
                    $userId     = (int)$targetUser['id'];

                    // Validate hours
                    $hours = (float)$rawHrs;
                    if ($hours <= 0 || $hours > 12) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Số giờ OT không hợp lệ (0 < giờ ≤ 12): ' . $rawHrs;
                        $results[] = $rowResult; $failed++; continue;
                    }

                    // Check duplicate
                    $checkDup->execute([$userId, $workDate]);
                    if ($checkDup->fetchColumn() > 0) {
                        $rowResult['status'] = 'skip';
                        $rowResult['msg']    = '⚠️ Bỏ qua: đã có đơn OT ngày này';
                        $results[] = $rowResult; $skipped++; continue;
                    }

                    // Xác định loại OT
                    $dow = (int)date('N', strtotime($workDate)); // 1=Mon..7=Sun
                    if (isset($holidaySet[$workDate]))  $otType = 'holiday';
                    elseif ($dow >= 6)                  $otType = 'weekend';
                    else                                $otType = 'weekday';

                    // Lấy ca làm việc
                    $shift    = $shiftMap[$userId] ?? null;
                    $shiftId  = $shift ? (int)$shift['shift_id'] : null;
                    $startOT  = $shift ? substr($shift['end_time'], 0, 5) : '17:00';

                    // Tính giờ kết thúc OT
                    $startTs = strtotime("$workDate $startOT");
                    $endTs   = $startTs + (int)($hours * 3600);
                    $endOT   = date('H:i', $endTs);

                    $reason = $rawNote !== '' ? $rawNote : 'Tăng ca theo kế hoạch';

                    try {
                        $insertStmt->execute([
                            $userId, $workDate,
                            $startOT . ':00',
                            $endOT   . ':00',
                            $hours, $reason, $otType, $shiftId
                        ]);
                        $newId = $pdo->lastInsertId();

                        // Gửi thông báo cho quản lý
                        foreach ($managers as $mgr) {
                            $pdo->prepare("
                                INSERT INTO notifications (user_id, title, message, type, reference_id)
                                VALUES (?, ?, ?, 'ot_request', ?)
                            ")->execute([
                                $mgr['id'],
                                '📋 Đơn đăng ký OT mới (Import)',
                                $rawCode . ' đăng ký OT ngày ' . formatDate($workDate) . ' (' . $hours . ' giờ) — ' . $reason,
                                $newId
                            ]);
                        }

                        $rowResult['status'] = 'success';
                        $rowResult['msg']    = '✅ Tạo đơn OT thành công (' . $otType . ')';
                        $inserted++;
                    } catch (Exception $ex) {
                        $rowResult['status'] = 'error';
                        $rowResult['msg']    = '❌ Lỗi DB: ' . $ex->getMessage();
                        $failed++;
                    }

                    $results[] = $rowResult;
                }

                $summary = [
                    'total'    => count($results),
                    'inserted' => $inserted,
                    'skipped'  => $skipped,
                    'failed'   => $failed,
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
        <a href="/erp/modules/attendance/ot_manage.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">📥 Import Đăng Ký OT Hàng Loạt</h4>
            <p class="text-muted small mb-0">Tải lên file Excel để đăng ký OT cho nhiều nhân viên cùng lúc</p>
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

    <!-- Hướng dẫn + form mẫu -->
    <div class="card border-info border-2 mb-4">
        <div class="card-header bg-info text-white fw-bold">
            <i class="fas fa-info-circle me-2"></i>Hướng dẫn & Form mẫu
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <p class="fw-semibold mb-2">Cấu trúc file Excel (dòng 1 là tiêu đề, dữ liệu từ dòng 2):</p>
                    <table class="table table-sm table-bordered w-auto mb-3">
                        <thead class="table-warning">
                            <tr>
                                <th>A - ngày</th>
                                <th>B - mã nhân viên</th>
                                <th>C - số giờ đăng ký OT</th>
                                <th>D - lý do (nếu có)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>2026-05-10</td><td>NV001</td><td>2</td><td>Hoàn thành đơn hàng gấp</td></tr>
                            <tr><td>2026-05-10</td><td>NV002</td><td>3</td><td>Hỗ trợ sản xuất</td></tr>
                            <tr><td>2026-05-11</td><td>NV001</td><td>1.5</td><td></td></tr>
                        </tbody>
                    </table>
                    <ul class="small mb-0">
                        <li>Định dạng ngày: <code>YYYY-MM-DD</code> hoặc <code>DD/MM/YYYY</code></li>
                        <li>Số giờ OT: số thực dương, tối đa 12 (VD: 1, 1.5, 2, 3)</li>
                        <li>Lý do có thể để trống → tự động dùng "Tăng ca theo kế hoạch"</li>
                        <li>Nếu đã có đơn OT ngày đó → <strong class="text-warning">bỏ qua</strong>, không tạo trùng</li>
                        <li>Ngày quá khứ sẽ bị <strong class="text-danger">bỏ qua</strong></li>
                        <li>Tất cả đơn tạo ra ở trạng thái <span class="badge bg-warning text-dark">⌛ Chờ duyệt</span></li>
                    </ul>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <a href="/erp/modules/attendance/download_ot_template.php"
                       class="btn btn-outline-success w-100 py-4">
                        <i class="fas fa-download fa-2x d-block mb-2 mx-auto"></i>
                        <strong>Tải file Excel mẫu</strong>
                        <div class="small text-muted mt-1">mau_dang_ky_OT.xlsx</div>
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
                        <div class="form-text">Chấp nhận .xlsx, .xls — Tối đa 5MB</div>
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
                <div class="small text-muted">✅ Tạo đơn thành công</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning"><?= $summary['skipped'] ?></div>
                <div class="small text-muted">⚠️ Bỏ qua (trùng/quá khứ)</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger"><?= $summary['failed'] ?></div>
                <div class="small text-muted">❌ Lỗi</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span>📋 Chi tiết kết quả</span>
            <a href="/erp/modules/attendance/import_ot.php"
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
                            <th>Số giờ OT</th>
                            <th>Lý do</th>
                            <th>Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['status'] === 'error' ? 'table-danger' : ($r['status'] === 'skip' ? 'table-warning bg-opacity-25' : 'table-success bg-opacity-10') ?>">
                        <td class="text-muted small"><?= $r['row'] ?></td>
                        <td><strong><?= htmlspecialchars($r['code']) ?></strong></td>
                        <td><?= htmlspecialchars($r['date']) ?></td>
                        <td><?= htmlspecialchars($r['hours']) ?>h</td>
                        <td><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($r['reason'], 0, 30, '...')) ?></small></td>
                        <td><?= $r['msg'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="/erp/modules/attendance/ot_manage.php" class="btn btn-success">
            <i class="fas fa-list me-2"></i>Xem danh sách đơn OT
        </a>
        <a href="/erp/modules/attendance/import_ot.php" class="btn btn-outline-primary">
            <i class="fas fa-upload me-2"></i>Import thêm
        </a>
    </div>
    <?php endif; ?>

</div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
