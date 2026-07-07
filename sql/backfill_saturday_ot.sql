-- ============================================================
-- Backfill: Chuyển OT Thứ 7 bị gán sai thành ngày thường
-- Rule mới: Thứ 7 = weekday OT; Chủ nhật = weekend OT
-- MySQL WEEKDAY(): 0=Mon ... 5=Sat ... 6=Sun
-- ============================================================

-- 1) Kiểm tra trước khi sửa
SELECT id, user_id, ot_date, ot_type, start_time, end_time
FROM overtime_requests
WHERE WEEKDAY(ot_date) = 5          -- Thứ 7
  AND ot_type IN ('weekend', 'night_weekend')
ORDER BY ot_date, id;

-- 2) Sửa dữ liệu: Thứ 7 => ngày thường
UPDATE overtime_requests
SET ot_type = CASE
    WHEN ot_type = 'weekend'       THEN 'weekday'
    WHEN ot_type = 'night_weekend' THEN 'night_weekday'
    ELSE ot_type
END
WHERE WEEKDAY(ot_date) = 5          -- Thứ 7
  AND ot_type IN ('weekend', 'night_weekend');

-- 3) Kiểm tra sau khi sửa: không còn bản ghi Thứ 7 nào là weekend
SELECT COUNT(*) AS wrong_sat_count
FROM overtime_requests
WHERE WEEKDAY(ot_date) = 5
  AND ot_type IN ('weekend', 'night_weekend');
-- Kết quả mong đợi: 0
