<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';

// Chỉ Giám đốc, Kế toán, Quản lý mới setup ca
requireRole('director', 'accountant', 'manager');

$pdo    = getDBConnection();
$user   = currentUser();
$errors = [];
$editShift = null;

// Thêm cột is_night_shift nếu chưa có
try {
    $pdo->query("ALTER TABLE work_shifts ADD COLUMN is_night_shift TINYINT(1) NOT NULL DEFAULT 0 AFTER holiday_multiplier");
} catch (PDOException $e) { /* Cột đã tồn tại, bỏ qua */ }

// ── XỬ LÝ FORM ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // XÓA CA
    if ($action === 'delete') {
        $sid = (int)$_POST['shift_id'];
        // Kiểm tra ca đang được dùng không
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM employee_shifts WHERE shift_id = ?");
        $inUse->execute([$sid]);
        if ($inUse->fetchColumn() > 0) {
            setFlash('danger', '❌ Không thể xóa ca này vì đang có nhân viên được phân công.');
        } else {
            $pdo->prepare("DELETE FROM work_shifts WHERE id = ?")->execute([$sid]);
            setFlash('success', '✅ Đã xóa ca làm việc.');
        }
        header('Location: /erp/modules/attendance/shift_setup.php');
        exit();
    }

    // KÍCH HOẠT / TẮT CA
    if ($action === 'toggle') {
        $sid    = (int)$_POST['shift_id'];
        $status = (int)$_POST['current_status'];
        $pdo->prepare("UPDATE work_shifts SET is_active = ? WHERE id = ?")->execute([!$status, $sid]);
        setFlash('success', '✅ Đã cập nhật trạng thái ca.');
        header('Location: /erp/modules/attendance/shift_setup.php');
        exit();
    }

    // TẠO / CẬP NHẬT CA
    if (in_array($action, ['create', 'update'])) {
        $shift_code          = strtoupper(trim($_POST['shift_code'] ?? ''));
        $shift_name          = trim($_POST['shift_name'] ?? '');
        $start_time          = $_POST['start_time'] ?? '';
        $end_time            = $_POST['end_time'] ?? '';
        $late_threshold      = (int)($_POST['late_threshold'] ?? 15);
        $break_minutes       = (int)($_POST['break_minutes'] ?? 60);
        $work_hours          = (float)($_POST['work_hours'] ?? 8);
        $ot_multiplier       = (float)($_POST['ot_multiplier'] ?? 1.5);
        $weekend_multiplier  = (float)($_POST['weekend_multiplier'] ?? 2.0);
        $holiday_multiplier  = (float)($_POST['holiday_multiplier'] ?? 3.0);
        $is_night_shift      = isset($_POST['is_night_shift']) ? 1 : 0;
        $color               = $_POST['color'] ?? '#0d6efd';
        $sid                 = (int)($_POST['shift_id'] ?? 0);

        if (empty($shift_code)) $errors[] = 'Mã ca không được để trống.';
        if (empty($shift_name)) $errors[] = 'Tên ca không được để trống.';
        if (empty($start_time)) $errors[] = 'Giờ bắt đầu không được để trống.';
        if (empty($end_time))   $errors[] = 'Giờ kết thúc không được để trống.';

        // Kiểm tra trùng mã ca
        if (empty($errors)) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM work_shifts WHERE shift_code = ? AND id != ?");
            $chk->execute([$shift_code, $sid]);
            if ($chk->fetchColumn() > 0) $errors[] = 'Mã ca đã tồn tại.';
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $pdo->prepare("
                    INSERT INTO work_shifts
                    (shift_code, shift_name, start_time, end_time, late_threshold, break_minutes,
                     work_hours, ot_multiplier, weekend_multiplier, holiday_multiplier, is_night_shift, color, created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ")->execute([$shift_code, $shift_name, $start_time, $end_time, $late_threshold,
                              $break_minutes, $work_hours, $ot_multiplier, $weekend_multiplier,
                              $holiday_multiplier, $is_night_shift, $color, $user['id']]);
                setFlash('success', "✅ Đã tạo ca <strong>$shift_name</strong> thành công!");
            } else {
                $pdo->prepare("
                    UPDATE work_shifts SET
                    shift_code=?, shift_name=?, start_time=?, end_time=?, late_threshold=?,
                    break_minutes=?, work_hours=?, ot_multiplier=?, weekend_multiplier=?,
                    holiday_multiplier=?, is_night_shift=?, color=?
                    WHERE id=?
                ")->execute([$shift_code, $shift_name, $start_time, $end_time, $late_threshold,
                              $break_minutes, $work_hours, $ot_multiplier, $weekend_multiplier,
                              $holiday_multiplier, $is_night_shift, $color, $sid]);
                setFlash('success', "✅ Đã cập nhật ca <strong>$shift_name</strong>.");
            }
            header('Location: /erp/modules/attendance/shift_setup.php');
            exit();
        }
        // Giữ lại dữ liệu nếu có lỗi
        if ($action === 'update') {
            $editShift = $_POST;
            $editShift['id'] = $sid;
        }
    }
}

// Lấy ca cần sửa
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM work_shifts WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editShift = $stmt->fetch();
}

// Danh sách tất cả ca
$shifts = $pdo->query("SELECT ws.*, u.full_name as creator,
    (SELECT COUNT(*) FROM employee_shifts es WHERE es.shift_id = ws.id) as assigned_count
    FROM work_shifts ws
    LEFT JOIN users u ON ws.created_by = u.id
    ORDER BY ws.is_active DESC, ws.start_time ASC")->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">⚙️ Setup Ca làm việc</h4>
            <p class="text-muted mb-0 small">Cấu hình giờ vào/ra, hệ số lương OT cho từng ca</p>
        </div>
        <a href="/erp/modules/attendance/shift_assign.php" class="btn btn-success">
            <i class="fas fa-user-clock me-2"></i>Phân công ca nhân viên
        </a>
    </div>

    <?php showFlash(); ?>
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ── FORM TẠO / SỬA CA ── -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold bg-primary text-white">
                    <?= $editShift ? '✏️ Chỉnh sửa ca: ' . htmlspecialchars($editShift['shift_name']) : '➕ Tạo ca mới' ?>
                </div>
                <div class="card-body">
                    <form method="POST" id="shiftForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="<?= $editShift ? 'update' : 'create' ?>">
                        <?php if ($editShift): ?>
                        <input type="hidden" name="shift_id" value="<?= $editShift['id'] ?>">
                        <?php endif; ?>

                        <!-- Thông tin cơ bản -->
                        <div class="row g-2 mb-3">
                            <div class="col-5">
                                <label class="form-label fw-semibold small">Mã ca <span class="text-danger">*</span></label>
                                <input type="text" name="shift_code" class="form-control form-control-sm text-uppercase"
                                       value="<?= htmlspecialchars($editShift['shift_code'] ?? '') ?>"
                                       placeholder="VD: CA1" maxlength="20" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label fw-semibold small">Tên ca <span class="text-danger">*</span></label>
                                <input type="text" name="shift_name" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($editShift['shift_name'] ?? '') ?>"
                                       placeholder="VD: Ca hành chính" required>
                            </div>
                        </div>

                        <!-- Giờ làm -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <p class="fw-semibold small mb-2">🕐 Giờ làm việc</p>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Giờ BẮT ĐẦU <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control form-control-sm"
                                           value="<?= $editShift['start_time'] ?? '08:00' ?>" required id="startTime">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Giờ KẾT THÚC <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control form-control-sm"
                                           value="<?= $editShift['end_time'] ?? '17:00' ?>" required id="endTime">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Nghỉ trưa (phút)</label>
                                    <input type="number" name="break_minutes" class="form-control form-control-sm"
                                           value="<?= $editShift['break_minutes'] ?? 60 ?>" min="0" max="120" id="breakMin">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Số giờ chuẩn</label>
                                    <input type="number" name="work_hours" class="form-control form-control-sm"
                                           value="<?= $editShift['work_hours'] ?? 8 ?>" step="0.5" min="1" max="24" id="workHours" readonly>
                                    <div class="form-text" style="font-size:10px;">Tự tính từ giờ vào/ra</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">
                                        Cho phép trễ (phút)
                                        <span class="text-muted" data-bs-toggle="tooltip" title="Đến muộn trong khoảng này sẽ KHÔNG bị tính trễ">ℹ️</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">±</span>
                                        <input type="number" name="late_threshold" class="form-control form-control-sm"
                                               value="<?= $editShift['late_threshold'] ?? 15 ?>" min="0" max="60">
                                        <span class="input-group-text">phút</span>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_night_shift" id="isNightShift"
                                               value="1" <?= !empty($editShift['is_night_shift']) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="isNightShift">
                                            🌙 Ca làm đêm (22h – 6h) — cộng thêm <strong>30% phụ trội đêm</strong> vào lương cơ bản
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info box hệ số OT — tự động đổi theo toggle ca đêm -->
                        <div id="otRateBox" class="mb-3">
                            <!-- Ca ngày -->
                            <div id="otRateDay" class="card border-info border-0 bg-light p-3">
                                <p class="fw-semibold small mb-2">💰 Hệ số lương OT áp dụng</p>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-warning text-dark">Ngày thường ×1.5</span>
                                    <span class="badge bg-info text-white">Cuối tuần ×2.0</span>
                                    <span class="badge bg-danger text-white">Ngày lễ ×3.0</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Hệ số cố định theo quy định — áp dụng cho tất cả ca ngày
                                </small>
                            </div>
                            <!-- Ca đêm (ẩn mặc định) -->
                            <div id="otRateNight" class="card border-0 bg-dark text-white p-3 d-none">
                                <p class="fw-semibold small mb-2">🌙 Hệ số lương OT ca đêm áp dụng</p>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-warning text-dark">🌙 Đêm thường ×2.1</span>
                                    <span class="badge bg-info text-white">🌙 Đêm CN ×2.7</span>
                                    <span class="badge bg-danger text-white">🌙 Đêm lễ ×3.9</span>
                                </div>
                                <small class="text-white-50">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Hệ số cố định cho ca đêm + 30% phụ trội đêm trên lương cơ bản/giờ
                                </small>
                            </div>
                        </div>

                        <!-- Màu hiển thị -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">🎨 Màu hiển thị trên lịch</label>
                            <div class="d-flex gap-2 flex-wrap" id="colorPicker">
                                <?php
                                $colors = [
                                    '#0d6efd' => 'Xanh dương',
                                    '#198754' => 'Xanh lá',
                                    '#fd7e14' => 'Cam',
                                    '#6f42c1' => 'Tím',
                                    '#dc3545' => 'Đỏ',
                                    '#20c997' => 'Xanh ngọc',
                                    '#6c757d' => 'Xám',
                                    '#d63384' => 'Hồng',
                                ];
                                $selectedColor = $editShift['color'] ?? '#0d6efd';
                                foreach ($colors as $hex => $name):
                                ?>
                                <div class="color-option <?= $hex === $selectedColor ? 'selected' : '' ?>"
                                     style="background:<?= $hex ?>; width:28px; height:28px; border-radius:50%; cursor:pointer; border: 3px solid <?= $hex === $selectedColor ? '#000' : 'transparent' ?>;"
                                     data-color="<?= $hex ?>" title="<?= $name ?>"
                                     onclick="selectColor('<?= $hex ?>')">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="color" id="colorInput" value="<?= $selectedColor ?>">
                        </div>

                        <!-- Preview ca -->
                        <div class="alert py-2 mb-3" id="shiftPreview" style="background:<?= $selectedColor ?>20; border-left: 4px solid <?= $selectedColor ?>;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span id="previewName" class="fw-bold"><?= htmlspecialchars($editShift['shift_name'] ?? 'Tên ca') ?></span>
                                <span id="previewTime" class="badge" style="background:<?= $selectedColor ?>">
                                    <?= ($editShift['start_time'] ?? '08:00') ?> – <?= ($editShift['end_time'] ?? '17:00') ?>
                                </span>
                            </div>
                            <small class="text-muted" id="previewInfo">
                                <?= $editShift['work_hours'] ?? 8 ?> giờ/ca &bull;
                                Trễ: ±<?= $editShift['late_threshold'] ?? 15 ?> phút &bull;
                                OT: <?= !empty($editShift['is_night_shift']) ? '×2.1/2.7/3.9 (đêm)' : '×1.5/2.0/3.0' ?>
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-save me-1"></i><?= $editShift ? 'Lưu thay đổi' : 'Tạo ca' ?>
                            </button>
                            <?php if ($editShift): ?>
                            <a href="/erp/modules/attendance/shift_setup.php" class="btn btn-outline-secondary">Huỷ</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── DANH SÁCH CA ── -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    📋 Danh sách ca làm việc (<?= count($shifts) ?> ca)
                </div>
                <div class="card-body p-0">
                    <?php foreach ($shifts as $sh): ?>
                    <div class="shift-card p-3 border-bottom <?= !$sh['is_active'] ? 'opacity-50' : '' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <!-- Màu ca -->
                            <div class="shift-color-dot mt-1" style="background:<?= $sh['color'] ?>; width:14px; height:14px; border-radius:50%; flex-shrink:0;"></div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars($sh['shift_name']) ?></span>
                                        <code class="ms-2 small text-muted"><?= $sh['shift_code'] ?></code>
                                        <?php if (!$sh['is_active']): ?>
                                            <span class="badge bg-secondary ms-1">Tắt</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge rounded-pill" style="background:<?= $sh['color'] ?>; font-size:13px;">
                                        <?= substr($sh['start_time'],0,5) ?> – <?= substr($sh['end_time'],0,5) ?>
                                    </span>
                                </div>

                                <!-- Thông số -->
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-light text-dark border">
                                        ⏱️ <?= $sh['work_hours'] ?>h/ca
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        🍽️ nghỉ <?= $sh['break_minutes'] ?>p
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        ⚡ trễ ±<?= $sh['late_threshold'] ?>p
                                    </span>
                                    <?php if (!empty($sh['is_night_shift'])): ?>
                                    <span class="badge bg-warning text-dark border">🌙 OT ×2.1/2.7/3.9</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark border">OT ×1.5/2.0/3.0</span>
                                    <?php endif; ?>
                                    <?php if (!empty($sh['is_night_shift'])): ?>
                                    <span class="badge bg-dark text-white border">🌙 Ca đêm +30%</span>
                                    <?php endif; ?>
                                    <span class="badge bg-primary text-white border">
                                        👥 <?= $sh['assigned_count'] ?> NV
                                    </span>
                                </div>

                                <!-- Action buttons -->
                                <div class="d-flex gap-1">
                                    <a href="?edit=<?= $sh['id'] ?>" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i>Sửa
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="shift_id" value="<?= $sh['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $sh['is_active'] ?>">
                                        <button class="btn btn-xs <?= $sh['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                            <?= $sh['is_active'] ? '🔇 Tắt' : '✅ Bật' ?>
                                        </button>
                                    </form>
                                    <?php if ($sh['assigned_count'] == 0): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="shift_id" value="<?= $sh['id'] ?>">
                                        <button class="btn btn-xs btn-outline-danger"
                                                onclick="return confirm('Xóa ca <?= htmlspecialchars($sh['shift_name']) ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($shifts)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-clock fa-3x mb-3 d-block opacity-25"></i>
                        Chưa có ca nào. Tạo ca đầu tiên!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
.btn-xs { padding: 2px 8px; font-size: 12px; }
.color-option.selected { border: 3px solid #000 !important; transform: scale(1.2); }
.shift-card:hover { background: #f8f9fa; }
</style>

<script>
// ── Chọn màu ──
function selectColor(hex) {
    document.querySelectorAll('.color-option').forEach(el => {
        el.style.border = '3px solid transparent';
        el.classList.remove('selected');
    });
    const el = document.querySelector(`.color-option[data-color="${hex}"]`);
    if (el) { el.style.border = '3px solid #000'; el.classList.add('selected'); }
    document.getElementById('colorInput').value = hex;
    updatePreview();
}

// ── Tính giờ chuẩn tự động ──
function calcWorkHours() {
    const start  = document.getElementById('startTime').value;
    const end    = document.getElementById('endTime').value;
    const brk    = parseInt(document.getElementById('breakMin').value) || 0;
    if (!start || !end) return;

    let [sh, sm] = start.split(':').map(Number);
    let [eh, em] = end.split(':').map(Number);
    let startMin = sh * 60 + sm;
    let endMin   = eh * 60 + em;
    if (endMin <= startMin) endMin += 24 * 60; // Ca đêm qua ngày hôm sau

    const netMin = endMin - startMin - brk;
    document.getElementById('workHours').value = Math.max(0, (netMin / 60).toFixed(2));
    updatePreview();
}

// ── Update preview ──
function updatePreview() {
    const name    = document.querySelector('[name="shift_name"]').value || 'Tên ca';
    const start   = document.getElementById('startTime').value;
    const end     = document.getElementById('endTime').value;
    const hours   = document.getElementById('workHours').value;
    const late    = document.querySelector('[name="late_threshold"]').value;
    const isNight = document.getElementById('isNightShift').checked;
    const color   = document.getElementById('colorInput').value;

    document.getElementById('previewName').textContent = name;
    document.getElementById('previewTime').textContent = `${start} – ${end}`;
    document.getElementById('previewTime').style.background = color;
    document.getElementById('previewInfo').textContent =
        `${hours} giờ/ca • Trễ: ±${late} phút • OT: ${isNight ? '×2.1/2.7/3.9 (đêm)' : '×1.5/2.0/3.0'}`;
    document.getElementById('shiftPreview').style.background = color + '20';
    document.getElementById('shiftPreview').style.borderLeftColor = color;
}

function updateOTRateBox() {
    const isNight = document.getElementById('isNightShift').checked;
    document.getElementById('otRateDay').classList.toggle('d-none', isNight);
    document.getElementById('otRateNight').classList.toggle('d-none', !isNight);
}
document.getElementById('isNightShift')?.addEventListener('change', function() {
    updateOTRateBox();
    updatePreview();
});
// Gọi ngay khi load để hiện đúng box
updateOTRateBox();

// Gắn events
['startTime','endTime','breakMin'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', calcWorkHours);
});
document.querySelector('[name="shift_name"]')?.addEventListener('input', updatePreview);
document.querySelector('[name="late_threshold"]')?.addEventListener('input', updatePreview);

// Tooltip bootstrap
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>