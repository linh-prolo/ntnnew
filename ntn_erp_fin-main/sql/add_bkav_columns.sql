-- Migration: Thêm cột BKAV vào bảng invoices
ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS bkav_invoice_no  VARCHAR(50)  NULL DEFAULT NULL COMMENT 'Số hoá đơn do BKAV cấp',
    ADD COLUMN IF NOT EXISTS bkav_status      VARCHAR(20)  NULL DEFAULT NULL COMMENT 'issued | error | null',
    ADD COLUMN IF NOT EXISTS bkav_issued_at   DATETIME     NULL DEFAULT NULL COMMENT 'Thời điểm xuất thành công',
    ADD COLUMN IF NOT EXISTS bkav_raw_response TEXT         NULL DEFAULT NULL COMMENT 'Raw response từ BKAV (debug)';
