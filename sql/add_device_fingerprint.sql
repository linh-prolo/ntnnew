-- Thêm device_id và same_device_alert vào attendance_logs
ALTER TABLE attendance_logs
    ADD COLUMN IF NOT EXISTS device_id VARCHAR(64) NULL DEFAULT NULL COMMENT 'SHA-256 fingerprint của thiết bị chấm công' AFTER check_in_location_flag,
    ADD COLUMN IF NOT EXISTS same_device_alert TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = cùng thiết bị với NV khác trong ngày' AFTER device_id;

-- Index để query nhanh
CREATE INDEX IF NOT EXISTS idx_att_device_date ON attendance_logs (device_id, work_date);
