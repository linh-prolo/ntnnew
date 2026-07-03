<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo = getDBConnection();
$errors = [];
$oldInputWasFlashed = false;
$statsWhere = ['1=1'];
$categoryMap = [
    'computer' => 'Máy tính',
    'printer' => 'Máy in',
    'furniture' => 'Bàn ghế',
    'machinery' => 'Máy móc',
    'vehicle' => 'Phương tiện',
    'other' => 'Khác',
];
$statusMap = [
    'active' => ['success', 'Đang dùng'],
    'assigned' => ['primary', 'Đã cấp phát'],
    'maintenance' => ['warning text-dark', 'Bảo dưỡng'],
    'disposed' => ['secondary', 'Thanh lý'],
];
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$assetsPageUrl = static function (array $overrides = []) use ($filterCategory, $filterStatus): string {
    $params = [];
    $category = $overrides['category'] ?? $filterCategory;
    $status = $overrides['status'] ?? $filterStatus;
    if ($category !== '') {
        $params['category'] = $category;
    }
    if ($status !== '') {
        $params['status'] = $status;
    }
    if (!empty($overrides['action'])) {
        $params['action'] = $overrides['action'];
    }
    if (!empty($overrides['id'])) {
        $params['id'] = (int)$overrides['id'];
    }
    if (!empty($overrides['show_form'])) {
        $params['show_form'] = 1;
    }
    return 'modules/admin/assets.php' . ($params ? '?' . http_build_query($params) : '');
};

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return false;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

$calculateDepreciationPerMonth = static function (array $asset): ?float {
    $depreciationYears = isset($asset['depreciation_years']) ? (float)$asset['depreciation_years'] : 0;
    $purchasePrice = isset($asset['purchase_price']) ? (float)$asset['purchase_price'] : 0;
    $startDate = $asset['depreciation_start_date'] ?? ($asset['purchase_date'] ?? null);
    if ($depreciationYears <= 0 || $purchasePrice <= 0) {
        return null;
    }
    if ($startDate && $startDate > date('Y-m-d')) {
        return null;
    }

    $depreciableValue = max(0, $purchasePrice - (float)($asset['salvage_value'] ?? 0));
    return round($depreciableValue / ($depreciationYears * 12));
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePostCsrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $assetCode = trim($_POST['asset_code'] ?? '');
        $assetName = trim($_POST['asset_name'] ?? '');
        $category = trim($_POST['category'] ?? 'other');
        $purchaseDate = trim($_POST['purchase_date'] ?? '') ?: null;
        $purchasePrice = trim($_POST['purchase_price'] ?? '') !== '' ? (float)$_POST['purchase_price'] : 0;
        $supplier = trim($_POST['supplier'] ?? '') ?: null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $depreciationYears = trim($_POST['depreciation_years'] ?? '') !== '' ? (float)$_POST['depreciation_years'] : null;
        $salvageValue = (float)($_POST['salvage_value'] ?? 0);
        $depreciationStartDate = trim($_POST['depreciation_start_date'] ?? '') ?: ($purchaseDate ?: null);
        $status = trim($_POST['status'] ?? 'active');
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($assetCode === '') {
            $errors[] = 'Vui lòng nhập mã tài sản.';
        }
        if ($assetName === '') {
            $errors[] = 'Vui lòng nhập tên tài sản.';
        }
        if (!isset($categoryMap[$category])) {
            $errors[] = 'Loại tài sản không hợp lệ.';
        }
        if (!isset($statusMap[$status])) {
            $errors[] = 'Trạng thái tài sản không hợp lệ.';
        }
        if ($status === 'assigned') {
            $errors[] = 'Không thể đặt trực tiếp trạng thái đã cấp phát.';
        }
        if ($purchaseDate !== null && !$isValidDate($purchaseDate)) {
            $errors[] = 'Ngày mua không hợp lệ.';
        }
        if ($purchasePrice < 0) {
            $errors[] = 'Giá mua không được âm.';
        }
        if ($depreciationYears !== null && $depreciationYears <= 0) {
            $errors[] = 'Thời gian khấu hao phải lớn hơn 0.';
        }
        if ($salvageValue < 0) {
            $errors[] = 'Giá trị còn lại không được âm.';
        }
        if ($purchasePrice > 0 && $salvageValue > $purchasePrice) {
            $errors[] = 'Giá trị còn lại không được lớn hơn giá mua.';
        }
        if ($depreciationStartDate !== null && !$isValidDate($depreciationStartDate)) {
            $errors[] = 'Ngày bắt đầu khấu hao không hợp lệ.';
        }

        $existingAsset = null;
        if ($action === 'edit') {
            $existingAsset = fetchOneSafe($pdo, 'SELECT * FROM company_assets WHERE id = ? LIMIT 1', [$id]);
            if (!$existingAsset) {
                $errors[] = 'Không tìm thấy tài sản cần cập nhật.';
            }
        }

        $duplicate = fetchOneSafe($pdo, 'SELECT id FROM company_assets WHERE asset_code = ? AND id != ? LIMIT 1', [$assetCode, $id]);
        if ($duplicate) {
            $errors[] = 'Mã tài sản đã tồn tại.';
        }

        if (!$errors) {
            try {
                if ($action === 'edit') {
                    $stmt = $pdo->prepare("UPDATE company_assets
                        SET asset_code = ?, asset_name = ?, category = ?, purchase_date = ?, purchase_price = ?, supplier = ?,
                            location = ?, depreciation_years = ?, salvage_value = ?, depreciation_start_date = ?, status = ?, note = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?");
                    $stmt->execute([$assetCode, $assetName, $category, $purchaseDate, $purchasePrice, $supplier, $location, $depreciationYears, $salvageValue, $depreciationStartDate, $status, $note, $id]);
                    setFlash('success', 'Đã cập nhật tài sản.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO company_assets
                        (asset_code, asset_name, category, purchase_date, purchase_price, supplier, location, depreciation_years, salvage_value, depreciation_start_date, status, note, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$assetCode, $assetName, $category, $purchaseDate, $purchasePrice, $supplier, $location, $depreciationYears, $salvageValue, $depreciationStartDate, $status, $note, currentUserId()]);
                    setFlash('success', 'Đã thêm tài sản mới.');
                }
                clearOldInput();
                redirect($assetsPageUrl());
            } catch (Throwable $e) {
                $errors[] = 'Không thể lưu tài sản.';
            }
        }
    }

    if ($action === 'assign') {
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $assignedDate = trim($_POST['assigned_date'] ?? '');
        $note = trim($_POST['note'] ?? '') ?: null;
        $asset = fetchOneSafe($pdo, 'SELECT * FROM company_assets WHERE id = ? LIMIT 1', [$assetId]);

        if (!$asset) {
            setFlash('danger', 'Không tìm thấy tài sản cần cấp phát.');
        } elseif ($asset['status'] === 'disposed') {
            setFlash('danger', 'Tài sản đã thanh lý, không thể cấp phát.');
        } elseif ($asset['status'] === 'assigned') {
            setFlash('danger', 'Tài sản đang được cấp phát.');
        } elseif (!$userId) {
            setFlash('danger', 'Vui lòng chọn nhân viên nhận tài sản.');
        } elseif (!$isValidDate($assignedDate)) {
            setFlash('danger', 'Ngày cấp phát không hợp lệ.');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO asset_assignments (asset_id, user_id, assigned_date, note, created_by) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$assetId, $userId, $assignedDate, $note, currentUserId()]);
                $pdo->prepare("UPDATE company_assets SET status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$assetId]);
                $pdo->commit();
                setFlash('success', 'Đã cấp phát tài sản.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                setFlash('danger', 'Không thể cấp phát tài sản.');
            }
        }
        redirect($assetsPageUrl());
    }

    if ($action === 'return') {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $assignment = fetchOneSafe($pdo, 'SELECT * FROM asset_assignments WHERE id = ? AND returned_date IS NULL LIMIT 1', [$assignmentId]);
        if (!$assignment) {
            setFlash('danger', 'Không tìm thấy bản ghi cấp phát đang mở.');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE asset_assignments SET returned_date = ? WHERE id = ?')->execute([date('Y-m-d'), $assignmentId]);
                $pdo->prepare("UPDATE company_assets SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([(int)$assignment['asset_id']]);
                $pdo->commit();
                setFlash('success', 'Đã thu hồi tài sản.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                setFlash('danger', 'Không thể thu hồi tài sản.');
            }
        }
        redirect($assetsPageUrl());
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $asset = fetchOneSafe($pdo, 'SELECT * FROM company_assets WHERE id = ? LIMIT 1', [$id]);
        if (!$asset) {
            setFlash('danger', 'Không tìm thấy tài sản.');
        } elseif ($asset['status'] === 'assigned') {
            setFlash('danger', 'Không thể xoá tài sản đang cấp phát. Vui lòng thu hồi trước.');
        } else {
            $pdo->prepare('DELETE FROM company_assets WHERE id = ?')->execute([$id]);
            setFlash('success', 'Đã xoá tài sản.');
        }
        redirect($assetsPageUrl());
    }

    if ($errors) {
        flashOldInput($_POST);
        $oldInputWasFlashed = true;
    }
}

$editAsset = null;
if (($_GET['action'] ?? '') === 'edit' && (int)($_GET['id'] ?? 0) > 0) {
    $editAsset = fetchOneSafe($pdo, 'SELECT * FROM company_assets WHERE id = ? LIMIT 1', [(int)$_GET['id']]);
}

$formValues = [
    'id' => $editAsset['id'] ?? '',
    'asset_code' => $editAsset['asset_code'] ?? '',
    'asset_name' => $editAsset['asset_name'] ?? '',
    'category' => $editAsset['category'] ?? 'other',
    'purchase_date' => $editAsset['purchase_date'] ?? '',
    'purchase_price' => isset($editAsset['purchase_price']) ? (string)(float)$editAsset['purchase_price'] : '',
    'supplier' => $editAsset['supplier'] ?? '',
    'location' => $editAsset['location'] ?? '',
    'depreciation_years' => isset($editAsset['depreciation_years']) && $editAsset['depreciation_years'] !== null ? (string)(float)$editAsset['depreciation_years'] : '',
    'salvage_value' => isset($editAsset['salvage_value']) ? (string)(float)$editAsset['salvage_value'] : '0',
    'depreciation_start_date' => $editAsset['depreciation_start_date'] ?? ($editAsset['purchase_date'] ?? ''),
    'status' => $editAsset['status'] ?? 'active',
    'note' => $editAsset['note'] ?? '',
];
if (isset($_SESSION['_old_input']) && is_array($_SESSION['_old_input'])) {
    foreach ($formValues as $key => $value) {
        if (array_key_exists($key, $_SESSION['_old_input'])) {
            $formValues[$key] = (string)$_SESSION['_old_input'][$key];
        }
    }
}
$showForm = !empty($errors) || $editAsset !== null || isset($_GET['show_form']);

$where = ['1=1'];
$params = [];
if ($filterCategory !== '') {
    $where[] = 'ca.category = ?';
    $statsWhere[] = 'category = ?';
    $params[] = $filterCategory;
}
if ($filterStatus !== '') {
    $where[] = 'ca.status = ?';
    $statsWhere[] = 'status = ?';
    $params[] = $filterStatus;
}
$assets = fetchAllSafe(
    $pdo,
    "SELECT ca.*, aa.id AS current_assignment_id, aa.assigned_date AS current_assigned_date, u.full_name AS current_user_name
     FROM company_assets ca
     LEFT JOIN asset_assignments aa ON aa.asset_id = ca.id AND aa.returned_date IS NULL
     LEFT JOIN users u ON u.id = aa.user_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY ca.created_at DESC, ca.id DESC",
    $params
);
$stats = fetchOneSafe(
    $pdo,
    "SELECT COUNT(*) AS total_assets,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_assets,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS assigned_assets,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_assets
     FROM company_assets
     WHERE " . implode(' AND ', $statsWhere),
    $params
) ?: ['total_assets' => 0, 'active_assets' => 0, 'assigned_assets' => 0, 'maintenance_assets' => 0];
$totalDeprMonth = (float)$pdo->query("
    SELECT COALESCE(SUM(
        GREATEST(purchase_price - COALESCE(salvage_value, 0), 0) / (depreciation_years * 12)
    ), 0)
    FROM company_assets
    WHERE depreciation_years > 0
      AND depreciation_years IS NOT NULL
      AND purchase_price > 0
      AND status NOT IN ('disposed')
      AND (
            COALESCE(depreciation_start_date, purchase_date) IS NULL
            OR COALESCE(depreciation_start_date, purchase_date) <= CURDATE()
      )
")->fetchColumn();
$employees = fetchAllSafe($pdo, 'SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name');
$assignmentsByAsset = [];
$assetIds = array_column($assets, 'id');
if ($assetIds) {
    $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
    $rows = fetchAllSafe(
        $pdo,
        "SELECT aa.*, u.full_name
         FROM asset_assignments aa
         JOIN users u ON u.id = aa.user_id
         WHERE aa.asset_id IN ($placeholders)
         ORDER BY aa.assigned_date DESC, aa.id DESC",
        $assetIds
    );
    foreach ($rows as $row) {
        $assignmentsByAsset[(int)$row['asset_id']][] = $row;
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-laptop me-2 text-primary"></i>Tài sản công ty</h4>
                <p class="text-muted mb-0">Thêm, cập nhật, cấp phát và thu hồi tài sản bằng form POST truyền thống.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#asset-form-card" aria-expanded="<?= $showForm ? 'true' : 'false' ?>">
                <i class="fas fa-plus me-1"></i> Thêm tài sản
            </button>
        </div>

        <?php showFlash(); ?>

        <div class="collapse <?= $showForm ? 'show' : '' ?> mb-4" id="asset-form-card">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= !empty($formValues['id']) ? 'Cập nhật tài sản' : 'Thêm tài sản mới' ?></h5>
                    <?php if (!empty($formValues['id'])): ?>
                        <a href="/erp/<?= e($assetsPageUrl()) ?>" class="btn btn-sm btn-outline-secondary">Huỷ sửa</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="<?= !empty($formValues['id']) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= e($formValues['id']) ?>">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Mã tài sản <span class="text-danger">*</span></label><input type="text" name="asset_code" class="form-control" value="<?= e($formValues['asset_code']) ?>" required></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Tên tài sản <span class="text-danger">*</span></label><input type="text" name="asset_name" class="form-control" value="<?= e($formValues['asset_name']) ?>" required></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Loại</label><select name="category" class="form-select"><?php foreach ($categoryMap as $value => $label): ?><option value="<?= e($value) ?>" <?= $formValues['category'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Ngày mua</label><input type="date" name="purchase_date" class="form-control" value="<?= e($formValues['purchase_date']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Giá mua</label><input type="number" name="purchase_price" class="form-control text-end" min="0" step="0.01" value="<?= e($formValues['purchase_price']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Nhà cung cấp</label><input type="text" name="supplier" class="form-control" value="<?= e($formValues['supplier']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Vị trí</label><input type="text" name="location" class="form-control" value="<?= e($formValues['location']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Trạng thái</label><select name="status" class="form-select"><?php foreach ($statusMap as $value => $meta): ?><?php if ($value === 'assigned') continue; ?><option value="<?= e($value) ?>" <?= $formValues['status'] === $value ? 'selected' : '' ?>><?= e($meta[1]) ?></option><?php endforeach; ?></select></div>
                            <div class="col-12"><div class="border-top pt-3 mt-1"><h6 class="mb-3 text-warning"><i class="fas fa-chart-line me-2"></i>Khấu hao tài sản</h6></div></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Thời gian khấu hao (năm)</label><input type="number" name="depreciation_years" class="form-control text-end" min="0" step="0.01" placeholder="VD: 5" value="<?= e($formValues['depreciation_years']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Giá trị còn lại</label><input type="number" name="salvage_value" class="form-control text-end" min="0" step="0.01" value="<?= e($formValues['salvage_value']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Ngày bắt đầu khấu hao</label><input type="date" name="depreciation_start_date" class="form-control" value="<?= e($formValues['depreciation_start_date'] ?: $formValues['purchase_date']) ?>"><div class="form-text">Để trống sẽ mặc định theo ngày mua.</div></div>
                            <div class="col-12"><label class="form-label fw-semibold">Ghi chú</label><textarea name="note" class="form-control" rows="2"><?= e($formValues['note']) ?></textarea></div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu tài sản</button>
                            <?php if (!empty($formValues['id'])): ?><a href="/erp/<?= e($assetsPageUrl()) ?>" class="btn btn-outline-secondary">Huỷ</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="get" class="row g-2 align-items-center">
                    <div class="col-md-3"><select name="category" class="form-select form-select-sm"><option value="">-- Loại tài sản --</option><?php foreach ($categoryMap as $value => $label): ?><option value="<?= e($value) ?>" <?= $filterCategory === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">-- Trạng thái --</option><?php foreach ($statusMap as $value => $meta): ?><option value="<?= e($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= e($meta[1]) ?></option><?php endforeach; ?></select></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button><a href="/erp/modules/admin/assets.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Tổng tài sản</div><h4 class="mb-0 text-primary"><?= (int)$stats['total_assets'] ?></h4></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Đang dùng</div><h4 class="mb-0 text-success"><?= (int)$stats['active_assets'] ?></h4></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Đã cấp phát</div><h4 class="mb-0 text-primary"><?= (int)$stats['assigned_assets'] ?></h4></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Bảo dưỡng</div><h4 class="mb-0 text-warning"><?= (int)$stats['maintenance_assets'] ?></h4></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Khấu hao tháng hiện tại</div><h4 class="mb-0 text-warning"><?= e(formatCurrency($totalDeprMonth)) ?></h4></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Mã TS</th><th>Tên</th><th>Loại</th><th>Ngày mua</th><th class="text-end">Giá mua</th><th class="text-end">Khấu hao/tháng</th><th>Vị trí</th><th>Trạng thái</th><th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$assets): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Chưa có tài sản nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                            <?php [$badgeClass, $statusLabel] = $statusMap[$asset['status']] ?? ['secondary', $asset['status']]; ?>
                            <?php $deprMonth = $calculateDepreciationPerMonth($asset); ?>
                            <tr>
                                <td class="fw-semibold text-primary"><?= e($asset['asset_code']) ?></td>
                                <td><div class="fw-semibold"><?= e($asset['asset_name']) ?></div><div class="small text-muted"><?= e($asset['supplier'] ?: '—') ?></div></td>
                                <td><?= e($categoryMap[$asset['category']] ?? $asset['category']) ?></td>
                                <td><?= e(formatDate($asset['purchase_date'])) ?></td>
                                <td class="text-end"><?= e(formatCurrency($asset['purchase_price'])) ?></td>
                                <td class="text-end"><?= $deprMonth !== null ? e(number_format($deprMonth, 0, ',', '.') . ' đ/tháng') : '—' ?></td>
                                <td><?= e($asset['location'] ?: '—') ?></td>
                                <td><span class="badge bg-<?= $badgeClass ?>"><?= e($statusLabel) ?></span><?php if (!empty($asset['current_user_name'])): ?><div class="small text-muted mt-1"><?= e($asset['current_user_name']) ?></div><?php endif; ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="/erp/<?= e($assetsPageUrl(['action' => 'edit', 'id' => (int)$asset['id'], 'show_form' => 1])) ?>#asset-form-card" class="btn btn-sm btn-outline-primary">Sửa</a>
                                        <?php if ($asset['status'] !== 'assigned' && $asset['status'] !== 'disposed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success btn-assign" data-bs-toggle="modal" data-bs-target="#assignModal" data-asset-id="<?= (int)$asset['id'] ?>" data-asset-name="<?= e($asset['asset_name']) ?>">Cấp phát</button>
                                        <?php endif; ?>
                                        <?php if (!empty($asset['current_assignment_id'])): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="return">
                                                <input type="hidden" name="assignment_id" value="<?= (int)$asset['current_assignment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('Thu hồi tài sản này?');">Thu hồi</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" class="d-inline">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$asset['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá tài sản này?');">Xóa</button>
                                        </form>
                                        <?php if (!empty($assignmentsByAsset[(int)$asset['id']])): ?>
                                            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#asset-history-<?= (int)$asset['id'] ?>">Lịch sử</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php if (!empty($assignmentsByAsset[(int)$asset['id']])): ?>
                                <tr class="collapse" id="asset-history-<?= (int)$asset['id'] ?>">
                                    <td colspan="9" class="bg-light">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Nhân viên</th><th>Ngày cấp</th><th>Ngày thu hồi</th><th>Ghi chú</th></tr></thead>
                                                <tbody>
                                                <?php foreach ($assignmentsByAsset[(int)$asset['id']] as $assignment): ?>
                                                    <tr>
                                                        <td><?= e($assignment['full_name']) ?></td>
                                                        <td><?= e(formatDate($assignment['assigned_date'])) ?></td>
                                                        <td><?= e($assignment['returned_date'] ? formatDate($assignment['returned_date']) : 'Đang giữ') ?></td>
                                                        <td><?= e($assignment['note'] ?? '—') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Cấp phát tài sản</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="asset_id" id="assignAssetId" value="">
                    <div class="mb-3"><label class="form-label fw-semibold">Tài sản</label><input type="text" class="form-control" id="assignAssetName" readonly></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Nhân viên <span class="text-danger">*</span></label><select name="user_id" class="form-select" required><option value="">-- Chọn nhân viên --</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= e($employee['full_name']) ?> (<?= e($employee['username']) ?>)</option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Ngày cấp <span class="text-danger">*</span></label><input type="date" name="assigned_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required></div>
                    <div><label class="form-label fw-semibold">Ghi chú</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="submit" class="btn btn-success">Lưu cấp phát</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-assign').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('assignAssetId').value = button.getAttribute('data-asset-id') || '';
        document.getElementById('assignAssetName').value = button.getAttribute('data-asset-name') || '';
    });
});
</script>
<?php
if ($oldInputWasFlashed || isset($_SESSION['_old_input'])) {
    clearOldInput();
}
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php';
