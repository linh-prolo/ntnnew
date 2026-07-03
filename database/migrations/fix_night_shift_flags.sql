-- =============================================================================
-- Migration: Sửa các lỗi liên quan đến ca đêm và OT đêm
-- =============================================================================
-- Chạy file này MỘT LẦN trên database để sửa:
--   1. is_night_shift = 1 cho ca đêm (CA_DEM, NIGHT, CA_KHUYA)
--   2. Mở rộng enum ot_type để hỗ trợ night_weekday, night_weekend, night_holiday
--   3. Chuyển đổi các bản ghi ot_type='night' cũ sang loại phù hợp
--
-- Cách chạy:
--   mysql -u root -p ten_database < database/migrations/fix_night_shift_flags.sql
-- =============================================================================

-- Fix 1: Đặt is_night_shift = 1 cho tất cả ca có giờ vào từ 18:00 trở đi HOẶC giờ ra trước 08:00 (ca qua đêm)
UPDATE work_shifts
SET is_night_shift = 1
WHERE shift_code = 'CA_DEM'
   OR (start_time >= '18:00:00')
   OR (end_time <= '08:00:00' AND end_time > '00:00:00');

-- Fix 2: Đảm bảo các ca đêm phổ biến được set đúng
UPDATE work_shifts SET is_night_shift = 1 WHERE shift_code IN ('CA_DEM', 'NIGHT', 'CA_KHUYA');

-- Fix 3: Mở rộng enum ot_type để hỗ trợ các loại OT đêm mới
ALTER TABLE overtime_requests
MODIFY COLUMN ot_type ENUM('weekday','weekend','holiday','night','night_weekday','night_weekend','night_holiday') NOT NULL DEFAULT 'weekday';

-- Fix 4: Convert bản ghi cũ ot_type='night' sang đúng loại dựa vào ngày

-- night_holiday: ngày lễ
UPDATE overtime_requests
SET ot_type = 'night_holiday'
WHERE ot_type = 'night'
  AND ot_date IN (SELECT holiday_date FROM holidays);

-- night_weekend: Thứ 7 (DAYOFWEEK=7)
UPDATE overtime_requests
SET ot_type = 'night_weekend'
WHERE ot_type = 'night'
  AND DAYOFWEEK(ot_date) = 7;

-- night_weekend: Chủ nhật (DAYOFWEEK=1)
UPDATE overtime_requests
SET ot_type = 'night_weekend'
WHERE ot_type = 'night'
  AND DAYOFWEEK(ot_date) = 1;

-- night_weekday: ngày thường còn lại
UPDATE overtime_requests
SET ot_type = 'night_weekday'
WHERE ot_type = 'night';
