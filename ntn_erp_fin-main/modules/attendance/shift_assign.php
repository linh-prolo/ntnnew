<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant', 'manager', 'production');

$pdo  = getDBConnection();
$user = currentUser();

// ── XỬ LÝ PHÂN CÔNG ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // Phân công ca mặc định cho nhân viên
    if ($action === 'assign') {
        $user_ids      = $_POST['user_ids']      ?? [];
        $shift_id      = (int)$_POST['shift_id'];
        $effective_date = $_POST['effective_date'];
        $end_date       = $_POST['end_date'] ?: null;

        foreach ($user_ids as $uid) {
            $uid = (int)$uid;
            // Kết thúc ca cũ trước ngày hiệu lực mới
            $pdo->prepare("UPDATE employee_shifts SET end_date = DATE_SUB(?, INTERVAL 1 DAY)
                           WHERE user_id = ? AND (end_date IS NULL OR end_date >= ?)")
                ->execute([$effective_date, $uid, $effective_date]);
            // Thêm ca mới
            $pdo->prepare("INSERT INTO employee_shifts (user_id, shift_id, effective_date, end_date, created_by)
                           VALUES (?, ?, ?, ?, ?)")
                ->execute([$uid, $shift_id, $effective_date, $end_date, $user['id']]);
        }
        setFlash('success', '✅ Đã phân công ca cho ' . count($user_ids) . ' nhân viên.');
        header('Location: /erp/modules/attendance/shift_assign.php');
        exit();
    }

    // Xóa phân công ca
    if ($action === 'remove') {
        $pdo->prepare("DELETE FROM employee_shifts WHERE id = ?")->execute([(int)$_POST['assign_id']]);
        setFlash('success', '✅ Đã xóa phân công ca.');
        header('Location: /erp/modules/attendance/shift_assign.php');
        exit();
    }
}

// Dữ liệu
$shifts = $pdo->query("SELECT * FROM work_shifts WHERE is_active = 1 ORDER BY start_time")->fetchAll();
$depts  = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Nhân viên + ca hiện tại
$employees = $pdo->query("
    SELECT u.id, u.full_name, u.employee_code, d.name AS dept_name, r.display_name AS role_name,
           ws.shift_name AS current_shift, ws.color AS shift_color,
           ws.start_time, ws.end_time, es.effective_date, es.id AS assign_id
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN employee_shifts es ON es.user_id = u.id
        AND es.effective_date <= CURDATE()
        AND (es.end_date IS NULL OR es.end_date >= CURDATE())
    LEFT JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE u.is_active = 1
    ORDER BY d.name, u.full_name
")->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">👥 Phân công Ca làm việc</h4>
            <p class="text-muted small mb-0">Gán ca làm mặc định cho từng nhân viên</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/erp/modules/attendance/shift_schedule.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-alt me-1"></i>Xem lịch ca tháng
            </a>
            <a href="/erp/modules/attendance/shift_setup.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog me-1"></i>Setup ca
            </a>
        </div>
    </div>

    <?php showFlash(); ?>

    <div class="row g-4">
        <!-- ── FORM PHÂN CÔNG ── -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:70px;">
                <div class="card-header bg-success text-white fw-bold">
                    ➕ Phân công ca mới
                </div>
                <div class="card-body">
                    <form method="POST" id="assignForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="assign">

                        <!-- Chọn ca -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Chọn ca làm <span class="text-danger">*</span></label>
                            <select name="shift_id" class="form-select form-select-sm" required id="shiftSelect">
                                <option value="">-- Chọn ca --</option>
                                <?php foreach ($shifts as $sh): ?>
                                <option value="<?= $sh['id'] ?>"
                                        data-start="<?= substr($sh['start_time'],0,5) ?>"
                                        data-end="<?= substr($sh['end_time'],0,5) ?>"
                                        data-color="<?= $sh['color'] ?>">
                                    <?= htmlspecialchars($sh['shift_name']) ?>
                                    (<?= substr($sh['start_time'],0,5) ?>–<?= substr($sh['end_time'],0,5) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Preview ca được chọn -->
                            <div id="shiftPreviewBadge" class="mt-2 d-none">
                                <span class="badge fs-6" id="shiftPreviewText"></span>
                            </div>
                        </div>

                        <!-- Thời gian áp dụng -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Từ ngày <span class="text-danger">*</span></label>
                                <input type="date" name="effective_date" class="form-control form-control-sm"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Đến ngày</label>
                                <input type="date" name="end_date" class="form-control form-control-sm"
                                       placeholder="Để trống = vô thời hạn">
                                <div class="form-text" style="font-size:10px;">Trống = không giới hạn</div>
                            </div>
                        </div>

                        <!-- Lọc phòng ban để chọn NV -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Lọc theo phòng ban</label>
                            <select class="form-select form-select-sm" id="deptFilter">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Danh sách checkbox nhân viên -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small mb-0">Chọn nhân viên <span class="text-danger">*</span></label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="selectAll(true)">Tất cả</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="selectAll(false)">Bỏ chọn</button>
                                </div>
                            </div>
                            <div class="employee-checklist border rounded p-2" style="max-height:220px; overflow-y:auto;">
                                <?php foreach ($employees as $emp): ?>
                                <div class="form-check emp-item py-1 border-bottom"
                                     data-dept="<?= $emp['department_id'] ?? 0 ?>">
                                    <input class="form-check-input emp-checkbox" type="checkbox"
                                           name="user_ids[]" value="<?= $emp['id'] ?>"
                                           id="emp<?= $emp['id'] ?>">
                                    <label class="form-check-label small w-100" for="emp<?= $emp['id'] ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($emp['full_name']) ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?= $emp['employee_code'] ?> &bull; <?= htmlspecialchars($emp['dept_name'] ?? '-') ?>
                                            <?php if ($emp['current_shift']): ?>
                                            &bull; <span class="badge" style="background:<?= $emp['shift_color'] ?>; font-size:10px;">
                                                <?= htmlspecialchars($emp['current_shift']) ?>
                                            </span>
                                            <?php else: ?>
                                            &bull; <span class="text-danger" style="font-size:10px;">Chưa có ca</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted"><span id="selectedCount">0</span> nhân viên được chọn</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100"
                                onclick="return document.querySelectorAll('.emp-checkbox:checked').length > 0 || alert('Vui lòng chọn ít nhất 1 nhân viên')">
                            <i class="fas fa-save me-2"></i>Phân công ca
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── BẢNG NHÂN VIÊN & CA HIỆN TẠI ── -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span>📋 Ca làm hiện tại của nhân viên</span>
                    <span class="text-muted small fw-normal">
                        <?= count(array_filter($employees, fn($e) => $e['current_shift'])) ?>/<?= count($employees) ?> đã phân công
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Phòng ban</th>
                                    <th>Ca hiện tại</th>
                                    <th>Giờ làm</th>
                                    <th>Áp dụng từ</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars($emp['full_name']) ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= $emp['employee_code'] ?></div>
                                </td>
                                <td><small><?= htmlspecialchars($emp['dept_name'] ?? '-') ?></small></td>
                                <td>
                                    <?php if ($emp['current_shift']): ?>
                                    <span class="badge rounded-pill" style="background:<?= $emp['shift_color'] ?>">
                                        <?= htmlspecialchars($emp['current_shift']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">⚠️ Chưa phân công</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($emp['start_time']): ?>
                                    <small class="text-muted">
                                        <?= substr($emp['start_time'],0,5) ?> – <?= substr($emp['end_time'],0,5) ?>
                                    </small>
                                    <?php else: ?><small>-</small><?php endif; ?>
                                </td>
                                <td>
                                    <small><?= $emp['effective_date'] ? formatDate($emp['effective_date']) : '-' ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($emp['assign_id']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="assign_id" value="<?= $emp['assign_id'] ?>">
                                        <button class="btn btn-xs btn-outline-danger"
                                                onclick="return confirm('Xóa phân công ca của <?= htmlspecialchars($emp['full_name']) ?>?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
.btn-xs { padding: 2px 8px; font-size: 12px; }
.emp-item:last-child { border-bottom: none !important; }
</style>

<script>
// ── Lọc nhân viên theo phòng ban ──
document.getElementById('deptFilter').addEventListener('change', function() {
    const deptId = this.value;
    document.querySelectorAll('.emp-item').forEach(item => {
        item.style.display = (!deptId || item.dataset.dept == deptId) ? '' : 'none';
    });
    updateCount();
});

// ── Chọn tất cả / bỏ chọn ──
function selectAll(checked) {
    document.querySelectorAll('.emp-item:not([style*="none"]) .emp-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    updateCount();
}

// ── Đếm số NV được chọn ──
function updateCount() {
    document.getElementById('selectedCount').textContent =
        document.querySelectorAll('.emp-checkbox:checked').length;
}
document.querySelectorAll('.emp-checkbox').forEach(cb => {
    cb.addEventListener('change', updateCount);
});

// ── Preview ca được chọn ──
document.getElementById('shiftSelect').addEventListener('change', function() {
    const opt    = this.options[this.selectedIndex];
    const badge  = document.getElementById('shiftPreviewBadge');
    const text   = document.getElementById('shiftPreviewText');
    if (opt.value) {
        badge.classList.remove('d-none');
        text.textContent = `${opt.text}`;
        text.style.background = opt.dataset.color || '#0d6efd';
    } else {
        badge.classList.add('d-none');
    }
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>