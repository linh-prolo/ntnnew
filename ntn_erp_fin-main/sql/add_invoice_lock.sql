-- Thêm các cột khoá hoá đơn vào bảng invoices
ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = đã khoá, chỉ director sửa được',
    ADD COLUMN IF NOT EXISTS locked_bkav_no VARCHAR(50) NULL COMMENT 'Số HĐ BKAV nhập khi khoá',
    ADD COLUMN IF NOT EXISTS locked_bkav_date DATE NULL COMMENT 'Ngày HĐ BKAV nhập khi khoá',
    ADD COLUMN IF NOT EXISTS locked_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS locked_by INT NULL;
