<?php
// ---- Format ngày tháng ----
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

// ---- Flash message ----
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'danger' ? '❌' : 'ℹ️');
        echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                {$icon} {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// ---- Lấy ngày làm việc trong tháng (trừ Chủ Nhật) ----
function getWorkingDaysInMonth($year, $month) {
    $days = [];
    $totalDays = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    for ($d = 1; $d <= $totalDays; $d++) {
        $date = "$year-$month-$d";
        $dow = date('N', strtotime($date)); // 7 = Sunday
        if ($dow != 7) {
            $days[] = $date;
        }
    }
    return $days;
}

// ---- Tính giờ làm việc ----
function calcWorkHours($check_in, $check_out) {
    if (empty($check_in) || empty($check_out)) return 0;
    $diff = strtotime($check_out) - strtotime($check_in);
    return round($diff / 3600, 2);
}

// ---- CSRF Token ----
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    // Không xoá token sau khi verify để cho phép nhiều request/modal dùng chung trong 1 trang
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

function getExpenseCategories($pdo) {
    try {
        $sql = "SELECT id, category_name FROM expense_categories WHERE is_active = 1 ORDER BY category_name";
        $categories = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($categories)) {
            return $categories;
        }

        $pdo->exec("INSERT IGNORE INTO expense_categories (id, category_name) VALUES
            (1,'Tiền điện'),(2,'Tiền nước'),(3,'Internet'),(4,'Điện thoại'),
            (5,'Thuê văn phòng'),(6,'Chuyển phát nhanh'),(7,'Văn phòng phẩm'),
            (8,'Vệ sinh'),(9,'Mua sắm máy móc / Thiết bị'),(10,'Mua sắm vật tư tiêu hao'),(11,'Khác')");

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

// ---- Safe DB helpers ----
function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function fetchOneSafe(PDO $pdo, string $sql, array $params = []): ?array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function fetchScalarSafe(PDO $pdo, string $sql, array $params = [], $default = null): mixed {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

// ---- CSRF helpers (form-based) ----
function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRF()) . '">';
}

function ensurePostCsrf(): void {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token không hợp lệ. Vui lòng tải lại trang.');
    }
}

function generateDocNo(PDO $pdo, string $type): string {
    $today = date('Y-m-d');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO document_sequences (doc_type, doc_date, last_seq)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE last_seq = last_seq + 1
        ");
        $stmt->execute([$type, $today]);

        $stmt = $pdo->prepare("
            SELECT last_seq
            FROM document_sequences
            WHERE doc_type = ? AND doc_date = ?
        ");
        $stmt->execute([$type, $today]);
        $seq = (int) $stmt->fetchColumn();

        $pdo->commit();
        return $type . '-' . date('Ymd') . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// ---- Output escape ----
function e($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---- Redirect helper ----
function redirect(string $path): never {
    if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
        header('Location: ' . $path);
    } else {
        header('Location: /erp/' . $path);
    }
    exit();
}

// ---- Old input (sau redirect với errors) ----
function old(string $key, string $default = ''): string {
    return (string) ($_SESSION['_old_input'][$key] ?? $default);
}

function flashOldInput(array $data): void {
    $_SESSION['_old_input'] = $data;
}

function clearOldInput(): void {
    unset($_SESSION['_old_input']);
}

// ---- Format currency ----
function formatCurrency($amount): string {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

// ---- Current user ID helper ----
function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function getClientIp(): string {
    $remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        return $remoteAddr;
    }

    return 'unknown';
}

/**
 * Trả về level số của từng role (dùng để so sánh cấp bậc).
 * Level cao hơn = quyền hạn cao hơn.
 */
function getRoleLevel(string $role): int {
    return match($role) {
        'employee'   => 1,
        'production' => 2,
        'manager'    => 3,
        'accountant' => 4,
        'director'   => 5,
        default      => 0,
    };
}

/**
 * Kiểm tra người duyệt ($approver) có quyền duyệt đơn của $requester không.
 * Điều kiện: level của approver > level của requester VÀ approver != requester (khác user_id).
 */
function canApprove(array $approver, array $requester): bool {
    if ($approver['id'] === $requester['id']) return false;        // không tự duyệt
    return getRoleLevel($approver['role']) > getRoleLevel($requester['role']); // phải cao hơn cấp
}

function resolveAttendanceLocation(PDO $pdo, ?float $lat, ?float $lng): array {
    $ip = getClientIp();
    $isWhitelisted = false;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM company_ip_whitelist WHERE ip_address = ? AND is_active = 1");
        $stmt->execute([$ip]);
        $isWhitelisted = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
    }

    $flag = 'unknown';

    if ($lat !== null && $lng !== null) {
        try {
            $configStmt = $pdo->query("SELECT config_key, config_value FROM company_location_config");
            $locationConfig = [];
            foreach ($configStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $locationConfig[$row['config_key']] = $row['config_value'];
            }

            if (isset($locationConfig['lat'], $locationConfig['lng'])) {
                $companyLat = (float)$locationConfig['lat'];
                $companyLng = (float)$locationConfig['lng'];
                $radiusM    = (float)($locationConfig['radius_meters'] ?? 500);

                if ($companyLat != 0.0 || $companyLng != 0.0) {
                    $earthR = 6371000; // Earth radius in meters
                    $dLat = deg2rad($lat - $companyLat);
                    $dLng = deg2rad($lng - $companyLng);
                    $a = sin($dLat / 2) * sin($dLat / 2)
                        + cos(deg2rad($companyLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
                    $distance = $earthR * 2 * atan2(sqrt($a), sqrt(1 - $a));

                    $flag = ($distance <= $radiusM) ? 'verified' : 'outside';
                }
            }
        } catch (Throwable $e) {
            $flag = 'unknown';
        }
    } elseif ($isWhitelisted) {
        $flag = 'verified';
    } else {
        $flag = 'no_gps';
    }

    return [
        'ip' => $ip,
        'flag' => $flag,
        'is_whitelisted' => $isWhitelisted,
    ];
}
?>
