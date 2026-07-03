<?php
// Chỉ chạy từ CLI hoặc localhost (IPv4 và IPv6)
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (php_sapi_name() !== 'cli' && $remoteAddr !== '127.0.0.1' && $remoteAddr !== '::1') {
    die('Chỉ được chạy từ localhost hoặc CLI');
}
// Dùng __DIR__ để hoạt động đúng cả khi chạy từ CLI lẫn web
require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

// Dùng stored procedure để tương thích cả MySQL 5.7 và 8.0+
$statements = [
    "DROP PROCEDURE IF EXISTS _add_night_ot_columns",
    "CREATE PROCEDURE _add_night_ot_columns()
     BEGIN
         IF NOT EXISTS (
             SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'payroll_slips'
               AND COLUMN_NAME  = 'ot_night_weekday_hours'
         ) THEN
             ALTER TABLE payroll_slips
                 ADD COLUMN ot_night_weekday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
                 ADD COLUMN ot_night_weekday_amount INT          NOT NULL DEFAULT 0,
                 ADD COLUMN ot_night_weekend_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
                 ADD COLUMN ot_night_weekend_amount INT          NOT NULL DEFAULT 0,
                 ADD COLUMN ot_night_holiday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
                 ADD COLUMN ot_night_holiday_amount INT          NOT NULL DEFAULT 0;
         END IF;
     END",
    "CALL _add_night_ot_columns()",
    "DROP PROCEDURE IF EXISTS _add_night_ot_columns",
];

try {
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
    echo "✅ Migration thành công! Đã thêm các cột OT đêm vào bảng payroll_slips.\n";
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
