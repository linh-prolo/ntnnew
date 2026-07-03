<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager');

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
$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($action === 'delete') {
    if (!$id) {
        echo json_encode(['ok' => false, 'msg' => 'Thiếu ID']);
        exit;
    }
    $assetCheck = $pdo->prepare('SELECT status FROM company_assets WHERE id = ?');
    $assetCheck->execute([$id]);
    $assetRow = $assetCheck->fetch();
    if ($assetRow && $assetRow['status'] === 'assigned') {
        echo json_encode(['ok' => false, 'msg' => 'Không thể xoá tài sản đang được cấp phát. Vui lòng thu hồi trước.']);
        exit;
    }
    $pdo->prepare('DELETE FROM company_assets WHERE id = ?')->execute([$id]);
    echo json_encode(['ok' => true, 'msg' => 'Đã xoá tài sản']);
    exit;
}

if ($action === 'assign') {
    $assetId = (int)($_POST['asset_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    $assignedDate = trim($_POST['assigned_date'] ?? date('Y-m-d'));
    $note = trim($_POST['note'] ?? '') ?: null;

    if (!$assetId || !$userId || !$assignedDate) {
        echo json_encode(['ok' => false, 'msg' => 'Thiếu dữ liệu cấp phát']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $assetStmt = $pdo->prepare('SELECT status FROM company_assets WHERE id = ? FOR UPDATE');
        $assetStmt->execute([$assetId]);
        $asset = $assetStmt->fetch();
        if (!$asset) {
            throw new RuntimeException('Không tìm thấy tài sản');
        }
        if ($asset['status'] === 'disposed') {
            throw new RuntimeException('Tài sản đã thanh lý');
        }

        $openStmt = $pdo->prepare('SELECT id FROM asset_assignments WHERE asset_id = ? AND returned_date IS NULL LIMIT 1');
        $openStmt->execute([$assetId]);
        if ($openStmt->fetch()) {
            throw new RuntimeException('Tài sản đang được cấp phát');
        }

        $pdo->prepare('INSERT INTO asset_assignments (asset_id, user_id, assigned_date, note, created_by) VALUES (?, ?, ?, ?, ?)')
            ->execute([$assetId, $userId, $assignedDate, $note, $user['id']]);
        $pdo->prepare("UPDATE company_assets SET status = 'assigned', updated_at = NOW() WHERE id = ?")
            ->execute([$assetId]);

        $pdo->commit();
        echo json_encode(['ok' => true, 'msg' => 'Đã cấp phát tài sản']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'return') {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    if (!$assignmentId) {
        echo json_encode(['ok' => false, 'msg' => 'Thiếu lịch sử cấp phát']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT asset_id FROM asset_assignments WHERE id = ? AND returned_date IS NULL FOR UPDATE');
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch();
        if (!$assignment) {
            throw new RuntimeException('Không tìm thấy lịch sử cấp phát đang mở');
        }

        $today = date('Y-m-d');
        $pdo->prepare('UPDATE asset_assignments SET returned_date = ? WHERE id = ?')->execute([$today, $assignmentId]);
        $pdo->prepare("UPDATE company_assets SET status = 'active', updated_at = NOW() WHERE id = ?")
            ->execute([(int)$assignment['asset_id']]);

        $pdo->commit();
        echo json_encode(['ok' => true, 'msg' => 'Đã thu hồi tài sản']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

if (!in_array($action, ['add', 'edit'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Action không hợp lệ']);
    exit;
}

$assetCode = trim($_POST['asset_code'] ?? '');
$assetName = trim($_POST['asset_name'] ?? '');
$category = trim($_POST['category'] ?? 'other');
$purchaseDate = trim($_POST['purchase_date'] ?? '') ?: null;
$purchasePrice = (float)($_POST['purchase_price'] ?? 0);
$supplier = trim($_POST['supplier'] ?? '') ?: null;
$location = trim($_POST['location'] ?? '') ?: null;
$status = trim($_POST['status'] ?? 'active');
$note = trim($_POST['note'] ?? '') ?: null;

if ($assetCode === '' || $assetName === '') {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu mã hoặc tên tài sản']);
    exit;
}
if (!in_array($category, ['computer', 'printer', 'furniture', 'machinery', 'vehicle', 'other'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Loại tài sản không hợp lệ']);
    exit;
}
if (!in_array($status, ['active', 'assigned', 'maintenance', 'disposed'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Trạng thái không hợp lệ']);
    exit;
}
if ($status === 'assigned') {
    echo json_encode(['ok' => false, 'msg' => 'Trạng thái "Đã cấp phát" chỉ được set tự động khi cấp phát tài sản']);
    exit;
}

try {
    $checkCode = $pdo->prepare('SELECT id FROM company_assets WHERE asset_code = ? AND id != ?');
    $checkCode->execute([$assetCode, $id ?: 0]);
    if ($checkCode->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'Mã tài sản đã tồn tại trong hệ thống.']);
        exit;
    }
    if ($action === 'edit') {
        if (!$id) {
            throw new RuntimeException('Thiếu ID');
        }
        $pdo->prepare("UPDATE company_assets
            SET asset_code = ?, asset_name = ?, category = ?, purchase_date = ?, purchase_price = ?, supplier = ?, location = ?, status = ?, note = ?, updated_at = NOW()
            WHERE id = ?")
            ->execute([$assetCode, $assetName, $category, $purchaseDate, $purchasePrice, $supplier, $location, $status, $note, $id]);
    } else {
        $pdo->prepare("INSERT INTO company_assets
            (asset_code, asset_name, category, purchase_date, purchase_price, supplier, location, status, note, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$assetCode, $assetName, $category, $purchaseDate, $purchasePrice, $supplier, $location, $status, $note, $user['id']]);
        $id = (int)$pdo->lastInsertId();
    }

    echo json_encode(['ok' => true, 'msg' => 'Đã lưu tài sản', 'id' => $id]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
