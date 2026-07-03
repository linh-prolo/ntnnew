<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo = getDBConnection();
$errors = [];
$oldInputWasFlashed = false;
$statusMap = [
    'active' => ['success', 'Đang dùng'],
    'maintenance' => ['warning text-dark', 'Bảo dưỡng'],
    'disposed' => ['secondary', 'Thanh lý'],
];
$docTypeMap = [
    'registration' => 'Đăng kiểm',
    'insurance' => 'Bảo hiểm',
    'maintenance' => 'Bảo dưỡng',
];
$filterStatus = trim($_GET['status'] ?? '');
$selectedTab = in_array($_GET['tab'] ?? '', ['info', 'documents', 'fuel', 'maintenance', 'trips'], true) ? (string)$_GET['tab'] : 'info';
$selectedVehicleId = (int)($_GET['id'] ?? 0);

$vehiclesPageUrl = static function (array $overrides = []) use ($filterStatus, $selectedTab, $selectedVehicleId): string {
    $params = [];
    $status = $overrides['status'] ?? $filterStatus;
    $tab = $overrides['tab'] ?? $selectedTab;
    $id = $overrides['id'] ?? $selectedVehicleId;
    if ($status !== '') {
        $params['status'] = $status;
    }
    if ((int)$id > 0) {
        $params['id'] = (int)$id;
    }
    if ($tab !== '') {
        $params['tab'] = $tab;
    }
    if (!empty($overrides['action'])) {
        $params['action'] = $overrides['action'];
    }
    if (!empty($overrides['show_form'])) {
        $params['show_form'] = 1;
    }
    return 'modules/admin/vehicles.php' . ($params ? '?' . http_build_query($params) : '');
};

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return false;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePostCsrf();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add' || $action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $plateNumber = trim($_POST['plate_number'] ?? '');
        $vehicleName = trim($_POST['vehicle_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '') ?: null;
        $model = trim($_POST['model'] ?? '') ?: null;
        $year = trim($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $color = trim($_POST['color'] ?? '') ?: null;
        $status = trim($_POST['status'] ?? 'active');
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($plateNumber === '') {
            $errors[] = 'Vui lòng nhập biển số xe.';
        }
        if ($vehicleName === '') {
            $errors[] = 'Vui lòng nhập tên xe.';
        }
        if (!isset($statusMap[$status])) {
            $errors[] = 'Trạng thái xe không hợp lệ.';
        }
        if ($year !== null && ($year < 1900 || $year > 2100)) {
            $errors[] = 'Năm sản xuất không hợp lệ.';
        }
        if ($action === 'edit' && !fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1', [$id])) {
            $errors[] = 'Không tìm thấy xe cần cập nhật.';
        }
        if (fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE plate_number = ? AND id != ? LIMIT 1', [$plateNumber, $id])) {
            $errors[] = 'Biển số xe đã tồn tại.';
        }

        if (!$errors) {
            try {
                if ($action === 'edit') {
                    $stmt = $pdo->prepare("UPDATE vehicles
                        SET plate_number = ?, vehicle_name = ?, brand = ?, model = ?, year = ?, color = ?, status = ?, note = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?");
                    $stmt->execute([$plateNumber, $vehicleName, $brand, $model, $year, $color, $status, $note, $id]);
                    setFlash('success', 'Đã cập nhật thông tin xe.');
                    $selectedVehicleId = $id;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO vehicles
                        (plate_number, vehicle_name, brand, model, year, color, status, note, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$plateNumber, $vehicleName, $brand, $model, $year, $color, $status, $note, currentUserId()]);
                    $selectedVehicleId = (int)$pdo->lastInsertId();
                    setFlash('success', 'Đã thêm phương tiện mới.');
                }
                clearOldInput();
                redirect($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'info']));
            } catch (Throwable $e) {
                $errors[] = 'Không thể lưu thông tin xe.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $vehicle = fetchOneSafe($pdo, 'SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$id]);
        if (!$vehicle) {
            setFlash('danger', 'Không tìm thấy xe.');
        } else {
            $relatedCount = (int)fetchScalarSafe(
                $pdo,
                'SELECT (SELECT COUNT(*) FROM vehicle_documents WHERE vehicle_id = ?) + (SELECT COUNT(*) FROM vehicle_fuel WHERE vehicle_id = ?) + (SELECT COUNT(*) FROM vehicle_maintenance WHERE vehicle_id = ?) + (SELECT COUNT(*) FROM vehicle_trips WHERE vehicle_id = ?) AS total',
                [$id, $id, $id, $id],
                0
            );
            if ($relatedCount > 0) {
                setFlash('danger', 'Không thể xoá xe này vì đã có dữ liệu liên quan. Vui lòng xoá các dữ liệu bảo dưỡng, hồ sơ, đổ dầu hoặc chuyến đi trước.');
            } else {
                $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
                setFlash('success', 'Đã xoá phương tiện.');
            }
        }
        redirect($vehiclesPageUrl(['id' => 0, 'tab' => 'info']));
    }

    if ($action === 'add_document') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $docType = trim($_POST['doc_type'] ?? 'registration');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $cost = trim($_POST['cost'] ?? '') !== '' ? (float)$_POST['cost'] : 0;
        $provider = trim($_POST['provider'] ?? '') ?: null;
        $note = trim($_POST['note'] ?? '') ?: null;
        if (!fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId])) {
            setFlash('danger', 'Không tìm thấy xe cần thêm hồ sơ.');
        } elseif (!isset($docTypeMap[$docType]) || !$isValidDate($startDate) || !$isValidDate($endDate)) {
            setFlash('danger', 'Dữ liệu hồ sơ xe không hợp lệ.');
        } elseif ($endDate < $startDate) {
            setFlash('danger', 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
        } else {
            $pdo->prepare('INSERT INTO vehicle_documents (vehicle_id, doc_type, start_date, end_date, cost, provider, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$vehicleId, $docType, $startDate, $endDate, $cost, $provider, $note, currentUserId()]);
            setFlash('success', 'Đã thêm hồ sơ xe.');
        }
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'documents']));
    }

    if ($action === 'delete_document') {
        $id = (int)($_POST['id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $pdo->prepare('DELETE FROM vehicle_documents WHERE id = ?')->execute([$id]);
        setFlash('success', 'Đã xoá hồ sơ xe.');
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'documents']));
    }

    if ($action === 'add_fuel') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $fuelDate = trim($_POST['fuel_date'] ?? '');
        $invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $liters = trim($_POST['liters'] ?? '') !== '' ? (float)$_POST['liters'] : null;
        $odometer = trim($_POST['odometer'] ?? '') !== '' ? (int)$_POST['odometer'] : null;
        $note = trim($_POST['note'] ?? '') ?: null;
        if (!fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId])) {
            setFlash('danger', 'Không tìm thấy xe cần ghi đổ dầu.');
        } elseif (!$isValidDate($fuelDate) || $amount <= 0) {
            setFlash('danger', 'Dữ liệu đổ dầu không hợp lệ.');
        } else {
            $pdo->prepare('INSERT INTO vehicle_fuel (vehicle_id, fuel_date, invoice_no, amount, liters, odometer, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$vehicleId, $fuelDate, $invoiceNo, $amount, $liters, $odometer, $note, currentUserId()]);
            setFlash('success', 'Đã ghi nhận lịch sử đổ dầu.');
        }
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'fuel']));
    }

    if ($action === 'delete_fuel') {
        $id = (int)($_POST['id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $pdo->prepare('DELETE FROM vehicle_fuel WHERE id = ?')->execute([$id]);
        setFlash('success', 'Đã xoá lịch sử đổ dầu.');
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'fuel']));
    }

    if ($action === 'add_maintenance') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $maintenanceDate = trim($_POST['maintenance_date'] ?? '');
        $maintenanceType = trim($_POST['maintenance_type'] ?? 'routine');
        $description = trim($_POST['description'] ?? '');
        $garageName = trim($_POST['garage_name'] ?? '') ?: null;
        $invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $odometer = trim($_POST['odometer'] ?? '') !== '' ? (int)$_POST['odometer'] : null;
        $note = trim($_POST['note'] ?? '') ?: null;

        if (!$vehicleId) {
            setFlash('danger', 'Thiếu phương tiện cần bảo dưỡng.');
        } elseif (!fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId])) {
            setFlash('danger', 'Không tìm thấy xe cần thêm bảo dưỡng.');
        } elseif (!$isValidDate($maintenanceDate)) {
            setFlash('danger', 'Ngày bảo dưỡng không hợp lệ.');
        } elseif ($description === '') {
            setFlash('danger', 'Vui lòng nhập mô tả công việc.');
        } elseif ($amount <= 0) {
            setFlash('danger', 'Chi phí bảo dưỡng phải lớn hơn 0.');
        } elseif (!in_array($maintenanceType, ['routine', 'repair'], true)) {
            setFlash('danger', 'Loại bảo dưỡng không hợp lệ.');
        } else {
            $pdo->prepare("INSERT INTO vehicle_maintenance
                (vehicle_id, maintenance_date, maintenance_type, description, garage_name, amount, invoice_no, odometer, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$vehicleId, $maintenanceDate, $maintenanceType, $description, $garageName, $amount, $invoiceNo, $odometer, $note, currentUserId()]);
            setFlash('success', 'Đã thêm bảo dưỡng/sửa chữa.');
        }
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'maintenance']));
    }

    if ($action === 'delete_maintenance') {
        $id = (int)($_POST['id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $pdo->prepare('DELETE FROM vehicle_maintenance WHERE id = ?')->execute([$id]);
        setFlash('success', 'Đã xoá.');
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'maintenance']));
    }

    if ($action === 'add_trip') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $tripDate = trim($_POST['trip_date'] ?? '');
        $driverId = trim($_POST['driver_id'] ?? '') !== '' ? (int)$_POST['driver_id'] : null;
        $origin = trim($_POST['origin'] ?? '') ?: null;
        $destination = trim($_POST['destination'] ?? '') ?: null;
        $kmStart = trim($_POST['km_start'] ?? '') !== '' ? (int)$_POST['km_start'] : null;
        $kmEnd = trim($_POST['km_end'] ?? '') !== '' ? (int)$_POST['km_end'] : null;
        $tollFee = trim($_POST['toll_fee'] ?? '') !== '' ? (float)$_POST['toll_fee'] : 0;
        $note = trim($_POST['note'] ?? '') ?: null;
        if (!fetchOneSafe($pdo, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1', [$vehicleId])) {
            setFlash('danger', 'Không tìm thấy xe cần thêm chuyến đi.');
        } elseif (!$isValidDate($tripDate)) {
            setFlash('danger', 'Ngày sử dụng xe không hợp lệ.');
        } elseif ($kmStart !== null && $kmEnd !== null && $kmEnd < $kmStart) {
            setFlash('danger', 'Km kết thúc phải lớn hơn hoặc bằng km bắt đầu.');
        } else {
            $pdo->prepare('INSERT INTO vehicle_trips (vehicle_id, trip_date, driver_id, origin, destination, km_start, km_end, toll_fee, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$vehicleId, $tripDate, $driverId, $origin, $destination, $kmStart, $kmEnd, $tollFee, $note, currentUserId()]);
            setFlash('success', 'Đã thêm lịch sử sử dụng xe.');
        }
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'trips']));
    }

    if ($action === 'delete_trip') {
        $id = (int)($_POST['id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $pdo->prepare('DELETE FROM vehicle_trips WHERE id = ?')->execute([$id]);
        setFlash('success', 'Đã xoá lịch sử sử dụng xe.');
        redirect($vehiclesPageUrl(['id' => $vehicleId, 'tab' => 'trips']));
    }

    if ($errors) {
        flashOldInput($_POST);
        $oldInputWasFlashed = true;
    }
}

$where = ['1=1'];
$params = [];
if ($filterStatus !== '') {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
}
$vehicles = fetchAllSafe($pdo, 'SELECT * FROM vehicles WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC', $params);
$vehicleIdsWithData = [];
if ($vehicles) {
    $ids = implode(',', array_map(fn($v) => (int)$v['id'], $vehicles));
    $rows = fetchAllSafe($pdo,
        "SELECT vehicle_id FROM (
            SELECT vehicle_id FROM vehicle_fuel WHERE vehicle_id IN ($ids)
            UNION
            SELECT vehicle_id FROM vehicle_maintenance WHERE vehicle_id IN ($ids)
            UNION
            SELECT vehicle_id FROM vehicle_trips WHERE vehicle_id IN ($ids)
        ) t GROUP BY vehicle_id"
    );
    $vehicleIdsWithData = array_column($rows, 'vehicle_id');
}
if ($selectedVehicleId <= 0 && !empty($vehicles[0]['id'])) {
    $selectedVehicleId = (int)$vehicles[0]['id'];
}
$selectedVehicle = $selectedVehicleId > 0 ? fetchOneSafe($pdo, 'SELECT * FROM vehicles WHERE id = ? LIMIT 1', [$selectedVehicleId]) : null;
$documents = $selectedVehicle ? fetchAllSafe($pdo, 'SELECT * FROM vehicle_documents WHERE vehicle_id = ? ORDER BY end_date ASC, id DESC', [$selectedVehicleId]) : [];
$fuels = $selectedVehicle ? fetchAllSafe($pdo, 'SELECT * FROM vehicle_fuel WHERE vehicle_id = ? ORDER BY fuel_date DESC, id DESC', [$selectedVehicleId]) : [];
$maintenanceList = $selectedVehicle ? fetchAllSafe($pdo,
    'SELECT vm.*, u.full_name AS created_by_name
     FROM vehicle_maintenance vm
     JOIN users u ON u.id = vm.created_by
     WHERE vm.vehicle_id = ?
     ORDER BY vm.maintenance_date DESC, vm.id DESC',
    [$selectedVehicleId]
) : [];
$trips = $selectedVehicle ? fetchAllSafe($pdo, 'SELECT vt.*, u.full_name AS driver_name FROM vehicle_trips vt LEFT JOIN users u ON u.id = vt.driver_id WHERE vt.vehicle_id = ? ORDER BY vt.trip_date DESC, vt.id DESC', [$selectedVehicleId]) : [];
$drivers = fetchAllSafe($pdo, 'SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name');
$thisMonthStart = date('Y-m-01');
$thisMonthEnd = date('Y-m-t');
$stmtVehicleCostMonth = $pdo->prepare("
    SELECT
        COALESCE((SELECT SUM(amount) FROM vehicle_fuel WHERE fuel_date BETWEEN ? AND ?), 0)
      + COALESCE((SELECT SUM(amount) FROM vehicle_maintenance WHERE maintenance_date BETWEEN ? AND ?), 0)
      + COALESCE((SELECT SUM(toll_fee) FROM vehicle_trips WHERE trip_date BETWEEN ? AND ? AND toll_fee IS NOT NULL), 0)
");
$stmtVehicleCostMonth->execute([$thisMonthStart, $thisMonthEnd, $thisMonthStart, $thisMonthEnd, $thisMonthStart, $thisMonthEnd]);
$vehicleCostMonth = (float)$stmtVehicleCostMonth->fetchColumn();

$editVehicle = null;
if (($_GET['action'] ?? '') === 'edit' && (int)($_GET['id'] ?? 0) > 0) {
    $editVehicle = fetchOneSafe($pdo, 'SELECT * FROM vehicles WHERE id = ? LIMIT 1', [(int)$_GET['id']]);
}
$formValues = [
    'id' => $editVehicle['id'] ?? '',
    'plate_number' => $editVehicle['plate_number'] ?? '',
    'vehicle_name' => $editVehicle['vehicle_name'] ?? '',
    'brand' => $editVehicle['brand'] ?? '',
    'model' => $editVehicle['model'] ?? '',
    'year' => isset($editVehicle['year']) ? (string)$editVehicle['year'] : '',
    'color' => $editVehicle['color'] ?? '',
    'status' => $editVehicle['status'] ?? 'active',
    'note' => $editVehicle['note'] ?? '',
];
if (isset($_SESSION['_old_input']) && is_array($_SESSION['_old_input'])) {
    foreach ($formValues as $key => $value) {
        if (array_key_exists($key, $_SESSION['_old_input'])) {
            $formValues[$key] = (string)$_SESSION['_old_input'][$key];
        }
    }
}
$showForm = !empty($errors) || $editVehicle !== null || isset($_GET['show_form']);

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-car me-2 text-primary"></i>Quản lý phương tiện</h4>
                <p class="text-muted mb-0">Danh sách xe, hồ sơ, đổ dầu, bảo dưỡng và lịch sử sử dụng theo luồng form POST truyền thống.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#vehicle-form-card" aria-expanded="<?= $showForm ? 'true' : 'false' ?>"><i class="fas fa-plus me-1"></i> Thêm xe</button>
        </div>

        <?php showFlash(); ?>

        <div class="collapse <?= $showForm ? 'show' : '' ?> mb-4" id="vehicle-form-card">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= !empty($formValues['id']) ? 'Cập nhật xe' : 'Thêm xe mới' ?></h5>
                    <?php if (!empty($formValues['id'])): ?><a href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId])) ?>" class="btn btn-sm btn-outline-secondary">Huỷ sửa</a><?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form method="post">
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="<?= !empty($formValues['id']) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= e($formValues['id']) ?>">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Biển số <span class="text-danger">*</span></label><input type="text" name="plate_number" class="form-control" value="<?= e($formValues['plate_number']) ?>" required></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Tên xe <span class="text-danger">*</span></label><input type="text" name="vehicle_name" class="form-control" value="<?= e($formValues['vehicle_name']) ?>" required></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Hãng</label><input type="text" name="brand" class="form-control" value="<?= e($formValues['brand']) ?>"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Model</label><input type="text" name="model" class="form-control" value="<?= e($formValues['model']) ?>"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Năm</label><input type="number" name="year" min="1900" max="2100" class="form-control" value="<?= e($formValues['year']) ?>"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Màu</label><input type="text" name="color" class="form-control" value="<?= e($formValues['color']) ?>"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Trạng thái</label><select name="status" class="form-select"><?php foreach ($statusMap as $value => $meta): ?><option value="<?= e($value) ?>" <?= $formValues['status'] === $value ? 'selected' : '' ?>><?= e($meta[1]) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Ghi chú</label><input type="text" name="note" class="form-control" value="<?= e($formValues['note']) ?>"></div>
                        </div>
                        <div class="mt-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu xe</button><?php if (!empty($formValues['id'])): ?><a href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId])) ?>" class="btn btn-outline-secondary">Huỷ</a><?php endif; ?></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="get" class="row g-2 align-items-center">
                    <?php if ($selectedVehicleId > 0): ?><input type="hidden" name="id" value="<?= $selectedVehicleId ?>"><?php endif; ?>
                    <input type="hidden" name="tab" value="<?= e($selectedTab) ?>">
                    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">-- Trạng thái xe --</option><?php foreach ($statusMap as $value => $meta): ?><option value="<?= e($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= e($meta[1]) ?></option><?php endforeach; ?></select></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Lọc</button><a href="/erp/modules/admin/vehicles.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Tổng chi phí phương tiện tháng <?= e(date('m/Y')) ?></div>
                        <h4 class="mb-0 text-warning"><?= e(formatCurrency($vehicleCostMonth)) ?></h4>
                        <div class="small text-muted mt-2">Gồm đổ dầu, bảo dưỡng/sửa chữa và phí cầu đường.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Biển số</th><th>Tên xe</th><th>Hãng / Model</th><th>Năm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    <?php if (!$vehicles): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Chưa có phương tiện nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <?php [$badgeClass, $statusLabel] = $statusMap[$vehicle['status']] ?? ['secondary', $vehicle['status']]; ?>
                            <?php $hasRelated = in_array((int)$vehicle['id'], $vehicleIdsWithData, false); ?>
                            <tr style="cursor:pointer" onclick="window.location='/erp/<?= e($vehiclesPageUrl(['id' => (int)$vehicle['id'], 'tab' => 'info'])) ?>'">
                                <td class="fw-semibold text-primary"><?= e($vehicle['plate_number']) ?></td>
                                <td><?= e($vehicle['vehicle_name']) ?></td>
                                <td><?= e(trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?: '—') ?></td>
                                <td><?= e($vehicle['year'] ? (string)$vehicle['year'] : '—') ?></td>
                                <td><span class="badge bg-<?= $badgeClass ?>"><?= e($statusLabel) ?></span></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="/erp/<?= e($vehiclesPageUrl(['id' => (int)$vehicle['id'], 'action' => 'edit', 'show_form' => 1])) ?>#vehicle-form-card" class="btn btn-sm btn-outline-warning" onclick="event.stopPropagation()">Sửa</a>
                                        <?php if ($hasRelated): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Không thể xoá: xe đã có dữ liệu hồ sơ, đổ dầu, bảo dưỡng hoặc chuyến đi" data-bs-toggle="tooltip" onclick="event.stopPropagation()">Xóa</button>
                                        <?php else: ?>
                                            <form method="post" class="d-inline" onclick="event.stopPropagation()">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$vehicle['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Xoá phương tiện này?');">Xóa</button>
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

        <?php if ($selectedVehicle): ?>
            <?php [$selectedBadgeClass, $selectedStatusLabel] = $statusMap[$selectedVehicle['status']] ?? ['secondary', $selectedVehicle['status']]; ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><?= e($selectedVehicle['vehicle_name']) ?></h5>
                        <div class="text-muted">Biển số: <strong><?= e($selectedVehicle['plate_number']) ?></strong></div>
                    </div>
                    <span class="badge bg-<?= $selectedBadgeClass ?>"><?= e($selectedStatusLabel) ?></span>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item"><a class="nav-link <?= $selectedTab === 'info' ? 'active' : '' ?>" href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'info'])) ?>">Info</a></li>
                        <li class="nav-item"><a class="nav-link <?= $selectedTab === 'documents' ? 'active' : '' ?>" href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'documents'])) ?>">Hồ sơ</a></li>
                        <li class="nav-item"><a class="nav-link <?= $selectedTab === 'fuel' ? 'active' : '' ?>" href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'fuel'])) ?>">Đổ dầu</a></li>
                        <li class="nav-item">
                            <a class="nav-link <?= $selectedTab === 'maintenance' ? 'active' : '' ?>" href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'maintenance'])) ?>">
                                <i class="fas fa-wrench me-1"></i>Bảo dưỡng &amp; Sửa chữa
                                <span class="badge bg-warning ms-1"><?= count($maintenanceList) ?></span>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link <?= $selectedTab === 'trips' ? 'active' : '' ?>" href="/erp/<?= e($vehiclesPageUrl(['id' => $selectedVehicleId, 'tab' => 'trips'])) ?>">Lịch sử sử dụng</a></li>
                    </ul>

                    <?php if ($selectedTab === 'info'): ?>
                        <div class="row g-3">
                            <div class="col-md-4"><strong>Biển số:</strong> <?= e($selectedVehicle['plate_number']) ?></div>
                            <div class="col-md-4"><strong>Hãng:</strong> <?= e($selectedVehicle['brand'] ?: '—') ?></div>
                            <div class="col-md-4"><strong>Model:</strong> <?= e($selectedVehicle['model'] ?: '—') ?></div>
                            <div class="col-md-4"><strong>Năm:</strong> <?= e($selectedVehicle['year'] ? (string)$selectedVehicle['year'] : '—') ?></div>
                            <div class="col-md-4"><strong>Màu:</strong> <?= e($selectedVehicle['color'] ?: '—') ?></div>
                            <div class="col-md-4"><strong>Trạng thái:</strong> <?= e($selectedStatusLabel) ?></div>
                            <div class="col-12"><strong>Ghi chú:</strong> <?= e($selectedVehicle['note'] ?: '—') ?></div>
                        </div>
                    <?php elseif ($selectedTab === 'documents'): ?>
                        <form method="post" class="border rounded p-3 mb-3 bg-light">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="add_document">
                            <input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label fw-semibold">Loại hồ sơ</label><select name="doc_type" class="form-select"><?php foreach ($docTypeMap as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Từ ngày</label><input type="date" name="start_date" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Đến ngày</label><input type="date" name="end_date" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Chi phí</label><input type="number" name="cost" step="0.01" min="0" class="form-control text-end"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Đơn vị cung cấp</label><input type="text" name="provider" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Ghi chú</label><input type="text" name="note" class="form-control"></div>
                            </div>
                            <div class="mt-3"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Thêm hồ sơ</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Loại</th><th>Từ ngày</th><th>Đến ngày</th><th class="text-end">Chi phí</th><th>Đơn vị</th><th>Ghi chú</th><th>Thao tác</th></tr></thead>
                                <tbody>
                                <?php if (!$documents): ?><tr><td colspan="7" class="text-center text-muted py-3">Chưa có hồ sơ xe.</td></tr><?php endif; ?>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td><?= e($docTypeMap[$document['doc_type']] ?? $document['doc_type']) ?></td>
                                        <td><?= e(formatDate($document['start_date'])) ?></td>
                                        <td><?= e(formatDate($document['end_date'])) ?></td>
                                        <td class="text-end"><?= e(formatCurrency($document['cost'])) ?></td>
                                        <td><?= e($document['provider'] ?: '—') ?></td>
                                        <td><?= e($document['note'] ?: '—') ?></td>
                                        <td><form method="post" class="d-inline"><?= csrfInput() ?><input type="hidden" name="action" value="delete_document"><input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>"><input type="hidden" name="id" value="<?= (int)$document['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá hồ sơ này?');">Xóa</button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($selectedTab === 'fuel'): ?>
                        <form method="post" class="border rounded p-3 mb-3 bg-light">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="add_fuel">
                            <input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label fw-semibold">Ngày đổ</label><input type="date" name="fuel_date" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Số hoá đơn</label><input type="text" name="invoice_no" class="form-control"></div>
                                <div class="col-md-2"><label class="form-label fw-semibold">Số tiền</label><input type="number" name="amount" step="0.01" min="0.01" class="form-control text-end" required></div>
                                <div class="col-md-2"><label class="form-label fw-semibold">Số lít</label><input type="number" name="liters" step="0.01" min="0" class="form-control text-end"></div>
                                <div class="col-md-2"><label class="form-label fw-semibold">Công tơ mét</label><input type="number" name="odometer" min="0" class="form-control text-end"></div>
                                <div class="col-12"><label class="form-label fw-semibold">Ghi chú</label><input type="text" name="note" class="form-control"></div>
                            </div>
                            <div class="mt-3"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Thêm đổ dầu</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Ngày</th><th>Số hoá đơn</th><th class="text-end">Số tiền</th><th class="text-end">Số lít</th><th class="text-end">Km</th><th>Ghi chú</th><th>Thao tác</th></tr></thead>
                                <tbody>
                                <?php if (!$fuels): ?><tr><td colspan="7" class="text-center text-muted py-3">Chưa có lịch sử đổ dầu.</td></tr><?php endif; ?>
                                <?php foreach ($fuels as $fuel): ?>
                                    <tr>
                                        <td><?= e(formatDate($fuel['fuel_date'])) ?></td>
                                        <td><?= e($fuel['invoice_no'] ?: '—') ?></td>
                                        <td class="text-end"><?= e(formatCurrency($fuel['amount'])) ?></td>
                                        <td class="text-end"><?= e($fuel['liters'] !== null ? number_format((float)$fuel['liters'], 2, ',', '.') : '—') ?></td>
                                        <td class="text-end"><?= e($fuel['odometer'] !== null ? number_format((int)$fuel['odometer'], 0, ',', '.') : '—') ?></td>
                                        <td><?= e($fuel['note'] ?: '—') ?></td>
                                        <td><form method="post" class="d-inline"><?= csrfInput() ?><input type="hidden" name="action" value="delete_fuel"><input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>"><input type="hidden" name="id" value="<?= (int)$fuel['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá lần đổ dầu này?');">Xóa</button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($selectedTab === 'maintenance'): ?>
                        <form method="post" class="border rounded p-3 mb-3 bg-light">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="add_maintenance">
                            <input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label fw-semibold">Ngày <span class="text-danger">*</span></label><input type="date" name="maintenance_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Loại <span class="text-danger">*</span></label><select name="maintenance_type" class="form-select" required><option value="routine">🔧 Bảo dưỡng định kỳ</option><option value="repair">🛠️ Sửa chữa</option></select></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Mô tả công việc <span class="text-danger">*</span></label><input type="text" name="description" class="form-control" placeholder="VD: Thay dầu máy, thay lốp..." required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Chi phí (đ) <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" min="0" step="1000" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Garage/Xưởng</label><input type="text" name="garage_name" class="form-control" placeholder="Tên garage..."></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Số hóa đơn</label><input type="text" name="invoice_no" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Km hiện tại</label><input type="number" name="odometer" class="form-control" min="0"></div>
                                <div class="col-md-12"><label class="form-label fw-semibold">Ghi chú</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                            </div>
                            <div class="mt-3"><button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus me-1"></i>Thêm bảo dưỡng/sửa chữa</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Loại</th>
                                        <th>Mô tả</th>
                                        <th>Garage</th>
                                        <th>Km</th>
                                        <th class="text-end">Chi phí</th>
                                        <th>Hóa đơn</th>
                                        <th>Người nhập</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!$maintenanceList): ?><tr><td colspan="9" class="text-center text-muted py-3">Chưa có lịch sử bảo dưỡng/sửa chữa.</td></tr><?php endif; ?>
                                <?php foreach ($maintenanceList as $maintenance): ?>
                                    <tr>
                                        <td><?= e(formatDate($maintenance['maintenance_date'])) ?></td>
                                        <td><?= $maintenance['maintenance_type'] === 'repair' ? '🛠️ Sửa chữa' : '🔧 Bảo dưỡng định kỳ' ?></td>
                                        <td><?= e($maintenance['description']) ?></td>
                                        <td><?= e($maintenance['garage_name'] ?: '—') ?></td>
                                        <td><?= e($maintenance['odometer'] !== null ? number_format((int)$maintenance['odometer'], 0, ',', '.') : '—') ?></td>
                                        <td class="text-end"><?= e(formatCurrency($maintenance['amount'])) ?></td>
                                        <td><?= e($maintenance['invoice_no'] ?: '—') ?></td>
                                        <td><?= e($maintenance['created_by_name']) ?></td>
                                        <td><form method="post" class="d-inline"><?= csrfInput() ?><input type="hidden" name="action" value="delete_maintenance"><input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>"><input type="hidden" name="id" value="<?= (int)$maintenance['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá lịch sử bảo dưỡng này?');">Xóa</button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($maintenanceList): ?>
                                    <tr class="table-warning fw-bold">
                                        <td colspan="5">Tổng chi phí bảo dưỡng/sửa chữa</td>
                                        <td class="text-end"><?= e(formatCurrency(array_sum(array_map('floatval', array_column($maintenanceList, 'amount'))))) ?></td>
                                        <td colspan="3"></td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <form method="post" class="border rounded p-3 mb-3 bg-light">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="add_trip">
                            <input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label fw-semibold">Ngày sử dụng</label><input type="date" name="trip_date" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Tài xế</label><select name="driver_id" class="form-select"><option value="">-- Chọn tài xế --</option><?php foreach ($drivers as $driver): ?><option value="<?= (int)$driver['id'] ?>"><?= e($driver['full_name']) ?> (<?= e($driver['username']) ?>)</option><?php endforeach; ?></select></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Km bắt đầu</label><input type="number" name="km_start" min="0" class="form-control text-end"></div>
                                <div class="col-md-3"><label class="form-label fw-semibold">Km kết thúc</label><input type="number" name="km_end" min="0" class="form-control text-end"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Điểm đi</label><input type="text" name="origin" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Điểm đến</label><input type="text" name="destination" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label fw-semibold">Phí cầu đường</label><input type="number" name="toll_fee" step="0.01" min="0" class="form-control text-end"></div>
                                <div class="col-md-8"><label class="form-label fw-semibold">Ghi chú</label><input type="text" name="note" class="form-control"></div>
                            </div>
                            <div class="mt-3"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Thêm lịch sử</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Ngày</th><th>Tài xế</th><th>Điểm đi</th><th>Điểm đến</th><th class="text-end">Km đi</th><th class="text-end">Phí cầu đường</th><th>Ghi chú</th><th>Thao tác</th></tr></thead>
                                <tbody>
                                <?php if (!$trips): ?><tr><td colspan="8" class="text-center text-muted py-3">Chưa có lịch sử sử dụng xe.</td></tr><?php endif; ?>
                                <?php foreach ($trips as $trip): ?>
                                    <?php $distance = ($trip['km_start'] !== null && $trip['km_end'] !== null) ? ((int)$trip['km_end'] - (int)$trip['km_start']) : null; ?>
                                    <tr>
                                        <td><?= e(formatDate($trip['trip_date'])) ?></td>
                                        <td><?= e($trip['driver_name'] ?: '—') ?></td>
                                        <td><?= e($trip['origin'] ?: '—') ?></td>
                                        <td><?= e($trip['destination'] ?: '—') ?></td>
                                        <td class="text-end"><?= e($distance !== null ? number_format($distance, 0, ',', '.') : '—') ?></td>
                                        <td class="text-end"><?= e(formatCurrency($trip['toll_fee'])) ?></td>
                                        <td><?= e($trip['note'] ?: '—') ?></td>
                                        <td><form method="post" class="d-inline"><?= csrfInput() ?><input type="hidden" name="action" value="delete_trip"><input type="hidden" name="vehicle_id" value="<?= $selectedVehicleId ?>"><input type="hidden" name="id" value="<?= (int)$trip['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá lịch sử sử dụng này?');">Xóa</button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
if ($oldInputWasFlashed || isset($_SESSION['_old_input'])) {
    clearOldInput();
}
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php';
