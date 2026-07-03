-- =============================================================================
-- Migration: Thêm cột phụ cấp trách nhiệm / thâm niên vào payroll_slips
-- =============================================================================
-- Chạy file này MỘT LẦN trên database để lưu 2 khoản phụ cấp được PayrollEngine
-- tính riêng thay vì bỏ sót khỏi gross/net/payslip.
--
-- Cách chạy:
--   mysql -u root -p ten_database < database/migrations/add_allowance_received_columns.sql
-- =============================================================================

DROP PROCEDURE IF EXISTS add_allowance_received_columns;

DELIMITER $$
CREATE PROCEDURE add_allowance_received_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'payroll_slips'
          AND COLUMN_NAME  = 'responsibility_allowance_received'
    ) THEN
        ALTER TABLE payroll_slips
            ADD COLUMN responsibility_allowance          DECIMAL(15,0) NOT NULL DEFAULT 0,
            ADD COLUMN responsibility_allowance_received DECIMAL(15,0) NOT NULL DEFAULT 0,
            ADD COLUMN seniority_allowance               DECIMAL(15,0) NOT NULL DEFAULT 0,
            ADD COLUMN seniority_allowance_received      DECIMAL(15,0) NOT NULL DEFAULT 0;
    END IF;
END$$
DELIMITER ;

CALL add_allowance_received_columns();
DROP PROCEDURE IF EXISTS add_allowance_received_columns;
