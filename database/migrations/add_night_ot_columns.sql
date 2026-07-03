-- =============================================================================
-- Migration: Thêm các cột OT đêm mới vào bảng payroll_slips
-- =============================================================================
-- Chạy file này MỘT LẦN trên database để hỗ trợ tính lương ca đêm.
-- Bảng payroll_slips cần có các cột này sau khi PayrollEngine được cập nhật
-- để tính 3 loại OT đêm: ngày thường (×2.1), cuối tuần (×2.7), ngày lễ (×3.9).
--
-- Cách chạy:
--   mysql -u root -p ten_database < database/migrations/add_night_ot_columns.sql
-- =============================================================================


-- =============================================================================
-- Cách 1 — MySQL 8.0+ (hỗ trợ ADD COLUMN IF NOT EXISTS)
-- =============================================================================
-- Bỏ comment khối bên dưới nếu bạn đang dùng MySQL 8.0 trở lên.
-- =============================================================================

/*
ALTER TABLE payroll_slips
    ADD COLUMN IF NOT EXISTS ot_night_weekday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekday_amount INT          NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekend_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_weekend_amount INT          NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_holiday_hours  DECIMAL(6,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ot_night_holiday_amount INT          NOT NULL DEFAULT 0;
*/


-- =============================================================================
-- Cách 2 — MySQL 5.7 compatible (dùng PROCEDURE, chạy được trên cả 5.7 và 8.0)
-- =============================================================================

DROP PROCEDURE IF EXISTS add_night_ot_columns;

DELIMITER $$
CREATE PROCEDURE add_night_ot_columns()
BEGIN
    -- Kiểm tra cột đầu tiên; nếu chưa có thì thêm toàn bộ 6 cột cùng lúc
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
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
END$$
DELIMITER ;

CALL add_night_ot_columns();
DROP PROCEDURE IF EXISTS add_night_ot_columns;
