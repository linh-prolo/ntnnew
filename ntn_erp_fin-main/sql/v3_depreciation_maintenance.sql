-- ── 1. Thêm cột khấu hao vào company_assets ──────────────────────────────
ALTER TABLE company_assets
    ADD COLUMN IF NOT EXISTS depreciation_years DECIMAL(5,2) DEFAULT NULL COMMENT 'Thời gian khấu hao (năm). NULL = không khấu hao',
    ADD COLUMN IF NOT EXISTS salvage_value DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Giá trị còn lại sau khấu hao',
    ADD COLUMN IF NOT EXISTS depreciation_start_date DATE DEFAULT NULL COMMENT 'Ngày bắt đầu khấu hao (mặc định = purchase_date)';

-- ── 2. Tạo bảng bảo dưỡng & sửa chữa phương tiện ────────────────────────
CREATE TABLE IF NOT EXISTS vehicle_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    maintenance_type ENUM('routine','repair') NOT NULL DEFAULT 'routine'
        COMMENT 'routine=bảo dưỡng định kỳ, repair=sửa chữa',
    description VARCHAR(500) NOT NULL COMMENT 'Mô tả công việc',
    garage_name VARCHAR(200) DEFAULT NULL COMMENT 'Garage/xưởng thực hiện',
    amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Chi phí',
    invoice_no VARCHAR(100) DEFAULT NULL COMMENT 'Số hóa đơn',
    odometer INT DEFAULT NULL COMMENT 'Số km khi bảo dưỡng',
    note TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
