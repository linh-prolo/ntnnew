-- Migration: Thêm các cột ca đêm / OT đêm còn thiếu vào payroll_slips
-- Chạy file này sau khi đã chạy ot_night_hours / ot_night_amount migration

ALTER TABLE `payroll_slips`
  ADD COLUMN IF NOT EXISTS `ot_night_hours`    DECIMAL(5,2)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `ot_night_amount`   DECIMAL(15,0) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `night_shift_bonus`  DECIMAL(15,0) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `is_night_shift`     TINYINT(1)    NOT NULL DEFAULT 0;

-- Thêm giá trị 'night' vào ENUM ot_type của bảng overtime_requests (nếu chưa có)
ALTER TABLE `overtime_requests`
  MODIFY COLUMN `ot_type` ENUM('weekday','weekend','holiday','night') NOT NULL DEFAULT 'weekday';
