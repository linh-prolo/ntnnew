<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();

$pdo   = getDBConnection();
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$action   = $input['action']    ?? '';
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

switch ($action) {

    case 'submit':
        if (!hasRole('accountant', 'director')) {
            echo json_encode(['ok' => false, 'msg' => 'Không có quyền trình duyệt']); exit;
        }
        if ($period['status'] !== 'draft') {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ trình được kỳ lương ở trạng thái Nháp']); exit;
        }
        // Kiểm tra có phiếu lương chưa
        $count = $pdo->prepare("SELECT COUNT(*) FROM payroll_slips WHERE period_id = ?");
        $count->execute([$periodId]);
        if ($count->fetchColumn() == 0) {
            echo json_encode(['ok' => false, 'msg' => 'Chưa có phiếu lương nào. Hãy tính lương trước!']); exit;
        }
        $pdo->prepare("UPDATE payroll_periods SET status='submitted', submitted_at=NOW(), submitted_by=? WHERE id=?")
            ->execute([$user['id'], $periodId]);
        echo json_encode(['ok' => true, 'msg' => '📤 Đã trình Giám đốc duyệt!', 'status' => 'submitted']);
        break;

    case 'approve':
        if (!hasRole('director')) {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ Giám đốc mới có quyền duyệt']); exit;
        }
        if ($period['status'] !== 'submitted') {
            echo json_encode(['ok' => false, 'msg' => 'Kỳ lương chưa được trình duyệt']); exit;
        }
        $pdo->prepare("UPDATE payroll_periods SET status='approved', approved_at=NOW(), approved_by=? WHERE id=?")
            ->execute([$user['id'], $periodId]);
        echo json_encode(['ok' => true, 'msg' => '✅ Đã duyệt! Nhân viên có thể xem phiếu lương.', 'status' => 'approved']);
        break;

    case 'lock':
        if (!hasRole('director')) {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ Giám đốc mới có quyền lock']); exit;
        }
        if (!in_array($period['status'], ['approved', 'submitted'])) {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ lock được kỳ đã duyệt hoặc đang chờ duyệt']); exit;
        }
        $pdo->prepare("UPDATE payroll_periods SET status='locked', locked_at=NOW(), locked_by=? WHERE id=?")
            ->execute([$user['id'], $periodId]);
        echo json_encode(['ok' => true, 'msg' => '🔒 Đã lock kỳ lương!', 'status' => 'locked']);
        break;

    case 'reopen':
        if (!hasRole('director')) {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ Giám đốc mới có quyền mở lại']); exit;
        }
        if ($period['status'] !== 'locked') {
            echo json_encode(['ok' => false, 'msg' => 'Chỉ mở lại được kỳ đã lock']); exit;
        }
        $pdo->prepare("UPDATE payroll_periods SET status='approved', locked_at=NULL, locked_by=NULL WHERE id=?")
            ->execute([$periodId]);
        echo json_encode(['ok' => true, 'msg' => '🔓 Đã mở lại kỳ lương!', 'status' => 'approved']);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => "Action '$action' không hợp lệ"]);
}