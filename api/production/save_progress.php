<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'warehouse', 'production', 'manager');
ensurePostCsrf();

$pdo = getDBConnection();
$orderId = (int)($_POST['order_id'] ?? 0);
$items = $_POST['items'] ?? [];

if ($orderId <= 0 || !is_array($items) || !$items) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu dữ liệu']);
    exit;
}

try {
    $pdo->beginTransaction();

    $selectItem = $pdo->prepare('SELECT id, qty_total FROM production_items WHERE id = ? AND order_id = ? LIMIT 1');
    $updateItem = $pdo->prepare('UPDATE production_items SET qty_done = ?, qty_error = ?, stage = ?, note = ?, status = ?, updated_by = ? WHERE id = ?');

    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $qtyDone = (float)($item['qty_done'] ?? 0);
        $qtyError = (float)($item['qty_error'] ?? 0);
        $stage = trim((string)($item['stage'] ?? '')) ?: null;
        $note = trim((string)($item['note'] ?? '')) ?: null;

        $selectItem->execute([$id, $orderId]);
        $dbItem = $selectItem->fetch(PDO::FETCH_ASSOC);
        if (!$dbItem) {
            throw new RuntimeException('Không tìm thấy item');
        }

        $qtyTotal = (float)$dbItem['qty_total'];
        if ($qtyDone < 0 || $qtyError < 0 || ($qtyDone + $qtyError) > $qtyTotal) {
            throw new RuntimeException('Số lượng không hợp lệ');
        }

        $status = ($qtyDone + $qtyError >= $qtyTotal && $qtyTotal > 0) ? 'done' : 'in_progress';

        $updateItem->execute([$qtyDone, $qtyError, $stage, $note, $status, currentUserId(), $id]);
    }

    $allDone = (int)fetchScalarSafe($pdo,
        "SELECT COUNT(*) FROM production_items WHERE order_id = ? AND status NOT IN ('done','error')",
        [$orderId],
        0
    ) === 0;

    $newStatus = $allDone ? 'done' : 'in_progress';
    $pdo->prepare('UPDATE production_orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);

    if ($allDone) {
        $iqcId = (int)fetchScalarSafe($pdo, 'SELECT iqc_receipt_id FROM production_orders WHERE id = ?', [$orderId], 0);
        if ($iqcId > 0) {
            $pdo->prepare("UPDATE iqc_receipts SET status = 'done' WHERE id = ?")->execute([$iqcId]);
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
