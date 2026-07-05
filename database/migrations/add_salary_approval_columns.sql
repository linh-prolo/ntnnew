-- Migration: Add salary approval columns to employee_salaries
-- Run once to add approval workflow support

ALTER TABLE employee_salaries
  ADD COLUMN approval_status ENUM('pending','approved') NOT NULL DEFAULT 'pending' AFTER is_active,
  ADD COLUMN approved_by INT NULL DEFAULT NULL AFTER approval_status,
  ADD COLUMN approved_at DATETIME NULL DEFAULT NULL AFTER approved_by;
