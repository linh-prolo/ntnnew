-- =============================================================================
-- Migration: Thêm cột note vào bảng employee_shifts (nếu chưa có)
-- =============================================================================
-- Chạy file này MỘT LẦN trên database.
--
-- Cách chạy:
--   mysql -u root -p ten_database < database/migrations/add_shift_note_column.sql
-- =============================================================================

DROP PROCEDURE IF EXISTS add_shift_note_column;

DELIMITER $$
CREATE PROCEDURE add_shift_note_column()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'employee_shifts'
          AND COLUMN_NAME  = 'note'
    ) THEN
        ALTER TABLE employee_shifts
            ADD COLUMN note VARCHAR(255) NULL DEFAULT NULL;
    END IF;
END$$
DELIMITER ;

CALL add_shift_note_column();
DROP PROCEDURE IF EXISTS add_shift_note_column;
