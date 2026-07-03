<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director','accountant','manager');

$pdo = getDBConnection();

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']); exit;
}

$action        = trim($_POST['action'] ?? 'save');
$id            = (int)($_POST['id'] ?? 0);
$customerId    = (int)($_POST['customer_id'] ?? 0);
$productCodeId = (int)($_POST['product_code_id'] ?? 0);
$unitPrice     = (float)($_POST['unit_price'] ?? 0);
$effectiveDate = trim($_POST['effective_date'] ?? date('Y-m-d'));
$note          = trim($_POST['note'] ?? '') ?: null;
$productCode   = strtoupper(trim($_POST['product_code'] ?? ''));
$description   = trim($_POST['description'] ?? '');
$unit          = trim($_POST['unit'] ?? '');
$expiredDate   = trim($_POST['expired_date'] ?? '') ?: null;

if ($action === 'save') {
    $action = $id ? 'edit' : 'add';
}

$isValidDate = function ($date): bool {
    if (!$date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
};

try {
    if ($action === 'delete') {
        if (!$customerId || !$productCodeId) {
            if (!$id) {
                echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin xoá']); exit;
            }
            $stmt = $pdo->prepare("SELECT customer_id, product_code_id FROM customer_prices WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy bản ghi']); exit;
            }
            $customerId = (int)$row['customer_id'];
            $productCodeId = (int)$row['product_code_id'];
        }

        $stmt = $pdo->prepare("DELETE FROM customer_prices WHERE customer_id = ? AND product_code_id = ?");
        $stmt->execute([$customerId, $productCodeId]);
        echo json_encode(['ok' => true, 'msg' => 'Đã xoá toàn bộ lịch sử giá của mã hàng']);
        exit;
    }

    if ($action === 'add') {
        if (!$customerId || !$productCode || !$description || !$unit || !$isValidDate($effectiveDate)) {
            echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin bắt buộc (khách hàng, mã SP, tên SP, đơn vị, ngày áp dụng)']); exit;
        }
        if ($unitPrice < 0) {
            echo json_encode(['ok' => false, 'msg' => 'Đơn giá không hợp lệ']); exit;
        }

        $priorPeriodEndDate = date('Y-m-d', strtotime($effectiveDate . ' -1 day'));

        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT id FROM product_codes WHERE product_code = ?");
        $checkStmt->execute([$productCode]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $productCodeId = (int)$existing['id'];
        } else {
            try {
                $pdo->prepare("
                    INSERT INTO product_codes (product_code, description, unit, is_active, created_by, created_at)
                    VALUES (?, ?, ?, 1, ?, NOW())
                ")->execute([$productCode, $description, $unit, currentUser()['id'] ?? null]);
                $productCodeId = (int)$pdo->lastInsertId();
            } catch (PDOException $insertEx) {
                if ($insertEx->getCode() === '23000') {
                    $checkStmt->execute([$productCode]);
                    $found = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$found) throw $insertEx;
                    $productCodeId = (int)$found['id'];
                } else {
                    throw $insertEx;
                }
            }
        }

        $pdo->prepare("
            UPDATE customer_prices
            SET expired_date = ?
            WHERE customer_id = ?
              AND product_code_id = ?
              AND effective_date <= ?
              AND (expired_date IS NULL OR expired_date >= ?)
        ")->execute([$priorPeriodEndDate, $customerId, $productCodeId, $effectiveDate, $effectiveDate]);

        $pdo->prepare("
            INSERT INTO customer_prices (customer_id, product_code_id, unit_price, effective_date, expired_date, note, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ")->execute([$customerId, $productCodeId, $unitPrice, $effectiveDate, $expiredDate, $note]);

        $pdo->commit();
        echo json_encode(['ok' => true, 'msg' => 'Đã thêm đơn giá']);
        exit;
    }

    if ($action === 'edit') {
        if (!$id) {
            echo json_encode(['ok' => false, 'msg' => 'Thiếu id bản ghi cần sửa']); exit;
        }
        if (!$customerId || !$isValidDate($effectiveDate)) {
            echo json_encode(['ok' => false, 'msg' => 'Thiếu khách hàng hoặc ngày áp dụng không hợp lệ']); exit;
        }
        if ($unitPrice < 0) {
            echo json_encode(['ok' => false, 'msg' => 'Đơn giá không hợp lệ']); exit;
        }

        $oldStmt = $pdo->prepare("SELECT * FROM customer_prices WHERE id = ?");
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
        if (!$old) {
            echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy giá cần sửa']); exit;
        }

        if (!$customerId) $customerId = (int)$old['customer_id'];
        if (!$productCodeId) $productCodeId = (int)$old['product_code_id'];

        $priorPeriodEndDate = date('Y-m-d', strtotime($effectiveDate . ' -1 day'));

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE customer_prices SET expired_date = ? WHERE id = ?")
            ->execute([$priorPeriodEndDate, $id]);
        $pdo->prepare("
            INSERT INTO customer_prices (customer_id, product_code_id, unit_price, effective_date, expired_date, note, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ")->execute([$customerId, $productCodeId, $unitPrice, $effectiveDate, $expiredDate, $note]);
        $pdo->commit();

        echo json_encode(['ok' => true, 'msg' => 'Đã cập nhật giá mới']);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Action không hợp lệ']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi hệ thống']);
}
