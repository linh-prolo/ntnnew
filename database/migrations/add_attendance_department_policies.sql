-- =============================================================================
-- Migration: Tạo bảng attendance_department_policies
-- Cho phép cấu hình vị trí chấm công theo từng phòng ban.
-- Nếu phòng ban chưa có policy riêng, hệ thống fallback về
-- cấu hình global trong attendance_location_settings.
-- =============================================================================
-- Chạy file này MỘT LẦN trên database.
--
-- Cách chạy:
--   mysql -u root -p ten_database < database/migrations/add_attendance_department_policies.sql
-- =============================================================================

CREATE TABLE IF NOT EXISTS `attendance_department_policies` (
    `id`             INT           NOT NULL AUTO_INCREMENT,
    `department_id`  INT           NOT NULL,
    `policy_name`    VARCHAR(100)  NOT NULL DEFAULT '',
    `location_mode`  ENUM('strict','flexible') NOT NULL DEFAULT 'flexible'
                     COMMENT 'strict=bắt buộc trong bán kính; flexible=cho phép ngoài phạm vi',
    `latitude`       DECIMAL(10,8) NULL     DEFAULT NULL
                     COMMENT 'NULL = dùng tọa độ toàn công ty',
    `longitude`      DECIMAL(11,8) NULL     DEFAULT NULL,
    `radius_meters`  INT           NULL     DEFAULT NULL
                     COMMENT 'NULL = dùng radius toàn công ty',
    `gps_required`   TINYINT(1)    NOT NULL DEFAULT 1,
    `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
    `updated_by`     INT           NULL     DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dept_policy` (`department_id`),
    CONSTRAINT `fk_adp_department`
        FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Policy vị trí chấm công theo phòng ban';
