<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$pdo  = getDBConnection();
$user = currentUser();

// ── XỬ LÝ FORM ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'create';

    // Huỷ đơn (chỉ khi còn pending)
    if ($action === 'cancel') {
        $ot_id = (int)$_POST['ot_id'];
        $stmt  = $pdo->prepare("UPDATE overtime_requests SET status = 'rejected', reject_reason = 'Nhân viên tự huỷ'
                                 WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$ot_id, $user['id']]);
        setFlash('success', '✅ Đã huỷ đơn OT.');
        header('Location: /erp/modules/attendance/ot_request.php');
        exit();
    }

    // Tạo đơn OT mới
    $ot_date    = $_POST['ot_date']    ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time   = $_POST['end_time']   ?? '';
    $reason     = trim($_POST['reason'] ?? '');
    $errors     = [];

    // ── Validate ──
    if (empty($ot_date))    $errors[] = 'Vui lòng chọn ngày OT.';
    if (empty($start_time)) $errors[] = 'Vui lòng nhập giờ bắt đầu.';
    if (empty($end_time))   $errors[] = 'Vui lòng nhập giờ kết thúc.';
    if (empty($reason))     $errors[] = 'Vui lòng nhập lý do OT.';
    if ($ot_date < date('Y-m-d')) $errors[] = 'Không thể đăng ký OT cho ngày đã qua.';

    if (empty($errors)) {
        // Tính số giờ OT
        $startMin = strtotime("$ot_date $start_time");
        $endMin   = strtotime("$ot_date $end_time");
        if ($endMin <= $startMin) $endMin += 86400; // Qua đêm
        $hours = round(($endMin - $startMin) / 3600, 2);
        if ($hours <= 0) $errors[] = 'Giờ kết thúc phải sau giờ bắt đầu.';
        if ($hours > 12) $errors[] = 'OT không được vượt quá 12 giờ/ngày.';
    }

    // Kiểm tra đã có đơn OT ngày này chưa
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM overtime_requests
                               WHERE user_id = ? AND ot_date = ? AND status != 'rejected'");
        $chk->execute([$user['id'], $ot_date]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Bạn đã có đơn OT cho ngày này rồi.';
        }
    }

    if (empty($errors)) {
        // Lấy ca của nhân viên vào ngày OT để xác định khung giờ đêm
        $shiftStmt = $pdo->prepare("
            SELECT ws.start_time AS shift_start, ws.end_time AS shift_end, ws.is_night_shift, es.shift_id,
                   ws.ot_multiplier, ws.weekend_multiplier, ws.holiday_multiplier
            FROM employee_shifts es
            JOIN work_shifts ws ON es.shift_id = ws.id
            WHERE es.user_id = ? AND es.effective_date <= ?
              AND (es.end_date IS NULL OR es.end_date >= ?)
            ORDER BY es.effective_date DESC LIMIT 1
        ");
        $shiftStmt->execute([$user['id'], $ot_date, $ot_date]);
        $shift    = $shiftStmt->fetch();
        $shift_id = $shift['shift_id'] ?? null;

        // Xác định có phải OT đêm không
        $isNightOT = false;

        if ($shift && (int)$shift['is_night_shift'] === 1) {
            // NV được gán ca đêm → MỌI OT ngày đó đều là OT đêm
            $isNightOT = true;
        } else {
            // Ca ngày hoặc không có ca → xét theo giờ bắt đầu OT (22:00–06:00)
            $startH   = (int)substr($start_time, 0, 2);
            $startM   = (int)substr($start_time, 3, 2);
            $startMin = $startH * 60 + $startM;
            // >= 22:00 (1320) hoặc < 06:00 (360)
            $isNightOT = ($startMin >= 22 * 60 || $startMin < 6 * 60);
        }

        // Xác định loại OT (ngày thường / cuối tuần / ngày lễ / đêm)
        $dow = date('N', strtotime($ot_date)); // 1=Mon ... 7=Sun
        $isHoliday = false;
        $hChk = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE holiday_date = ?");
        $hChk->execute([$ot_date]);
        if ($hChk->fetchColumn() > 0) $isHoliday = true;

        // PHP date('N'): 1=Thứ 2 ... 6=Thứ 7 ... 7=Chủ nhật
        // Thứ 7 (6) = ngày thường; Chủ nhật (7) = cuối tuần
        if ($isNightOT) {
            if ($isHoliday)    $ot_type = 'night_holiday';
            elseif ($dow == 7) $ot_type = 'night_weekend';
            else               $ot_type = 'night_weekday';
        } elseif ($isHoliday) {
            $ot_type = 'holiday';
        } elseif ($dow == 7) {
            $ot_type = 'weekend';
        } else {
            $ot_type = 'weekday';
        }

        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests
            (user_id, ot_date, start_time, end_time, hours, reason, ot_type, shift_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user['id'], $ot_date, $start_time, $end_time, $hours, $reason, $ot_type, $shift_id]);
        $newId = $pdo->lastInsertId();

        // Gửi thông báo cho quản lý
        $managers = $pdo->query("
            SELECT id FROM users
            WHERE role_id IN (SELECT id FROM roles WHERE name IN ('production','manager','director'))
            AND is_active = 1
        ")->fetchAll();
        foreach ($managers as $mgr) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id)
                           VALUES (?, ?, ?, 'ot_request', ?)")
                ->execute([
                    $mgr['id'],
                    '📋 Đơn đăng ký OT mới',
                    $user['full_name'] . ' đăng ký OT ngày ' . formatDate($ot_date) .
                    ' (' . $start_time . ' – ' . $end_time . ', ' . $hours . ' giờ)',
                    $newId
                ]);
        }

        setFlash('success', "✅ Đã gửi đơn đăng ký OT <strong>" . formatDate($ot_date) . "</strong> thành công!");
        header('Location: /erp/modules/attendance/ot_request.php');
        exit();
    }
}

// ── Lấy ca làm việc của user để gợi ý giờ OT ──
$myShift = $pdo->prepare("
    SELECT ws.* FROM employee_shifts es
    JOIN work_shifts ws ON es.shift_id = ws.id
    WHERE es.user_id = ? AND es.effective_date <= CURDATE()
      AND (es.end_date IS NULL OR es.end_date >= CURDATE())
    ORDER BY es.effective_date DESC LIMIT 1
");
$myShift->execute([$user['id']]);
$myShift = $myShift->fetch();

// ── Lịch sử đơn OT ──
$stmt = $pdo->prepare("
    SELECT ot.*, ws.shift_name, ws.color AS shift_color,
           u.full_name AS approver_name
    FROM overtime_requests ot
    LEFT JOIN work_shifts ws ON ot.shift_id = ws.id
    LEFT JOIN users u ON ot.approved_by = u.id
    WHERE ot.user_id = ?
    ORDER BY ot.created_at DESC
    LIMIT 30
");
$stmt->execute([$user['id']]);
$myOTs = $stmt->fetchAll();

// ── Tổng OT tháng này ──
$totalOT = $pdo->prepare("
    SELECT SUM(hours) FROM overtime_requests
    WHERE user_id = ? AND status = 'approved'
      AND MONTH(ot_date) = ? AND YEAR(ot_date) = ?
");
$totalOT->execute([$user['id'], date('m'), date('Y')]);
$totalOTHours = $totalOT->fetchColumn() ?? 0;

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';

// OT type labels & badges
$otTypeLabel = [
    'weekday'       => ['Ngày thường',        'secondary'],
    'weekend'       => ['Cuối tuần',           'warning'],
    'holiday'       => ['Ngày lễ',             'danger'],
    'night'         => ['🌙 Đêm (cũ)',         'dark'],       // backward compat
    'night_weekday' => ['🌙 Đêm ngày thường',  'dark'],
    'night_weekend' => ['🌙 Đêm cuối tuần',    'purple'],
    'night_holiday' => ['🌙 Đêm ngày lễ',      'danger'],
];
$statusLabel = ['pending' => ['⌛ Chờ duyệt', 'warning'], 'approved' => ['✅ Đã duyệt', 'success'], 'rejected' => ['❌ Từ chối', 'danger']];
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">⏱️ Đăng ký Tăng ca (OT)</h4>
            <p class="text-muted small mb-0"><?= htmlspecialchars($user['full_name']) ?></p>
        </div>
        <?php if ($myShift): ?>
        <div class="text-end">
            <div class="small text-muted">Ca hiện tại</div>
            <span class="badge fs-6" style="background:<?= $myShift['color'] ?>">
                <?= htmlspecialchars($myShift['shift_name']) ?>
                (<?= substr($myShift['start_time'],0,5) ?>–<?= substr($myShift['end_time'],0,5) ?>)
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>
    <?php if (!empty($errors ?? [])): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── CỘT TRÁI: Form + Thống kê ── -->
        <div class="col-lg-5">

            <!-- Thống kê nhanh -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="fs-3 fw-bold text-warning"><?= number_format($totalOTHours, 1) ?></div>
                        <div class="small text-muted">Giờ OT tháng <?= date('m') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <?php
                        $pendingCount = count(array_filter($myOTs, fn($o) => $o['status'] === 'pending'));
                        ?>
                        <div class="fs-3 fw-bold text-primary"><?= $pendingCount ?></div>
                        <div class="small text-muted">Đơn chờ duyệt</div>
                    </div>
                </div>
            </div>

            <!-- Form tạo đơn OT -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    ➕ Tạo đơn đăng ký OT
                </div>
                <div class="card-body">
                    <form method="POST" id="otForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="create">

                        <!-- Ngày OT -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📅 Ngày tăng ca <span class="text-danger">*</span></label>
                            <input type="date" name="ot_date" id="otDate" class="form-control"
                                   value="<?= $_POST['ot_date'] ?? date('Y-m-d') ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                            <!-- Hiển thị loại ngày -->
                            <div id="dayTypeBadge" class="mt-1"></div>
                        </div>

                        <!-- Giờ OT -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">⏰ Giờ bắt đầu <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="otStart" class="form-control"
                                       value="<?= $_POST['start_time'] ?? ($myShift ? substr($myShift['end_time'],0,5) : '17:00') ?>"
                                       required>
                                <?php if ($myShift): ?>
                                <div class="form-text" style="font-size:11px;">
                                    Giờ tan ca: <?= substr($myShift['end_time'],0,5) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">⏰ Giờ kết thúc <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="otEnd" class="form-control"
                                       value="<?= $_POST['end_time'] ?? '20:00' ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Preview tính giờ & hệ số -->
                        <div id="otPreview" class="alert alert-info py-2 mb-3 d-none">
                            <div class="d-flex justify-content-between">
                                <span>⏱️ Số giờ OT:</span>
                                <strong id="previewHours">0 giờ</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>📊 Loại:</span>
                                <span id="previewType">—</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>💰 Hệ số:</span>
                                <strong id="previewMultiplier" class="text-success">—</strong>
                            </div>
                        </div>

                        <!-- OT đêm tự động -->
                        <div class="alert alert-dark py-2 mb-3 small">
                            <i class="fas fa-moon me-1"></i>
                            <?php if ($myShift && (int)$myShift['is_night_shift'] === 1): ?>
                                <strong>Ca đêm <?= substr($myShift['start_time'],0,5) ?>–<?= substr($myShift['end_time'],0,5) ?>:</strong>
                                Tất cả OT của bạn hôm nay được tính hệ số <strong>đêm</strong> tự động.
                            <?php else: ?>
                                <strong>OT đêm tự động:</strong> OT từ <strong>22:00</strong> đến <strong>06:00</strong>
                                → hệ thống tự tính hệ số OT đêm (×2.1 / ×2.7 / ×3.9)
                            <?php endif; ?>
                        </div>

                        <!-- Lý do -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📝 Lý do tăng ca <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required
                                      placeholder="Mô tả công việc cần làm thêm giờ..."><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Gửi đơn OT
                        </button>
                    </form>
                </div>
            </div>

            <!-- Hướng d��n hệ số OT -->
            <?php if ($myShift): ?>
            <div class="card border-0 shadow-sm mt-3 bg-light">
                <div class="card-body py-3">
                    <p class="fw-bold small mb-2">💰 Hệ số OT ca <?= htmlspecialchars($myShift['shift_name']) ?></p>
                    <div class="row g-1 text-center">
                        <div class="col-3">
                            <div class="bg-white rounded p-2">
                                <div class="fw-bold text-secondary"><?= $myShift['ot_multiplier'] ?>x</div>
                                <div style="font-size:10px;">Ngày thường</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-white rounded p-2">
                                <div class="fw-bold text-warning"><?= $myShift['weekend_multiplier'] ?>x</div>
                                <div style="font-size:10px;">Cuối tuần</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-white rounded p-2">
                                <div class="fw-bold text-danger"><?= $myShift['holiday_multiplier'] ?>x</div>
                                <div style="font-size:10px;">Ngày lễ</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="bg-dark rounded p-2">
                                <div class="fw-bold text-warning">2.1x</div>
                                <div style="font-size:10px;" class="text-white">OT đêm</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── CỘT PHẢI: Lịch sử đơn OT ── -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📋 Lịch sử đơn OT của tôi</span>
                    <small class="text-muted"><?= count($myOTs) ?> đơn</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myOTs)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-clock fa-3x mb-3 d-block opacity-25"></i>
                        Chưa có đơn OT nào
                    </div>
                    <?php else: ?>
                    <?php foreach ($myOTs as $ot): ?>
                    <?php
                        $st  = $statusLabel[$ot['status']];
                        $otp = $otTypeLabel[$ot['ot_type']] ?? ['Ngày thường','secondary'];
                    ?>
                    <div class="ot-item p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="fw-bold">📅 <?= formatDate($ot['ot_date']) ?></span>
                                <span class="ms-1 text-muted small">
                                    (<?= date('l', strtotime($ot['ot_date'])) ?>)
                                </span>
                                <span class="badge bg-<?= $otp[1] ?> text-<?= $otp[1]==='warning'?'dark':'white' ?> ms-1">
                                    <?= $otp[0] ?>
                                </span>
                            </div>
                            <span class="badge bg-<?= $st[1] ?> text-<?= $st[1]==='warning'?'dark':'white' ?>">
                                <?= $st[0] ?>
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            <span class="small">
                                <i class="fas fa-clock text-primary me-1"></i>
                                <?= $ot['start_time'] ?> – <?= $ot['end_time'] ?>
                            </span>
                            <span class="small fw-bold text-primary">
                                <i class="fas fa-hourglass-half me-1"></i>
                                <?= $ot['hours'] ?> giờ
                            </span>
                            <?php if ($ot['shift_name']): ?>
                            <span class="badge" style="background:<?= $ot['shift_color'] ?>; font-size:11px;">
                                <?= htmlspecialchars($ot['shift_name']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted mb-1">
                            <i class="fas fa-comment me-1"></i><?= htmlspecialchars($ot['reason']) ?>
                        </div>
                        <?php if ($ot['status'] === 'approved' && $ot['approver_name']): ?>
                        <div class="small text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Duyệt bởi: <?= htmlspecialchars($ot['approver_name']) ?>
                            lúc <?= formatDateTime($ot['approved_at']) ?>
                        </div>
                        <?php elseif ($ot['status'] === 'rejected'): ?>
                        <div class="small text-danger">
                            <i class="fas fa-times-circle me-1"></i>
                            Lý do: <?= htmlspecialchars($ot['reject_reason'] ?? 'Không được duyệt') ?>
                        </div>
                        <?php endif; ?>

                        <!-- Nút huỷ nếu còn pending -->
                        <?php if ($ot['status'] === 'pending'): ?>
                        <form method="POST" class="mt-2 d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="ot_id" value="<?= $ot['id'] ?>">
                            <button class="btn btn-xs btn-outline-danger"
                                    onclick="return confirm('Huỷ đơn OT ngày <?= formatDate($ot['ot_date']) ?>?')">
                                <i class="fas fa-times me-1"></i>Huỷ đơn
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
.btn-xs { padding: 2px 10px; font-size: 12px; }
.ot-item:hover { background: #f8f9fa; }
.ot-item:last-child { border-bottom: none !important; }
</style>

<?php
// Pass night-shift flag to JavaScript
$jsIsNightShift = ($myShift && (int)$myShift['is_night_shift'] === 1) ? 'true' : 'false';
?>

<script>
// ── Ngày lễ từ server ──
const holidays = <?= json_encode(
    array_column(
        $pdo->query("SELECT holiday_date FROM holidays WHERE year = " . date('Y'))->fetchAll(),
        'holiday_date'
    )
) ?>;

const shiftOT = {
    weekday:       <?= $myShift ? $myShift['ot_multiplier']       : 1.5 ?>,
    weekend:       <?= $myShift ? $myShift['weekend_multiplier']  : 2.0 ?>,
    holiday:       <?= $myShift ? $myShift['holiday_multiplier']  : 3.0 ?>,
    night_weekday: 2.1,
    night_weekend: 2.7,
    night_holiday: 3.9,
    night:         1.3  // backward compat
};

// ── Thông tin ca làm việc ──
const employeeShift = {
    isNight: <?= $jsIsNightShift ?>
};

function timeToMin(t) {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

function isNightOT(startTimeStr) {
    if (!startTimeStr) return false;
    // Nếu NV có ca đêm → MỌI OT đều là đêm
    if (employeeShift.isNight) return true;
    // Ca ngày hoặc không có ca → xét theo giờ bắt đầu OT (22:00–06:00)
    const min = timeToMin(startTimeStr);
    return min >= 22 * 60 || min < 6 * 60;
}

function getOTType(dateStr, startTimeStr) {
    const isNight   = isNightOT(startTimeStr);
    const isHol     = holidays.includes(dateStr);
    const dow       = new Date(dateStr).getDay(); // 0=Sun, 6=Sat
    const isWeekend = (dow === 0); // Chỉ Chủ nhật mới là cuối tuần; Thứ 7 (6) = ngày thường

    if (isNight) {
        if (isHol)     return 'night_holiday';
        if (isWeekend) return 'night_weekend';
        return 'night_weekday';
    } else {
        if (isHol)     return 'holiday';
        if (isWeekend) return 'weekend';
        return 'weekday';
    }
}

const dayTypeInfo = {
    weekday:       { label: '📋 Ngày thường',        badgeClass: 'bg-secondary' },
    weekend:       { label: '🌤️ Cuối tuần',          badgeClass: 'bg-warning text-dark' },
    holiday:       { label: '🎉 Ngày lễ',            badgeClass: 'bg-danger' },
    night_weekday: { label: '🌙 Đêm ngày thường',    badgeClass: 'bg-dark' },
    night_weekend: { label: '🌙 Đêm cuối tuần',      badgeClass: 'bg-dark' },
    night_holiday: { label: '🌙 Đêm ngày lễ',        badgeClass: 'bg-danger' },
    night:         { label: '🌙 Làm đêm',            badgeClass: 'bg-dark' }  // backward compat
};

// ── Update preview ──
function updatePreview() {
    const dateVal  = document.getElementById('otDate').value;
    const startVal = document.getElementById('otStart').value;
    const endVal   = document.getElementById('otEnd').value;

    const badgeDiv = document.getElementById('dayTypeBadge');
    if (dateVal && startVal) {
        const type = getOTType(dateVal, startVal);
        const info = dayTypeInfo[type];
        badgeDiv.innerHTML = `<span class="badge ${info.badgeClass}">${info.label}</span>`;

        if (endVal) {
            let startMin = timeToMin(startVal);
            let endMin   = timeToMin(endVal);
            if (endMin <= startMin) endMin += 24 * 60;
            const hours = ((endMin - startMin) / 60).toFixed(2);
            const mult  = shiftOT[type];

            document.getElementById('otPreview').classList.remove('d-none');
            document.getElementById('previewHours').textContent = hours + ' giờ';
            document.getElementById('previewType').innerHTML = `<span class="badge ${info.badgeClass}">${info.label}</span>`;
            document.getElementById('previewMultiplier').textContent = mult + 'x lương';
        }
    }
}

document.getElementById('otDate').addEventListener('change', updatePreview);
document.getElementById('otStart').addEventListener('change', updatePreview);
document.getElementById('otEnd').addEventListener('change', updatePreview);
// Chạy ngay khi load
updatePreview();
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>