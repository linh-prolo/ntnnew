ALTER TABLE attendance_logs
    ADD COLUMN IF NOT EXISTS check_in_photo  VARCHAR(255) NULL DEFAULT NULL COMMENT 'Đường dẫn ảnh chụp lúc vào ca' AFTER same_device_alert,
    ADD COLUMN IF NOT EXISTS check_out_photo VARCHAR(255) NULL DEFAULT NULL COMMENT 'Đường dẫn ảnh chụp lúc ra ca' AFTER check_in_photo;
