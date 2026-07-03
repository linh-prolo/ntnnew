<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager','warehouse');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}
if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']);
    exit;
}

$pdo = getDBConnection();
$user = currentUser();
$type = trim($_POST['type'] ?? 'vehicle');
$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if (!in_array($type, ['vehicle', 'fuel', 'document', 'maintenance', 'trip'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Type không hợp lệ']);
    exit;
}
if (!in_array($action, ['add', 'edit', 'delete'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Action không hợp lệ']);
    exit;
}

try {
    if ($type === 'vehicle') {
        if ($action === 'delete') {
            if (!$id) {
                throw new RuntimeException('Thiếu ID');
            }
            $hasData = $pdo->prepare('SELECT (SELECT COUNT(*) FROM vehicle_fuel WHERE vehicle_id=?) + (SELECT COUNT(*) FROM vehicle_maintenance WHERE vehicle_id=?) + (SELECT COUNT(*) FROM vehicle_trips WHERE vehicle_id=?) AS total');
            $hasData->execute([$id, $id, $id]);
            $count = (int)$hasData->fetchColumn();
            if ($count > 0) {
                echo json_encode(['ok' => false, 'msg' => "Xe có $count bản ghi liên quan (nhiên liệu/bảo dưỡng/chuyến đi). Không thể xoá."]);
                exit;
            }
            $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
            echo json_encode(['ok' => true, 'msg' => 'Đã xoá xe']);
            exit;
        }

        $plateNumber = trim($_POST['plate_number'] ?? '');
        $vehicleName = trim($_POST['vehicle_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '') ?: null;
        $model = trim($_POST['model'] ?? '') ?: null;
        $year = trim($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $color = trim($_POST['color'] ?? '') ?: null;
        $status = trim($_POST['status'] ?? 'active');
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($plateNumber === '' || $vehicleName === '') {
            throw new RuntimeException('Thiếu biển số hoặc tên xe');
        }
        if (!in_array($status, ['active', 'maintenance', 'disposed'], true)) {
            throw new RuntimeException('Trạng thái xe không hợp lệ');
        }

        if ($action === 'edit') {
            if (!$id) {
                throw new RuntimeException('Thiếu ID');
            }
            $pdo->prepare("UPDATE vehicles
                SET plate_number = ?, vehicle_name = ?, brand = ?, model = ?, year = ?, color = ?, status = ?, note = ?, updated_at = NOW()
                WHERE id = ?")
                ->execute([$plateNumber, $vehicleName, $brand, $model, $year, $color, $status, $note, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicles
                (plate_number, vehicle_name, brand, model, year, color, status, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$plateNumber, $vehicleName, $brand, $model, $year, $color, $status, $note, $user['id']]);
            $id = (int)$pdo->lastInsertId();
        }

        echo json_encode(['ok' => true, 'msg' => 'Đã lưu thông tin xe', 'id' => $id]);
        exit;
    }

    if ($action === 'delete') {
        if (!$id) {
            throw new RuntimeException('Thiếu ID');
        }
        $tableMap = [
            'fuel' => 'vehicle_fuel',
            'document' => 'vehicle_documents',
            'maintenance' => 'vehicle_maintenance',
            'trip' => 'vehicle_trips',
        ];
        $pdo->prepare('DELETE FROM ' . $tableMap[$type] . ' WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true, 'msg' => 'Đã xoá dữ liệu']);
        exit;
    }

    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    if (!$vehicleId) {
        throw new RuntimeException('Thiếu xe');
    }

    if ($type === 'fuel') {
        $fuelDate = trim($_POST['fuel_date'] ?? '');
        $invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $liters = trim($_POST['liters'] ?? '') !== '' ? (float)$_POST['liters'] : null;
        $odometer = trim($_POST['odometer'] ?? '') !== '' ? (int)$_POST['odometer'] : null;
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($fuelDate === '' || $amount <= 0) {
            throw new RuntimeException('Số lượng nhiên liệu phải lớn hơn 0.');
        }
        if ($action === 'edit' && !$id) {
            throw new RuntimeException('Thiếu ID lịch sử đổ dầu');
        }

        if ($action === 'edit') {
            $pdo->prepare("UPDATE vehicle_fuel
                SET fuel_date = ?, invoice_no = ?, amount = ?, liters = ?, odometer = ?, note = ?
                WHERE id = ?")
                ->execute([$fuelDate, $invoiceNo, $amount, $liters, $odometer, $note, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicle_fuel
                (vehicle_id, fuel_date, invoice_no, amount, liters, odometer, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$vehicleId, $fuelDate, $invoiceNo, $amount, $liters, $odometer, $note, $user['id']]);
            $id = (int)$pdo->lastInsertId();
        }
    }

    if ($type === 'document') {
        $docType = trim($_POST['doc_type'] ?? 'registration');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $provider = trim($_POST['provider'] ?? '') ?: null;
        $note = trim($_POST['note'] ?? '') ?: null;

        if (!in_array($docType, ['registration', 'insurance', 'maintenance'], true) || $startDate === '' || $endDate === '') {
            throw new RuntimeException('Dữ liệu hồ sơ xe không hợp lệ');
        }
        if ($action === 'edit' && !$id) {
            throw new RuntimeException('Thiếu ID hồ sơ xe');
        }

        if ($action === 'edit') {
            $pdo->prepare("UPDATE vehicle_documents
                SET doc_type = ?, start_date = ?, end_date = ?, cost = ?, provider = ?, note = ?
                WHERE id = ?")
                ->execute([$docType, $startDate, $endDate, $cost, $provider, $note, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicle_documents
                (vehicle_id, doc_type, start_date, end_date, cost, provider, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$vehicleId, $docType, $startDate, $endDate, $cost, $provider, $note, $user['id']]);
            $id = (int)$pdo->lastInsertId();
        }
    }

    if ($type === 'maintenance') {
        $maintenanceDate = trim($_POST['maintenance_date'] ?? '');
        $maintenanceType = trim($_POST['maintenance_type'] ?? 'routine');
        $description = trim($_POST['description'] ?? '');
        $garageName = trim($_POST['garage_name'] ?? '') ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $invoiceNo = trim($_POST['invoice_no'] ?? '') ?: null;
        $odometer = trim($_POST['odometer'] ?? '') !== '' ? (int)$_POST['odometer'] : null;
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($maintenanceDate === '' || $description === '' || $amount <= 0) {
            throw new RuntimeException('Vui lòng nhập đầy đủ thông tin bảo dưỡng/sửa chữa.');
        }
        if (!in_array($maintenanceType, ['routine', 'repair'], true)) {
            throw new RuntimeException('Loại bảo dưỡng không hợp lệ');
        }
        if ($action === 'edit' && !$id) {
            throw new RuntimeException('Thiếu ID lịch sử bảo dưỡng');
        }

        if ($action === 'edit') {
            $pdo->prepare("UPDATE vehicle_maintenance
                SET maintenance_date = ?, maintenance_type = ?, description = ?, garage_name = ?, amount = ?, invoice_no = ?, odometer = ?, note = ?
                WHERE id = ?")
                ->execute([$maintenanceDate, $maintenanceType, $description, $garageName, $amount, $invoiceNo, $odometer, $note, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicle_maintenance
                (vehicle_id, maintenance_date, maintenance_type, description, garage_name, amount, invoice_no, odometer, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$vehicleId, $maintenanceDate, $maintenanceType, $description, $garageName, $amount, $invoiceNo, $odometer, $note, $user['id']]);
            $id = (int)$pdo->lastInsertId();
        }
    }

    if ($type === 'trip') {
        $tripDate = trim($_POST['trip_date'] ?? '');
        $driverId = trim($_POST['driver_id'] ?? '') !== '' ? (int)$_POST['driver_id'] : null;
        $origin = trim($_POST['origin'] ?? '') ?: null;
        $destination = trim($_POST['destination'] ?? '') ?: null;
        $kmStart = trim($_POST['km_start'] ?? '') !== '' ? (int)$_POST['km_start'] : null;
        $kmEnd = trim($_POST['km_end'] ?? '') !== '' ? (int)$_POST['km_end'] : null;
        $tollFee = (float)($_POST['toll_fee'] ?? 0);
        $note = trim($_POST['note'] ?? '') ?: null;

        if ($tripDate === '') {
            throw new RuntimeException('Ngày sử dụng xe không hợp lệ');
        }
        if ($kmStart !== null && $kmEnd !== null && $kmEnd < $kmStart) {
            throw new RuntimeException('Số km kết thúc phải lớn hơn hoặc bằng km bắt đầu.');
        }
        if ($action === 'edit' && !$id) {
            throw new RuntimeException('Thiếu ID lịch sử sử dụng xe');
        }

        if ($action === 'edit') {
            $pdo->prepare("UPDATE vehicle_trips
                SET trip_date = ?, driver_id = ?, origin = ?, destination = ?, km_start = ?, km_end = ?, toll_fee = ?, note = ?
                WHERE id = ?")
                ->execute([$tripDate, $driverId, $origin, $destination, $kmStart, $kmEnd, $tollFee, $note, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicle_trips
                (vehicle_id, trip_date, driver_id, origin, destination, km_start, km_end, toll_fee, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$vehicleId, $tripDate, $driverId, $origin, $destination, $kmStart, $kmEnd, $tollFee, $note, $user['id']]);
            $id = (int)$pdo->lastInsertId();
        }
    }

    echo json_encode(['ok' => true, 'msg' => 'Đã lưu dữ liệu phương tiện', 'id' => $id]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
