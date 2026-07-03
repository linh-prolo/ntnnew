<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
requireRole('director');

$pdo = getDBConnection();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// ── KPI: Sản lượng theo khoảng thời gian ─────────────────────────────────
// production_items không có date filter tốt, dùng created_at của production_orders
$rowKpi = $pdo->query("
    SELECT
        COALESCE(SUM(pi.qty_total), 0) AS qty_target,
        COALESCE(SUM(pi.qty_done),  0) AS qty_done,
        COALESCE(SUM(pi.qty_error), 0) AS qty_error
    FROM production_items pi
    JOIN production_orders po ON po.id = pi.order_id
")->fetch();

$qtyTarget = (float)($rowKpi['qty_target'] ?? 0);
$qtyDone   = (float)($rowKpi['qty_done']   ?? 0);
$qtyError  = (float)($rowKpi['qty_error']  ?? 0);
$completionRate = ($qtyTarget > 0) ? round($qtyDone / $qtyTarget * 100, 1) : 0;
$errorRate      = ($qtyTarget > 0) ? round($qtyError / $qtyTarget * 100, 2) : 0;

$ordersInProgress = (int)$pdo->query(
    "SELECT COUNT(*) FROM production_orders WHERE status = 'in_progress'"
)->fetchColumn();

$ordersDone = (int)$pdo->query(
    "SELECT COUNT(*) FROM production_orders WHERE status = 'done'"
)->fetchColumn();

// ── Biểu đồ sản lượng theo ngày (30 ngày gần nhất) ───────────────────────
$chartDaily = $pdo->query("
    SELECT DATE(pi.updated_at) AS day, SUM(pi.qty_done) AS qty_done
    FROM production_items pi
    WHERE pi.updated_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(pi.updated_at)
    ORDER BY day ASC
")->fetchAll();

// ── Bảng tiến độ từng đơn hàng ───────────────────────────────────────────
// JOIN qua iqc_receipts để lấy customer_name (KHÔNG dùng po.customer_id vì không có)
$orderRows = $pdo->query("
    SELECT po.id, po.order_no,
           c.customer_name,
           po.status,
           po.created_at,
           COALESCE(SUM(pi.qty_done),  0) AS qty_done,
           COALESCE(SUM(pi.qty_error), 0) AS qty_error,
           COALESCE(SUM(pi.qty_total), 0) AS qty_total
    FROM production_orders po
    JOIN iqc_receipts r ON r.id = po.iqc_receipt_id
    JOIN customers c ON c.id = r.customer_id
    LEFT JOIN production_items pi ON pi.order_id = po.id
    WHERE po.status IN ('pending','in_progress','done')
    GROUP BY po.id
    ORDER BY po.id DESC
    LIMIT 30
")->fetchAll();

$orders = array_map(function($r) {
    $pct = ($r['qty_total'] > 0) ? round($r['qty_done'] / $r['qty_total'] * 100, 1) : 0;
    return [
        'order_no'               => $r['order_no'],
        'customer_name'          => $r['customer_name'],
        'created_at'             => $r['created_at'],
        'expected_delivery_date' => null,
        'status'                 => $r['status'],
        'qty_total'              => $r['qty_total'],
        'qty_done'               => $r['qty_done'],
        'qty_error'              => $r['qty_error'],
        'progress_pct'           => $pct,
    ];
}, $orderRows);

// ── Đơn sắp trễ: lấy đơn đang in_progress tạo > 7 ngày chưa xong ────────
$soonLate = $pdo->query("
    SELECT po.order_no,
           c.customer_name,
           po.status,
           po.created_at,
           COALESCE(SUM(pi.qty_done), 0)  AS qty_done,
           COALESCE(SUM(pi.qty_total), 0) AS qty_total
    FROM production_orders po
    JOIN iqc_receipts r ON r.id = po.iqc_receipt_id
    JOIN customers c ON c.id = r.customer_id
    LEFT JOIN production_items pi ON pi.order_id = po.id
    WHERE po.status = 'in_progress'
      AND po.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY po.id
    ORDER BY po.created_at ASC
    LIMIT 20
")->fetchAll();

echo json_encode([
    'ok'  => true,
    'kpi' => [
        'qty_target'         => $qtyTarget,
        'qty_done'           => $qtyDone,
        'qty_error'          => $qtyError,
        'completion_rate'    => $completionRate,
        'error_rate'         => $errorRate,
        'orders_in_progress' => $ordersInProgress,
        'orders_done'        => $ordersDone,
    ],
    'chart_daily' => $chartDaily,
    'orders'      => $orders,
    'soon_late'   => $soonLate,
], JSON_UNESCAPED_UNICODE);
