<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo  = getDBConnection();
$user = currentUser();

// ── Hàm tính ngày công chuẩn ─────────────────────────────────────────────
function calcWorkingDays(PDO $pdo, string $from, string $to): int {
    $count   = 0;
    $current = new DateTime($from);
    $end     = new DateTime($to);
    while ($current <= $end) {
        $dow  = (int)$current->format('N');
        if ($dow !== 7) $count++;
        $current->modify('+1 day');
    }
    return $count;
}

// ── Xử lý tạo kỳ lương mới ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_period') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $y = (int)$_POST['year'];
    $m = (int)$_POST['month'];

    if ($y < 2020 || $y > 2100 || $m < 1 || $m > 12) {
        setFlash('danger', 'Tháng/năm không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $y);
    $from        = sprintf('%04d-%02d-01', $y, $m);
    $to          = sprintf('%04d-%02d-%02d', $y, $m, $daysInMonth);
    $workingDays = calcWorkingDays($pdo, $from, $to);

    try {
        $pdo->prepare("
            INSERT INTO payroll_periods
                (period_year, period_month, period_from, period_to, working_days, status, created_by)
            VALUES (?, ?, ?, ?, ?, 'draft', ?)
        ")->execute([$y, $m, $from, $to, $workingDays, $user['id']]);

        $newId = $pdo->lastInsertId();
        setFlash('success', "✅ Đã tạo kỳ lương Tháng $m/$y ($workingDays ngày công chuẩn)");
        header("Location: /erp/modules/payroll/slip_list.php?period_id=$newId"); exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000)
            setFlash('danger', "⚠️ Kỳ lương Tháng $m/$y đã tồn tại!");
        else
            setFlash('danger', 'Lỗi: ' . $e->getMessage());
        header('Location: /erp/modules/payroll/index.php'); exit;
    }
}

// ── Xử lý xoá kỳ lương ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_period') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    // Chỉ Giám đốc mới được xoá
    if (!hasRole('director')) {
        setFlash('danger', '❌ Chỉ Giám đốc mới có quyền xoá kỳ lương.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $period_id = (int)($_POST['period_id'] ?? 0);
    if (!$period_id) {
        setFlash('danger', 'Kỳ lương không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    // Kiểm tra kỳ lương có tồn tại không và không phải locked
    $chk = $pdo->prepare("SELECT period_month, period_year, status FROM payroll_periods WHERE id = ?");
    $chk->execute([$period_id]);
    $period = $chk->fetch();

    if (!$period) {
        setFlash('danger', 'Không tìm thấy kỳ lương.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    if ($period['status'] === 'locked') {
        setFlash('danger', '🔒 Không thể xoá kỳ lương đã lock.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    // Xoá phiếu lương trước, sau đó xoá kỳ lương
    $pdo->prepare("DELETE FROM payroll_slips WHERE period_id = ?")->execute([$period_id]);
    $pdo->prepare("DELETE FROM payroll_periods WHERE id = ?")->execute([$period_id]);

    setFlash('success', "🗑️ Đã xoá kỳ lương Tháng {$period['period_month']}/{$period['period_year']} và toàn bộ phiếu lương liên quan.");
    header('Location: /erp/modules/payroll/index.php'); exit;
}

// ── Cập nhật lại ngày công chuẩn cho kỳ đã có ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recalc_period_days') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $periodId = (int)($_POST['period_id'] ?? 0);
    if (!$periodId) {
        setFlash('danger', 'Kỳ lương không hợp lệ.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM payroll_periods WHERE id = ?");
    $stmt->execute([$periodId]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$period) {
        setFlash('danger', 'Không tìm thấy kỳ lương.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    if ($period['status'] === 'locked') {
        setFlash('danger', '🔒 Không thể cập nhật ngày công chuẩn cho kỳ đã lock.');
        header('Location: /erp/modules/payroll/index.php'); exit;
    }

    $workingDays = calcWorkingDays($pdo, $period['period_from'], $period['period_to']);
    $pdo->prepare("UPDATE payroll_periods SET working_days = ? WHERE id = ?")
        ->execute([$workingDays, $periodId]);

    setFlash(
        'success',
        "🔄 Đã cập nhật ngày công chuẩn kỳ Tháng {$period['period_month']}/{$period['period_year']} thành {$workingDays} ngày. Hãy bấm tính lại lương để cập nhật phiếu."
    );
    header('Location: /erp/modules/payroll/index.php'); exit;
}

// ── Lấy danh sách kỳ lương ───────────────────────────────────────────────
$periods = $pdo->query("
    SELECT pp.*,
           u.full_name AS created_name,
           u2.full_name AS submitted_name,
           u3.full_name AS approved_name,
           u4.full_name AS locked_name,
           (SELECT COUNT(*) FROM payroll_slips WHERE period_id = pp.id) AS slip_count
    FROM payroll_periods pp
    LEFT JOIN users u  ON pp.created_by   = u.id
    LEFT JOIN users u2 ON pp.submitted_by = u2.id
    LEFT JOIN users u3 ON pp.approved_by  = u3.id
    LEFT JOIN users u4 ON pp.locked_by    = u4.id
    ORDER BY pp.period_year DESC, pp.period_month DESC
")->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">💰 Quản lý bảng lương</h4>
            <p class="text-muted small mb-0">Tạo và quản lý kỳ lương hàng tháng</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/erp/modules/payroll/holidays.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-calendar-times me-1"></i>Quản lý ngày lễ
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Tạo kỳ lương mới
            </button>
        </div>
    </div>

    <?php showFlash(); ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Kỳ lương</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th class="text-center">Ngày công chuẩn</th>
                        <th class="text-center">Số phiếu</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Người tạo</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($periods)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                        Chưa có kỳ lương nào. Tạo kỳ lương đầu tiên!
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($periods as $p):
                    $statusMap = [
                        'draft'     => ['secondary', '📝 Nháp'],
                        'submitted' => ['warning',   '📤 Chờ duyệt'],
                        'approved'  => ['success',   '✅ Đã duyệt'],
                        'locked'    => ['dark',      '🔒 Đã lock'],
                    ];
                    [$sCls, $sLbl] = $statusMap[$p['status']] ?? ['secondary', '?'];
                ?>
                <tr style="cursor:pointer;" onclick="window.location='/erp/modules/payroll/slip_list.php?period_id=<?= $p['id'] ?>'">
                    <td class="fw-bold">
                        <a href="/erp/modules/payroll/slip_list.php?period_id=<?= $p['id'] ?>"
                           class="text-decoration-none text-primary"
                           onclick="event.stopPropagation()">
                            Tháng <?= $p['period_month'] ?>/<?= $p['period_year'] ?>
                        </a>
                    </td>
                    <td><?= date('d/m/Y', strtotime($p['period_from'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['period_to'])) ?></td>
                    <td class="text-center fw-bold text-primary"><?= $p['working_days'] ?></td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= $p['slip_count'] ?> phiếu</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $sCls ?>"><?= $sLbl ?></span>
                        <?php if ($p['approved_at']): ?>
                        <div class="small text-muted mt-1">
                            <?= date('d/m H:i', strtotime($p['approved_at'])) ?>
                            · <?= htmlspecialchars($p['approved_name'] ?? '') ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($p['created_name'] ?? '') ?>
                    </td>
                    <td class="text-center" onclick="event.stopPropagation()">
                        <div class="d-flex gap-1 justify-content-center flex-wrap">

                            <!-- Tính lại lương (trừ khi locked) -->
                            <?php if ($p['status'] !== 'locked'): ?>
                            <form method="POST" class="d-inline"
                                  onsubmit="return confirm('Cập nhật lại ngày công chuẩn cho kỳ Tháng <?= $p['period_month'] ?>/<?= $p['period_year'] ?>?\n\nSau đó nên bấm tính lại lương để cập nhật toàn bộ phiếu.');">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="recalc_period_days">
                                <input type="hidden" name="period_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                        title="Cập nhật ngày công chuẩn">
                                    <i class="fas fa-redo me-1"></i>Ngày công chuẩn
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-info"
                                    onclick="recalcAll(<?= $p['id'] ?>, this)"
                                    title="Tính lại lương hàng loạt">
                                <i class="fas fa-calculator"></i>
                            </button>
                            <?php endif; ?>

                            <!-- KT trình GĐ -->
                            <?php if ($p['status'] === 'draft' && hasRole('accountant','director')): ?>
                            <button class="btn btn-sm btn-outline-warning"
                                    onclick="doWorkflow(<?= $p['id'] ?>, 'submit', this)"
                                    title="Trình Giám đốc duyệt">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <?php endif; ?>

                            <!-- GĐ duyệt -->
                            <?php if ($p['status'] === 'submitted' && hasRole('director')): ?>
                            <button class="btn btn-sm btn-success"
                                    onclick="doWorkflow(<?= $p['id'] ?>, 'approve', this)"
                                    title="Duyệt - NV sẽ thấy phiếu lương sau khi duyệt">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>

                            <!-- GĐ lock -->
                            <?php if ($p['status'] === 'approved' && hasRole('director')): ?>
                            <button class="btn btn-sm btn-dark"
                                    onclick="doWorkflow(<?= $p['id'] ?>, 'lock', this)"
                                    title="Lock - Không thể sửa nữa">
                                <i class="fas fa-lock"></i>
                            </button>
                            <?php endif; ?>

                            <!-- GĐ mở lại -->
                            <?php if ($p['status'] === 'locked' && hasRole('director')): ?>
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="doWorkflow(<?= $p['id'] ?>, 'reopen', this)"
                                    title="Mở lại để chỉnh sửa">
                                <i class="fas fa-lock-open"></i>
                            </button>
                            <?php endif; ?>

                            <!-- Xoá kỳ lương - chỉ GĐ, không được xoá khi locked -->
                            <?php if (hasRole('director') && $p['status'] !== 'locked'): ?>
                            <form method="POST" class="d-inline"
                                  onsubmit="return confirm('⚠️ Xoá kỳ lương Tháng <?= $p['period_month'] ?>/<?= $p['period_year'] ?>?\n\nToàn bộ <?= $p['slip_count'] ?> phiếu lương sẽ bị xoá vĩnh viễn!');">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action"    value="delete_period">
                                <input type="hidden" name="period_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá kỳ lương">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- ── Modal tạo kỳ lương ── -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">➕ Tạo kỳ lương mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     value="create_period">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tháng</label>
                            <select name="month" class="form-select">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                    Tháng <?= $m ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Năm</label>
                            <select name="year" class="form-select">
                                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Ngày công chuẩn sẽ tự động tính theo Thứ 2-Thứ 7 (ngày lễ vẫn hưởng nguyên lương).
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-plus me-2"></i>Tạo kỳ lương
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function recalcAll(periodId, btn) {
    if (!confirm('Tính lại lương cho tất cả nhân viên?\n⚠️ Phiếu đã điều chỉnh tay sẽ giữ nguyên phần nhập tay.')) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch('/erp/api/payroll/calculate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ period_id: periodId })
        });
        const data = await res.json();
        if (data.ok) {
            alert(data.msg + (data.errors?.length ? '\n\nLỗi:\n' + data.errors.join('\n') : ''));
            location.reload();
        } else {
            alert('❌ ' + data.msg);
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    } catch(e) {
        alert('Lỗi kết nối server!');
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function doWorkflow(periodId, action, btn) {
    const labels = {
        submit:  'Trình Giám đốc duyệt kỳ lương này?',
        approve: '✅ Duyệt kỳ lương?\nNhân viên sẽ thấy phiếu lương sau khi duyệt.',
        lock:    '🔒 Lock kỳ lương?\nSau khi lock sẽ không thể chỉnh sửa.',
        reopen:  '🔓 Mở lại kỳ lương để chỉnh sửa?'
    };
    if (!confirm(labels[action] || 'Xác nhận?')) return;

    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res  = await fetch('/erp/api/payroll/workflow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ period_id: periodId, action })
        });
        const data = await res.json();
        if (data.ok) { alert(data.msg); location.reload(); }
        else {
            alert('❌ ' + data.msg);
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    } catch(e) {
        alert('Lỗi kết nối server!');
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
