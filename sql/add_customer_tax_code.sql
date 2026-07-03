-- Thêm cột tax_code vào bảng customers (nếu chưa có)
ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS tax_code VARCHAR(20) DEFAULT NULL COMMENT 'Mã số thuế' AFTER address,
  ADD COLUMN IF NOT EXISTS vat_rate TINYINT NOT NULL DEFAULT 8 COMMENT 'VAT mặc định (%)' AFTER tax_code;
