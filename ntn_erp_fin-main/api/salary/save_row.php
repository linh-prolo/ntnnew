<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireRole('director', 'accountant');

$pdo   = getDBConnection();
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$rowId        = (int)($input['row_id']        ?? 0);
$userId       = (int)($input['user_id']       ?? 0);
$componentId  = (int)($input['component_id']  ?? 0) ?: null;
$customName   = trim($input['custom_name']    ?? '');
$customNameEn = trim($input['custom_name_en'] ?? '');
$amount       = (int)preg_replace('/[^0-9]/', '', $input['amount'] ?? '0');
$type         = in_array($input['component_type'] ?? '', ['earning','bonus','deduction'])
                ? $input['component_type'] : 'earning';
$note         = trim($input['note']           ?? '');
$effectiveDate = !empty($input['effective_date']) ? $input['effective_date'] : null;

// Validate
if (!$userId) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu user_id']); exit;
}
if (!$customName) {
    echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập tên khoản lương']); exit;
}
if ($amount <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Số tiền phải lớn hơn 0']); exit;
}

// Kiểm tra user tồn tại
$chkUser = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
$chkUser->execute([$userId]);
if (!$chkUser->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'Nhân viên không tồn tại']); exit;
}

try {
    if ($rowId > 0) {
        // ── Cập nhật ────────────────────────────────────────────────
        $chk = $pdo->prepare("SELECT id FROM employee_salaries WHERE id = ? AND user_id = ?");
        $chk->execute([$rowId, $userId]);
        if (!$chk->fetch()) {
            echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy khoản lương']); exit;
        }

        $pdo->prepare("
            UPDATE employee_salaries
            SET component_id   = ?,
                custom_name    = ?,
                custom_name_en = ?,
                amount         = ?,
                component_type = ?,
                note           = ?,
                effective_date = ?,
                updated_at     = NOW()
            WHERE id = ? AND user_id = ?
        ")->execute([
            $componentId, $customName, $customNameEn,
            $amount, $type, $note, $effectiveDate,
            $rowId, $userId
        ]);

        echo json_encode([
            'ok'  => true,
            'msg' => '✅ Đã cập nhật khoản lương: ' . $customName,
            'id'  => $rowId,
        ]);

    } else {
        // ── Thêm mới ────────────────────────────────────────────────
        // Lấy sort_order tiếp theo
        $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM employee_salaries WHERE user_id = ?");
        $maxOrder->execute([$userId]);
        $nextOrder = (int)$maxOrder->fetchColumn() + 1;

        $pdo->prepare("
            INSERT INTO employee_salaries
                (user_id, component_id, custom_name, custom_name_en,
                 amount, component_type, note, effective_date,
                 sort_order, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ")->execute([
            $userId, $componentId, $customName, $customNameEn,
            $amount, $type, $note, $effectiveDate,
            $nextOrder, $user['id']
        ]);

        $newId = $pdo->lastInsertId();
        echo json_encode([
            'ok'  => true,
            'msg' => '✅ Đã thêm khoản lương: ' . $customName,
            'id'  => $newId,
        ]);
    }
} catch (Throwable $e) {
    error_log("save_row.php error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Lỗi server: ' . $e->getMessage()]);
}