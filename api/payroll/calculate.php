<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/modules/payroll/engine/PayrollEngine.php';
header('Content-Type: application/json');
requireRole('director', 'accountant');

$pdo   = getDBConnection();
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$periodId = (int)($input['period_id'] ?? 0);
if (!$periodId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu period_id']); exit;
}

$stmt = $pdo->prepare("SELECT * FROM payroll_periods WHERE id = ?");
$stmt->execute([$periodId]);
$period = $stmt->fetch();

if (!$period) {
    echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy kỳ lương']); exit;
}
if ($period['status'] === 'locked') {
    echo json_encode(['ok' => false, 'msg' => '🔒 Kỳ lương đã lock, không thể tính lại']); exit;
}

// Lấy tất cả nhân viên active
$users = $pdo->query("
    SELECT id FROM users
    WHERE is_active = 1
    ORDER BY full_name
")->fetchAll(PDO::FETCH_COLUMN);

$engine  = new PayrollEngine($pdo);
$success = 0;
$errors  = [];

foreach ($users as $uid) {
    try {
        $data = $engine->calculate($periodId, (int)$uid);

        // Kiểm tra slip đã tồn tại chưa
        $chk = $pdo->prepare("SELECT id, manually_adjusted FROM payroll_slips WHERE period_id = ? AND user_id = ?");
        $chk->execute([$periodId, $uid]);
        $slip = $chk->fetch();

        if ($slip) {
            if ($slip['manually_adjusted']) {
                // Giữ lại phần KT nhập tay
                $keepFields = [
                    'other_income', 'adjustment', 'other_bonus',
                    'advance_payment', 'remark', 'performance_bonus',
                    'annual_leave_payout', 'pit_adjustment',
                ];
                $autoFields = array_diff_key($data, array_flip($keepFields));
                $set = implode('=?, ', array_keys($autoFields)) . '=?';
                $pdo->prepare("UPDATE payroll_slips SET $set WHERE id = ?")
                    ->execute(array_merge(array_values($autoFields), [$slip['id']]));
            } else {
                $set = implode('=?, ', array_keys($data)) . '=?';
                $pdo->prepare("UPDATE payroll_slips SET $set WHERE period_id = ? AND user_id = ?")
                    ->execute(array_merge(array_values($data), [$periodId, $uid]));
            }
        } else {
            $cols = implode(', ', array_keys($data));
            $vals = implode(', ', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO payroll_slips ($cols) VALUES ($vals)")
                ->execute(array_values($data));
        }
        $success++;
    } catch (Throwable $e) {
        $errors[] = "User #$uid: " . $e->getMessage();
        error_log("Payroll calculate error user #$uid: " . $e->getMessage());
    }
}

echo json_encode([
    'ok'      => true,
    'success' => $success,
    'errors'  => $errors,
    'msg'     => "✅ Đã tính lương cho $success nhân viên"
                . (count($errors) ? ', có ' . count($errors) . ' lỗi' : ''),
]);