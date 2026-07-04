<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant', 'manager', 'production');

$pdo  = getDBConnection();
$user = currentUser();

// Lọc tháng/năm (mặc định tháng hiện tại)
$filterMonth = (int)($_GET['month'] ?? $_POST['month'] ?? date('m'));
$filterYear  = (int)($_GET['year']  ?? $_POST['year']  ?? date('Y'));
if ($filterMonth < 1 || $filterMonth > 12) $filterMonth = (int)date('m');
if ($filterYear < 2000 || $filterYear > 2100) $filterYear = (int)date('Y');
$monthFrom   = sprintf('%04d-%02d-01', $filterYear, $filterMonth);
$monthTo     = date('Y-m-t', mktime(0, 0, 0, $filterMonth, 1, $filterYear));

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
        header('Location: /erp/modules/attendance/shift_assign.php?month=' . $filterMonth . '&year=' . $filterYear);
        exit();
    }

    // Xóa phân công ca
    if ($action === 'remove') {
        $pdo->prepare("DELETE FROM employee_shifts WHERE id = ?")->execute([(int)$_POST['assign_id']]);
        setFlash('success', '✅ Đã xóa phân công ca.');
        header('Location: /erp/modules/attendance/shift_assign.php?month=' . $filterMonth . '&year=' . $filterYear);
        exit();
    }
}

// Dữ liệu
$shifts = $pdo->query("SELECT * FROM work_shifts WHERE is_active = 1 ORDER BY start_time")->fetchAll();
$depts  = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Nhân viên
$employees = $pdo->query("
    SELECT u.id, u.full_name, u.employee_code, u.department_id,
           d.name AS dept_name, r.display_name AS role_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.is_active = 1
    ORDER BY d.name, u.full_name
")->fetchAll();

// Lấy toàn bộ ca của từng nhân viên trong tháng lọc
$shiftsByUser = [];
$stmtShifts = $pdo->prepare("
    SELECT es.id AS assign_id, es.effective_date, es.end_date,
           ws.shift_name, ws.color, ws.start_time, ws.end_time, ws.is_night_shift
    FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.user_id = ?
      AND es.effective_date <= ?
      AND (es.end_date IS NULL OR es.end_date >= ?)
    ORDER BY es.effective_date ASC
");
foreach ($employees as $emp) {
    $stmtShifts->execute([$emp['id'], $monthTo, $monthFrom]);
    $shiftsByUser[$emp['id']] = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);
}

$assignedCount = count(array_filter($employees, fn($e) => !empty($shiftsByUser[$e['id']])));
$employeesMap = [];
foreach ($employees as $emp) {
    $employeesMap[(int)$emp['id']] = [
        'id' => (int)$emp['id'],
        'full_name' => $emp['full_name'],
        'employee_code' => $emp['employee_code']
    ];
}

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
                                <?php $empTimelineShifts = $shiftsByUser[$emp['id']] ?? []; ?>
                                <div class="form-check emp-item py-1 border-bottom"
                                             data-dept="<?= $emp['department_id'] ?? 0 ?>">
                                    <input class="form-check-input emp-checkbox" type="checkbox"
                                           name="user_ids[]" value="<?= $emp['id'] ?>"
                                           id="emp<?= $emp['id'] ?>">
                                    <label class="form-check-label small w-100" for="emp<?= $emp['id'] ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($emp['full_name']) ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?= $emp['employee_code'] ?> &bull; <?= htmlspecialchars($emp['dept_name'] ?? '-') ?>
                                            <?php if (!empty($empTimelineShifts)): ?>
                                            &bull; <span class="badge" style="background:<?= $empTimelineShifts[0]['color'] ?>; font-size:10px;">
                                                <?= htmlspecialchars($empTimelineShifts[0]['shift_name']) ?>
                                            </span>
                                            <?php if (count($empTimelineShifts) > 1): ?>
                                                <span class="text-muted" style="font-size:10px;">+<?= count($empTimelineShifts) - 1 ?> ca</span>
                                            <?php endif; ?>
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

        <!-- ── BẢNG NHÂN VIÊN & CA THEO THÁNG ── -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span>📋 Ca tháng <?= $filterMonth ?>/<?= $filterYear ?> của nhân viên</span>
                    <div class="d-flex gap-2 align-items-center">
                        <form method="GET" class="d-flex gap-2 align-items-center">
                            <select name="month" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === $filterMonth ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                            <input type="number" name="year" class="form-control form-control-sm" style="width:90px" value="<?= $filterYear ?>">
                            <button class="btn btn-sm btn-outline-primary">Xem</button>
                        </form>
                        <span class="text-muted small fw-normal">
                            <?= $assignedCount ?>/<?= count($employees) ?> đã phân công
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Phòng ban</th>
                                    <th>Ca tháng <?= $filterMonth ?>/<?= $filterYear ?></th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <?php $empShifts = $shiftsByUser[$emp['id']] ?? []; ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars($emp['full_name']) ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= $emp['employee_code'] ?></div>
                                </td>
                                <td><small><?= htmlspecialchars($emp['dept_name'] ?? '-') ?></small></td>
                                <td>
                                    <?php if (!empty($empShifts)): ?>
                                        <?php foreach ($empShifts as $sh): ?>
                                            <?php
                                            $from = $sh['effective_date'] < $monthFrom ? $monthFrom : $sh['effective_date'];
                                            $toRaw = $sh['end_date'] ?: $monthTo;
                                            $to = $toRaw > $monthTo ? $monthTo : $toRaw;
                                            ?>
                                            <span class="badge rounded-pill me-1 mb-1" style="background:<?= htmlspecialchars($sh['color']) ?>">
                                                <?= htmlspecialchars($sh['shift_name']) ?> <?= date('d/m', strtotime($from)) ?>–<?= date('d/m', strtotime($to)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <span class="badge bg-danger">⚠️ Chưa phân công</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-primary"
                                            onclick="openShiftTimeline(<?= (int)$emp['id'] ?>)">
                                        👁 Xem lịch
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.row g-4 -->

    <!-- ── PHÂN CA LUÂN PHIÊN THEO THÁNG ── -->
    <div class="card border-0 shadow-sm mb-4 mt-4">
        <div class="card-header bg-info text-white fw-bold">
            <i class="fas fa-sync me-2"></i>Phân ca luân phiên theo tháng
            <small class="fw-normal opacity-75 ms-2">2 tuần ca ngày + 2 tuần ca đêm</small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Chọn nhân viên -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nhân viên</label>
                    <select id="rotateUserId" class="form-select">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>">
                            <?= htmlspecialchars($emp['full_name']) ?>
                            (<?= $emp['employee_code'] ?>)
                            – <?= htmlspecialchars($emp['dept_name'] ?? '-') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Chọn tháng/năm -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tháng</label>
                    <select id="rotateMonth" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $filterMonth ? 'selected' : '' ?>>
                            Tháng <?= $m ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Năm</label>
                    <input type="number" id="rotateYear" class="form-control" value="<?= $filterYear ?>">
                </div>
                <!-- Chọn ca -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nửa đầu tháng (1–15): Ca</label>
                    <select id="rotateShift1" class="form-select">
                        <option value="">-- Chọn ca --</option>
                        <?php foreach ($shifts as $sh): ?>
                        <option value="<?= $sh['id'] ?>">
                            <?= htmlspecialchars($sh['shift_name']) ?>
                            (<?= substr($sh['start_time'],0,5) ?>–<?= substr($sh['end_time'],0,5) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nửa sau tháng (16–cuối): Ca</label>
                    <select id="rotateShift2" class="form-select">
                        <option value="">-- Chọn ca --</option>
                        <?php foreach ($shifts as $sh): ?>
                        <option value="<?= $sh['id'] ?>">
                            <?= htmlspecialchars($sh['shift_name']) ?>
                            (<?= substr($sh['start_time'],0,5) ?>–<?= substr($sh['end_time'],0,5) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-info text-white" onclick="applyRotateShift()">
                    <i class="fas fa-sync me-1"></i>Áp dụng phân ca luân phiên
                </button>
            </div>
            <div id="rotateResult" class="mt-2"></div>
        </div>
    </div>

</div><!-- /.container-fluid -->
</div><!-- /.main-content -->

<!-- Modal xem lịch ca chi tiết -->
<div class="modal fade" id="shiftTimelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="shiftTimelineTitle">Lịch ca</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Khoảng ngày</th>
                            <th>Ca</th>
                            <th>Giờ làm</th>
                            <th class="text-center">Xóa</th>
                        </tr>
                        </thead>
                        <tbody id="shiftTimelineBody"></tbody>
                    </table>
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
const monthFrom = <?= json_encode($monthFrom) ?>;
const monthTo = <?= json_encode($monthTo) ?>;
const filterMonth = <?= (int)$filterMonth ?>;
const filterYear = <?= (int)$filterYear ?>;
const csrfToken = <?= json_encode($csrf) ?>;
const employeesMap = <?= json_encode($employeesMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const shiftsByUser = <?= json_encode($shiftsByUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

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

// ── Phân ca luân phiên ──
async function applyRotateShift() {
    const userId  = document.getElementById('rotateUserId').value;
    const month   = document.getElementById('rotateMonth').value;
    const year    = document.getElementById('rotateYear').value;
    const shift1  = document.getElementById('rotateShift1').value;
    const shift2  = document.getElementById('rotateShift2').value;
    const result  = document.getElementById('rotateResult');

    if (!userId || !month || !year || !shift1 || !shift2) {
        result.innerHTML = '<div class="alert alert-warning py-2">⚠️ Vui lòng chọn đầy đủ nhân viên, tháng/năm và cả 2 ca.</div>';
        return;
    }

    result.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...</div>';

    try {
        const resp = await fetch('/erp/api/attendance/rotate_shift.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: parseInt(userId), month: parseInt(month), year: parseInt(year), shift1_id: parseInt(shift1), shift2_id: parseInt(shift2) })
        });
        const data = await resp.json();
        if (data.ok) {
            const detailHtml = Array.isArray(data.detail)
                ? `<ul class="mb-0 mt-1">${
                    data.detail.map((item) => {
                        return `<li>📅 ${escapeHtml(item.from)} – ${escapeHtml(item.to)} → <strong>${escapeHtml(item.shift)}</strong></li>`;
                    }).join('')
                }</ul>`
                : '';
            const summary = data.employee_name
                ? `✅ Đã phân ca luân phiên cho <strong>${escapeHtml(data.employee_name)}</strong>:`
                : `✅ ${escapeHtml(data.msg || '')}`;
            result.innerHTML = `<div class="alert alert-success">${summary}${detailHtml}</div>`;
        } else {
            result.innerHTML = `<div class="alert alert-danger py-2">❌ ${data.msg}</div>`;
        }
    } catch (e) {
        result.innerHTML = '<div class="alert alert-danger py-2">❌ Lỗi kết nối server.</div>';
    }
}

function clipDateRange(fromDate, toDate) {
    const from = fromDate < monthFrom ? monthFrom : fromDate;
    const toOrigin = toDate || monthTo;
    const to = toOrigin > monthTo ? monthTo : toOrigin;
    return { from, to };
}

function formatDateVn(dateStr) {
    if (!dateStr) return '-';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openShiftTimeline(userId) {
    const employee = employeesMap[userId];
    const list = Array.isArray(shiftsByUser[userId]) ? shiftsByUser[userId] : [];
    const title = document.getElementById('shiftTimelineTitle');
    const body = document.getElementById('shiftTimelineBody');

    const employeeName = employee && employee.full_name ? employee.full_name : 'Nhân viên';
    const employeeCode = employee && employee.employee_code ? employee.employee_code : '-';
    title.textContent = `Lịch ca tháng ${filterMonth}/${filterYear} — ${employeeName} (${employeeCode})`;

    if (!list.length) {
        body.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">⚠️ Chưa phân công ca trong tháng này</td></tr>`;
    } else {
        body.innerHTML = list.map((item) => {
            const range = clipDateRange(item.effective_date, item.end_date);
            return `<tr>
                <td>📅 ${formatDateVn(range.from)} – ${formatDateVn(range.to)}</td>
                <td><span class="badge" style="background:${escapeHtml(item.color)}">${escapeHtml(item.shift_name)}</span></td>
                <td>${escapeHtml((item.start_time || '').slice(0, 5))}–${escapeHtml((item.end_time || '').slice(0, 5))}</td>
                <td class="text-center">
                    <form method="POST" onsubmit="return confirm('Xóa phân công ca này?')">
                        <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="assign_id" value="${parseInt(item.assign_id, 10) || 0}">
                        <input type="hidden" name="month" value="${filterMonth}">
                        <input type="hidden" name="year" value="${filterYear}">
                        <button class="btn btn-xs btn-outline-danger"><i class="fas fa-times"></i></button>
                    </form>
                </td>
            </tr>`;
        }).join('');
    }

    new bootstrap.Modal(document.getElementById('shiftTimelineModal')).show();
}
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>