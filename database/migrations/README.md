# Database Migrations

## Cách chạy migration

```bash
mysql -u root -p ten_database < database/migrations/add_night_ot_columns.sql
```

Hoặc mở phpMyAdmin → chọn database → tab SQL → paste nội dung file vào → Run.

## Danh sách migration

| File | Mô tả | Ngày |
|------|-------|------|
| add_night_ot_columns.sql | Thêm cột OT ca đêm vào bảng payroll_slips | 2026-07-03 |
