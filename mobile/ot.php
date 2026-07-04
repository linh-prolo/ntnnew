<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once __DIR__ . '/common.php';
requireLogin();

$user = currentUser();
$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ. Vui lòng thử lại.');
        header('Location: /erp/mobile/ot.php');
        exit();
    }

    $otDate = $_POST['ot_date'] ?? '';
    $otType = $_POST['ot_type'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $hours = (float)($_POST['hours'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    $validTypes = ['weekday', 'weekend', 'holiday', 'night_weekday', 'night_weekend', 'night_holiday'];
    $otDateObj = null;
    if ($otDate === '') {
        $errors[] = 'Vui lòng chọn ngày OT.';
    } else {
        $otDateObj = DateTime::createFromFormat('Y-m-d', $otDate);
        if (!$otDateObj || $otDateObj->format('Y-m-d') !== $otDate) {
            $errors[] = 'Ngày OT không hợp lệ.';
        } elseif ($otDateObj < new DateTime('today')) {
            $errors[] = 'Không thể đăng ký OT cho ngày đã qua.';
        }
    }
    if (!in_array($otType, $validTypes, true)) $errors[] = 'Loại OT không hợp lệ.';
    if ($startTime === '') $errors[] = 'Vui lòng nhập giờ bắt đầu.';
    if ($hours <= 0) $errors[] = 'Số giờ OT phải lớn hơn 0.';
    if ($hours > 12) $errors[] = 'OT không được vượt quá 12 giờ/ngày.';
    if ($reason === '') $errors[] = 'Vui lòng nhập lý do OT.';

    $endTime = '';
    if (empty($errors)) {
        $start = DateTime::createFromFormat('Y-m-d H:i', $otDate . ' ' . $startTime);
        if (!$start) {
            $errors[] = 'Giờ bắt đầu không hợp lệ.';
        } else {
            $minutes = (int)round($hours * 60);
            $end = (clone $start)->modify('+' . $minutes . ' minutes');
            $endTime = $end->format('H:i:s');
            $startTime = $start->format('H:i:s');
        }
    }

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM overtime_requests WHERE user_id = ? AND ot_date = ? AND status != 'rejected'");
        $chk->execute([$user['id'], $otDate]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Bạn đã có đơn OT cho ngày này rồi.';
        }
    }

    if (empty($errors)) {
        $shiftStmt = $pdo->prepare("
            SELECT ws.id AS shift_id
            FROM employee_shifts es
            JOIN work_shifts ws ON es.shift_id = ws.id
            WHERE es.user_id = ? AND es.effective_date <= ?
              AND (es.end_date IS NULL OR es.end_date >= ?)
            ORDER BY es.effective_date DESC
            LIMIT 1
        ");
        $shiftStmt->execute([$user['id'], $otDate, $otDate]);
        $shiftId = $shiftStmt->fetchColumn() ?: null;

        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests (user_id, ot_date, start_time, end_time, hours, reason, ot_type, shift_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user['id'], $otDate, $startTime, $endTime, $hours, $reason, $otType, $shiftId]);
        $requestId = (int)$pdo->lastInsertId();

        $managers = $pdo->query("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE name IN ('production','manager','director')) AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($managers as $mgr) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'ot_request', ?)")
                ->execute([
                    $mgr['id'],
                    '📋 Đơn đăng ký OT mới',
                    $user['full_name'] . ' đăng ký OT ngày ' . formatDate($otDate) . ' (' . substr($startTime, 0, 5) . ' – ' . substr($endTime, 0, 5) . ', ' . number_format($hours, 2) . ' giờ)',
                    $requestId,
                ]);
        }

        setFlash('success', 'Đã gửi đơn đăng ký OT thành công!');
        header('Location: /erp/mobile/ot.php');
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$myOTs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csrf = generateCSRF();

mobilePageStart('Đăng ký OT', $user);
showFlash();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card mobile-card mb-3">
    <div class="card-body p-4">
        <form method="POST" class="list-compact">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div>
                <label class="form-label fw-semibold">Ngày OT</label>
                <input type="date" name="ot_date" class="form-control" required value="<?= e($_POST['ot_date'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label fw-semibold">Loại OT</label>
                <select name="ot_type" class="form-select" required>
                    <?php foreach ([
                        'weekday' => 'Ngày thường',
                        'weekend' => 'Cuối tuần',
                        'holiday' => 'Ngày lễ',
                        'night_weekday' => 'Đêm thường',
                        'night_weekend' => 'Đêm cuối tuần',
                        'night_holiday' => 'Đêm ngày lễ',
                    ] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($_POST['ot_type'] ?? 'weekday') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Giờ bắt đầu</label>
                    <input type="time" name="start_time" class="form-control" required value="<?= e($_POST['start_time'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Số giờ</label>
                    <input type="number" name="hours" class="form-control" min="0.5" step="0.5" max="12" required value="<?= e($_POST['hours'] ?? '') ?>">
                </div>
            </div>
            <div>
                <label class="form-label fw-semibold">Lý do</label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Mô tả công việc OT..."><?= e($_POST['reason'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold rounded-4">
                <i class="fas fa-paper-plane me-2"></i>Gửi đăng ký OT
            </button>
        </form>
    </div>
</div>

<div class="fw-bold mb-2">5 đơn OT gần nhất</div>
<div class="list-compact">
    <?php foreach ($myOTs as $ot): ?>
        <?php $badge = mobileStatusBadge($ot['status']); ?>
        <div class="summary-item">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <div class="fw-semibold"><?= e(mobileOtTypeLabel($ot['ot_type'])) ?></div>
                    <div class="small text-muted"><?= e(formatDate($ot['ot_date'])) ?> • <?= e(substr((string)$ot['start_time'], 0, 5)) ?> → <?= e(substr((string)$ot['end_time'], 0, 5)) ?></div>
                </div>
                <span class="badge bg-<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
            </div>
            <div class="small">Số giờ: <strong><?= number_format((float)$ot['hours'], 2) ?>h</strong></div>
            <div class="small text-muted mt-1"><?= nl2br(e($ot['reason'])) ?></div>
            <?php if ($ot['status'] === 'rejected' && !empty($ot['reject_reason'])): ?>
            <div class="small text-danger mt-2">Lý do từ chối: <?= e($ot['reject_reason']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($myOTs)): ?>
    <div class="summary-item text-center text-muted">Chưa có đơn OT nào.</div>
    <?php endif; ?>
</div>

<?php mobilePageEnd(); ?>
