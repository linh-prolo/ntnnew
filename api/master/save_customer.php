<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','warehouse','manager');

$pdo  = getDBConnection();
$user = currentUser();
$action = trim($_POST['action'] ?? 'save');

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']); exit;
}

$id            = (int)($_POST['id'] ?? 0);
$customerCode  = strtoupper(trim($_POST['customer_code'] ?? '')) ?: null;
$customerName  = trim($_POST['customer_name'] ?? '');
$address       = trim($_POST['address'] ?? '') ?: null;
$taxCode       = strtoupper(trim($_POST['tax_code'] ?? '')) ?: null;
$contactPerson = trim($_POST['contact_person'] ?? '') ?: null;
$phone         = trim($_POST['phone'] ?? '') ?: null;
$vatRate       = (int)($_POST['vat_rate'] ?? 8);
$email         = trim($_POST['email'] ?? '');
$emailList     = [];
$isActive      = isset($_POST['is_active']) ? 1 : 0;

if (!in_array($vatRate, [0, 5, 8, 10], true)) {
    $vatRate = 8;
}

if ($email) {
    $emailList = array_filter(array_map('trim', explode(';', $email)), static fn($emailItem) => $emailItem !== '');
    $email     = implode('; ', $emailList) ?: null;
} else {
    $email = null;
}

if ($action !== 'delete' && !$customerName) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu tên khách hàng']); exit;
}

if ($action !== 'delete' && $email) {
    foreach ($emailList as $emailItem) {
        if (!filter_var($emailItem, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'Danh sách email có địa chỉ không hợp lệ']); exit;
        }
    }
}

try {
    if ($action === 'delete') {
        if (!hasRole('director')) {
            echo json_encode(['ok' => false, 'msg' => 'Bạn không có quyền xoá khách hàng']); exit;
        }
        if (!$id) {
            echo json_encode(['ok' => false, 'msg' => 'Thiếu ID khách hàng']); exit;
        }

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $hasTxn = false;
        foreach ([
            'warehouse_in'  => "SELECT COUNT(*) FROM warehouse_in WHERE customer_id = ?",
            'warehouse_out' => "SELECT COUNT(*) FROM warehouse_out WHERE customer_id = ?",
            'deliveries'    => "SELECT COUNT(*) FROM deliveries WHERE customer_id = ?",
            'invoices'      => "SELECT COUNT(*) FROM invoices WHERE customer_id = ?"
        ] as $table => $countSql) {
            $checkStmt->execute([$table]);
            if (!(int)$checkStmt->fetchColumn()) {
                continue;
            }
            $cntStmt = $pdo->prepare($countSql);
            $cntStmt->execute([$id]);
            if ((int)$cntStmt->fetchColumn() > 0) {
                $hasTxn = true;
                break;
            }
        }

        if ($hasTxn) {
            $pdo->prepare("UPDATE customers SET is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
            echo json_encode(['ok' => true, 'msg' => 'Khách hàng đã có giao dịch, đã chuyển sang trạng thái ngừng']);
        } else {
            $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
            echo json_encode(['ok' => true, 'msg' => 'Đã xoá khách hàng']);
        }
        exit;
    }

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE customers
            SET customer_code=?, customer_name=?, address=?, tax_code=?,
                contact_person=?, phone=?, email=?, vat_rate=?, is_active=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$customerCode, $customerName, $address,
                        $taxCode, $contactPerson, $phone, $email, $vatRate, $isActive, $id]);
        echo json_encode(['ok' => true, 'msg' => 'Đã cập nhật']);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO customers
                (customer_code, customer_name, address, tax_code, contact_person, phone, email, vat_rate, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customerCode, $customerName, $address,
                        $taxCode, $contactPerson, $phone, $email, $vatRate, $isActive, $user['id']]);
        echo json_encode(['ok' => true, 'msg' => 'Đã thêm mới', 'id' => $pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['ok' => false, 'msg' => 'Mã KH đã tồn tại']);
    } else {
        error_log($e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Lỗi hệ thống']);
    }
}