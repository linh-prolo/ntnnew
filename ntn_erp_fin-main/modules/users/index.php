<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';

// Chỉ Giám đốc & Kế toán được vào
requireRole('director', 'accountant');

$user = currentUser();
$pdo  = getDBConnection();

// Xử lý khoá / mở khoá tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action    = $_POST['action']    ?? '';
    $target_id = (int)($_POST['user_id'] ?? 0);

    // Không được khoá chính mình
    if ($target_id && $target_id !== $user['id']) {
        if ($action === 'lock') {
            $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$target_id]);
            setFlash('warning', 'Đã khoá tài khoản.');
        } elseif ($action === 'unlock') {
            $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$target_id]);
            setFlash('success', 'Đã mở khoá tài khoản.');
        } elseif ($action === 'delete') {
            // Chỉ Giám đốc mới được xoá
            if (hasRole('director')) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
                setFlash('success', 'Đã xoá tài khoản.');
            } else {
                setFlash('danger', 'Bạn không có quyền xoá tài khoản.');
            }
        }
    }
    header('Location: /erp/modules/users/index.php');
    exit();
}

// Lọc & tìm kiếm
$search     = trim($_GET['search']  ?? '');
$filterRole = $_GET['role']         ?? '';
$filterDept = (int)($_GET['dept']   ?? 0);

$sql    = "SELECT u.*, r.name AS role, r.display_name AS role_name, d.name AS dept_name
           FROM users u
           JOIN roles r ON u.role_id = r.id
           LEFT JOIN departments d ON u.department_id = d.id
           WHERE 1=1 ";
$params = [];

if ($search) {
    $sql    .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.employee_code LIKE ? OR u.email LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like, $like]);
}
if ($filterRole) {
    $sql    .= " AND r.name = ?";
    $params[] = $filterRole;
}
if ($filterDept) {
    $sql    .= " AND u.department_id = ?";
    $params[] = $filterDept;
}
$sql .= " ORDER BY u.is_active DESC, r.id ASC, u.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Danh sách phòng ban cho filter
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">👥 Quản lý Tài khoản</h4>
            <p class="text-muted mb-0">Tổng: <strong><?= count($users) ?></strong> tài khoản</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/erp/modules/users/import_excel.php" class="btn btn-outline-success">
                <i class="fas fa-file-excel me-2"></i>Import Excel
            </a>
            <a href="/erp/modules/users/create.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Tạo tài khoản mới
            </a>
        </div>
    </div>

    <?php showFlash(); ?>

    <!-- Bộ lọc tìm kiếm -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">🔍 Tìm kiếm</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Tên, username, mã NV, email..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">🎭 Phân quyền</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['name'] ?>" <?= $filterRole === $r['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['display_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">🏢 Phòng ban</label>
                    <select name="dept" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($depts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Lọc</button>
                    <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary btn-sm">↺</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nhân viên</th>
                            <th>Username</th>
                            <th>Phân quyền</th>
                            <th>Phòng ban</th>
                            <th>Liên hệ</th>
                            <th>Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <?php $badge = getRoleBadge($u['role']); ?>
                    <tr class="<?= !$u['is_active'] ? 'table-secondary text-muted' : '' ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Avatar chữ cái đầu -->
                                <div class="avatar-circle bg-<?= $badge['class'] ?>">
                                    <?= mb_substr($u['full_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['employee_code']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                        <td>
                            <span class="badge bg-<?= $badge['class'] ?>">
                                <?= $badge['icon'] ?> <?= $badge['label'] ?>
                            </span>
                        </td>
                        <td><small><?= htmlspecialchars($u['dept_name'] ?? '-') ?></small></td>
                        <td>
                            <small class="d-block"><?= htmlspecialchars($u['email'] ?? '-') ?></small>
                            <small class="text-muted"><?= htmlspecialchars($u['phone'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">✅ Hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">🔒 Đã khoá</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
				<!-- Nút Hồ sơ (thêm trước nút Sửa) -->
				<a href="/erp/modules/users/profile.php?id=<?= $u['id'] ?>"
  					class="btn btn-sm btn-outline-success" title="Hồ sơ nhân viên">
  					  <i class="fas fa-id-card"></i>
				</a>
                                <!-- Sửa -->
                                <a href="/erp/modules/users/edit.php?id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!-- Đổi mật khẩu -->
                                <a href="/erp/modules/users/change_password.php?id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-outline-warning" title="Đổi mật khẩu">
                                    <i class="fas fa-key"></i>
                                </a>
                                <?php if ($u['id'] !== $user['id']): ?>
                                <!-- Khoá / Mở khoá -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['is_active']): ?>
                                        <input type="hidden" name="action" value="lock">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Khoá tài khoản"
                                                onclick="return confirm('Khoá tài khoản <?= htmlspecialchars($u['full_name']) ?>?')">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="unlock">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Mở khoá">
                                            <i class="fas fa-lock-open"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <!-- Xoá - chỉ Giám đốc -->
                                <?php if (hasRole('director')): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá tài khoản"
                                            onclick="return confirm('⚠️ Xoá tài khoản <?= htmlspecialchars($u['full_name']) ?>?\nHành động này không thể hoàn tác!')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-search fa-2x mb-2 d-block"></i>
                        Không tìm thấy tài khoản nào
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

<style>
.avatar-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 15px;
    flex-shrink: 0;
}
</style>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>