<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');

$pdo = getDBConnection();

$today = date('Y-m-d');

// ── KPI: Tổng mặt hàng vật tư ────────────────────────────────────────────
$totalItems = (int)$pdo->query("SELECT COUNT(*) FROM wa_items WHERE is_active = 1")->fetchColumn();

// ── KPI: Nhập / Xuất hôm nay ─────────────────────────────────────────────
$stmtImport = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) FROM wa_transactions WHERE type = 'import' AND DATE(transacted_at) = ?");
$stmtImport->execute([$today]);
$importToday = (float)$stmtImport->fetchColumn();

$stmtExport = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) FROM wa_transactions WHERE type = 'export' AND DATE(transacted_at) = ?");
$stmtExport->execute([$today]);
$exportToday = (float)$stmtExport->fetchColumn();

// ── KPI: Vật tư sắp hết (tồn < min_stock) ───────────────────────────────
$lowStockCount = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT wi.id,
               COALESCE(SUM(CASE WHEN wt.type='import' THEN wt.qty ELSE -wt.qty END), 0) AS remaining,
               wi.min_stock
        FROM wa_items wi
        LEFT JOIN wa_transactions wt ON wt.item_id = wi.id
        WHERE wi.min_stock > 0
        GROUP BY wi.id
        HAVING remaining < wi.min_stock
    ) t
")->fetchColumn();

// ── KPI: Thành phẩm chờ giao ─────────────────────────────────────────────
// Dùng production_items có qty_done > 0 và chưa xuất qua oqc_delivery_items
$waitingDelivery = (int)$pdo->query("
    SELECT COUNT(DISTINCT pi.id)
    FROM production_items pi
    WHERE pi.qty_done > 0
      AND pi.id NOT IN (
          SELECT DISTINCT production_item_id FROM oqc_delivery_items
          WHERE production_item_id IS NOT NULL
      )
")->fetchColumn();

// ── KPI: Hàng tồn lâu (import > 30 ngày không có export) ────────────────
$slowMoving = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT item_id
        FROM wa_transactions
        WHERE type = 'import'
          AND transacted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND item_id NOT IN (
              SELECT DISTINCT item_id FROM wa_transactions
              WHERE type = 'export' AND transacted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          )
        GROUP BY item_id
    ) t
")->fetchColumn();

// ── Biểu đồ nhập/xuất 30 ngày ────────────────────────────────────────────
$chartDaily = $pdo->query("
    SELECT DATE(transacted_at) AS day,
           SUM(CASE WHEN type = 'import' THEN qty ELSE 0 END) AS import_qty,
           SUM(CASE WHEN type = 'export' THEN qty ELSE 0 END) AS export_qty
    FROM wa_transactions
    WHERE transacted_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(transacted_at)
    ORDER BY day ASC
")->fetchAll();

// ── Top 10 vật tư tồn nhiều ──────────────────────────────────────────────
$topStock = $pdo->query("
    SELECT wi.item_name,
           COALESCE(SUM(CASE WHEN wt.type='import' THEN wt.qty ELSE -wt.qty END), 0) AS remaining
    FROM wa_items wi
    LEFT JOIN wa_transactions wt ON wt.item_id = wi.id
    GROUP BY wi.id
    HAVING remaining > 0
    ORDER BY remaining DESC
    LIMIT 10
")->fetchAll();

// ── Cảnh báo vật tư dưới min ─────────────────────────────────────────────
$lowStockList = $pdo->query("
    SELECT wi.item_name, wi.min_stock,
           COALESCE(SUM(CASE WHEN wt.type='import' THEN wt.qty ELSE -wt.qty END), 0) AS current_stock
    FROM wa_items wi
    LEFT JOIN wa_transactions wt ON wt.item_id = wi.id
    WHERE wi.min_stock > 0
    GROUP BY wi.id
    HAVING current_stock < wi.min_stock
    ORDER BY current_stock ASC
    LIMIT 20
")->fetchAll();

// ── Thành phẩm tồn lâu (> 30 ngày chưa xuất) ───────────────────────────
$oldFinished = $pdo->query("
    SELECT po.order_no AS lot_no, c.customer_name,
           pi.qty_done, pi.qty_total, pi.updated_at AS last_updated
    FROM production_items pi
    JOIN production_orders po ON po.id = pi.order_id
    JOIN iqc_receipts r ON r.id = po.iqc_receipt_id
    JOIN customers c ON c.id = r.customer_id
    WHERE pi.qty_done > 0
      AND pi.updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND pi.id NOT IN (
          SELECT DISTINCT production_item_id FROM oqc_delivery_items
          WHERE production_item_id IS NOT NULL
      )
    ORDER BY pi.updated_at ASC
    LIMIT 20
")->fetchAll();

// ── Vật tư không giao dịch 30 ngày ──────────────────────────────────────
$inactiveItems = $pdo->query("
    SELECT wi.item_name,
           MAX(wt.transacted_at) AS last_transaction
    FROM wa_items wi
    LEFT JOIN wa_transactions wt ON wt.item_id = wi.id
    WHERE wi.is_active = 1
    GROUP BY wi.id
    HAVING last_transaction < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_transaction IS NULL
    ORDER BY last_transaction ASC
    LIMIT 20
")->fetchAll();

// ── Danh sách tất cả vật tư và tồn kho hiện tại ─────────────────────────
$stockList = $pdo->query("
    SELECT
        wi.item_code,
        wi.item_name,
        wc.name AS category_name,
        wi.unit,
        wi.min_stock,
        COALESCE(SUM(CASE WHEN wt.type='import' THEN wt.qty ELSE -wt.qty END), 0) AS current_stock,
        COALESCE(SUM(CASE WHEN wt.type='import' THEN wt.qty ELSE 0 END), 0) AS total_import,
        COALESCE(SUM(CASE WHEN wt.type='export' THEN wt.qty ELSE 0 END), 0) AS total_export,
        MAX(wt.transacted_at) AS last_transaction
    FROM wa_items wi
    LEFT JOIN wa_categories wc ON wc.id = wi.category_id
    LEFT JOIN wa_transactions wt ON wt.item_id = wi.id
    WHERE wi.is_active = 1
    GROUP BY wi.id
    ORDER BY current_stock DESC, wi.item_name ASC
")->fetchAll();

echo json_encode([
    'ok'  => true,
    'kpi' => [
        'total_items'      => $totalItems,
        'import_today'     => $importToday,
        'export_today'     => $exportToday,
        'low_stock_count'  => $lowStockCount,
        'waiting_delivery' => $waitingDelivery,
        'slow_moving'      => $slowMoving,
    ],
    'chart_daily'     => $chartDaily,
    'top_stock'       => $topStock,
    'low_stock_list'  => $lowStockList,
    'old_finished'    => $oldFinished,
    'inactive_items'  => $inactiveItems,
    'stock_list'      => $stockList,
], JSON_UNESCAPED_UNICODE);
