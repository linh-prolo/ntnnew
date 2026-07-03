<?php
// Chỉ chạy từ CLI hoặc localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    die('Chỉ được chạy từ localhost hoặc CLI');
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
$pdo = getDBConnection();

$sql = "
ALTER TABLE payroll_slips
    ADD COLUMN IF NOT EXISTS ot_night_weekday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekday_amount INT          NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekend_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekend_amount INT          NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_holiday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_holiday_amount INT          NOT NULL DEFAULT 0
";

try {
    $pdo->exec($sql);
    echo "✅ Migration thành công! Đã thêm các cột OT đêm vào bảng payroll_slips.\n";
} catch (PDOException $e) {
    // Nếu cột đã tồn tại thì bỏ qua lỗi 1060
    if (strpos($e->getMessage(), '1060') !== false) {
        echo "ℹ️ Các cột đã tồn tại, không cần chạy lại.\n";
    } else {
        echo "❌ Lỗi: " . $e->getMessage() . "\n";
    }
}
