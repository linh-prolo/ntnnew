-- Thêm cột ngày giao hàng dự kiến vào production_orders
ALTER TABLE production_orders
    ADD COLUMN IF NOT EXISTS expected_delivery_date DATE NULL COMMENT 'Ngày giao hàng dự kiến' AFTER status,
    ADD COLUMN IF NOT EXISTS qty_target DECIMAL(12,2) DEFAULT 0 COMMENT 'Sản lượng kế hoạch' AFTER expected_delivery_date;
