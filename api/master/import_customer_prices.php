<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'manager');
ensurePostCsrf();

$pdo = getDBConnection();
$customerId = (int)($_POST['customer_id'] ?? 0);
$rowsJson = $_POST['rows'] ?? '[]';

if (!$customerId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu khách hàng']);
    exit;
}

$checkCustomer = $pdo->prepare("SELECT id FROM customers WHERE id = ? LIMIT 1");
$checkCustomer->execute([$customerId]);
if (!$checkCustomer->fetchColumn()) {
    echo json_encode(['ok' => false, 'msg' => 'Khách hàng không tồn tại']);
    exit;
}

$rows = json_decode($rowsJson, true);
if (!is_array($rows) || empty($rows)) {
    echo json_encode(['ok' => false, 'msg' => 'Không có dữ liệu']);
    exit;
}

$imported = 0;
$skipped = 0;
$userId = currentUserId();

$isValidDate = static function ($d): bool {
    if (!is_string($d) || $d === '') {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt instanceof DateTime && $dt->format('Y-m-d') === $d;
};

$parsePrice = static function (string $raw): ?float {
    $normalized = str_replace(',', '', preg_replace('/\s+/', '', $raw));
    if ($normalized === '' || !preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
        return null;
    }
    return (float)$normalized;
};

try {
    $pdo->beginTransaction();

    $findProductStmt = $pdo->prepare("SELECT id FROM product_codes WHERE product_code = ?");
    $insertProductStmt = $pdo->prepare("
        INSERT INTO product_codes (product_code, description, unit, is_active, created_by, created_at)
        VALUES (?, ?, ?, 1, ?, NOW())
    ");
    $closeOldPriceStmt = $pdo->prepare("
        UPDATE customer_prices
        SET expired_date = ?
        WHERE customer_id = ?
          AND product_code_id = ?
          AND effective_date <= ?
          AND (expired_date IS NULL OR expired_date >= ?)
    ");
    $insertPriceStmt = $pdo->prepare("
        INSERT INTO customer_prices (customer_id, product_code_id, unit_price, effective_date, expired_date, note, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");

    foreach ($rows as $r) {
        $productCode = strtoupper(trim((string)($r[0] ?? '')));
        $description = trim((string)($r[1] ?? ''));
        $unit = trim((string)($r[2] ?? '')) ?: 'cái';
        $unitPriceRaw = trim((string)($r[3] ?? ''));
        $unitPrice = $parsePrice($unitPriceRaw);
        $effectiveDate = trim((string)($r[4] ?? ''));
        $expiredDate = trim((string)($r[5] ?? '')) ?: null;
        $note = trim((string)($r[6] ?? '')) ?: null;

        if (
            !$productCode ||
            !$description ||
            !$unit ||
            $unitPriceRaw === '' ||
            $unitPrice === null ||
            $unitPrice < 0 ||
            !$isValidDate($effectiveDate) ||
            ($expiredDate !== null && !$isValidDate($expiredDate))
        ) {
            $skipped++;
            continue;
        }

        if ($expiredDate !== null && $expiredDate < $effectiveDate) {
            $skipped++;
            continue;
        }

        $findProductStmt->execute([$productCode]);
        $productCodeId = (int)$findProductStmt->fetchColumn();

        if (!$productCodeId) {
            try {
                $insertProductStmt->execute([$productCode, $description, $unit, $userId]);
                $productCodeId = (int)$pdo->lastInsertId();
            } catch (PDOException $insertEx) {
                if ($insertEx->getCode() !== '23000') {
                    throw $insertEx;
                }
                $findProductStmt->execute([$productCode]);
                $productCodeId = (int)$findProductStmt->fetchColumn();
                if (!$productCodeId) {
                    throw $insertEx;
                }
            }
        }

        $priorDate = date('Y-m-d', strtotime($effectiveDate . ' -1 day'));
        $closeOldPriceStmt->execute([$priorDate, $customerId, $productCodeId, $effectiveDate, $effectiveDate]);
        $insertPriceStmt->execute([$customerId, $productCodeId, $unitPrice, $effectiveDate, $expiredDate, $note]);
        $imported++;
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'imported' => $imported, 'skipped' => $skipped]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi hệ thống']);
}
