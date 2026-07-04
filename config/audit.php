<?php
/**
 * Ghi system audit log
 *
 * @param PDO    $pdo
 * @param string $action      VD: 'create_user', 'delete_payslip', 'check_in'
 * @param string $module      VD: 'users', 'payroll', 'attendance'
 * @param string $level       'success' | 'warning' | 'danger'
 * @param string $description Mô tả dễ đọc
 * @param array  $options     [target_id, target_label, old_value, new_value]
 */
function auditLog(
    PDO    $pdo,
    string $action,
    string $module,
    string $level = 'success',
    string $description = '',
    array  $options = []
): void {
    try {
        $user     = session_status() === PHP_SESSION_ACTIVE ? ($_SESSION ?? []) : [];
        $userId   = $user['user_id']   ?? null;
        $username = $user['username']  ?? null;
        $fullName = $user['full_name'] ?? null;
        $role     = $user['role']      ?? null;

        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = 'unknown';
        }

        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        $oldVal = isset($options['old_value'])
            ? (is_string($options['old_value']) ? $options['old_value'] : json_encode($options['old_value'], JSON_UNESCAPED_UNICODE))
            : null;
        $newVal = isset($options['new_value'])
            ? (is_string($options['new_value']) ? $options['new_value'] : json_encode($options['new_value'], JSON_UNESCAPED_UNICODE))
            : null;

        if (!in_array($level, ['success', 'warning', 'danger'], true)) {
            $level = 'success';
        }

        $stmt = $pdo->prepare("
            INSERT INTO system_audit_logs
                (user_id, username, full_name, role, action, module, target_id, target_label,
                 level, description, old_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $username,
            $fullName,
            $role,
            $action,
            $module,
            $options['target_id']    ?? null,
            $options['target_label'] ?? null,
            $level,
            $description,
            $oldVal,
            $newVal,
            $ip,
            $ua,
        ]);
    } catch (\Throwable $e) {
        // Không được để lỗi audit làm crash chức năng chính
        error_log('auditLog error: ' . $e->getMessage());
    }
}
