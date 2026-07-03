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

$action = trim($_POST['action'] ?? '');
if ($action !== 'save') {
    echo json_encode(['ok' => false, 'msg' => 'Action không hợp lệ']);
    exit;
}

$year = (int)($_POST['budget_year'] ?? date('Y'));
$month = (int)($_POST['budget_month'] ?? date('n'));
$budgets = $_POST['budgets'] ?? [];
if (!is_array($budgets)) {
    echo json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ.']);
    exit;
}
$user = currentUser();
$pdo = getDBConnection();

if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
    echo json_encode(['ok' => false, 'msg' => 'Kỳ ngân sách không hợp lệ']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO admin_budgets
        (budget_year, budget_month, category_id, budget_amount, note, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            budget_amount = VALUES(budget_amount),
            note = VALUES(note),
            updated_at = CURRENT_TIMESTAMP");

    foreach ($budgets as $categoryId => $row) {
        $categoryId = (int)$categoryId;
        if (!$categoryId) {
            continue;
        }
        $amount = isset($row['amount']) && $row['amount'] !== '' ? (float)$row['amount'] : 0;
        $note = trim($row['note'] ?? '') ?: null;
        $stmt->execute([$year, $month, $categoryId, $amount, $note, $user['id']]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => 'Đã lưu ngân sách']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
