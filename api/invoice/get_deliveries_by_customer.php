<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant');

$pdo        = getDBConnection();
$customerId = (int) ($_GET['customer_id'] ?? 0);
$fromDate   = trim((string)($_GET['from'] ?? date('Y-m-01')));
$toDate     = trim((string)($_GET['to'] ?? date('Y-m-d')));
if (!$customerId) { echo json_encode(['ok' => false, 'msg' => 'Thiếu khách hàng']); exit; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    echo json_encode(['ok' => false, 'msg' => 'Khoảng ngày không hợp lệ']); exit;
}
if ($fromDate > $toDate) {
    echo json_encode(['ok' => false, 'msg' => 'Từ ngày không được lớn hơn đến ngày']); exit;
}

try {
    // 1. Lấy danh sách biên bản chưa xuất HĐ của khách trong khoảng ngày
    $deliveries = $pdo->prepare("
        SELECT d.id, d.delivery_no, d.delivery_date,
               COUNT(di.id) AS item_count,
               COALESCE(SUM(CASE WHEN di.type = 'done' THEN di.qty_deliver ELSE 0 END), 0) AS total_qty
        FROM oqc_deliveries d
        LEFT JOIN oqc_delivery_items di ON di.delivery_id = d.id
        WHERE d.customer_id = ?
          AND d.status <> 'invoiced'
          AND d.delivery_date BETWEEN ? AND ?
        GROUP BY d.id
        ORDER BY d.delivery_date DESC, d.id DESC
    ");
    $deliveries->execute([$customerId, $fromDate, $toDate]);
    $deliveries = $deliveries->fetchAll(PDO::FETCH_ASSOC);

    // 2. Lấy tất cả items của những biên bản đó, kèm đơn giá từ customer_prices
    if (empty($deliveries)) {
        echo json_encode(['ok' => true, 'deliveries' => [], 'items_by_delivery' => []]);
        exit;
    }

    $deliveryIds  = array_column($deliveries, 'id');
    $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));

    $itemsStmt = $pdo->prepare("
        SELECT di.delivery_id,
               pc.id AS product_code_id,
               pc.product_code,
               pc.description,
               iri.unit,
               di.qty_deliver AS quantity,
               COALESCE(
                   (SELECT cp.unit_price
                    FROM customer_prices cp
                    WHERE cp.customer_id    = ?
                      AND cp.product_code_id = iri.product_code_id
                      AND cp.effective_date <= d.delivery_date
                      AND (cp.expired_date IS NULL OR cp.expired_date >= d.delivery_date)
                      AND cp.is_active = 1
                    ORDER BY cp.effective_date DESC, cp.id DESC
                    LIMIT 1),
                  0
               ) AS unit_price
        FROM oqc_delivery_items di
        JOIN oqc_deliveries d ON d.id = di.delivery_id
        JOIN production_items pi ON di.production_item_id = pi.id
        JOIN iqc_receipt_items iri ON pi.iqc_item_id = iri.id
        JOIN product_codes pc ON pc.id = iri.product_code_id
        WHERE di.delivery_id IN ($placeholders)
          AND di.type = 'done'
        ORDER BY di.delivery_id, di.id
    ");
    $params = array_merge([$customerId], $deliveryIds);
    $itemsStmt->execute($params);
    $allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Nhóm items theo delivery_id
    $itemsByDelivery = [];
    foreach ($allItems as $item) {
        $item['total_price'] = round((float) $item['quantity'] * (float) $item['unit_price']);
        $itemsByDelivery[$item['delivery_id']][] = $item;
    }

    echo json_encode([
        'ok'               => true,
        'deliveries'       => $deliveries,
        'items_by_delivery' => $itemsByDelivery,
    ]);
} catch (Throwable $e) {
    error_log('get_deliveries_by_customer error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi truy vấn dữ liệu']);
}
