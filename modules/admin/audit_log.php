<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director');

$pdo = getDBConnection();

$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 50;
$offset    = ($page - 1) * $perPage;
$module    = trim((string)($_GET['module'] ?? ''));
$level     = trim((string)($_GET['level'] ?? ''));
$userId    = (int)($_GET['user_id'] ?? 0);
$dateFrom  = trim((string)($_GET['from'] ?? ''));
$dateTo    = trim((string)($_GET['to'] ?? ''));
$q         = trim((string)($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];

if ($module !== '') {
    $where[] = 'sal.module = ?';
    $params[] = $module;
}
if (in_array($level, ['success', 'warning', 'danger'], true)) {
    $where[] = 'sal.level = ?';
    $params[] = $level;
}
if ($userId > 0) {
    $where[] = 'sal.user_id = ?';
    $params[] = $userId;
}
if ($dateFrom !== '') {
    $where[] = 'sal.created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'sal.created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}
if ($q !== '') {
    $where[] = '(sal.description LIKE ? OR sal.action LIKE ? OR sal.target_label LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM system_audit_logs sal WHERE $whereSql");
$totalStmt->execute($params);
$totalRows = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;

$listStmt = $pdo->prepare("\n    SELECT sal.*, u.full_name AS actor_name\n    FROM system_audit_logs sal\n    LEFT JOIN users u ON u.id = sal.user_id\n    WHERE $whereSql\n    ORDER BY sal.created_at DESC\n    LIMIT ? OFFSET ?\n");
$listStmt->execute($listParams);
$logs = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$modules = $pdo->query("SELECT DISTINCT module FROM system_audit_logs ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("\n    SELECT DISTINCT sal.user_id, COALESCE(u.full_name, sal.full_name, sal.username) AS display_name\n    FROM system_audit_logs sal\n    LEFT JOIN users u ON u.id = sal.user_id\n    WHERE sal.user_id IS NOT NULL\n    ORDER BY display_name\n")->fetchAll(PDO::FETCH_ASSOC);

$statsStmt = $pdo->query("\n    SELECT\n      SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS total_today,\n      SUM(CASE WHEN DATE(created_at) = CURDATE() AND action LIKE 'delete%' THEN 1 ELSE 0 END) AS delete_today,\n      SUM(CASE WHEN DATE(created_at) = CURDATE() AND action = 'login_failed' THEN 1 ELSE 0 END) AS login_failed_today\n    FROM system_audit_logs\n");
$stats = $statsStmt ? $statsStmt->fetch(PDO::FETCH_ASSOC) : ['total_today' => 0, 'delete_today' => 0, 'login_failed_today' => 0];

$levelMeta = [
    'success' => ['badge bg-success', '🟢 Bình thường', 'table-success'],
    'warning' => ['badge bg-warning text-dark', '🟡 Chỉnh sửa', 'table-warning'],
    'danger'  => ['badge bg-danger', '🔴 Xóa/Lỗi', 'table-danger'],
];

$queryBase = [
    'module' => $module,
    'level'  => $level,
    'user_id'=> $userId ?: '',
    'from'   => $dateFrom,
    'to'     => $dateTo,
    'q'      => $q,
];

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">📜 Lịch sử hệ thống</h4>
            <p class="text-muted mb-0 small">Theo dõi mọi thao tác quan trọng trong hệ thống</p>
        </div>
        <button class="btn btn-success btn-sm" onclick="exportExcel()"><i class="fas fa-file-excel me-1"></i>Xuất Excel</button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Tổng thao tác hôm nay</div><div class="fs-4 fw-bold text-primary"><?= (int)($stats['total_today'] ?? 0) ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Thao tác xóa hôm nay</div><div class="fs-4 fw-bold text-danger"><?= (int)($stats['delete_today'] ?? 0) ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Đăng nhập thất bại hôm nay</div><div class="fs-4 fw-bold text-warning"><?= (int)($stats['login_failed_today'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Mức độ</label>
                    <select name="level" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="success" <?= $level === 'success' ? 'selected' : '' ?>>🟢 Bình thường</option>
                        <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>🟡 Chỉnh sửa</option>
                        <option value="danger" <?= $level === 'danger' ? 'selected' : '' ?>>🔴 Xóa/Lỗi</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Người dùng</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['user_id'] ?>" <?= $userId === (int)$u['user_id'] ? 'selected' : '' ?>><?= e($u['display_name'] ?: ('#' . $u['user_id'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Từ ngày</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Đến ngày</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="action / mô tả...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Lọc</button>
                    <a href="/erp/modules/admin/audit_log.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="auditTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Thời gian</th>
                            <th>Người dùng</th>
                            <th>Role</th>
                            <th>Module</th>
                            <th>Hành động</th>
                            <th>Mức</th>
                            <th>Mô tả</th>
                            <th>IP</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log):
                        $meta = $levelMeta[$log['level']] ?? ['badge bg-secondary', e($log['level']), ''];
                    ?>
                        <tr class="<?= $meta[2] ?>">
                            <td><small><?= formatDateTime($log['created_at']) ?></small></td>
                            <td>
                                <div class="fw-semibold"><?= e($log['actor_name'] ?: $log['full_name'] ?: $log['username'] ?: 'N/A') ?></div>
                                <small class="text-muted">ID: <?= e($log['user_id'] ?? 'NULL') ?></small>
                            </td>
                            <td><small><?= e($log['role'] ?: '-') ?></small></td>
                            <td><code><?= e($log['module']) ?></code></td>
                            <td><code><?= e($log['action']) ?></code></td>
                            <td><span class="<?= $meta[0] ?>"><?= $meta[1] ?></span></td>
                            <td>
                                <div><?= e($log['description']) ?></div>
                                <?php if (!empty($log['target_label'])): ?><small class="text-muted">🎯 <?= e($log['target_label']) ?></small><?php endif; ?>
                            </td>
                            <td><small><?= e($log['ip_address'] ?: '-') ?></small></td>
                            <td>
                                <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal"
                                            data-old="<?= e($log['old_value'] ?? '') ?>"
                                            data-new="<?= e($log['new_value'] ?? '') ?>"
                                            data-title="<?= e(($log['action'] ?? '') . ' · ' . ($log['created_at'] ?? '')) ?>">Chi tiết</button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Không có dữ liệu.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++):
                $qs = http_build_query(array_merge($queryBase, ['page' => $p]));
            ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= e($qs) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailTitle">Chi tiết log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">old_value</label>
                        <pre class="bg-light border rounded p-2 mb-0"><code id="detailOld">null</code></pre>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">new_value</label>
                        <pre class="bg-light border rounded p-2 mb-0"><code id="detailNew">null</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const detailModal = document.getElementById('detailModal');
if (detailModal) {
    detailModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const oldVal = button?.getAttribute('data-old') || '';
        const newVal = button?.getAttribute('data-new') || '';
        const title  = button?.getAttribute('data-title') || 'Chi tiết log';
        document.getElementById('detailTitle').textContent = title;
        document.getElementById('detailOld').textContent = prettyJson(oldVal);
        document.getElementById('detailNew').textContent = prettyJson(newVal);
    });
}

function prettyJson(raw) {
    if (!raw) return 'null';
    try {
        return JSON.stringify(JSON.parse(raw), null, 2);
    } catch (e) {
        return raw;
    }
}

function exportExcel() {
    if (!window.XLSX) return;
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(document.getElementById('auditTable'));
    XLSX.utils.book_append_sheet(wb, ws, 'AuditLog');
    XLSX.writeFile(wb, 'audit_log.xlsx');
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
