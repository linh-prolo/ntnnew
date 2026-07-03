-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 30, 2026 lúc 12:23 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `ntn_erp`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_budgets`
--

CREATE TABLE `admin_budgets` (
  `id` int(11) NOT NULL,
  `budget_year` int(11) NOT NULL,
  `budget_month` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `budget_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_budgets`
--

INSERT INTO `admin_budgets` (`id`, `budget_year`, `budget_month`, `category_id`, `budget_amount`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2026, 6, 6, 500000.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(2, 2026, 6, 4, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(3, 2026, 6, 3, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(4, 2026, 6, 11, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(5, 2026, 6, 9, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(6, 2026, 6, 10, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(7, 2026, 6, 5, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(8, 2026, 6, 1, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(9, 2026, 6, 2, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(10, 2026, 6, 7, 300000.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17'),
(11, 2026, 6, 8, 0.00, NULL, 18, '2026-06-29 22:57:17', '2026-06-29 22:57:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `asset_assignments`
--

CREATE TABLE `asset_assignments` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attendance_audit_logs`
--

CREATE TABLE `attendance_audit_logs` (
  `id` int(11) NOT NULL,
  `attendance_log_id` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_type` varchar(50) DEFAULT 'manual_edit',
  `old_check_in` datetime DEFAULT NULL,
  `old_check_out` datetime DEFAULT NULL,
  `new_check_in` datetime DEFAULT NULL,
  `new_check_out` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `attendance_audit_logs`
--

INSERT INTO `attendance_audit_logs` (`id`, `attendance_log_id`, `changed_by`, `change_type`, `old_check_in`, `old_check_out`, `new_check_in`, `new_check_out`, `note`, `created_at`) VALUES
(56, 1538, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-02 08:52:19'),
(57, 1538, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-02 08:52:25'),
(58, 1951, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-03 14:02:49'),
(59, 1754, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-03 14:03:06'),
(60, 1952, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-03 14:06:16'),
(61, 1953, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-03 14:06:37'),
(62, 1846, 1, 'manual_edit', NULL, NULL, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '', '2026-06-03 14:37:14'),
(63, 1753, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 09:03:09'),
(64, 1753, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 09:15:06'),
(65, 3400, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 14:04:50'),
(66, 3400, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 14:05:03'),
(67, 1932, 1, 'manual_edit', NULL, NULL, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '', '2026-06-04 14:06:57'),
(68, 3400, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 14:09:27'),
(69, 1797, 1, 'manual_edit', NULL, NULL, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '', '2026-06-04 14:12:51'),
(70, 1797, 1, 'manual_edit', NULL, NULL, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '', '2026-06-04 14:12:52'),
(71, 1752, 1, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-04 14:48:57'),
(72, 3429, 2, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-28 21:53:39'),
(73, 3420, 2, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-28 21:53:45'),
(74, 3438, 18, 'manual_edit', NULL, NULL, NULL, NULL, 'Xóa bản ghi', '2026-06-29 23:04:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attendance_location_settings`
--

CREATE TABLE `attendance_location_settings` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL DEFAULT 'Công ty',
  `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `longitude` decimal(11,8) NOT NULL DEFAULT 0.00000000,
  `radius_meters` int(11) NOT NULL DEFAULT 200,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `work_date` date NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT 0.00,
  `source` enum('machine','manual','system') DEFAULT 'manual',
  `check_in_ip` varchar(45) DEFAULT NULL,
  `check_in_lat` decimal(10,7) DEFAULT NULL,
  `check_in_lng` decimal(10,7) DEFAULT NULL,
  `check_in_location_flag` enum('verified','outside','no_gps','unknown') DEFAULT 'unknown',
  `check_out_ip` varchar(45) DEFAULT NULL,
  `check_out_lat` decimal(10,7) DEFAULT NULL,
  `check_out_lng` decimal(10,7) DEFAULT NULL,
  `check_out_location_flag` enum('verified','outside','no_gps','unknown') DEFAULT 'unknown',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_late` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0,
  `early_leave` tinyint(1) DEFAULT 0,
  `early_leave_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `user_id`, `check_in`, `check_out`, `work_date`, `shift_id`, `work_hours`, `source`, `check_in_ip`, `check_in_lat`, `check_in_lng`, `check_in_location_flag`, `check_out_ip`, `check_out_lat`, `check_out_lng`, `check_out_location_flag`, `note`, `created_at`, `updated_at`, `is_late`, `late_minutes`, `early_leave`, `early_leave_minutes`) VALUES
(1745, 4, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1746, 9, '2026-05-02 07:00:00', NULL, '2026-05-02', NULL, 0.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1747, 5, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1748, 8, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1749, 7, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1750, 6, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1751, 12, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1752, 13, NULL, NULL, '2026-05-02', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-02 02:08:29', '2026-06-04 07:48:57', 0, 0, 0, 0),
(1753, 14, NULL, NULL, '2026-05-02', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-02 02:08:29', '2026-06-04 02:15:06', 0, 0, 0, 0),
(1754, 15, '2026-05-02 07:00:00', '2026-05-02 16:00:00', '2026-05-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-02 02:08:29', '2026-06-04 02:02:30', 0, 0, 0, 0),
(1755, 4, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1756, 5, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1757, 8, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1758, 7, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1759, 6, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1760, 12, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1761, 13, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1762, 14, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1763, 15, '2026-05-04 07:00:00', '2026-05-04 16:00:00', '2026-05-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1764, 4, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1765, 5, '2026-05-05 08:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 8.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 1, 60, 0, 0),
(1766, 8, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1767, 7, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1768, 6, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1769, 12, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1770, 13, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1771, 14, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1772, 15, '2026-05-05 07:00:00', '2026-05-05 16:00:00', '2026-05-05', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1773, 4, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1774, 5, '2026-05-06 09:30:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 6.50, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 1, 150, 0, 0),
(1775, 7, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1776, 6, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1777, 12, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1778, 13, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1779, 14, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1780, 15, '2026-05-06 07:00:00', '2026-05-06 16:00:00', '2026-05-06', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1781, 4, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1782, 5, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1783, 7, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1784, 6, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1785, 12, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1786, 13, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1787, 14, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1788, 15, '2026-05-07 07:00:00', '2026-05-07 16:00:00', '2026-05-07', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1789, 4, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:33:08', 0, 0, 0, 0),
(1790, 5, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1791, 8, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1792, 7, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1793, 6, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1794, 12, '2026-05-08 10:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 6.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 1, 180, 0, 0),
(1795, 13, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1796, 15, '2026-05-08 07:00:00', '2026-05-08 16:00:00', '2026-05-08', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1797, 4, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', '', '2026-06-02 02:08:29', '2026-06-04 07:12:52', 0, 0, 0, 0),
(1798, 5, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1799, 8, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1800, 7, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1801, 6, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1802, 12, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1803, 13, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1804, 14, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1805, 15, '2026-05-09 07:00:00', '2026-05-09 16:00:00', '2026-05-09', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1806, 4, '2026-05-10 10:00:00', '2026-05-10 16:00:00', '2026-05-10', NULL, 6.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 1, 180, 0, 0),
(1807, 9, '2026-05-10 07:00:00', '2026-05-10 16:00:00', '2026-05-10', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1808, 5, '2026-05-10 07:00:00', '2026-05-10 16:00:00', '2026-05-10', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1809, 8, '2026-05-10 07:00:00', '2026-05-10 16:00:00', '2026-05-10', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1810, 9, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1811, 5, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1812, 8, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1813, 7, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1814, 6, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1815, 12, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1816, 13, '2026-05-11 07:00:00', '2026-05-11 16:00:00', '2026-05-11', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1817, 4, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1818, 9, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1819, 5, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1820, 8, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1821, 7, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1822, 6, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1823, 12, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1824, 13, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1825, 15, '2026-05-12 07:00:00', '2026-05-12 16:00:00', '2026-05-12', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1826, 4, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1827, 9, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1828, 5, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1829, 8, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1830, 7, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1831, 6, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1832, 12, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1833, 13, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1834, 15, '2026-05-13 07:00:00', '2026-05-13 16:00:00', '2026-05-13', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1835, 4, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:33:08', 0, 0, 0, 0),
(1836, 9, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1837, 5, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1838, 8, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1839, 7, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1840, 6, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1841, 12, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1842, 15, '2026-05-14 07:00:00', '2026-05-14 16:00:00', '2026-05-14', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1843, 7, '2026-05-15 07:00:00', '2026-05-15 16:00:00', '2026-05-15', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1844, 12, '2026-05-15 07:00:00', '2026-05-15 16:00:00', '2026-05-15', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1845, 15, '2026-05-15 07:00:00', '2026-05-15 16:00:00', '2026-05-15', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1846, 4, '2026-05-16 07:00:00', '2026-05-16 16:00:00', '2026-05-16', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', '', '2026-06-02 02:08:29', '2026-06-04 02:39:00', 0, 0, 0, 0),
(1847, 9, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 0, 0, 1, 120),
(1848, 5, '2026-05-16 07:00:00', '2026-05-16 16:00:00', '2026-05-16', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1849, 8, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 0, 0, 1, 120),
(1850, 7, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 0, 0, 1, 120),
(1851, 6, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 0, 0, 1, 120),
(1852, 12, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-04 02:11:16', 0, 0, 1, 120),
(1853, 15, '2026-05-16 07:00:00', '2026-05-16 14:00:00', '2026-05-16', NULL, 7.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 1, 120),
(1854, 4, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1855, 9, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1856, 5, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1857, 8, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1858, 7, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1859, 6, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1860, 12, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1861, 15, '2026-05-18 07:00:00', '2026-05-18 16:00:00', '2026-05-18', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1862, 9, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1863, 8, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1864, 7, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1865, 6, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1866, 12, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1867, 15, '2026-05-19 07:00:00', '2026-05-19 16:00:00', '2026-05-19', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1868, 4, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1869, 9, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1870, 5, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1871, 8, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1872, 7, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1873, 6, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1874, 12, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1875, 15, '2026-05-20 07:00:00', '2026-05-20 16:00:00', '2026-05-20', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1876, 4, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1877, 9, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1878, 5, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1879, 8, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1880, 7, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1881, 6, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1882, 12, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1883, 15, '2026-05-21 07:00:00', '2026-05-21 16:00:00', '2026-05-21', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1884, 4, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1885, 8, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1886, 7, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1887, 12, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1888, 15, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1889, 4, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1890, 9, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1891, 5, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1892, 8, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1893, 7, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1894, 12, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1895, 15, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1896, 4, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1897, 9, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1898, 5, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1899, 8, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1900, 7, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1901, 6, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1902, 12, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1903, 15, '2026-05-25 07:00:00', '2026-05-25 16:00:00', '2026-05-25', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1904, 4, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1905, 9, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1906, 5, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1907, 8, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1908, 7, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1909, 6, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1910, 12, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1911, 15, '2026-05-26 07:00:00', '2026-05-26 16:00:00', '2026-05-26', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1912, 4, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1913, 9, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1914, 5, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1915, 8, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1916, 7, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1917, 6, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1918, 12, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1919, 15, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1920, 16, '2026-05-27 07:00:00', '2026-05-27 16:00:00', '2026-05-27', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1921, 4, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1922, 9, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1923, 5, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1924, 8, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1925, 7, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1926, 6, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1927, 12, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1928, 15, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1929, 16, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1930, 17, '2026-05-28 07:00:00', '2026-05-28 16:00:00', '2026-05-28', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1931, 4, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1932, 9, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', '', '2026-06-02 02:08:29', '2026-06-04 07:06:57', 0, 0, 0, 0),
(1933, 5, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1934, 8, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1935, 7, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1936, 6, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1937, 12, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1938, 15, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1939, 16, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1940, 17, '2026-05-29 07:00:00', '2026-05-29 16:00:00', '2026-05-29', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1941, 4, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1942, 9, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1943, 5, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1944, 8, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1945, 7, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1946, 6, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1947, 12, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1948, 15, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1949, 16, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1950, 17, '2026-05-30 07:00:00', '2026-05-30 16:00:00', '2026-05-30', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-02 02:08:29', '2026-06-02 02:08:29', 0, 0, 0, 0),
(1951, 15, NULL, NULL, '2026-05-01', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-03 07:02:49', '2026-06-03 07:02:49', 0, 0, 0, 0),
(1952, 17, NULL, NULL, '2026-05-01', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-03 07:06:16', '2026-06-03 07:06:16', 0, 0, 0, 0),
(1953, 16, NULL, NULL, '2026-05-01', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-03 07:06:37', '2026-06-03 07:06:37', 0, 0, 0, 0),
(2095, 6, '2026-05-22 07:00:00', '2026-05-22 16:00:00', '2026-05-22', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-03 07:15:33', '2026-06-03 07:15:33', 0, 0, 0, 0),
(2103, 6, '2026-05-23 07:00:00', '2026-05-23 16:00:00', '2026-05-23', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-03 07:15:33', '2026-06-03 07:15:33', 0, 0, 0, 0),
(3400, 9, NULL, NULL, '2026-05-08', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-04 07:04:50', '2026-06-04 07:09:27', 0, 0, 0, 0),
(3401, 4, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3402, 9, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3403, 8, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3404, 7, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3405, 6, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3406, 12, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3407, 15, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3408, 16, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3409, 17, '2026-06-01 07:00:00', '2026-06-01 16:00:00', '2026-06-01', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3410, 4, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3411, 9, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3412, 8, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3413, 7, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3414, 6, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3415, 12, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3416, 15, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3417, 16, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3418, 17, '2026-06-02 07:00:00', '2026-06-02 16:00:00', '2026-06-02', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3419, 4, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3420, 9, NULL, NULL, '2026-06-03', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-04 08:26:25', '2026-06-28 14:53:45', 0, 0, 0, 0),
(3421, 8, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3422, 7, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3423, 6, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3424, 12, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3425, 15, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3426, 16, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3427, 17, '2026-06-03 07:00:00', '2026-06-03 16:00:00', '2026-06-03', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0);
INSERT INTO `attendance_logs` (`id`, `user_id`, `check_in`, `check_out`, `work_date`, `shift_id`, `work_hours`, `source`, `check_in_ip`, `check_in_lat`, `check_in_lng`, `check_in_location_flag`, `check_out_ip`, `check_out_lat`, `check_out_lng`, `check_out_location_flag`, `note`, `created_at`, `updated_at`, `is_late`, `late_minutes`, `early_leave`, `early_leave_minutes`) VALUES
(3428, 4, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3429, 9, NULL, NULL, '2026-06-04', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', 'Xóa bản ghi', '2026-06-04 08:26:25', '2026-06-28 14:53:39', 0, 0, 0, 0),
(3430, 8, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3431, 7, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3432, 6, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3433, 12, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3434, 15, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3435, 16, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3436, 17, '2026-06-04 07:00:00', '2026-06-04 16:00:00', '2026-06-04', NULL, 9.00, '', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-04 08:26:25', '2026-06-04 08:26:25', 0, 0, 0, 0),
(3437, 2, '2026-06-29 15:09:52', '2026-06-29 15:10:03', '2026-06-29', NULL, 0.00, 'manual', NULL, NULL, NULL, 'unknown', NULL, NULL, NULL, 'unknown', NULL, '2026-06-29 15:09:52', '2026-06-29 15:10:03', 0, 0, 0, 0),
(3438, 4, '2026-06-29 16:15:01', '2026-06-29 16:15:06', '2026-06-29', NULL, 0.00, 'manual', '::1', 21.0613019, 105.8790674, 'outside', '::1', 21.0613019, 105.8790674, 'outside', 'Xóa bản ghi', '2026-06-29 15:15:06', '2026-06-29 16:15:06', 0, 0, 0, 0),
(3439, 18, '2026-06-29 16:03:56', '2026-06-29 16:04:14', '2026-06-29', NULL, 0.00, 'manual', '::1', NULL, NULL, 'no_gps', '::1', 21.0613173, 105.8790476, 'unknown', NULL, '2026-06-29 16:03:56', '2026-06-29 16:04:14', 0, 0, 0, 0),
(3451, 9, '2026-06-29 16:10:52', '2026-06-29 16:11:09', '2026-06-29', NULL, 0.00, 'manual', '::1', 21.0614890, 105.8794062, 'unknown', '::1', 21.0614890, 105.8794062, 'unknown', NULL, '2026-06-29 16:10:52', '2026-06-29 16:11:09', 0, 0, 0, 0),
(3452, 4, '2026-06-30 01:22:57', '2026-06-30 01:23:04', '2026-06-30', NULL, 0.00, 'manual', '116.96.44.133', 21.0613428, 105.8791623, 'outside', '116.96.44.133', 21.0613428, 105.8791623, 'outside', NULL, '2026-06-29 18:22:57', '2026-06-29 18:23:04', 0, 0, 0, 0),
(3453, 9, '2026-06-30 06:49:30', '2026-06-30 06:49:45', '2026-06-30', NULL, 0.00, 'manual', '27.68.137.55', 21.0617417, 105.8803330, 'outside', '27.68.137.55', 21.0617416, 105.8803330, 'outside', NULL, '2026-06-29 23:49:30', '2026-06-29 23:49:45', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu trước khi sửa' CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu sau khi sửa' CHECK (json_valid(`new_data`)),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử sửa/xóa toàn hệ thống';

--
-- Đang đổ dữ liệu cho bảng `audit_log`
--

INSERT INTO `audit_log` (`id`, `table_name`, `record_id`, `action`, `changed_by`, `changed_at`, `old_data`, `new_data`, `note`) VALUES
(1, 'delivery_notes', 6, 'delete', 1, '2026-03-11 14:44:41', '{\"id\":6,\"delivery_no\":\"DN-20260311-0001\",\"delivery_date\":\"2026-03-11\",\"sender_name\":null,\"sender_phone\":null,\"vehicle_plate\":null,\"driver_name\":null,\"driver_phone\":null,\"customer_id\":1,\"total_amount\":\"0\",\"status\":\"confirmed\",\"note\":\"\",\"created_by\":1,\"created_at\":\"2026-03-11 21:10:53\",\"updated_at\":\"2026-03-11 21:10:53\",\"items\":[{\"id\":6,\"delivery_note_id\":6,\"production_output_id\":4,\"product_code_id\":1,\"description\":\"hàng ốc vít\",\"unit\":\"cái\",\"quantity\":\"2500.00\",\"unit_price\":\"0\",\"total_price\":\"0\",\"note\":null}]}', NULL, 'Xóa biên bản DN-20260311-0001'),
(2, 'delivery_notes', 5, 'delete', 1, '2026-03-11 14:44:44', '{\"id\":5,\"delivery_no\":\"GH-20260311-001\",\"delivery_date\":\"2026-03-11\",\"sender_name\":null,\"sender_phone\":null,\"vehicle_plate\":null,\"driver_name\":null,\"driver_phone\":null,\"customer_id\":1,\"total_amount\":\"0\",\"status\":\"confirmed\",\"note\":null,\"created_by\":5,\"created_at\":\"2026-03-11 20:15:07\",\"updated_at\":\"2026-03-11 20:15:07\",\"items\":[{\"id\":5,\"delivery_note_id\":5,\"production_output_id\":4,\"product_code_id\":1,\"description\":\"hàng ốc vít\",\"unit\":\"cái\",\"quantity\":\"2000.00\",\"unit_price\":\"0\",\"total_price\":\"0\",\"note\":null}]}', NULL, 'Xóa biên bản GH-20260311-001'),
(3, 'delivery_notes', 4, 'delete', 1, '2026-03-11 14:44:48', '{\"id\":4,\"delivery_no\":\"GH-20260310-001\",\"delivery_date\":\"2026-03-10\",\"sender_name\":null,\"sender_phone\":null,\"vehicle_plate\":null,\"driver_name\":null,\"driver_phone\":null,\"customer_id\":1,\"total_amount\":\"0\",\"status\":\"confirmed\",\"note\":null,\"created_by\":1,\"created_at\":\"2026-03-11 00:38:22\",\"updated_at\":\"2026-03-11 00:38:22\",\"items\":[{\"id\":4,\"delivery_note_id\":4,\"production_output_id\":3,\"product_code_id\":1,\"description\":\"hàng ốc vít\",\"unit\":\"cái\",\"quantity\":\"100.00\",\"unit_price\":\"0\",\"total_price\":\"0\",\"note\":null}]}', NULL, 'Xóa biên bản GH-20260310-001'),
(4, 'production_outputs', 4, 'delete', 1, '2026-03-11 14:44:54', '{\"id\":4,\"output_no\":\"OUT-20260311-001\",\"output_date\":\"2026-03-11\",\"production_receipt_id\":3,\"product_code_id\":1,\"description\":null,\"quantity_completed\":\"5000.00\",\"quantity_defect\":\"1000.00\",\"quantity_delivered\":\"0.00\",\"note\":null,\"created_by\":5,\"created_at\":\"2026-03-11 20:13:23\",\"updated_at\":\"2026-03-11 21:44:44\"}', NULL, 'Xóa output OUT-20260311-001'),
(5, 'production_receipts', 4, 'delete', 1, '2026-03-11 14:44:59', '{\"id\":4,\"receipt_no\":\"PR-20260311-0001\",\"receipt_date\":\"2026-03-11\",\"warehouse_import_id\":2,\"product_code_id\":1,\"description\":null,\"quantity_received\":\"80000.00\",\"note\":\"\",\"created_by\":1,\"created_at\":\"2026-03-11 21:10:15\",\"updated_at\":\"2026-03-11 21:10:15\"}', NULL, 'Xóa phiếu nhận PR-20260311-0001'),
(6, 'production_receipts', 3, 'delete', 1, '2026-03-11 14:45:00', '{\"id\":3,\"receipt_no\":\"SX-20260311-001\",\"receipt_date\":\"2026-03-11\",\"warehouse_import_id\":1,\"product_code_id\":1,\"description\":null,\"quantity_received\":\"8000.00\",\"note\":null,\"created_by\":5,\"created_at\":\"2026-03-11 20:12:47\",\"updated_at\":\"2026-03-11 20:12:47\"}', NULL, 'Xóa phiếu nhận SX-20260311-001'),
(7, 'warehouse_imports', 2, 'delete', 1, '2026-03-11 14:45:22', '{\"id\":2,\"import_no\":\"WH-20260311-001\",\"import_date\":\"2026-03-11\",\"product_code_id\":1,\"description\":\"hàng ốc vít\",\"quantity\":\"100000.00\",\"quantity_sent\":\"0.00\",\"note\":null,\"status\":\"pending\",\"created_by\":2,\"created_at\":\"2026-03-11 20:30:01\",\"updated_at\":\"2026-03-11 21:44:59\",\"product_code\":\"SP-01\"}', NULL, 'Xóa phiếu nhập WH-20260311-001'),
(8, 'warehouse_imports', 3, 'delete', 1, '2026-03-11 15:02:44', '{\"id\":3,\"import_no\":\"WI-20260311-0001\",\"import_date\":\"2026-03-11\",\"product_code_id\":1,\"description\":null,\"quantity\":\"100000.00\",\"quantity_sent\":\"100000.00\",\"note\":\"\",\"status\":\"completed\",\"created_by\":1,\"created_at\":\"2026-03-11 21:57:14\",\"updated_at\":\"2026-03-11 21:57:30\",\"product_code\":\"SP-01\"}', NULL, 'Xóa phiếu nhập WI-20260311-0001'),
(9, 'production_outputs', 5, 'create', 1, '2026-03-11 15:07:17', NULL, '{\"output_no\":\"OUT-20260311-001\",\"output_date\":\"2026-03-11\",\"production_receipt_id\":6,\"quantity_completed\":50000,\"quantity_defect\":0}', 'Tao output OUT-20260311-001'),
(10, 'warehouse_imports', 5, 'delete', 1, '2026-04-29 07:31:44', '{\"id\":\"5\",\"import_no\":\"WI-20260429-0001\",\"import_date\":\"2026-04-29\",\"product_code_id\":\"1\",\"description\":null,\"quantity\":\"2000.00\",\"quantity_sent\":\"0.00\",\"note\":\"\",\"status\":\"pending\",\"created_by\":\"1\",\"created_at\":\"2026-04-29 14:31:26\",\"updated_at\":\"2026-04-29 14:31:26\",\"product_code\":\"123621\"}', NULL, 'Xóa phiếu nhập WI-20260429-0001');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `communes`
--

CREATE TABLE `communes` (
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_code` varchar(10) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `company_assets`
--

CREATE TABLE `company_assets` (
  `id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `asset_name` varchar(200) NOT NULL,
  `category` enum('computer','printer','furniture','machinery','vehicle','other') DEFAULT 'other',
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','assigned','maintenance','disposed') DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `company_ip_whitelist`
--

CREATE TABLE `company_ip_whitelist` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `label` varchar(100) DEFAULT NULL COMMENT 'Mô tả: WiFi văn phòng, mạng LAN...',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `company_location_config`
--

CREATE TABLE `company_location_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  `label` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `company_location_config`
--

INSERT INTO `company_location_config` (`id`, `config_key`, `config_value`, `label`) VALUES
(1, 'lat', '0.0', 'Vĩ độ công ty'),
(2, 'lng', '0.0', 'Kinh độ công ty'),
(3, 'radius_meters', '500', 'Bán kính cho phép (mét)'),
(4, 'gps_required', '0', 'Bắt buộc GPS (1=có, 0=không)');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cost_entries`
--

CREATE TABLE `cost_entries` (
  `id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `cost_type` enum('material','supplies','machinery','transport','other') NOT NULL DEFAULT 'material',
  `supplier_name` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,0) DEFAULT 0,
  `total_amount` decimal(15,0) DEFAULT 0,
  `invoice_no` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(30) DEFAULT NULL,
  `customer_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `customer_name`, `address`, `contact_person`, `phone`, `email`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'KH01', 'Công ty CP Đúc HSK', 'Thôn Chi Long, xã Yên Phong, Tỉnh Bắc Ninh', 'Phương', NULL, NULL, 1, 1, '2026-03-10 17:10:45', '2026-04-29 07:03:30'),
(2, 'KH02', 'CÔNG TY TNHH SẢN XUẤT THƯƠNG MẠI DỊCH VỤ KAIHATSU VIỆT NAM', 'Tổ 37,xã Đông Anh,Thành phố Hà Nội,Việt Nam', 'Chinh', NULL, NULL, 1, 2, '2026-04-29 07:04:19', '2026-04-29 07:04:19'),
(3, 'KH03', 'CÔNG TY TNHH TEXON SEMICONDUCTOR TECHNOLOGIES', 'Lô K-1-2, Khu công nghiệp Đại Đồng-Hoàn Sơn, Phường Từ Sơn, Tỉnh Bắc Ninh, Việt Nam', NULL, NULL, NULL, 1, 2, '2026-04-29 07:05:35', '2026-04-29 07:05:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_prices`
--

CREATE TABLE `customer_prices` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `effective_date` date NOT NULL DEFAULT '2025-01-01',
  `expired_date` date DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_prices`
--

INSERT INTO `customer_prices` (`id`, `customer_id`, `product_code_id`, `unit_price`, `effective_date`, `expired_date`, `note`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 4, 3690.00, '2026-06-28', NULL, NULL, 1, '2026-06-29 00:20:40', '2026-06-29 00:20:40'),
(3, 2, 5, 10000.00, '2026-06-30', NULL, NULL, 1, '2026-06-30 12:06:17', '2026-06-30 12:06:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `day_close_log`
--

CREATE TABLE `day_close_log` (
  `id` int(11) NOT NULL,
  `close_date` date NOT NULL,
  `close_type` enum('manual','auto') NOT NULL DEFAULT 'manual',
  `qty_completed_returned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `qty_defect_returned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `qty_pending_returned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `closed_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `day_close_log`
--

INSERT INTO `day_close_log` (`id`, `close_date`, `close_type`, `qty_completed_returned`, `qty_defect_returned`, `qty_pending_returned`, `closed_by`, `note`, `created_at`) VALUES
(1, '2026-03-11', 'manual', 0.00, 0.00, 80000.00, 1, NULL, '2026-03-11 14:25:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `debt_payments`
--

CREATE TABLE `debt_payments` (
  `id` int(11) NOT NULL,
  `debt_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,0) NOT NULL DEFAULT 0,
  `payment_method` enum('cash','transfer','other') DEFAULT 'transfer',
  `reference_no` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `debt_tracking`
--

CREATE TABLE `debt_tracking` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `paid_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `remaining_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `delivery_no` varchar(50) DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `warehouse_out_id` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum('draft','confirmed','invoiced') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `deliveries`
--

INSERT INTO `deliveries` (`id`, `delivery_no`, `delivery_date`, `customer_id`, `warehouse_out_id`, `total_amount`, `note`, `status`, `created_by`, `created_at`) VALUES
(1, 'DL-20260628-001', '2026-06-28', 1, 1, 0.00, NULL, 'confirmed', 2, '2026-06-28 22:56:03'),
(2, 'DL-20260628-002', '2026-06-28', 1, 1, 0.00, NULL, 'confirmed', 2, '2026-06-28 23:20:43'),
(3, 'DL-20260628-003', '2026-06-28', 1, 1, 0.00, NULL, 'confirmed', 2, '2026-06-29 00:25:20'),
(6, 'DL-20260630-003', '2026-06-30', 1, NULL, 0.00, NULL, 'confirmed', 18, '2026-06-30 12:57:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `delivery_items`
--

CREATE TABLE `delivery_items` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_price` decimal(15,2) DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `delivery_items`
--

INSERT INTO `delivery_items` (`id`, `delivery_id`, `product_code_id`, `quantity`, `unit_price`, `total_price`, `note`) VALUES
(1, 1, 2, 100.000, 0.00, 0.00, NULL),
(2, 2, 2, 100.000, 0.00, 0.00, NULL),
(3, 3, 2, 100.000, 0.00, 0.00, NULL),
(6, 6, 4, 50.000, 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `delivery_notes`
--

CREATE TABLE `delivery_notes` (
  `id` int(11) NOT NULL,
  `delivery_no` varchar(30) NOT NULL,
  `delivery_date` date NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `sender_phone` varchar(20) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `status` enum('draft','confirmed','invoiced') DEFAULT 'draft',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `receiver_name` varchar(100) DEFAULT NULL COMMENT 'Người nhận hàng',
  `receiver_phone` varchar(20) DEFAULT NULL COMMENT 'SĐT người nhận'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `delivery_notes`
--

INSERT INTO `delivery_notes` (`id`, `delivery_no`, `delivery_date`, `sender_name`, `sender_phone`, `vehicle_plate`, `driver_name`, `driver_phone`, `customer_id`, `total_amount`, `status`, `note`, `created_by`, `created_at`, `updated_at`, `receiver_name`, `receiver_phone`) VALUES
(7, 'DN-20260311-0001', '2026-03-11', NULL, NULL, NULL, NULL, NULL, 1, 0, 'confirmed', '', 1, '2026-03-11 15:07:34', '2026-03-11 15:07:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `delivery_note_items`
--

CREATE TABLE `delivery_note_items` (
  `id` int(11) NOT NULL,
  `delivery_note_id` int(11) NOT NULL,
  `production_output_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,0) NOT NULL DEFAULT 0,
  `total_price` decimal(15,0) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `delivery_note_items`
--

INSERT INTO `delivery_note_items` (`id`, `delivery_note_id`, `production_output_id`, `product_code_id`, `description`, `unit`, `quantity`, `unit_price`, `total_price`, `note`) VALUES
(7, 7, 5, 1, 'hàng ốc vít', 'cái', 5000.00, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Ban Giám đốc', '2026-03-10 06:57:59'),
(2, 'Kế toán - Tài chính', '2026-03-10 06:57:59'),
(3, 'Kho vận', '2026-03-10 06:57:59'),
(4, 'Sản xuất', '2026-03-10 06:57:59'),
(5, 'Kinh doanh', '2026-03-10 06:57:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `districts`
--

CREATE TABLE `districts` (
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `province_code` varchar(10) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `document_sequences`
--

CREATE TABLE `document_sequences` (
  `id` int(11) NOT NULL,
  `doc_type` varchar(10) NOT NULL,
  `doc_date` date NOT NULL,
  `last_seq` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `document_sequences`
--

INSERT INTO `document_sequences` (`id`, `doc_type`, `doc_date`, `last_seq`) VALUES
(1, 'WH', '2026-03-10', 1),
(6, 'SX', '2026-03-10', 2),
(10, 'OUT', '2026-03-10', 3),
(17, 'GH', '2026-03-10', 1),
(18, 'SX', '2026-03-11', 1),
(19, 'OUT', '2026-03-11', 1),
(20, 'GH', '2026-03-11', 1),
(21, 'WH', '2026-03-11', 1),
(23, 'WI', '2026-06-28', 1),
(24, 'WO', '2026-06-28', 1),
(25, 'DL', '2026-06-28', 3),
(29, 'WI', '2026-06-30', 2),
(30, 'WO', '2026-06-30', 2),
(31, 'DL', '2026-06-30', 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employee_profiles`
--

CREATE TABLE `employee_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gender` enum('male','female','other') NOT NULL DEFAULT 'male',
  `date_of_birth` date DEFAULT NULL,
  `ethnicity` varchar(50) DEFAULT 'Kinh',
  `marital_status` enum('single','married','divorced','widowed') DEFAULT 'single',
  `mobile_phone` varchar(20) DEFAULT NULL,
  `permanent_province` varchar(10) DEFAULT NULL,
  `permanent_district_text` varchar(150) DEFAULT NULL,
  `permanent_commune_text` varchar(150) DEFAULT NULL,
  `permanent_hamlet` varchar(200) DEFAULT NULL,
  `same_as_permanent` tinyint(1) DEFAULT 0,
  `temp_province` varchar(10) DEFAULT NULL,
  `temp_district_text` varchar(150) DEFAULT NULL,
  `temp_commune_text` varchar(150) DEFAULT NULL,
  `temp_hamlet` varchar(200) DEFAULT NULL,
  `identity_no` varchar(20) DEFAULT NULL,
  `identity_issue_date` date DEFAULT NULL,
  `identity_issue_place` varchar(200) DEFAULT NULL,
  `social_book_no` varchar(30) DEFAULT NULL,
  `personal_tax_code` varchar(20) DEFAULT NULL,
  `bank_account` varchar(30) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(200) DEFAULT NULL,
  `dependants` tinyint(4) DEFAULT 0,
  `has_social_insurance` tinyint(1) NOT NULL DEFAULT 0,
  `insurance_from` date DEFAULT NULL,
  `date_joined` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `annual_leave_total` tinyint(4) NOT NULL DEFAULT 9
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `employee_profiles`
--

INSERT INTO `employee_profiles` (`id`, `user_id`, `gender`, `date_of_birth`, `ethnicity`, `marital_status`, `mobile_phone`, `permanent_province`, `permanent_district_text`, `permanent_commune_text`, `permanent_hamlet`, `same_as_permanent`, `temp_province`, `temp_district_text`, `temp_commune_text`, `temp_hamlet`, `identity_no`, `identity_issue_date`, `identity_issue_place`, `social_book_no`, `personal_tax_code`, `bank_account`, `bank_name`, `bank_branch`, `dependants`, `has_social_insurance`, `insurance_from`, `date_joined`, `created_at`, `updated_at`, `annual_leave_total`) VALUES
(1, 2, 'male', NULL, 'Kinh', 'single', '', '77', 'dd', 'dd', 'dd', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-03-10 10:51:41', '2026-03-10 10:51:41', 9),
(2, 1, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-03-10 14:16:19', '2026-03-10 14:16:19', 9),
(3, 6, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 1, NULL, '2026-02-01', '2026-03-10 14:21:55', '2026-06-02 08:01:58', 9),
(4, 7, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 1, NULL, NULL, '2026-03-10 15:34:57', '2026-06-02 08:01:46', 9),
(5, 8, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 1, NULL, '2026-03-01', '2026-03-11 03:31:13', '2026-05-08 01:58:43', 9),
(6, 5, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 1, NULL, NULL, '2026-04-29 07:52:43', '2026-05-08 01:58:37', 9),
(7, 9, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 1, NULL, NULL, '2026-04-29 08:00:20', '2026-05-08 01:58:24', 9),
(8, 4, 'female', '2004-08-14', 'Kinh', 'single', '0359258401', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '21231', '', '', '', '', 0, 1, NULL, '2026-03-09', '2026-04-29 08:01:40', '2026-05-27 02:09:47', 9),
(9, 10, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-04-29 08:03:36', '2026-04-29 08:03:36', 9),
(10, 11, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-04-29 08:05:20', '2026-04-29 08:05:20', 9),
(11, 12, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-04-29 08:06:50', '2026-04-29 08:06:50', 9),
(12, 13, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-04-29 08:08:02', '2026-04-29 08:08:02', 9),
(13, 15, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, '2026-05-04', '2026-05-08 09:17:51', '2026-06-03 07:01:51', 9),
(14, 14, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, NULL, '2026-05-08 09:18:58', '2026-05-08 09:18:58', 9),
(15, 17, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, '2026-05-04', '2026-06-02 08:10:13', '2026-06-04 06:20:37', 9),
(16, 16, 'male', NULL, 'Kinh', 'single', '', NULL, '', '', '', 0, NULL, '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, NULL, '2026-05-25', '2026-06-03 07:03:51', '2026-06-03 07:03:51', 9);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employee_salaries`
--

CREATE TABLE `employee_salaries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `custom_name` varchar(200) DEFAULT NULL,
  `custom_name_en` varchar(200) DEFAULT NULL,
  `component_type` enum('earning','deduction','bonus') NOT NULL DEFAULT 'earning',
  `amount` decimal(15,0) DEFAULT 0,
  `insurance_amount` bigint(20) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `note` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `employee_salaries`
--

INSERT INTO `employee_salaries` (`id`, `user_id`, `component_id`, `custom_name`, `custom_name_en`, `component_type`, `amount`, `insurance_amount`, `sort_order`, `is_active`, `note`, `effective_date`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5000000, 0, 1, 0, '', '2026-03-10', 1, NULL, '2026-03-10 14:16:13', '2026-04-29 07:47:31'),
(2, 6, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-03-10 14:21:54', '2026-05-08 02:58:12'),
(3, 7, 1, 'Lương cơ bản', 'Basic salary', 'earning', 6000000, 0, 1, 0, '', '2026-03-01', 1, NULL, '2026-03-10 15:34:56', '2026-04-29 07:57:07'),
(4, 8, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-03-11 03:30:48', '2026-05-08 02:54:54'),
(5, 8, 8, 'Phụ cấp trách nhiệm', 'Responsibility allowance', 'earning', 5000000, 0, 2, 0, '', '2026-03-11', 1, NULL, '2026-03-11 03:31:02', '2026-03-11 03:58:38'),
(6, 8, 3, 'Trợ cấp trang phục', 'Clothes allowance', 'earning', 400000, 0, 3, 0, '', '2026-03-11', 1, NULL, '2026-03-11 03:31:12', '2026-03-11 03:58:35'),
(7, 7, 3, 'Trợ cấp trang phục', 'Clothes allowance', 'earning', 5000000, 0, 2, 0, '', '2026-03-11', 1, NULL, '2026-03-11 03:31:31', '2026-03-11 03:58:08'),
(8, 7, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 3000000, 0, 3, 0, '', '2026-03-11', 1, NULL, '2026-03-11 04:07:01', '2026-03-11 04:50:51'),
(9, 7, NULL, 'Chuyên Cần', '', 'earning', 500000, 0, 4, 0, '', '2026-03-11', 1, NULL, '2026-03-11 04:07:15', '2026-03-11 04:45:38'),
(10, 7, 11, 'Chuyên Cần', 'Attendance Bonus', 'bonus', 500000, 0, 99, 0, NULL, '2026-03-11', 1, NULL, '2026-03-11 04:39:10', '2026-04-29 07:57:09'),
(11, 6, 11, 'Chuyên Cần', 'Attendance Bonus', 'bonus', 500000, 0, 2, 0, '', '2026-03-11', 1, NULL, '2026-03-11 04:46:01', '2026-04-29 07:53:33'),
(12, 7, 3, 'Trợ cấp trang phục', 'Clothes allowance', 'earning', 5000000, 0, 100, 0, '', '2026-03-10', 1, NULL, '2026-03-11 04:53:17', '2026-04-29 07:57:11'),
(13, 6, 2, 'Trợ cấp ăn uống', 'Meal allowance', 'earning', 1040000, 0, 3, 0, '', '2026-03-10', 1, NULL, '2026-03-11 05:06:07', '2026-04-29 07:53:36'),
(14, 8, 11, 'Chuyên Cần', 'Attendance Bonus', 'bonus', 1000000, 0, 4, 0, '', '2026-03-11', 1, NULL, '2026-03-11 13:39:24', '2026-04-29 07:55:26'),
(15, 7, 2, 'Trợ cấp ăn uống', 'Meal allowance', 'earning', 1040000, 0, 101, 0, '', '2026-03-11', 1, NULL, '2026-03-11 15:29:47', '2026-04-29 07:57:14'),
(16, 1, 2, 'Trợ cấp ăn uống', 'Meal allowance', 'earning', 520000, 0, 2, 0, '', '2026-04-01', 1, NULL, '2026-04-29 07:41:38', '2026-04-29 07:47:29'),
(17, 1, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 3, 0, '', '2026-04-29', 1, NULL, '2026-04-29 07:42:03', '2026-04-29 07:47:27'),
(18, 1, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 470600, 0, 4, 0, '', '2026-04-01', 1, NULL, '2026-04-29 07:43:44', '2026-04-29 07:47:25'),
(19, 5, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:52:02', '2026-05-08 02:54:21'),
(20, 5, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-29', 1, NULL, '2026-04-29 07:52:22', '2026-05-12 02:29:41'),
(21, 5, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:52:38', '2026-05-08 02:54:29'),
(22, 6, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 4, 1, '', '2026-04-01', 1, NULL, '2026-04-29 07:53:49', '2026-05-12 02:33:09'),
(23, 6, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 460600, 0, 5, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:54:10', '2026-05-08 02:58:28'),
(24, 6, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 500000, 0, 6, 1, '', '2026-04-29', 1, NULL, '2026-04-29 07:54:23', '2026-04-29 07:54:23'),
(25, 8, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 5, 1, '', '2026-04-01', 1, NULL, '2026-04-29 07:55:36', '2026-05-12 02:31:50'),
(26, 8, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 6, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:56:04', '2026-05-08 02:55:02'),
(27, 7, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 102, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:57:25', '2026-05-08 02:57:08'),
(28, 7, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 103, 1, '', '2026-04-01', 1, NULL, '2026-04-29 07:57:34', '2026-05-12 02:32:55'),
(29, 7, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 460600, 0, 104, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:57:51', '2026-05-08 02:57:27'),
(30, 7, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 500000, 0, 105, 1, '', '2026-04-01', 1, NULL, '2026-04-29 07:58:14', '2026-04-29 07:58:14'),
(31, 9, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 07:59:45', '2026-05-08 02:53:31'),
(32, 9, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-01', 1, NULL, '2026-04-29 07:59:57', '2026-05-12 02:28:46'),
(33, 9, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:00:16', '2026-05-08 02:53:52'),
(34, 4, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:00:41', '2026-05-08 02:51:55'),
(35, 4, 3, 'Trợ cấp trang phục', 'Clothes allowance', 'earning', 780000, 0, 2, 0, '', '2026-04-01', 1, NULL, '2026-04-29 08:00:54', '2026-05-09 04:25:06'),
(36, 4, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 470600, 0, 3, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:01:10', '2026-04-29 08:01:10'),
(37, 4, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 690000, 0, 4, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:01:25', '2026-05-08 02:52:35'),
(38, 10, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:03:08', '2026-05-08 02:56:41'),
(39, 10, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:03:18', '2026-04-29 08:03:18'),
(40, 10, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:03:29', '2026-05-08 02:56:49'),
(41, 11, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:04:35', '2026-05-08 02:56:16'),
(42, 11, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:04:41', '2026-04-29 08:04:47'),
(43, 11, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:05:14', '2026-05-08 02:56:23'),
(44, 12, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:06:30', '2026-05-08 02:55:29'),
(45, 12, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:06:38', '2026-05-12 02:32:34'),
(46, 12, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 460600, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-04-29 08:06:47', '2026-06-02 08:08:19'),
(47, 13, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5000000, 0, 1, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:07:43', '2026-04-29 08:07:43'),
(48, 13, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:07:51', '2026-05-12 02:33:22'),
(49, 13, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 470600, 0, 3, 1, '', '2026-04-01', 1, NULL, '2026-04-29 08:08:00', '2026-04-29 08:08:00'),
(50, 15, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:16:25', '2026-05-08 09:16:25'),
(51, 15, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 390000, 0, 2, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:16:41', '2026-05-12 02:32:21'),
(52, 15, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:17:03', '2026-05-08 09:17:03'),
(53, 14, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:18:28', '2026-05-08 09:18:28'),
(54, 14, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:18:40', '2026-05-08 09:18:40'),
(55, 14, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-05-08 09:18:55', '2026-05-12 02:30:55'),
(56, 4, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 5, 1, '', '2026-05-01', 1, NULL, '2026-05-09 04:24:55', '2026-05-12 02:28:28'),
(57, 12, 4, 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 500000, 0, 4, 1, '', '2026-05-01', 1, NULL, '2026-06-02 08:08:39', '2026-06-02 08:08:39'),
(58, 17, 1, 'Lương cơ bản', 'Basic salary', 'earning', 5310000, 0, 1, 1, '', '2026-05-01', 1, NULL, '2026-06-02 08:09:13', '2026-06-02 08:09:59'),
(59, 17, 7, 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 780000, 0, 2, 1, '', '2026-05-01', 1, NULL, '2026-06-02 08:09:24', '2026-06-02 08:09:24'),
(60, 17, 5, 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 390000, 0, 3, 1, '', '2026-05-01', 1, NULL, '2026-06-02 08:09:51', '2026-06-02 08:09:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employee_shifts`
--

CREATE TABLE `employee_shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `employee_shifts`
--

INSERT INTO `employee_shifts` (`id`, `user_id`, `shift_id`, `effective_date`, `end_date`, `created_by`, `created_at`) VALUES
(1, 3, 1, '2026-03-10', NULL, 1, '2026-03-10 09:56:59'),
(2, 1, 1, '2026-03-10', '2026-03-30', 1, '2026-03-10 09:56:59'),
(3, 2, 1, '2026-03-10', NULL, 1, '2026-03-10 09:56:59'),
(4, 4, 1, '2026-03-10', '2026-03-30', 1, '2026-03-10 09:56:59'),
(5, 5, 1, '2026-03-10', '2026-03-30', 1, '2026-03-10 09:56:59'),
(6, 6, 1, '2026-03-10', '2026-03-30', 1, '2026-03-10 09:56:59'),
(7, 7, 1, '2026-03-10', '2026-03-30', 1, '2026-03-10 09:56:59'),
(35, 14, 1, '2026-05-27', '2026-04-30', 1, '2026-05-27 08:33:58'),
(37, 15, 1, '2026-05-27', '2026-04-30', 1, '2026-05-27 08:33:58'),
(38, 16, 1, '2026-05-27', '2026-04-30', 1, '2026-05-27 08:33:58'),
(43, 1, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(44, 4, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(45, 9, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(46, 5, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(47, 17, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(48, 14, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(49, 8, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(50, 15, 1, '2026-05-01', '2026-05-01', 1, '2026-06-04 02:05:25'),
(51, 16, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(52, 12, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(53, 7, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(54, 6, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(55, 13, 1, '2026-05-01', NULL, 1, '2026-06-04 02:05:25'),
(56, 15, 1, '2026-05-02', NULL, 1, '2026-06-04 03:23:27'),
(57, 18, 1, '2026-06-29', NULL, 18, '2026-06-29 15:14:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ethnicities`
--

CREATE TABLE `ethnicities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ethnicities`
--

INSERT INTO `ethnicities` (`id`, `name`) VALUES
(12, 'Ba Na'),
(48, 'Bố Y'),
(53, 'Brâu'),
(24, 'Bru - Vân Kiều'),
(14, 'Chăm'),
(31, 'Chơ Ro'),
(34, 'Chu Ru'),
(44, 'Chứt'),
(29, 'Co'),
(15, 'Cơ Ho'),
(47, 'Cờ Lao'),
(25, 'Cơ Tu'),
(49, 'Cống'),
(9, 'Dao'),
(11, 'Ê Đê'),
(10, 'Gia Rai'),
(26, 'Giáy'),
(30, 'Giẻ Triêng'),
(33, 'Hà Nhì'),
(8, 'Hoa'),
(18, 'Hrê'),
(37, 'Kháng'),
(5, 'Khmer'),
(23, 'Khơ Mú'),
(1, 'Kinh'),
(36, 'La Chí'),
(40, 'La Ha'),
(39, 'La Hủ'),
(35, 'Lào'),
(45, 'Lô Lô'),
(42, 'Lự'),
(28, 'Mạ'),
(46, 'Mảng'),
(20, 'Mnông'),
(6, 'Mông'),
(4, 'Mường'),
(43, 'Ngái'),
(7, 'Nùng'),
(54, 'Ơ Đu'),
(41, 'Pà Thẻn'),
(38, 'Phù Lá'),
(51, 'Pu Péo'),
(19, 'Ra Glai'),
(52, 'Rơ Măm'),
(13, 'Sán Chay'),
(17, 'Sán Dìu'),
(50, 'Si La'),
(27, 'Tà Ôi'),
(2, 'Tày'),
(3, 'Thái'),
(21, 'Thổ'),
(32, 'Xinh Mun'),
(16, 'Xơ Đăng'),
(22, 'Xtiêng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`, `is_active`) VALUES
(1, 'Tiền điện', 1),
(2, 'Tiền nước', 1),
(3, 'Internet', 1),
(4, 'Điện thoại', 1),
(5, 'Thuê văn phòng', 1),
(6, 'Chuyển phát nhanh', 1),
(7, 'Văn phòng phẩm', 1),
(8, 'Vệ sinh', 1),
(9, 'Mua sắm máy móc / Thiết bị', 1),
(10, 'Mua sắm vật tư tiêu hao', 1),
(11, 'Khác', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `expense_payments`
--

CREATE TABLE `expense_payments` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer') DEFAULT 'cash',
  `paid_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `expense_requests`
--

CREATE TABLE `expense_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expense_date` date NOT NULL,
  `purpose` text NOT NULL,
  `has_invoice` tinyint(1) DEFAULT 0,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_company` varchar(200) DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer') DEFAULT 'cash',
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `expense_requests`
--

INSERT INTO `expense_requests` (`id`, `request_no`, `category_id`, `amount`, `expense_date`, `purpose`, `has_invoice`, `invoice_no`, `invoice_date`, `invoice_company`, `payment_method`, `status`, `requested_by`, `approved_by`, `approved_at`, `reject_reason`, `note`, `created_at`, `updated_at`) VALUES
(1, 'EXP-20260629-001', 7, 5000000.00, '2026-06-29', 'sử dụng', 0, NULL, NULL, NULL, 'cash', 'approved', 18, 18, '2026-06-29 22:58:01', NULL, NULL, '2026-06-29 22:57:53', '2026-06-29 22:58:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `year` year(4) NOT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_date`, `holiday_name`, `year`, `created_by`) VALUES
(1, '2026-01-01', 'Tết Dương lịch', '2026', NULL),
(2, '2026-01-28', 'Tết Nguyên Đán (28/1)', '2026', NULL),
(3, '2026-01-29', 'Tết Nguyên Đán (29/1)', '2026', NULL),
(4, '2026-01-30', 'Tết Nguyên Đán (30/1)', '2026', NULL),
(5, '2026-01-31', 'Tết Nguyên Đán (31/1)', '2026', NULL),
(6, '2026-02-01', 'Tết Nguyên Đán (1/2)', '2026', NULL),
(7, '2026-02-02', 'Tết Nguyên Đán (2/2)', '2026', NULL),
(9, '2026-04-30', 'Ngày Giải phóng miền Nam', '2026', NULL),
(10, '2026-05-01', 'Quốc tế Lao động', '2026', NULL),
(11, '2026-09-02', 'Quốc khánh', '2026', NULL),
(12, '2026-09-03', 'Quốc khánh (bù)', '2026', NULL),
(13, '2025-01-01', 'Tết Dương lịch', '2025', NULL),
(14, '2025-01-27', 'Nghỉ Tết Nguyên Đán (27 tháng Chạp)', '2025', NULL),
(15, '2025-01-28', 'Nghỉ Tết Nguyên Đán (28 tháng Chạp)', '2025', NULL),
(16, '2025-01-29', 'Nghỉ Tết Nguyên Đán (29 tháng Chạp)', '2025', NULL),
(17, '2025-01-30', 'Nghỉ Tết Nguyên Đán (Mùng 1)', '2025', NULL),
(18, '2025-01-31', 'Nghỉ Tết Nguyên Đán (Mùng 2)', '2025', NULL),
(19, '2025-02-01', 'Nghỉ Tết Nguyên Đán (Mùng 3)', '2025', NULL),
(20, '2025-04-07', 'Giỗ Tổ Hùng Vương', '2025', NULL),
(21, '2025-04-30', 'Ngày Giải phóng miền Nam', '2025', NULL),
(22, '2025-05-01', 'Ngày Quốc tế Lao động', '2025', NULL),
(23, '2025-09-01', 'Nghỉ bù Quốc khánh', '2025', NULL),
(24, '2025-09-02', 'Ngày Quốc khánh', '2025', NULL),
(25, '2026-02-17', 'Nghỉ Tết Nguyên Đán (26 tháng Chạp)', '2026', NULL),
(26, '2026-02-18', 'Nghỉ Tết Nguyên Đán (27 tháng Chạp)', '2026', NULL),
(27, '2026-02-19', 'Nghỉ Tết Nguyên Đán (28 tháng Chạp)', '2026', NULL),
(28, '2026-02-20', 'Nghỉ Tết Nguyên Đán (Mùng 1)', '2026', NULL),
(29, '2026-02-21', 'Nghỉ Tết Nguyên Đán (Mùng 2)', '2026', NULL),
(30, '2026-02-22', 'Nghỉ Tết Nguyên Đán (Mùng 3)', '2026', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `subtotal` decimal(15,0) NOT NULL DEFAULT 0,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,0) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `status` enum('draft','unpaid','partial','paid','cancelled') DEFAULT 'unpaid',
  `created_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `invoice_date`, `due_date`, `customer_id`, `total_amount`, `subtotal`, `vat_rate`, `vat_amount`, `note`, `delivery_id`, `status`, `created_by`, `confirmed_by`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(28, 'INV-DRAFT-3', NULL, NULL, 1, 0, 0, 0.00, 0, 'Tự động tạo từ phiếu giao hàng', 3, 'draft', 18, NULL, NULL, '2026-06-30 05:53:41', '2026-06-30 05:53:41'),
(29, 'INV-DRAFT-6', NULL, NULL, 1, 184500, 184500, 0.00, 0, 'Tự động tạo từ phiếu giao hàng', 6, 'draft', 18, NULL, NULL, '2026-06-30 05:57:12', '2026-06-30 05:57:12');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `invoices_v`
-- (See below for the actual view)
--
CREATE TABLE `invoices_v` (
`id` int(11)
,`invoice_no` varchar(30)
,`invoice_date` date
,`due_date` date
,`customer_id` int(11)
,`total_amount` decimal(15,0)
,`subtotal` decimal(15,0)
,`vat_rate` decimal(5,2)
,`vat_amount` decimal(15,0)
,`note` text
,`delivery_id` int(11)
,`status` enum('draft','unpaid','partial','paid','cancelled')
,`created_by` int(11)
,`confirmed_by` int(11)
,`confirmed_at` timestamp
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoice_delivery_notes`
--

CREATE TABLE `invoice_delivery_notes` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `delivery_note_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `delivery_note_id` int(11) NOT NULL,
  `delivery_note_item_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,0) NOT NULL DEFAULT 0,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `delivery_note_id`, `delivery_note_item_id`, `product_code_id`, `description`, `unit`, `quantity`, `unit_price`, `total_price`) VALUES
(8, 28, 0, 0, 2, 'Phí gia công mài phun cát cho sản phẩm nhôm mã 122987', 'cái', 100.00, 0, 0.00),
(9, 29, 0, 0, 4, 'PHÍ DỊCH VỤ GIAO NHẬN HÀNG HOÁ', 'chiếc', 50.00, 3690, 184500.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inv_exports`
--

CREATE TABLE `inv_exports` (
  `id` int(11) NOT NULL,
  `export_no` varchar(30) NOT NULL,
  `item_id` int(11) NOT NULL,
  `export_date` date NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `requested_by_name` varchar(150) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inv_exports`
--

INSERT INTO `inv_exports` (`id`, `export_no`, `item_id`, `export_date`, `quantity`, `purpose`, `department`, `requested_by_name`, `approved_by`, `note`, `created_by`, `created_at`) VALUES
(1, 'EXP-20260630-001', 1, '2026-06-30', 400.00, 'bán hàng', NULL, NULL, NULL, NULL, 18, '2026-06-29 17:12:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inv_imports`
--

CREATE TABLE `inv_imports` (
  `id` int(11) NOT NULL,
  `import_no` varchar(30) NOT NULL,
  `item_id` int(11) NOT NULL,
  `import_date` date NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price` * (1 + `vat_percent` / 100)) STORED,
  `invoice_no` varchar(100) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inv_imports`
--

INSERT INTO `inv_imports` (`id`, `import_no`, `item_id`, `import_date`, `quantity`, `unit_price`, `vat_percent`, `invoice_no`, `supplier`, `payment_status`, `note`, `created_by`, `created_at`) VALUES
(1, 'IMP-20260629-001', 1, '2026-06-29', 500.00, 10000.00, 8.00, '321', 'bán hoá chất', 'unpaid', NULL, 18, '2026-06-29 17:11:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inv_items`
--

CREATE TABLE `inv_items` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('consumable','stationery','equipment','machinery','other') NOT NULL DEFAULT 'other',
  `unit` varchar(50) NOT NULL DEFAULT 'Cái',
  `min_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inv_items`
--

INSERT INTO `inv_items` (`id`, `item_code`, `item_name`, `category`, `unit`, `min_stock`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'N20', 'Hoá Chất N2O', 'consumable', 'Lit', 100.00, NULL, 1, '2026-06-29 17:10:54', '2026-06-29 17:10:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kpi_assignments`
--

CREATE TABLE `kpi_assignments` (
  `id` int(11) NOT NULL,
  `assign_date` date NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'nhân viên sản xuất',
  `manager_id` int(11) NOT NULL COMMENT 'quản lý phân bổ',
  `kpi_target` int(11) NOT NULL DEFAULT 0 COMMENT 'số SP mục tiêu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `over_bonus_pct` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '% thưởng cho mỗi % vượt KPI (vd: 50 = mỗi 1% vượt được thưởng 0.5% lương ngày)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `kpi_assignments`
--

INSERT INTO `kpi_assignments` (`id`, `assign_date`, `user_id`, `manager_id`, `kpi_target`, `created_at`, `updated_at`, `over_bonus_pct`) VALUES
(7, '2026-03-11', 6, 4, 600, '2026-03-11 13:16:43', '2026-03-11 13:16:43', 0.00),
(8, '2026-03-11', 8, 4, 600, '2026-03-11 13:16:43', '2026-03-11 13:16:43', 0.00),
(9, '2026-03-11', 7, 4, 500, '2026-03-11 13:16:43', '2026-03-11 13:16:43', 0.00),
(10, '2026-03-18', 6, 1, 400, '2026-03-18 09:21:41', '2026-03-18 09:21:41', 0.00),
(11, '2026-04-29', 6, 1, 200, '2026-04-29 07:36:24', '2026-04-29 07:36:24', 0.00),
(12, '2026-05-04', 9, 1, 600, '2026-05-04 03:45:57', '2026-05-04 03:45:57', 0.00),
(13, '2026-05-04', 5, 1, 600, '2026-05-04 03:48:00', '2026-05-04 03:48:00', 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kpi_results`
--

CREATE TABLE `kpi_results` (
  `id` int(11) NOT NULL,
  `kpi_assignment_id` int(11) NOT NULL,
  `actual_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'SP thực tế',
  `salary_per_day` decimal(15,0) DEFAULT 0 COMMENT 'lương ngày đầy đủ',
  `salary_actual` decimal(15,0) DEFAULT 0 COMMENT 'lương ngày thực tế sau KPI',
  `is_deducted` tinyint(1) DEFAULT 0 COMMENT '1=trừ lương, 0=đủ ngày công',
  `reason` text DEFAULT NULL COMMENT 'lý do không trừ lương',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `kpi_results`
--

INSERT INTO `kpi_results` (`id`, `kpi_assignment_id`, `actual_qty`, `salary_per_day`, `salary_actual`, `is_deducted`, `reason`, `confirmed_by`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(32, 7, 650, 232308, 251667, 0, '', 1, '2026-03-11 15:46:04', '2026-03-11 13:17:34', '2026-03-11 15:46:04'),
(33, 8, 750, 192308, 240385, 0, '', 1, '2026-03-11 15:46:08', '2026-03-11 13:17:34', '2026-03-11 15:46:08'),
(34, 9, 250, 463077, 231539, 1, '', 1, '2026-03-11 15:46:12', '2026-03-11 13:17:34', '2026-03-11 15:46:12'),
(35, 10, 0, 232308, 232308, 0, '', 1, '2026-03-18 09:21:57', '2026-03-18 09:21:57', '2026-03-18 09:21:57'),
(36, 11, 300, 232308, 232308, 0, '', 1, '2026-04-29 07:36:38', '2026-04-29 07:36:38', '2026-04-29 07:36:38'),
(40, 12, 0, 249231, 249231, 0, '', 1, '2026-05-27 08:40:46', '2026-05-27 08:40:46', '2026-05-27 08:40:46'),
(41, 13, 0, 249231, 249231, 0, '', 1, '2026-05-27 08:40:46', '2026-05-27 08:40:46', '2026-05-27 08:40:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','unpaid','other') NOT NULL DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(4,1) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `approved_by`, `approved_at`, `reject_reason`, `created_at`) VALUES
(1, 6, 'annual', '2026-03-10', '2026-03-11', 2.0, 'Em xin nghỉ phép', 'approved', 1, '2026-03-10 17:19:49', '', '2026-03-10 09:59:01'),
(2, 6, 'annual', '2026-03-11', '2026-03-11', 1.0, 'có công việc gia đình', 'approved', 5, '2026-03-10 20:17:52', '', '2026-03-10 13:17:15'),
(3, 6, 'annual', '2026-03-21', '2026-03-21', 1.0, 'Thăm anh Dũng', 'rejected', 1, '2026-05-02 12:32:45', 'ko đồng ý', '2026-03-20 04:58:35'),
(4, 9, 'annual', '2026-06-30', '2026-06-30', 1.0, 'Ốm', 'pending', NULL, NULL, NULL, '2026-06-29 23:50:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
(1, 1, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 10/03/2026 đến 11/03/2026', 'leave_request', 0, 0, '2026-03-10 09:59:01'),
(2, 3, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 10/03/2026 đến 11/03/2026', 'leave_request', 1, 0, '2026-03-10 09:59:01'),
(3, 5, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 10/03/2026 đến 11/03/2026', 'leave_request', 2, 0, '2026-03-10 09:59:01'),
(4, 6, 'Kết quả đơn nghỉ phép', '✅ Đơn xin nghỉ phép của bạn đã được duyệt.', 'leave_request', 1, 0, '2026-03-10 10:19:49'),
(5, 1, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 11/03/2026 đến 11/03/2026', 'leave_request', 0, 0, '2026-03-10 13:17:15'),
(6, 3, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 11/03/2026 đến 11/03/2026', 'leave_request', 5, 0, '2026-03-10 13:17:15'),
(7, 5, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 11/03/2026 đến 11/03/2026', 'leave_request', 6, 0, '2026-03-10 13:17:15'),
(8, 6, 'Kết quả đơn nghỉ phép', '✅ Đơn xin nghỉ phép của bạn đã được duyệt.', 'leave_request', 2, 0, '2026-03-10 13:17:52'),
(9, 1, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 21/03/2026 đến 21/03/2026', 'leave_request', 3, 0, '2026-03-20 04:58:35'),
(10, 3, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 21/03/2026 đến 21/03/2026', 'leave_request', 9, 0, '2026-03-20 04:58:35'),
(11, 5, 'Đơn xin nghỉ phép mới', 'Nguyễn Thị Lan xin nghỉ từ 21/03/2026 đến 21/03/2026', 'leave_request', 10, 0, '2026-03-20 04:58:35'),
(12, 6, 'Kết quả đơn nghỉ phép', '❌ Đơn xin nghỉ phép bị từ chối: ko đồng ý', 'leave_request', 3, 0, '2026-05-02 05:32:45'),
(13, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 1, 0, '2026-05-07 06:14:08'),
(14, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 2, 0, '2026-05-07 06:14:08'),
(15, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 3, 0, '2026-05-07 06:14:08'),
(16, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 4, 0, '2026-05-07 06:14:08'),
(17, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 5, 0, '2026-05-07 06:14:08'),
(18, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 6, 0, '2026-05-07 06:14:08'),
(19, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 7, 0, '2026-05-07 06:14:08'),
(20, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 8, 0, '2026-05-07 06:14:08'),
(21, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 9, 0, '2026-05-07 06:14:08'),
(22, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 10, 0, '2026-05-07 06:14:08'),
(23, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 11, 0, '2026-05-07 06:14:08'),
(24, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 12, 0, '2026-05-07 06:14:08'),
(25, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 13, 0, '2026-05-07 06:14:08'),
(26, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 14, 0, '2026-05-07 06:14:08'),
(27, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 15, 0, '2026-05-07 06:14:08'),
(28, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 16, 0, '2026-05-07 06:14:08'),
(29, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 17, 0, '2026-05-07 06:14:08'),
(30, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 18, 0, '2026-05-07 06:14:08'),
(31, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 19, 0, '2026-05-07 06:14:08'),
(32, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 20, 0, '2026-05-07 06:14:08'),
(33, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 11/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 21, 0, '2026-05-07 06:14:08'),
(34, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 22, 0, '2026-05-07 06:14:08'),
(35, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 23, 0, '2026-05-07 06:14:08'),
(36, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 24, 0, '2026-05-07 06:14:08'),
(37, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 25, 0, '2026-05-07 06:14:08'),
(38, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 26, 0, '2026-05-07 06:14:08'),
(39, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 27, 0, '2026-05-07 06:14:08'),
(40, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 28, 0, '2026-05-07 06:14:08'),
(41, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 29, 0, '2026-05-07 06:14:08'),
(42, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 30, 0, '2026-05-07 06:14:08'),
(43, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 31, 0, '2026-05-07 06:14:08'),
(44, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 32, 0, '2026-05-07 06:14:08'),
(45, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 33, 0, '2026-05-07 06:14:08'),
(46, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 34, 0, '2026-05-07 06:14:08'),
(47, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 35, 0, '2026-05-07 06:14:08'),
(48, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 36, 0, '2026-05-07 06:14:08'),
(49, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 37, 0, '2026-05-07 06:14:08'),
(50, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 38, 0, '2026-05-07 06:14:08'),
(51, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 39, 0, '2026-05-07 06:14:08'),
(52, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 40, 0, '2026-05-07 06:14:08'),
(53, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 41, 0, '2026-05-07 06:14:08'),
(54, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 42, 0, '2026-05-07 06:14:08'),
(55, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 43, 0, '2026-05-07 06:14:08'),
(56, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 44, 0, '2026-05-07 06:14:08'),
(57, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 45, 0, '2026-05-07 06:14:08'),
(58, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 46, 0, '2026-05-07 06:14:08'),
(59, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 47, 0, '2026-05-07 06:14:08'),
(60, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 48, 0, '2026-05-07 06:14:08'),
(61, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 49, 0, '2026-05-07 06:14:08'),
(62, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 50, 0, '2026-05-07 06:14:08'),
(63, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 51, 0, '2026-05-07 06:14:08'),
(64, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 52, 0, '2026-05-07 06:14:08'),
(65, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 53, 0, '2026-05-07 06:14:08'),
(66, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 54, 0, '2026-05-07 06:14:08'),
(67, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 55, 0, '2026-05-07 06:14:08'),
(68, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 56, 0, '2026-05-07 06:14:08'),
(69, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 57, 0, '2026-05-07 06:14:08'),
(70, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 58, 0, '2026-05-07 06:14:08'),
(71, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 59, 0, '2026-05-07 06:14:08'),
(72, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 60, 0, '2026-05-07 06:14:08'),
(73, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 61, 0, '2026-05-07 06:14:08'),
(74, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 62, 0, '2026-05-07 06:14:08'),
(75, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 63, 0, '2026-05-07 06:14:08'),
(76, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 64, 0, '2026-05-07 06:14:08'),
(77, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 65, 0, '2026-05-07 06:14:08'),
(78, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 66, 0, '2026-05-07 06:14:08'),
(79, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 67, 0, '2026-05-07 06:14:08'),
(80, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 68, 0, '2026-05-07 06:14:08'),
(81, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 69, 0, '2026-05-07 06:14:08'),
(82, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 70, 0, '2026-05-07 06:14:08'),
(83, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 07/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 71, 0, '2026-05-07 06:14:08'),
(84, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 72, 0, '2026-05-07 06:14:08'),
(85, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 73, 0, '2026-05-07 06:14:08'),
(86, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 74, 0, '2026-05-07 06:14:08'),
(87, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 13/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 75, 0, '2026-05-07 06:14:08'),
(88, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 76, 0, '2026-05-07 06:14:08'),
(89, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 77, 0, '2026-05-07 06:14:08'),
(90, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 78, 0, '2026-05-07 06:14:08'),
(91, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 79, 0, '2026-05-07 06:14:08'),
(92, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 80, 0, '2026-05-07 06:14:08'),
(93, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 81, 0, '2026-05-07 06:14:08'),
(94, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 82, 0, '2026-05-07 06:14:08'),
(95, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 83, 0, '2026-05-07 06:14:08'),
(96, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 84, 0, '2026-05-07 06:14:08'),
(97, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 85, 0, '2026-05-07 06:14:08'),
(98, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 86, 0, '2026-05-07 06:14:08'),
(99, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 27/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 87, 0, '2026-05-07 06:14:08'),
(100, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 88, 0, '2026-05-07 06:14:08'),
(101, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 89, 0, '2026-05-07 06:14:08'),
(102, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 90, 0, '2026-05-07 06:14:08'),
(103, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 91, 0, '2026-05-07 06:14:08'),
(104, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 92, 0, '2026-05-07 06:14:08'),
(105, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 93, 0, '2026-05-07 06:14:08'),
(106, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 94, 0, '2026-05-07 06:14:08'),
(107, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 95, 0, '2026-05-07 06:14:08'),
(108, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 96, 0, '2026-05-07 06:14:08'),
(109, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 97, 0, '2026-05-07 06:14:08'),
(110, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 98, 0, '2026-05-07 06:14:08'),
(111, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 99, 0, '2026-05-07 06:14:08'),
(112, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 100, 0, '2026-05-07 06:14:08'),
(113, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 101, 0, '2026-05-07 06:14:08'),
(114, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 09/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 102, 0, '2026-05-07 06:14:08'),
(115, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 103, 0, '2026-05-07 06:14:08'),
(116, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 104, 0, '2026-05-07 06:14:08'),
(117, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 105, 0, '2026-05-07 06:14:08'),
(118, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 106, 0, '2026-05-07 06:14:08'),
(119, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 107, 0, '2026-05-07 06:14:08'),
(120, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 108, 0, '2026-05-07 06:14:08'),
(121, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 109, 0, '2026-05-07 06:14:08'),
(122, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 110, 0, '2026-05-07 06:14:08'),
(123, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 111, 0, '2026-05-07 06:14:08'),
(124, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 112, 0, '2026-05-07 06:14:08'),
(125, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 113, 0, '2026-05-07 06:14:08'),
(126, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 114, 0, '2026-05-07 06:14:08'),
(127, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 115, 0, '2026-05-07 06:14:08'),
(128, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 116, 0, '2026-05-07 06:14:08'),
(129, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 117, 0, '2026-05-07 06:14:08'),
(130, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 118, 0, '2026-05-07 06:14:08'),
(131, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 119, 0, '2026-05-07 06:14:08'),
(132, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 120, 0, '2026-05-07 06:14:08'),
(133, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 09/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 121, 0, '2026-05-07 06:14:08'),
(134, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 122, 0, '2026-05-07 06:14:08'),
(135, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 123, 0, '2026-05-07 06:14:08'),
(136, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 124, 0, '2026-05-07 06:14:08'),
(137, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 125, 0, '2026-05-07 06:14:08'),
(138, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 126, 0, '2026-05-07 06:14:08'),
(139, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 127, 0, '2026-05-07 06:14:08'),
(140, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 128, 0, '2026-05-07 06:14:08'),
(141, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 129, 0, '2026-05-07 06:14:08'),
(142, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 130, 0, '2026-05-07 06:14:08'),
(143, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 131, 0, '2026-05-07 06:14:08'),
(144, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 132, 0, '2026-05-07 06:14:08'),
(145, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 133, 0, '2026-05-07 06:14:08'),
(146, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 134, 0, '2026-05-07 06:14:08'),
(147, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 135, 0, '2026-05-07 06:14:08'),
(148, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 136, 0, '2026-05-07 06:14:08'),
(149, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 137, 0, '2026-05-07 06:14:08'),
(150, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 138, 0, '2026-05-07 06:14:08'),
(151, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 139, 0, '2026-05-07 06:14:08'),
(152, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 140, 0, '2026-05-07 06:14:08'),
(153, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 141, 0, '2026-05-07 06:14:08'),
(154, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 142, 0, '2026-05-07 06:14:08'),
(155, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 16, 0, '2026-05-07 06:19:59'),
(156, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 67, 0, '2026-05-07 06:20:01'),
(157, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 49, 0, '2026-05-07 06:20:02'),
(158, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 117, 0, '2026-05-07 06:20:02'),
(159, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 135, 0, '2026-05-07 06:20:02'),
(160, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 142, 0, '2026-05-07 06:20:03'),
(161, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–18:00:00, 1.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 87, 0, '2026-05-07 06:20:03'),
(162, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 15, 0, '2026-05-07 06:20:04'),
(163, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 32, 0, '2026-05-07 06:20:04'),
(164, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 66, 0, '2026-05-07 06:20:04'),
(165, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 48, 0, '2026-05-07 06:20:05'),
(166, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 116, 0, '2026-05-07 06:20:05'),
(167, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 134, 0, '2026-05-07 06:20:06'),
(168, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 141, 0, '2026-05-07 06:20:06'),
(169, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 86, 0, '2026-05-07 06:20:08'),
(170, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 14, 0, '2026-05-07 06:20:08'),
(171, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 31, 0, '2026-05-07 06:20:09'),
(172, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 65, 0, '2026-05-07 06:20:09'),
(173, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 47, 0, '2026-05-07 06:20:10'),
(174, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 115, 0, '2026-05-07 06:20:11'),
(175, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 133, 0, '2026-05-07 06:20:11'),
(176, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 140, 0, '2026-05-07 06:20:12'),
(177, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 85, 0, '2026-05-07 06:20:12'),
(178, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 13, 0, '2026-05-07 06:23:52'),
(179, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 64, 0, '2026-05-07 06:24:02'),
(180, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 46, 0, '2026-05-07 06:27:26'),
(181, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 114, 0, '2026-05-07 06:27:27'),
(182, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 132, 0, '2026-05-07 06:27:28'),
(183, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (17:00:00–20:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 139, 0, '2026-05-07 06:27:29'),
(184, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 84, 0, '2026-05-07 06:36:42'),
(185, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 12, 0, '2026-05-07 06:36:42'),
(186, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 30, 0, '2026-05-07 06:36:42'),
(187, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 63, 0, '2026-05-07 06:36:42'),
(188, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 45, 0, '2026-05-07 06:36:42'),
(189, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 113, 0, '2026-05-07 06:36:42'),
(190, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 131, 0, '2026-05-07 06:36:42'),
(191, 12, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 23/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 138, 0, '2026-05-07 06:36:42'),
(192, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 83, 0, '2026-05-07 06:36:42'),
(193, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 29, 0, '2026-05-07 06:36:42'),
(194, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 62, 0, '2026-05-07 06:36:42'),
(195, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 44, 0, '2026-05-07 06:36:42'),
(196, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 112, 0, '2026-05-07 06:36:42'),
(197, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 130, 0, '2026-05-07 06:36:42'),
(198, 12, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 22/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 137, 0, '2026-05-07 06:36:42'),
(199, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 82, 0, '2026-05-07 06:36:42'),
(200, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 11, 0, '2026-05-07 06:36:42'),
(201, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 28, 0, '2026-05-07 06:36:42'),
(202, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 61, 0, '2026-05-07 06:36:42'),
(203, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 43, 0, '2026-05-07 06:36:42'),
(204, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 111, 0, '2026-05-07 06:36:42'),
(205, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 129, 0, '2026-05-07 06:36:42'),
(206, 12, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 21/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 136, 0, '2026-05-07 06:36:42'),
(207, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 20/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 81, 0, '2026-05-07 06:36:42'),
(208, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 20/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 10, 0, '2026-05-07 06:36:42'),
(209, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 20/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 27, 0, '2026-05-07 06:36:42'),
(210, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 20/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 60, 0, '2026-05-07 06:36:42'),
(211, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 20/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 110, 0, '2026-05-07 06:36:42'),
(212, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 18/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 80, 0, '2026-05-07 06:36:42'),
(213, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 18/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 9, 0, '2026-05-07 06:36:42'),
(214, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 18/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 26, 0, '2026-05-07 06:36:42'),
(215, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 18/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 59, 0, '2026-05-07 06:36:42'),
(216, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 18/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 109, 0, '2026-05-07 06:36:42'),
(217, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 17/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 79, 0, '2026-05-07 06:36:42'),
(218, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 17/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 8, 0, '2026-05-07 06:36:42'),
(219, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 17/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 97, 0, '2026-05-07 06:36:42'),
(220, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 17/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 108, 0, '2026-05-07 06:36:42'),
(221, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 17/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 128, 0, '2026-05-07 06:36:42'),
(222, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 78, 0, '2026-05-07 06:36:42'),
(223, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 7, 0, '2026-05-07 06:36:42'),
(224, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 25, 0, '2026-05-07 06:36:42'),
(225, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 58, 0, '2026-05-07 06:36:42'),
(226, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 42, 0, '2026-05-07 06:36:42'),
(227, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 96, 0, '2026-05-07 06:36:42'),
(228, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 107, 0, '2026-05-07 06:36:42'),
(229, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 16/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 127, 0, '2026-05-07 06:36:42'),
(230, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 77, 0, '2026-05-07 06:36:42'),
(231, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 6, 0, '2026-05-07 06:36:42'),
(232, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 24, 0, '2026-05-07 06:36:42'),
(233, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 57, 0, '2026-05-07 06:36:42'),
(234, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 41, 0, '2026-05-07 06:36:42'),
(235, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 95, 0, '2026-05-07 06:36:42'),
(236, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 106, 0, '2026-05-07 06:36:42'),
(237, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 15/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 126, 0, '2026-05-07 06:36:42'),
(238, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 76, 0, '2026-05-07 06:36:42'),
(239, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 5, 0, '2026-05-07 06:36:42'),
(240, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 23, 0, '2026-05-07 06:36:42'),
(241, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 56, 0, '2026-05-07 06:36:42'),
(242, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 40, 0, '2026-05-07 06:36:42'),
(243, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 94, 0, '2026-05-07 06:36:42'),
(244, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 105, 0, '2026-05-07 06:36:42'),
(245, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 14/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 125, 0, '2026-05-07 06:36:42'),
(246, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 75, 0, '2026-05-07 06:36:42'),
(247, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 22, 0, '2026-05-07 06:36:42'),
(248, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 55, 0, '2026-05-07 06:36:42'),
(249, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 39, 0, '2026-05-07 06:36:42'),
(250, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 93, 0, '2026-05-07 06:36:42'),
(251, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 13/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 124, 0, '2026-05-07 06:36:42'),
(252, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 74, 0, '2026-05-07 06:36:42'),
(253, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 21, 0, '2026-05-07 06:36:42'),
(254, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 54, 0, '2026-05-07 06:36:42'),
(255, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 38, 0, '2026-05-07 06:36:42'),
(256, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 104, 0, '2026-05-07 06:36:42'),
(257, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 11/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 123, 0, '2026-05-07 06:36:42'),
(258, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 73, 0, '2026-05-07 06:36:42'),
(259, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 4, 0, '2026-05-07 06:36:42'),
(260, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 20, 0, '2026-05-07 06:36:42'),
(261, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 37, 0, '2026-05-07 06:36:42'),
(262, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 92, 0, '2026-05-07 06:36:42'),
(263, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 103, 0, '2026-05-07 06:36:42'),
(264, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 10/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 122, 0, '2026-05-07 06:36:42'),
(265, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 09/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 102, 0, '2026-05-07 06:36:42'),
(266, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 09/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 121, 0, '2026-05-07 06:36:42'),
(267, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 72, 0, '2026-05-07 06:36:42'),
(268, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 3, 0, '2026-05-07 06:36:42'),
(269, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 19, 0, '2026-05-07 06:36:42'),
(270, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 53, 0, '2026-05-07 06:36:42'),
(271, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 36, 0, '2026-05-07 06:36:42'),
(272, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 91, 0, '2026-05-07 06:36:42'),
(273, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 101, 0, '2026-05-07 06:36:42'),
(274, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 08/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 120, 0, '2026-05-07 06:36:42'),
(275, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 07/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 71, 0, '2026-05-07 06:36:42'),
(276, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 70, 0, '2026-05-07 06:36:42'),
(277, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 2, 0, '2026-05-07 06:36:42'),
(278, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 18, 0, '2026-05-07 06:36:42'),
(279, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 52, 0, '2026-05-07 06:36:42'),
(280, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 35, 0, '2026-05-07 06:36:42'),
(281, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 90, 0, '2026-05-07 06:36:42'),
(282, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 100, 0, '2026-05-07 06:36:42'),
(283, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 06/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 119, 0, '2026-05-07 06:36:42'),
(284, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 69, 0, '2026-05-07 06:36:42'),
(285, 6, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 17, 0, '2026-05-07 06:36:42'),
(286, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 51, 0, '2026-05-07 06:36:42'),
(287, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 34, 0, '2026-05-07 06:36:42'),
(288, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 89, 0, '2026-05-07 06:36:42'),
(289, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 02/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 99, 0, '2026-05-07 06:36:42'),
(290, 4, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 68, 0, '2026-05-07 06:36:42'),
(291, 5, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 1, 0, '2026-05-07 06:36:42'),
(292, 7, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 50, 0, '2026-05-07 06:36:42'),
(293, 8, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 33, 0, '2026-05-07 06:36:42'),
(294, 9, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 88, 0, '2026-05-07 06:36:42'),
(295, 10, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 98, 0, '2026-05-07 06:36:42'),
(296, 11, '❌ Đơn OT bị từ chối', 'Đơn OT ngày 01/04/2026 bị từ chối. Lý do: không', 'ot_rejected', 118, 0, '2026-05-07 06:36:42'),
(297, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 143, 0, '2026-05-07 06:42:55'),
(298, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 144, 0, '2026-05-07 06:42:55'),
(299, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 145, 0, '2026-05-07 06:42:55'),
(300, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 146, 0, '2026-05-07 06:42:55'),
(301, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 147, 0, '2026-05-07 06:42:55'),
(302, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 148, 0, '2026-05-07 06:42:55'),
(303, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 149, 0, '2026-05-07 06:42:55'),
(304, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 150, 0, '2026-05-07 06:42:55'),
(305, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 151, 0, '2026-05-07 06:42:55'),
(306, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 152, 0, '2026-05-07 06:42:55'),
(307, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 153, 0, '2026-05-07 06:42:55'),
(308, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 154, 0, '2026-05-07 06:42:55'),
(309, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 155, 0, '2026-05-07 06:42:55'),
(310, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 156, 0, '2026-05-07 06:42:55'),
(311, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 157, 0, '2026-05-07 06:42:55'),
(312, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV01 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 158, 0, '2026-05-07 06:42:55'),
(313, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 159, 0, '2026-05-07 06:42:55'),
(314, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 160, 0, '2026-05-07 06:42:55'),
(315, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 161, 0, '2026-05-07 06:42:55'),
(316, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 162, 0, '2026-05-07 06:42:55'),
(317, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 11/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 163, 0, '2026-05-07 06:42:55'),
(318, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 164, 0, '2026-05-07 06:42:55'),
(319, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 165, 0, '2026-05-07 06:42:55'),
(320, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 166, 0, '2026-05-07 06:42:55'),
(321, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 167, 0, '2026-05-07 06:42:55'),
(322, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 168, 0, '2026-05-07 06:42:55'),
(323, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 169, 0, '2026-05-07 06:42:55'),
(324, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 170, 0, '2026-05-07 06:42:55'),
(325, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 171, 0, '2026-05-07 06:42:55'),
(326, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 172, 0, '2026-05-07 06:42:55'),
(327, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 173, 0, '2026-05-07 06:42:55'),
(328, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 174, 0, '2026-05-07 06:42:55'),
(329, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 175, 0, '2026-05-07 06:42:55'),
(330, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 176, 0, '2026-05-07 06:42:55'),
(331, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 177, 0, '2026-05-07 06:42:55'),
(332, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 178, 0, '2026-05-07 06:42:55'),
(333, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 179, 0, '2026-05-07 06:42:55'),
(334, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 180, 0, '2026-05-07 06:42:55'),
(335, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 181, 0, '2026-05-07 06:42:55'),
(336, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 182, 0, '2026-05-07 06:42:55'),
(337, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 183, 0, '2026-05-07 06:42:55'),
(338, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 184, 0, '2026-05-07 06:42:55'),
(339, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 185, 0, '2026-05-07 06:42:55'),
(340, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 186, 0, '2026-05-07 06:42:55'),
(341, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 187, 0, '2026-05-07 06:42:55'),
(342, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 188, 0, '2026-05-07 06:42:55'),
(343, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 189, 0, '2026-05-07 06:42:55'),
(344, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 190, 0, '2026-05-07 06:42:55');
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
(345, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 191, 0, '2026-05-07 06:42:55'),
(346, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 192, 0, '2026-05-07 06:42:55'),
(347, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 193, 0, '2026-05-07 06:42:55'),
(348, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 194, 0, '2026-05-07 06:42:55'),
(349, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 195, 0, '2026-05-07 06:42:55'),
(350, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 196, 0, '2026-05-07 06:42:55'),
(351, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 197, 0, '2026-05-07 06:42:55'),
(352, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 198, 0, '2026-05-07 06:42:55'),
(353, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 199, 0, '2026-05-07 06:42:55'),
(354, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 200, 0, '2026-05-07 06:42:55'),
(355, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 201, 0, '2026-05-07 06:42:55'),
(356, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 202, 0, '2026-05-07 06:42:55'),
(357, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 203, 0, '2026-05-07 06:42:55'),
(358, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 204, 0, '2026-05-07 06:42:55'),
(359, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 205, 0, '2026-05-07 06:42:55'),
(360, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 206, 0, '2026-05-07 06:42:55'),
(361, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 207, 0, '2026-05-07 06:42:55'),
(362, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 208, 0, '2026-05-07 06:42:55'),
(363, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 209, 0, '2026-05-07 06:42:55'),
(364, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 210, 0, '2026-05-07 06:42:55'),
(365, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 211, 0, '2026-05-07 06:42:55'),
(366, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 212, 0, '2026-05-07 06:42:55'),
(367, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 07/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 213, 0, '2026-05-07 06:42:55'),
(368, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 214, 0, '2026-05-07 06:42:55'),
(369, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 215, 0, '2026-05-07 06:42:55'),
(370, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 216, 0, '2026-05-07 06:42:55'),
(371, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 13/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 217, 0, '2026-05-07 06:42:55'),
(372, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 218, 0, '2026-05-07 06:42:55'),
(373, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 219, 0, '2026-05-07 06:42:55'),
(374, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 220, 0, '2026-05-07 06:42:55'),
(375, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 221, 0, '2026-05-07 06:42:55'),
(376, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 222, 0, '2026-05-07 06:42:55'),
(377, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 223, 0, '2026-05-07 06:42:55'),
(378, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 224, 0, '2026-05-07 06:42:55'),
(379, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 225, 0, '2026-05-07 06:42:55'),
(380, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 226, 0, '2026-05-07 06:42:55'),
(381, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 227, 0, '2026-05-07 06:42:55'),
(382, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 228, 0, '2026-05-07 06:42:55'),
(383, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 27/04/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 229, 0, '2026-05-07 06:42:55'),
(384, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 230, 0, '2026-05-07 06:42:55'),
(385, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 231, 0, '2026-05-07 06:42:55'),
(386, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 232, 0, '2026-05-07 06:42:55'),
(387, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 233, 0, '2026-05-07 06:42:55'),
(388, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 234, 0, '2026-05-07 06:42:55'),
(389, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 235, 0, '2026-05-07 06:42:55'),
(390, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 236, 0, '2026-05-07 06:42:55'),
(391, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 237, 0, '2026-05-07 06:42:55'),
(392, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 238, 0, '2026-05-07 06:42:55'),
(393, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 239, 0, '2026-05-07 06:42:55'),
(394, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 240, 0, '2026-05-07 06:42:55'),
(395, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 02/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 241, 0, '2026-05-07 06:42:55'),
(396, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 242, 0, '2026-05-07 06:42:55'),
(397, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 243, 0, '2026-05-07 06:42:55'),
(398, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 09/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 244, 0, '2026-05-07 06:42:55'),
(399, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 245, 0, '2026-05-07 06:42:55'),
(400, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 246, 0, '2026-05-07 06:42:55'),
(401, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 247, 0, '2026-05-07 06:42:55'),
(402, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 248, 0, '2026-05-07 06:42:55'),
(403, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 249, 0, '2026-05-07 06:42:55'),
(404, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 250, 0, '2026-05-07 06:42:55'),
(405, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 18/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 251, 0, '2026-05-07 06:42:55'),
(406, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 20/04/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 252, 0, '2026-05-07 06:42:55'),
(407, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 253, 0, '2026-05-07 06:42:55'),
(408, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 254, 0, '2026-05-07 06:42:55'),
(409, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 255, 0, '2026-05-07 06:42:55'),
(410, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 256, 0, '2026-05-07 06:42:55'),
(411, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 257, 0, '2026-05-07 06:42:55'),
(412, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 258, 0, '2026-05-07 06:42:55'),
(413, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 259, 0, '2026-05-07 06:42:55'),
(414, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 01/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 260, 0, '2026-05-07 06:42:55'),
(415, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 06/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 261, 0, '2026-05-07 06:42:55'),
(416, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 08/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 262, 0, '2026-05-07 06:42:55'),
(417, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 09/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 263, 0, '2026-05-07 06:42:55'),
(418, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 10/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 264, 0, '2026-05-07 06:42:55'),
(419, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 11/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 265, 0, '2026-05-07 06:42:55'),
(420, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 13/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 266, 0, '2026-05-07 06:42:55'),
(421, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 14/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 267, 0, '2026-05-07 06:42:55'),
(422, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 15/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 268, 0, '2026-05-07 06:42:55'),
(423, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 16/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 269, 0, '2026-05-07 06:42:55'),
(424, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 17/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 270, 0, '2026-05-07 06:42:55'),
(425, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 271, 0, '2026-05-07 06:42:55'),
(426, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 272, 0, '2026-05-07 06:42:55'),
(427, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 273, 0, '2026-05-07 06:42:55'),
(428, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 274, 0, '2026-05-07 06:42:55'),
(429, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 275, 0, '2026-05-07 06:42:55'),
(430, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 276, 0, '2026-05-07 06:42:55'),
(431, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 277, 0, '2026-05-07 06:42:55'),
(432, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 21/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 278, 0, '2026-05-07 06:42:55'),
(433, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 22/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 279, 0, '2026-05-07 06:42:55'),
(434, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 23/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 280, 0, '2026-05-07 06:42:55'),
(435, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 24/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 281, 0, '2026-05-07 06:42:55'),
(436, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 25/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 282, 0, '2026-05-07 06:42:55'),
(437, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 27/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 283, 0, '2026-05-07 06:42:55'),
(438, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV11 đăng ký OT ngày 28/04/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 284, 0, '2026-05-07 06:42:55'),
(439, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 158, 0, '2026-05-07 06:43:23'),
(440, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 209, 0, '2026-05-07 06:43:23'),
(441, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 191, 0, '2026-05-07 06:43:23'),
(442, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 259, 0, '2026-05-07 06:43:23'),
(443, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 277, 0, '2026-05-07 06:43:23'),
(444, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 28/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 284, 0, '2026-05-07 06:43:23'),
(445, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 229, 0, '2026-05-07 06:43:23'),
(446, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 157, 0, '2026-05-07 06:43:23'),
(447, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 174, 0, '2026-05-07 06:43:23'),
(448, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 208, 0, '2026-05-07 06:43:23'),
(449, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 190, 0, '2026-05-07 06:43:23'),
(450, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 258, 0, '2026-05-07 06:43:23'),
(451, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 276, 0, '2026-05-07 06:43:23'),
(452, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 283, 0, '2026-05-07 06:43:23'),
(453, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 228, 0, '2026-05-07 06:43:23'),
(454, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 156, 0, '2026-05-07 06:43:23'),
(455, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 173, 0, '2026-05-07 06:43:23'),
(456, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 207, 0, '2026-05-07 06:43:23'),
(457, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 189, 0, '2026-05-07 06:43:23'),
(458, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 257, 0, '2026-05-07 06:43:23'),
(459, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 275, 0, '2026-05-07 06:43:23'),
(460, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 282, 0, '2026-05-07 06:43:23'),
(461, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 227, 0, '2026-05-07 06:43:23'),
(462, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 155, 0, '2026-05-07 06:43:23'),
(463, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 206, 0, '2026-05-07 06:43:23'),
(464, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 188, 0, '2026-05-07 06:43:23'),
(465, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 256, 0, '2026-05-07 06:43:23'),
(466, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 274, 0, '2026-05-07 06:43:23'),
(467, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 24/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 281, 0, '2026-05-07 06:43:23'),
(468, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 226, 0, '2026-05-07 06:43:23'),
(469, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 154, 0, '2026-05-07 06:43:23'),
(470, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 172, 0, '2026-05-07 06:43:23'),
(471, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 205, 0, '2026-05-07 06:43:23'),
(472, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 187, 0, '2026-05-07 06:43:23'),
(473, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 255, 0, '2026-05-07 06:43:23'),
(474, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 273, 0, '2026-05-07 06:43:23'),
(475, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 280, 0, '2026-05-07 06:43:23'),
(476, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 225, 0, '2026-05-07 06:43:23'),
(477, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 171, 0, '2026-05-07 06:43:23'),
(478, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 204, 0, '2026-05-07 06:43:23'),
(479, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 186, 0, '2026-05-07 06:43:23'),
(480, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 254, 0, '2026-05-07 06:43:23'),
(481, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 272, 0, '2026-05-07 06:43:23'),
(482, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 22/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 279, 0, '2026-05-07 06:43:23'),
(483, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 224, 0, '2026-05-07 06:43:23'),
(484, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 153, 0, '2026-05-07 06:43:23'),
(485, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 170, 0, '2026-05-07 06:43:23'),
(486, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 203, 0, '2026-05-07 06:43:23'),
(487, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 185, 0, '2026-05-07 06:43:23'),
(488, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 253, 0, '2026-05-07 06:43:23'),
(489, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 271, 0, '2026-05-07 06:43:23'),
(490, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 278, 0, '2026-05-07 06:43:23'),
(491, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/04/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 223, 0, '2026-05-07 06:43:23'),
(492, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/04/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 152, 0, '2026-05-07 06:43:23'),
(493, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/04/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 169, 0, '2026-05-07 06:43:23'),
(494, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/04/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 202, 0, '2026-05-07 06:43:23'),
(495, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/04/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 252, 0, '2026-05-07 06:43:23'),
(496, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 222, 0, '2026-05-07 06:43:23'),
(497, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 151, 0, '2026-05-07 06:43:23'),
(498, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 168, 0, '2026-05-07 06:43:23'),
(499, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 201, 0, '2026-05-07 06:43:23'),
(500, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 251, 0, '2026-05-07 06:43:23'),
(501, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 17/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 221, 0, '2026-05-07 06:43:23'),
(502, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 17/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 150, 0, '2026-05-07 06:43:23'),
(503, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 17/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 239, 0, '2026-05-07 06:43:23'),
(504, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 17/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 250, 0, '2026-05-07 06:43:23'),
(505, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 17/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 270, 0, '2026-05-07 06:43:23'),
(506, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 220, 0, '2026-05-07 06:43:23'),
(507, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 149, 0, '2026-05-07 06:43:23'),
(508, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 167, 0, '2026-05-07 06:43:23'),
(509, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 200, 0, '2026-05-07 06:43:23'),
(510, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 184, 0, '2026-05-07 06:43:23'),
(511, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 238, 0, '2026-05-07 06:43:23'),
(512, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 249, 0, '2026-05-07 06:43:23'),
(513, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 16/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 269, 0, '2026-05-07 06:43:23'),
(514, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 219, 0, '2026-05-07 06:43:23'),
(515, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 148, 0, '2026-05-07 06:43:23'),
(516, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 166, 0, '2026-05-07 06:43:23'),
(517, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 199, 0, '2026-05-07 06:43:23'),
(518, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 183, 0, '2026-05-07 06:43:23'),
(519, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 237, 0, '2026-05-07 06:43:23'),
(520, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 248, 0, '2026-05-07 06:43:23'),
(521, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 15/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 268, 0, '2026-05-07 06:43:23'),
(522, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 218, 0, '2026-05-07 06:43:23'),
(523, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 147, 0, '2026-05-07 06:43:23'),
(524, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 165, 0, '2026-05-07 06:43:23'),
(525, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 198, 0, '2026-05-07 06:43:23'),
(526, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 182, 0, '2026-05-07 06:43:23'),
(527, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 236, 0, '2026-05-07 06:43:23'),
(528, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 247, 0, '2026-05-07 06:43:23'),
(529, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 267, 0, '2026-05-07 06:43:23'),
(530, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 217, 0, '2026-05-07 06:43:23'),
(531, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 164, 0, '2026-05-07 06:43:23'),
(532, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 197, 0, '2026-05-07 06:43:23'),
(533, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 181, 0, '2026-05-07 06:43:23'),
(534, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 235, 0, '2026-05-07 06:43:23'),
(535, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 13/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 266, 0, '2026-05-07 06:43:23'),
(536, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 216, 0, '2026-05-07 06:43:23'),
(537, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 163, 0, '2026-05-07 06:43:23'),
(538, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 196, 0, '2026-05-07 06:43:23'),
(539, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 180, 0, '2026-05-07 06:43:23'),
(540, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 246, 0, '2026-05-07 06:43:23'),
(541, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 11/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 265, 0, '2026-05-07 06:43:23'),
(542, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 215, 0, '2026-05-07 06:43:23'),
(543, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 146, 0, '2026-05-07 06:43:23'),
(544, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 162, 0, '2026-05-07 06:43:23'),
(545, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 179, 0, '2026-05-07 06:43:23'),
(546, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 234, 0, '2026-05-07 06:43:23'),
(547, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 245, 0, '2026-05-07 06:43:23'),
(548, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 264, 0, '2026-05-07 06:43:23'),
(549, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 244, 0, '2026-05-07 06:43:23'),
(550, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 263, 0, '2026-05-07 06:43:23'),
(551, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 214, 0, '2026-05-07 06:43:23'),
(552, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 145, 0, '2026-05-07 06:43:23'),
(553, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 161, 0, '2026-05-07 06:43:23'),
(554, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 195, 0, '2026-05-07 06:43:23'),
(555, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 178, 0, '2026-05-07 06:43:23'),
(556, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 233, 0, '2026-05-07 06:43:23'),
(557, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 243, 0, '2026-05-07 06:43:23'),
(558, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 262, 0, '2026-05-07 06:43:23'),
(559, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 07/04/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 213, 0, '2026-05-07 06:43:23'),
(560, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 212, 0, '2026-05-07 06:43:23'),
(561, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 144, 0, '2026-05-07 06:43:23'),
(562, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 160, 0, '2026-05-07 06:43:23'),
(563, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 194, 0, '2026-05-07 06:43:23'),
(564, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 177, 0, '2026-05-07 06:43:23'),
(565, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 232, 0, '2026-05-07 06:43:23'),
(566, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 242, 0, '2026-05-07 06:43:23'),
(567, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 261, 0, '2026-05-07 06:43:23'),
(568, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 211, 0, '2026-05-07 06:43:23'),
(569, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 159, 0, '2026-05-07 06:43:23'),
(570, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 193, 0, '2026-05-07 06:43:23'),
(571, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 176, 0, '2026-05-07 06:43:23'),
(572, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 231, 0, '2026-05-07 06:43:23'),
(573, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 241, 0, '2026-05-07 06:43:23'),
(574, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 210, 0, '2026-05-07 06:43:23'),
(575, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 143, 0, '2026-05-07 06:43:23'),
(576, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 192, 0, '2026-05-07 06:43:23'),
(577, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 175, 0, '2026-05-07 06:43:23'),
(578, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 230, 0, '2026-05-07 06:43:23'),
(579, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 240, 0, '2026-05-07 06:43:23'),
(580, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/04/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 260, 0, '2026-05-07 06:43:23'),
(581, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 12/04/2026 (4 giờ) — Tăng ca theo kế hoạch', 'ot_request', 285, 0, '2026-05-07 07:01:53'),
(582, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV04 đăng ký OT ngày 19/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 286, 0, '2026-05-07 07:01:53'),
(583, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV05 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 287, 0, '2026-05-07 07:01:53'),
(584, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 288, 0, '2026-05-07 07:01:53'),
(585, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV06 đăng ký OT ngày 19/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 289, 0, '2026-05-07 07:01:53'),
(586, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 290, 0, '2026-05-07 07:01:53'),
(587, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV07 đăng ký OT ngày 19/04/2026 (7 giờ) — Tăng ca theo kế hoạch', 'ot_request', 291, 0, '2026-05-07 07:01:53'),
(588, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV08 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 292, 0, '2026-05-07 07:01:53'),
(589, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 293, 0, '2026-05-07 07:01:53'),
(590, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV09 đăng ký OT ngày 19/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 294, 0, '2026-05-07 07:01:53'),
(591, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 12/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 295, 0, '2026-05-07 07:01:53'),
(592, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV10 đăng ký OT ngày 19/04/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 296, 0, '2026-05-07 07:01:53'),
(593, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/04/2026 (7.00 giờ) đã được duyệt.', 'ot_approved', 291, 0, '2026-05-07 07:02:07'),
(594, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 286, 0, '2026-05-07 07:02:07'),
(595, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 289, 0, '2026-05-07 07:02:07'),
(596, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 294, 0, '2026-05-07 07:02:07'),
(597, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 296, 0, '2026-05-07 07:02:07'),
(598, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 290, 0, '2026-05-07 07:02:07'),
(599, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (4.00 giờ) đã được duyệt.', 'ot_approved', 285, 0, '2026-05-07 07:02:07'),
(600, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 288, 0, '2026-05-07 07:02:07'),
(601, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 287, 0, '2026-05-07 07:02:07'),
(602, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 292, 0, '2026-05-07 07:02:07'),
(603, 10, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 293, 0, '2026-05-07 07:02:07'),
(604, 11, '✅ Đơn OT được duyệt', 'Đơn OT ngày 12/04/2026 (8.00 giờ) đã được duyệt.', 'ot_approved', 295, 0, '2026-05-07 07:02:07'),
(605, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 08/05/2026 (2 giờ) — Hoàn thiện báo cáo tháng', 'ot_request', 297, 0, '2026-05-09 01:25:21'),
(606, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 08/05/2026 (2 giờ) — Hoàn thiện báo cáo tháng', 'ot_request', 297, 0, '2026-05-09 01:25:21'),
(607, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV002 đăng ký OT ngày 08/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 298, 0, '2026-05-09 01:25:21'),
(608, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV002 đăng ký OT ngày 08/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 298, 0, '2026-05-09 01:25:21'),
(609, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 09/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 299, 0, '2026-05-09 01:25:21'),
(610, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 09/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 299, 0, '2026-05-09 01:25:21'),
(611, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 09/05/2026 (4 giờ) — Giao hàng cuối tuần', 'ot_request', 300, 0, '2026-05-09 01:25:21'),
(612, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 09/05/2026 (4 giờ) — Giao hàng cuối tuần', 'ot_request', 300, 0, '2026-05-09 01:25:21'),
(613, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 301, 0, '2026-05-09 01:25:21'),
(614, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 301, 0, '2026-05-09 01:25:21'),
(615, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 302, 0, '2026-05-09 01:25:21'),
(616, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 302, 0, '2026-05-09 01:25:21'),
(617, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 303, 0, '2026-05-09 01:25:21'),
(618, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 303, 0, '2026-05-09 01:25:21'),
(619, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 304, 0, '2026-05-09 01:25:21'),
(620, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 304, 0, '2026-05-09 01:25:21'),
(621, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 305, 0, '2026-05-09 01:25:21'),
(622, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 305, 0, '2026-05-09 01:25:21'),
(623, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 306, 0, '2026-05-09 01:25:21'),
(624, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 306, 0, '2026-05-09 01:25:21'),
(625, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 307, 0, '2026-05-09 01:25:21'),
(626, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 307, 0, '2026-05-09 01:25:21'),
(627, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV006 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 308, 0, '2026-05-09 01:25:21'),
(628, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV006 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 308, 0, '2026-05-09 01:25:21'),
(629, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 309, 0, '2026-05-09 01:25:21'),
(630, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 309, 0, '2026-05-09 01:25:21'),
(631, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 310, 0, '2026-05-09 01:25:21'),
(632, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 310, 0, '2026-05-09 01:25:21'),
(633, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 07/05/2026 (3.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 311, 0, '2026-05-09 01:25:21'),
(634, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 07/05/2026 (3.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 311, 0, '2026-05-09 01:25:21'),
(635, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/05/2026 (4.00 giờ) đã được duyệt.', 'ot_approved', 300, 0, '2026-05-09 01:26:05'),
(636, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 299, 0, '2026-05-09 01:26:05'),
(637, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 297, 0, '2026-05-09 01:26:05'),
(638, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 298, 0, '2026-05-09 01:26:05'),
(639, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 07/05/2026 (3.50 giờ) đã được duyệt.', 'ot_approved', 311, 0, '2026-05-09 01:26:05'),
(640, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 305, 0, '2026-05-09 01:26:05'),
(641, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 306, 0, '2026-05-09 01:26:05'),
(642, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 308, 0, '2026-05-09 01:26:05'),
(643, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 307, 0, '2026-05-09 01:26:05'),
(644, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 309, 0, '2026-05-09 01:26:05'),
(645, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 310, 0, '2026-05-09 01:26:05'),
(646, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 301, 0, '2026-05-09 01:26:05'),
(647, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 302, 0, '2026-05-09 01:26:05'),
(648, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 303, 0, '2026-05-09 01:26:05'),
(649, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 304, 0, '2026-05-09 01:26:05'),
(650, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 14/05/2026 (0.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 312, 0, '2026-05-20 08:50:09'),
(651, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 14/05/2026 (0.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 312, 0, '2026-05-20 08:50:09'),
(652, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 18/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 313, 0, '2026-05-20 08:50:09'),
(653, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 18/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 313, 0, '2026-05-20 08:50:09'),
(654, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 18/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 314, 0, '2026-05-20 08:50:09'),
(655, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 18/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 314, 0, '2026-05-20 08:50:09'),
(656, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 19/05/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 315, 0, '2026-05-20 08:50:09'),
(657, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 19/05/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 315, 0, '2026-05-20 08:50:09'),
(658, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 19/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 316, 0, '2026-05-20 08:50:09'),
(659, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 19/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 316, 0, '2026-05-20 08:50:09'),
(660, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 19/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 317, 0, '2026-05-20 08:50:09'),
(661, 4, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 19/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 317, 0, '2026-05-20 08:50:09'),
(662, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 315, 0, '2026-05-20 08:50:32'),
(663, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 316, 0, '2026-05-20 08:50:32'),
(664, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 317, 0, '2026-05-20 08:50:32'),
(665, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 313, 0, '2026-05-20 08:50:32'),
(666, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 314, 0, '2026-05-20 08:50:32'),
(667, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/05/2026 (0.50 giờ) đã được duyệt.', 'ot_approved', 312, 0, '2026-05-20 08:50:32'),
(668, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 08/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 318, 0, '2026-06-02 02:01:04'),
(669, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV002 đăng ký OT ngày 08/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 319, 0, '2026-06-02 02:01:04'),
(670, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 09/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 320, 0, '2026-06-02 02:01:04'),
(671, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 09/05/2026 (4 giờ) — Tăng ca theo kế hoạch', 'ot_request', 321, 0, '2026-06-02 02:01:04'),
(672, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 322, 0, '2026-06-02 02:01:04'),
(673, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 323, 0, '2026-06-02 02:01:04'),
(674, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 324, 0, '2026-06-02 02:01:04'),
(675, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 05/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 325, 0, '2026-06-02 02:01:04'),
(676, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 326, 0, '2026-06-02 02:01:04'),
(677, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 327, 0, '2026-06-02 02:01:04'),
(678, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 328, 0, '2026-06-02 02:01:04'),
(679, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV006 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 329, 0, '2026-06-02 02:01:04'),
(680, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 330, 0, '2026-06-02 02:01:04'),
(681, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 06/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 331, 0, '2026-06-02 02:01:04'),
(682, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 07/05/2026 (3.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 332, 0, '2026-06-02 02:01:04'),
(683, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 14/05/2026 (0.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 333, 0, '2026-06-02 02:01:04'),
(684, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 18/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 334, 0, '2026-06-02 02:01:04'),
(685, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 18/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 335, 0, '2026-06-02 02:01:04'),
(686, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 19/05/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 336, 0, '2026-06-02 02:01:04'),
(687, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 19/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 337, 0, '2026-06-02 02:01:04'),
(688, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 19/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 338, 0, '2026-06-02 02:01:04'),
(689, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 20/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 339, 0, '2026-06-02 02:01:04'),
(690, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 20/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 340, 0, '2026-06-02 02:01:04'),
(691, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 21/05/2026 (6 giờ) — Tăng ca theo kế hoạch', 'ot_request', 341, 0, '2026-06-02 02:01:04'),
(692, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 21/05/2026 (6 giờ) — Tăng ca theo kế hoạch', 'ot_request', 342, 0, '2026-06-02 02:01:04'),
(693, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV005 đăng ký OT ngày 21/05/2026 (6 giờ) — Tăng ca theo kế hoạch', 'ot_request', 343, 0, '2026-06-02 02:01:04'),
(694, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 21/05/2026 (6 giờ) — Tăng ca theo kế hoạch', 'ot_request', 344, 0, '2026-06-02 02:01:04'),
(695, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 21/05/2026 (6 giờ) — Tăng ca theo kế hoạch', 'ot_request', 345, 0, '2026-06-02 02:01:04'),
(696, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 23/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 346, 0, '2026-06-02 02:01:04');
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
(697, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 23/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 347, 0, '2026-06-02 02:01:04'),
(698, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 25/05/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 348, 0, '2026-06-02 02:01:04'),
(699, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 25/05/2026 (1 giờ) — Tăng ca theo kế hoạch', 'ot_request', 349, 0, '2026-06-02 02:01:04'),
(700, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 25/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 350, 0, '2026-06-02 02:01:04'),
(701, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 25/05/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 351, 0, '2026-06-02 02:01:04'),
(702, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 27/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 352, 0, '2026-06-02 02:01:04'),
(703, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 27/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 353, 0, '2026-06-02 02:01:04'),
(704, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV002 đăng ký OT ngày 29/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 354, 0, '2026-06-02 02:01:04'),
(705, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 29/05/2026 (2 giờ) — Tăng ca theo kế hoạch', 'ot_request', 355, 0, '2026-06-02 02:01:04'),
(706, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 29/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 355, 0, '2026-06-02 02:03:28'),
(707, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 29/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 354, 0, '2026-06-02 02:03:28'),
(708, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 352, 0, '2026-06-02 02:03:28'),
(709, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 27/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 353, 0, '2026-06-02 02:03:28'),
(710, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/05/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 348, 0, '2026-06-02 02:03:28'),
(711, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/05/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 349, 0, '2026-06-02 02:03:28'),
(712, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 350, 0, '2026-06-02 02:03:28'),
(713, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 25/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 351, 0, '2026-06-02 02:03:28'),
(714, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 346, 0, '2026-06-02 02:03:28'),
(715, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 23/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 347, 0, '2026-06-02 02:03:28'),
(716, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/05/2026 (6.00 giờ) đã được duyệt.', 'ot_approved', 341, 0, '2026-06-02 02:03:28'),
(717, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/05/2026 (6.00 giờ) đã được duyệt.', 'ot_approved', 343, 0, '2026-06-02 02:03:28'),
(718, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/05/2026 (6.00 giờ) đã được duyệt.', 'ot_approved', 342, 0, '2026-06-02 02:03:28'),
(719, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/05/2026 (6.00 giờ) đã được duyệt.', 'ot_approved', 344, 0, '2026-06-02 02:03:28'),
(720, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 21/05/2026 (6.00 giờ) đã được duyệt.', 'ot_approved', 345, 0, '2026-06-02 02:03:28'),
(721, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 339, 0, '2026-06-02 02:03:28'),
(722, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 20/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 340, 0, '2026-06-02 02:03:28'),
(723, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (1.00 giờ) đã được duyệt.', 'ot_approved', 336, 0, '2026-06-02 02:03:28'),
(724, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 337, 0, '2026-06-02 02:03:28'),
(725, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 19/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 338, 0, '2026-06-02 02:03:28'),
(726, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 334, 0, '2026-06-02 02:03:28'),
(727, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 18/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 335, 0, '2026-06-02 02:03:28'),
(728, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 14/05/2026 (0.50 giờ) đã được duyệt.', 'ot_approved', 333, 0, '2026-06-02 02:03:28'),
(729, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/05/2026 (4.00 giờ) đã được duyệt.', 'ot_approved', 321, 0, '2026-06-02 02:03:28'),
(730, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 09/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 320, 0, '2026-06-02 02:03:28'),
(731, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/05/2026 (2.00 giờ) đã được duyệt.', 'ot_approved', 318, 0, '2026-06-02 02:03:28'),
(732, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 08/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 319, 0, '2026-06-02 02:03:28'),
(733, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 07/05/2026 (3.50 giờ) đã được duyệt.', 'ot_approved', 332, 0, '2026-06-02 02:03:28'),
(734, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 326, 0, '2026-06-02 02:03:28'),
(735, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 327, 0, '2026-06-02 02:03:28'),
(736, 6, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 329, 0, '2026-06-02 02:03:28'),
(737, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 328, 0, '2026-06-02 02:03:28'),
(738, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 330, 0, '2026-06-02 02:03:28'),
(739, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 06/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 331, 0, '2026-06-02 02:03:28'),
(740, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 322, 0, '2026-06-02 02:03:28'),
(741, 7, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 323, 0, '2026-06-02 02:03:28'),
(742, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 324, 0, '2026-06-02 02:03:28'),
(743, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 05/05/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 325, 0, '2026-06-02 02:03:28'),
(744, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV002 đăng ký OT ngày 10/05/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 356, 0, '2026-06-04 06:55:27'),
(745, 9, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/05/2026 (16:00:00–00:00:00, 8.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 356, 0, '2026-06-04 07:07:59'),
(746, 1, '📋 Đơn đăng ký OT mới', 'Lương Văn Tá đăng ký OT ngày 04/06/2026 (16:00 – 19:00, 3 giờ)', 'ot_request', 357, 0, '2026-06-04 08:14:52'),
(747, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 04/06/2026 (16:00:00–19:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 357, 0, '2026-06-04 08:16:39'),
(748, 1, '📋 Đơn đăng ký OT mới', 'Lục Văn Thủy đăng ký OT ngày 04/06/2026 (16:00 – 19:00, 3 giờ)', 'ot_request', 358, 0, '2026-06-04 08:21:14'),
(749, 1, '📋 Đơn đăng ký OT mới', 'Hoàng Văn Ngôn đăng ký OT ngày 04/06/2026 (16:00 – 19:00, 3 giờ)', 'ot_request', 359, 0, '2026-06-04 08:21:52'),
(750, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 04/06/2026 (16:00:00–19:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 358, 0, '2026-06-04 08:27:26'),
(751, 17, '✅ Đơn OT được duyệt', 'Đơn OT ngày 04/06/2026 (16:00:00–19:00:00, 3.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 359, 0, '2026-06-04 08:27:28'),
(752, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV001 đăng ký OT ngày 01/06/2026 (3.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 360, 0, '2026-06-04 08:27:48'),
(753, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 01/06/2026 (3.5 giờ) — Tăng ca theo kế hoạch', 'ot_request', 361, 0, '2026-06-04 08:27:48'),
(754, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 02/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 362, 0, '2026-06-04 08:27:48'),
(755, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 02/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 363, 0, '2026-06-04 08:27:48'),
(756, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV014 đăng ký OT ngày 02/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 364, 0, '2026-06-04 08:27:48'),
(757, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 03/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 365, 0, '2026-06-04 08:27:48'),
(758, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 03/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 366, 0, '2026-06-04 08:27:48'),
(759, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV014 đăng ký OT ngày 03/06/2026 (3 giờ) — Tăng ca theo kế hoạch', 'ot_request', 367, 0, '2026-06-04 08:27:48'),
(760, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 03/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 365, 0, '2026-06-04 08:28:19'),
(761, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 03/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 366, 0, '2026-06-04 08:28:19'),
(762, 17, '✅ Đơn OT được duyệt', 'Đơn OT ngày 03/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 367, 0, '2026-06-04 08:28:19'),
(763, 12, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 362, 0, '2026-06-04 08:28:19'),
(764, 15, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 363, 0, '2026-06-04 08:28:19'),
(765, 17, '✅ Đơn OT được duyệt', 'Đơn OT ngày 02/06/2026 (3.00 giờ) đã được duyệt.', 'ot_approved', 364, 0, '2026-06-04 08:28:19'),
(766, 4, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/06/2026 (3.50 giờ) đã được duyệt.', 'ot_approved', 360, 0, '2026-06-04 08:28:19'),
(767, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 01/06/2026 (3.50 giờ) đã được duyệt.', 'ot_approved', 361, 0, '2026-06-04 08:28:19'),
(768, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV008 đăng ký OT ngày 23/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 368, 0, '2026-06-04 08:43:39'),
(769, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV012 đăng ký OT ngày 23/05/2026 (3 giờ) — Xử lý đơn hàng khẩn', 'ot_request', 369, 0, '2026-06-04 08:43:39'),
(770, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV003 đăng ký OT ngày 10/05/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 370, 0, '2026-06-04 08:43:39'),
(771, 1, '📋 Đơn đăng ký OT mới (Import)', 'NV004 đăng ký OT ngày 10/05/2026 (8 giờ) — Tăng ca theo kế hoạch', 'ot_request', 371, 0, '2026-06-04 08:43:39'),
(772, 8, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/05/2026 (16:00:00–00:00:00, 8.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 371, 0, '2026-06-04 08:46:03'),
(773, 5, '✅ Đơn OT được duyệt', 'Đơn OT ngày 10/05/2026 (16:00:00–00:00:00, 8.00 giờ) đã được duyệt bởi Đào Ngọc Minh Nam', 'ot_approved', 370, 0, '2026-06-04 08:46:28'),
(774, 1, '📋 Đơn đăng ký OT mới', 'Hoàng Thị Xuân Mai đăng ký OT ngày 08/06/2026 (16:00 – 17:30, 1.5 giờ)', 'ot_request', 372, 0, '2026-06-08 10:19:34'),
(775, 1, '📋 Đơn đăng ký OT mới', 'Đặng Thị Minh đăng ký OT ngày 08/06/2026 (16:00 – 17:00, 1 giờ)', 'ot_request', 373, 0, '2026-06-08 10:20:19'),
(776, 1, '📋 Đơn đăng ký OT mới', 'Lương Văn Tá đăng ký OT ngày 08/06/2026 (16:00 – 19:00, 3 giờ)', 'ot_request', 374, 0, '2026-06-08 10:21:04'),
(777, 1, '📋 Đơn đăng ký OT mới', 'Hoàng Văn Ngôn đăng ký OT ngày 08/06/2026 (16:00 – 19:00, 3 giờ)', 'ot_request', 375, 0, '2026-06-08 10:21:42'),
(778, 1, 'Đơn xin nghỉ phép mới', 'Đặng Thị Minh xin nghỉ từ 30/06/2026 đến 30/06/2026', 'leave_request', 4, 0, '2026-06-29 23:50:17'),
(779, 18, 'Đơn xin nghỉ phép mới', 'Đặng Thị Minh xin nghỉ từ 30/06/2026 đến 30/06/2026', 'leave_request', 778, 0, '2026-06-29 23:50:17'),
(780, 3, 'Đơn xin nghỉ phép mới', 'Đặng Thị Minh xin nghỉ từ 30/06/2026 đến 30/06/2026', 'leave_request', 779, 0, '2026-06-29 23:50:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `ot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hours` decimal(4,2) NOT NULL,
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `reason` text NOT NULL,
  `ot_type` enum('weekday','weekend','holiday') DEFAULT 'weekday',
  `shift_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `user_id`, `department_id`, `ot_date`, `start_time`, `end_time`, `hours`, `actual_hours`, `reason`, `ot_type`, `shift_id`, `status`, `approved_by`, `approved_at`, `reject_reason`, `created_at`) VALUES
(318, 4, NULL, '2026-05-08', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(320, 5, NULL, '2026-05-09', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekend', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(321, 4, NULL, '2026-05-09', '16:00:00', '20:00:00', 4.00, NULL, 'Tăng ca theo kế hoạch', 'weekend', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(322, 5, NULL, '2026-05-05', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(323, 7, NULL, '2026-05-05', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(324, 12, NULL, '2026-05-05', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(325, 15, NULL, '2026-05-05', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(326, 4, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(327, 5, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(328, 7, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(329, 6, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(330, 12, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(331, 15, NULL, '2026-05-06', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(332, 4, NULL, '2026-05-07', '16:00:00', '19:30:00', 3.50, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(333, 4, NULL, '2026-05-14', '16:00:00', '16:30:00', 0.50, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(334, 12, NULL, '2026-05-18', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(335, 15, NULL, '2026-05-18', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(336, 8, NULL, '2026-05-19', '16:00:00', '17:00:00', 1.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(337, 12, NULL, '2026-05-19', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(338, 15, NULL, '2026-05-19', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(339, 12, NULL, '2026-05-20', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(340, 15, NULL, '2026-05-20', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(341, 4, NULL, '2026-05-21', '16:00:00', '22:00:00', 6.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(342, 8, NULL, '2026-05-21', '16:00:00', '22:00:00', 6.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(343, 7, NULL, '2026-05-21', '16:00:00', '22:00:00', 6.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(344, 12, NULL, '2026-05-21', '16:00:00', '22:00:00', 6.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(345, 15, NULL, '2026-05-21', '16:00:00', '22:00:00', 6.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(348, 4, NULL, '2026-05-25', '16:00:00', '17:00:00', 1.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(349, 8, NULL, '2026-05-25', '16:00:00', '17:00:00', 1.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(350, 12, NULL, '2026-05-25', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(351, 15, NULL, '2026-05-25', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(352, 12, NULL, '2026-05-27', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(353, 15, NULL, '2026-05-27', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(354, 9, NULL, '2026-05-29', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(355, 8, NULL, '2026-05-29', '16:00:00', '18:00:00', 2.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-02 09:03:28', NULL, '2026-06-02 02:01:04'),
(356, 9, NULL, '2026-05-10', '16:00:00', '00:00:00', 8.00, NULL, 'Tăng ca theo kế hoạch', 'weekend', 1, 'approved', 1, '2026-06-04 14:07:59', NULL, '2026-06-04 06:55:27'),
(360, 4, NULL, '2026-06-01', '16:00:00', '19:30:00', 3.50, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(361, 8, NULL, '2026-06-01', '16:00:00', '19:30:00', 3.50, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(362, 12, NULL, '2026-06-02', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(363, 15, NULL, '2026-06-02', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(364, 17, NULL, '2026-06-02', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(365, 12, NULL, '2026-06-03', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(366, 15, NULL, '2026-06-03', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(367, 17, NULL, '2026-06-03', '16:00:00', '19:00:00', 3.00, NULL, 'Tăng ca theo kế hoạch', 'weekday', 1, 'approved', 1, '2026-06-04 15:28:19', NULL, '2026-06-04 08:27:48'),
(368, 12, NULL, '2026-05-23', '16:00:00', '19:00:00', 3.00, NULL, 'Xử lý đơn hàng khẩn', 'weekend', 1, 'pending', NULL, NULL, NULL, '2026-06-04 08:43:39'),
(369, 15, NULL, '2026-05-23', '16:00:00', '19:00:00', 3.00, NULL, 'Xử lý đơn hàng khẩn', 'weekend', 1, 'pending', NULL, NULL, NULL, '2026-06-04 08:43:39'),
(370, 5, NULL, '2026-05-10', '16:00:00', '00:00:00', 8.00, NULL, 'Tăng ca theo kế hoạch', 'weekend', 1, 'approved', 1, '2026-06-04 15:46:28', NULL, '2026-06-04 08:43:39'),
(371, 8, NULL, '2026-05-10', '16:00:00', '00:00:00', 8.00, NULL, 'Tăng ca theo kế hoạch', 'weekend', 1, 'approved', 1, '2026-06-04 15:46:03', NULL, '2026-06-04 08:43:39'),
(372, 4, NULL, '2026-06-08', '16:00:00', '17:30:00', 1.50, NULL, 'Xử lí hàng', 'weekday', 1, 'rejected', NULL, NULL, 'Nhân viên tự huỷ', '2026-06-08 10:19:34'),
(373, 9, NULL, '2026-06-08', '16:00:00', '17:00:00', 1.00, NULL, 'Xử lí hàng', 'weekday', 1, 'rejected', NULL, NULL, 'Nhân viên tự huỷ', '2026-06-08 10:20:19'),
(374, 12, NULL, '2026-06-08', '16:00:00', '19:00:00', 3.00, NULL, 'Xử lí đơn hàng khẩn', 'weekday', 1, 'rejected', NULL, NULL, 'Nhân viên tự huỷ', '2026-06-08 10:21:04'),
(375, 17, NULL, '2026-06-08', '16:00:00', '19:00:00', 3.00, NULL, 'Xử lí đơn hàng khẩn', 'weekday', 1, 'rejected', NULL, NULL, 'Nhân viên tự huỷ', '2026-06-08 10:21:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','transfer','check') DEFAULT 'cash',
  `note` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL,
  `period_year` smallint(6) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `working_days` tinyint(4) NOT NULL,
  `status` enum('draft','submitted','approved','locked') DEFAULT 'draft',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `period_year`, `period_month`, `period_from`, `period_to`, `working_days`, `status`, `note`, `created_by`, `submitted_at`, `submitted_by`, `approved_at`, `approved_by`, `locked_at`, `locked_by`, `created_at`, `updated_at`) VALUES
(8, 2026, 5, '2026-05-01', '2026-05-31', 26, 'approved', NULL, 1, '2026-06-04 13:11:45', 1, '2026-06-04 13:11:49', 1, NULL, NULL, '2026-06-04 03:57:01', '2026-06-04 06:11:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payroll_slips`
--

CREATE TABLE `payroll_slips` (
  `id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `basic_salary` decimal(15,0) DEFAULT 0,
  `working_days_standard` tinyint(4) DEFAULT 0,
  `salary_per_day` decimal(10,0) DEFAULT 0,
  `salary_per_hour` decimal(10,0) DEFAULT 0,
  `actual_workdays` decimal(5,2) DEFAULT 0.00,
  `paid_leave_days` decimal(5,2) DEFAULT 0.00,
  `other_paid_leave_days` decimal(5,2) DEFAULT 0.00,
  `unpaid_leave_days` decimal(5,2) DEFAULT 0.00,
  `late_early_hours` decimal(5,2) DEFAULT 0.00,
  `late_early_deduction` decimal(15,0) DEFAULT 0,
  `total_paid_days` decimal(5,2) DEFAULT 0.00,
  `basic_salary_received` decimal(15,0) DEFAULT 0,
  `meal_allowance` decimal(15,0) DEFAULT 0,
  `meal_received` decimal(15,0) DEFAULT 0,
  `clothes_allowance` decimal(15,0) DEFAULT 0,
  `clothes_received` decimal(15,0) DEFAULT 0,
  `phone_allowance` decimal(15,0) DEFAULT 0,
  `phone_received` decimal(15,0) DEFAULT 0,
  `transport_allowance` decimal(15,0) DEFAULT 0,
  `housing_allowance` int(11) NOT NULL DEFAULT 0,
  `transport_received` decimal(15,0) DEFAULT 0,
  `housing_received` int(11) NOT NULL DEFAULT 0,
  `performance_bonus` decimal(15,0) DEFAULT 0,
  `basic_salary_per_hour` decimal(10,0) DEFAULT 0,
  `ot_weekday_hours` decimal(5,2) DEFAULT 0.00,
  `ot_weekend_hours` decimal(5,2) DEFAULT 0.00,
  `ot_holiday_hours` decimal(5,2) DEFAULT 0.00,
  `ot_weekday_amount` decimal(15,0) DEFAULT 0,
  `ot_weekend_amount` decimal(15,0) DEFAULT 0,
  `ot_holiday_amount` decimal(15,0) DEFAULT 0,
  `total_ot_amount` decimal(15,0) DEFAULT 0,
  `ot_meal_days` int(11) NOT NULL DEFAULT 0,
  `ot_meal_bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kpi_bonus` decimal(15,0) NOT NULL DEFAULT 0 COMMENT 'Thưởng KPI vượt mục tiêu',
  `kpi_over_days` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Số ngày vượt KPI',
  `kpi_under_days` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Số ngày KPI bị trừ lương',
  `annual_leave_total` tinyint(4) DEFAULT 0,
  `annual_leave_used` decimal(5,2) DEFAULT 0.00,
  `annual_leave_remaining` decimal(5,2) DEFAULT 0.00,
  `annual_leave_payout` decimal(15,0) DEFAULT 0,
  `other_income` decimal(15,0) DEFAULT 0,
  `adjustment` decimal(15,0) DEFAULT 0,
  `other_bonus` decimal(15,0) DEFAULT 0,
  `attendance_bonus` decimal(15,0) DEFAULT 0,
  `attendance_bonus_eligible` tinyint(4) DEFAULT 0,
  `has_social_insurance` tinyint(4) DEFAULT 0,
  `si_employee` decimal(15,0) DEFAULT 0,
  `si_company` decimal(15,0) DEFAULT 0,
  `dependants` tinyint(4) DEFAULT 0,
  `personal_deduction` decimal(15,0) DEFAULT 15500000,
  `dependant_deduction` decimal(15,0) DEFAULT 0,
  `ot_exclude_pit` decimal(15,0) DEFAULT 0,
  `taxable_income` decimal(15,0) DEFAULT 0,
  `pit_amount` decimal(15,0) DEFAULT 0,
  `late_deduction` decimal(15,0) DEFAULT 0,
  `kpi_deduction` decimal(15,0) DEFAULT 0,
  `gross_salary` decimal(15,0) DEFAULT 0,
  `advance_payment` decimal(15,0) DEFAULT 0,
  `net_salary` decimal(15,0) DEFAULT 0,
  `pit_adjustment` decimal(15,0) DEFAULT 0,
  `bank_transfer` decimal(15,0) DEFAULT 0,
  `remark` text DEFAULT NULL,
  `is_late_warning` tinyint(4) DEFAULT 0,
  `late_warning_note` text DEFAULT NULL,
  `manually_adjusted` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payroll_slips`
--

INSERT INTO `payroll_slips` (`id`, `period_id`, `user_id`, `basic_salary`, `working_days_standard`, `salary_per_day`, `salary_per_hour`, `actual_workdays`, `paid_leave_days`, `other_paid_leave_days`, `unpaid_leave_days`, `late_early_hours`, `late_early_deduction`, `total_paid_days`, `basic_salary_received`, `meal_allowance`, `meal_received`, `clothes_allowance`, `clothes_received`, `phone_allowance`, `phone_received`, `transport_allowance`, `housing_allowance`, `transport_received`, `housing_received`, `performance_bonus`, `basic_salary_per_hour`, `ot_weekday_hours`, `ot_weekend_hours`, `ot_holiday_hours`, `ot_weekday_amount`, `ot_weekend_amount`, `ot_holiday_amount`, `total_ot_amount`, `ot_meal_days`, `ot_meal_bonus`, `kpi_bonus`, `kpi_over_days`, `kpi_under_days`, `annual_leave_total`, `annual_leave_used`, `annual_leave_remaining`, `annual_leave_payout`, `other_income`, `adjustment`, `other_bonus`, `attendance_bonus`, `attendance_bonus_eligible`, `has_social_insurance`, `si_employee`, `si_company`, `dependants`, `personal_deduction`, `dependant_deduction`, `ot_exclude_pit`, `taxable_income`, `pit_amount`, `late_deduction`, `kpi_deduction`, `gross_salary`, `advance_payment`, `net_salary`, `pit_adjustment`, `bank_transfer`, `remark`, `is_late_warning`, `late_warning_note`, `manually_adjusted`, `created_at`, `updated_at`) VALUES
(56, 8, 9, 5310000, 26, 249231, 31154, 17.00, 0.00, 0.00, 0.00, 2.00, 62308, 18.00, 3676154, 0, 0, 0, 0, 0, 0, 390000, 780000, 270000, 540000, 0, 25529, 2.00, 8.00, 0.00, 76587, 408464, 0, 485051, 0, 0.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 0, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 62308, 0, 4971205, 0, 4351347, 0, 4351347, 'Trừ muộn/sớm: -62,308 đ (về sớm: 120p); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 1, '%d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 07:23:52'),
(57, 8, 1, 0, 26, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 1.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Nghỉ lễ: 1 ngày (hưởng lương)', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(58, 8, 5, 5310000, 26, 249231, 31154, 22.00, 0.00, 0.00, 0.00, 3.50, 109039, 23.00, 4697308, 0, 28000, 0, 0, 0, 0, 390000, 780000, 345000, 690000, 0, 25529, 6.00, 10.00, 0.00, 229761, 510580, 0, 740341, 2, 28000.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 0, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 109039, 0, 6500649, 0, 5834060, 0, 5834060, 'Ăn ca OT: +28,000 đ (2 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -109,039 đ (trễ: 210p); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 1, '%d/%m(T), %d/%m(T)', 0, '2026-06-04 03:57:07', '2026-06-04 08:46:33'),
(59, 8, 4, 5310000, 26, 278869, 34859, 22.00, 0.00, 0.00, 0.00, 0.00, 0, 23.00, 4697308, 0, 56000, 0, 0, 690000, 610385, 470600, 780000, 416300, 690000, 0, 25529, 16.00, 4.00, 0.00, 612696, 204232, 0, 816928, 4, 56000.00, 0, 0, 0, 7, 0.00, 7.00, 0, 0, 0, 0, 0, 0, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 0, 0, 7286921, 0, 6729371, 0, 6729371, 'Ăn ca OT: +56,000 đ (4 ngày OT ≥ 3h × 14,000 đ); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(60, 8, 17, 5310000, 26, 249231, 31154, 3.00, 0.00, 0.00, 0.00, 0.00, 0, 3.00, 612692, 0, 0, 0, 0, 0, 0, 390000, 780000, 45000, 90000, 0, 25529, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0.00, 0, 0, 0, 5, 0.00, 5.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 0, 0, 747692, 0, 747692, 0, 747692, '', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 06:20:52'),
(61, 8, 14, 5310000, 26, 249231, 31154, 5.00, 0.00, 0.00, 0.00, 0.00, 0, 6.00, 1225385, 0, 0, 0, 0, 0, 0, 390000, 780000, 90000, 180000, 0, 25529, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 0, 0, 1495385, 0, 1495385, 0, 1495385, 'Nghỉ lễ: 1 ngày (hưởng lương)', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(62, 8, 8, 5310000, 26, 249231, 31154, 22.00, 0.00, 0.00, 0.00, 2.00, 62308, 23.00, 4697308, 0, 14000, 0, 0, 0, 0, 390000, 780000, 345000, 690000, 0, 25529, 10.00, 8.00, 0.00, 382935, 408464, 0, 791399, 1, 14000.00, 0, 0, 0, 8, 0.00, 8.00, 0, 0, 0, 0, 0, 0, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 62308, 0, 6537707, 0, 5917849, 0, 5917849, 'Ăn ca OT: +14,000 đ (1 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -62,308 đ (về sớm: 120p); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 1, '%d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 08:46:33'),
(63, 8, 15, 5310000, 26, 249231, 31154, 24.00, 0.00, 0.00, 0.00, 2.00, 62308, 24.00, 4901538, 0, 98000, 0, 0, 390000, 360000, 0, 780000, 0, 720000, 0, 25529, 26.00, 0.00, 0.00, 995631, 0, 0, 995631, 7, 98000.00, 0, 0, 0, 5, 0.00, 5.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 62308, 0, 7075169, 0, 7012861, 0, 7012861, 'Ăn ca OT: +98,000 đ (7 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -62,308 đ (về sớm: 120p)', 1, '%d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 08:40:25'),
(64, 8, 16, 0, 26, 0, 0, 4.00, 0.00, 0.00, 0.00, 0.00, 0, 4.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0.00, 0, 0, 0, 5, 0.00, 5.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(65, 8, 12, 5310000, 26, 271177, 33897, 25.00, 0.00, 0.00, 0.00, 5.00, 169485, 26.00, 5310000, 0, 98000, 0, 0, 500000, 500000, 460600, 780000, 460600, 780000, 0, 25529, 26.00, 0.00, 0.00, 995631, 0, 0, 995631, 7, 98000.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 169485, 0, 8144231, 0, 7974746, 0, 7974746, 'Ăn ca OT: +98,000 đ (7 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -169,485 đ (trễ: 180p, về sớm: 120p); Nghỉ lễ: 1 ngày (hưởng lương)', 1, '%d/%m(T), %d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 08:40:25'),
(66, 8, 7, 5310000, 26, 271177, 33897, 25.00, 0.00, 0.00, 0.00, 2.00, 67794, 26.00, 5310000, 0, 42000, 0, 0, 500000, 500000, 460600, 780000, 460600, 780000, 0, 25529, 12.00, 0.00, 0.00, 459522, 0, 0, 459522, 3, 42000.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 1, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 67794, 0, 7552122, 0, 6926778, 0, 6926778, 'Ăn ca OT: +42,000 đ (3 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -67,794 đ (về sớm: 120p); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 1, '%d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(67, 8, 6, 5310000, 26, 271177, 33897, 24.00, 0.00, 0.00, 0.00, 2.00, 67794, 25.00, 5105769, 0, 14000, 0, 0, 500000, 480769, 460600, 780000, 442885, 750000, 0, 25529, 3.00, 0.00, 0.00, 114881, 0, 0, 114881, 1, 14000.00, 0, 0, 0, 8, 3.00, 5.00, 0, 0, 0, 0, 0, 0, 1, 557550, 1141650, 0, 15500000, 0, 0, 0, 0, 67794, 0, 6908304, 0, 6282960, 0, 6282960, 'Ăn ca OT: +14,000 đ (1 ngày OT ≥ 3h × 14,000 đ); Trừ muộn/sớm: -67,794 đ (về sớm: 120p); Nghỉ lễ: 1 ngày (hưởng lương); BHXH NV: -557,550 đ (10.5% × lương CB)', 1, '%d/%m(S)', 0, '2026-06-04 03:57:07', '2026-06-04 03:57:07'),
(68, 8, 13, 5000000, 26, 240408, 30051, 9.00, 0.00, 0.00, 0.00, 0.00, 0, 10.00, 1923077, 0, 0, 0, 0, 0, 0, 470600, 780000, 181000, 300000, 0, 24039, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0.00, 0, 0, 0, 9, 0.00, 9.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15500000, 0, 0, 0, 0, 0, 0, 2404077, 0, 2404077, 0, 2404077, 'Nghỉ lễ: 1 ngày (hưởng lương)', 0, '', 0, '2026-06-04 03:57:07', '2026-06-04 07:49:10');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `prices`
-- (See below for the actual view)
--
CREATE TABLE `prices` (
`id` int(11)
,`product_code_id` int(11)
,`unit_price` decimal(15,0)
,`effective_from` date
,`note` text
,`created_by` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `production_outputs`
--

CREATE TABLE `production_outputs` (
  `id` int(11) NOT NULL,
  `output_no` varchar(30) NOT NULL,
  `output_date` date NOT NULL,
  `production_receipt_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `quantity_completed` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity_defect` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity_delivered` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `production_outputs`
--

INSERT INTO `production_outputs` (`id`, `output_no`, `output_date`, `production_receipt_id`, `product_code_id`, `description`, `quantity_completed`, `quantity_defect`, `quantity_delivered`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 'OUT-20260311-001', '2026-03-11', 6, 1, NULL, 50000.00, 0.00, 5000.00, '', 1, '2026-03-11 15:07:17', '2026-03-11 15:07:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `production_receipts`
--

CREATE TABLE `production_receipts` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(30) NOT NULL,
  `receipt_date` date NOT NULL,
  `warehouse_import_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `quantity_received` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `production_receipts`
--

INSERT INTO `production_receipts` (`id`, `receipt_no`, `receipt_date`, `warehouse_import_id`, `product_code_id`, `description`, `quantity_received`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 'PR-20260311-0001', '2026-03-11', 4, 1, NULL, 50000.00, '', 1, '2026-03-11 15:03:09', '2026-03-11 15:03:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `production_stock`
--

CREATE TABLE `production_stock` (
  `id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `stock_date` date NOT NULL,
  `qty_pending` decimal(15,2) DEFAULT 0.00,
  `qty_completed` decimal(15,2) DEFAULT 0.00,
  `qty_defect` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `production_stock`
--

INSERT INTO `production_stock` (`id`, `product_code_id`, `stock_date`, `qty_pending`, `qty_completed`, `qty_defect`, `updated_at`) VALUES
(2, 1, '2026-03-11', 0.00, 45000.00, 0.00, '2026-03-11 15:07:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_codes`
--

CREATE TABLE `product_codes` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_category` enum('finished_goods','raw_material','consumable','office','equipment','other') NOT NULL DEFAULT 'finished_goods',
  `description` varchar(500) NOT NULL,
  `unit` varchar(20) DEFAULT 'cái',
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_codes`
--

INSERT INTO `product_codes` (`id`, `product_code`, `product_category`, `description`, `unit`, `category`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '123621', 'finished_goods', 'Phí gia công mài phun cát cho sản phẩm nhôm mã 123621', 'cái', 'Thành Phẩm', 1, 1, '2026-03-10 17:10:18', '2026-04-29 07:20:49'),
(2, '122987', 'finished_goods', 'Phí gia công mài phun cát cho sản phẩm nhôm mã 122987', 'cái', 'Thành Phẩm', 1, 2, '2026-04-29 07:20:40', '2026-04-29 07:21:14'),
(3, '122988', 'finished_goods', 'Phí gia công mài phun cát cho sản phẩm nhôm mã 122988', 'cái', 'Thành Phẩm', 1, 2, '2026-04-29 07:21:05', '2026-04-29 07:21:10'),
(4, 'SP-01', 'finished_goods', 'PHÍ DỊCH VỤ GIAO NHẬN HÀNG HOÁ', 'chiếc', NULL, 1, 2, '2026-06-28 17:20:40', '2026-06-28 17:20:40'),
(5, '12938', 'finished_goods', 'Phun Mài', 'Cái', NULL, 1, 18, '2026-06-30 05:06:17', '2026-06-30 05:06:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_prices`
--

CREATE TABLE `product_prices` (
  `id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `unit_price` decimal(15,0) NOT NULL DEFAULT 0,
  `effective_from` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_prices`
--

INSERT INTO `product_prices` (`id`, `product_code_id`, `unit_price`, `effective_from`, `note`, `created_by`, `created_at`) VALUES
(1, 1, 4300, '2026-04-29', NULL, 2, '2026-04-29 07:21:46'),
(2, 2, 3460, '2026-04-29', NULL, 2, '2026-04-29 07:21:59'),
(3, 3, 4250, '2026-04-29', NULL, 2, '2026-04-29 07:22:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `provinces`
--

CREATE TABLE `provinces` (
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `provinces`
--

INSERT INTO `provinces` (`code`, `name`, `name_en`, `full_name`) VALUES
('01', 'Hà Nội', NULL, 'Thành phố Hà Nội'),
('02', 'Hà Giang', NULL, 'Tỉnh Hà Giang'),
('04', 'Cao Bằng', NULL, 'Tỉnh Cao Bằng'),
('06', 'Bắc Kạn', NULL, 'Tỉnh Bắc Kạn'),
('08', 'Tuyên Quang', NULL, 'Tỉnh Tuyên Quang'),
('10', 'Lào Cai', NULL, 'Tỉnh Lào Cai'),
('11', 'Điện Biên', NULL, 'Tỉnh Điện Biên'),
('12', 'Lai Châu', NULL, 'Tỉnh Lai Châu'),
('14', 'Sơn La', NULL, 'Tỉnh Sơn La'),
('15', 'Yên Bái', NULL, 'Tỉnh Yên Bái'),
('17', 'Hoà Bình', NULL, 'Tỉnh Hoà Bình'),
('19', 'Thái Nguyên', NULL, 'Tỉnh Thái Nguyên'),
('20', 'Lạng Sơn', NULL, 'Tỉnh Lạng Sơn'),
('22', 'Quảng Ninh', NULL, 'Tỉnh Quảng Ninh'),
('24', 'Bắc Giang', NULL, 'Tỉnh Bắc Giang'),
('25', 'Phú Thọ', NULL, 'Tỉnh Phú Thọ'),
('26', 'Vĩnh Phúc', NULL, 'Tỉnh Vĩnh Phúc'),
('27', 'Bắc Ninh', NULL, 'Tỉnh Bắc Ninh'),
('30', 'Hải Dương', NULL, 'Tỉnh Hải Dương'),
('31', 'Hải Phòng', NULL, 'Thành phố Hải Phòng'),
('33', 'Hưng Yên', NULL, 'Tỉnh Hưng Yên'),
('34', 'Thái Bình', NULL, 'Tỉnh Thái Bình'),
('35', 'Hà Nam', NULL, 'Tỉnh Hà Nam'),
('36', 'Nam Định', NULL, 'Tỉnh Nam Định'),
('37', 'Ninh Bình', NULL, 'Tỉnh Ninh Bình'),
('38', 'Thanh Hóa', NULL, 'Tỉnh Thanh Hóa'),
('40', 'Nghệ An', NULL, 'Tỉnh Nghệ An'),
('42', 'Hà Tĩnh', NULL, 'Tỉnh Hà Tĩnh'),
('44', 'Quảng Bình', NULL, 'Tỉnh Quảng Bình'),
('45', 'Quảng Trị', NULL, 'Tỉnh Quảng Trị'),
('46', 'Thừa Thiên Huế', NULL, 'Tỉnh Thừa Thiên Huế'),
('48', 'Đà Nẵng', NULL, 'Thành phố Đà Nẵng'),
('49', 'Quảng Nam', NULL, 'T��nh Quảng Nam'),
('51', 'Quảng Ngãi', NULL, 'Tỉnh Quảng Ngãi'),
('52', 'Bình Định', NULL, 'Tỉnh Bình Định'),
('54', 'Phú Yên', NULL, 'Tỉnh Phú Yên'),
('56', 'Khánh Hòa', NULL, 'Tỉnh Khánh Hòa'),
('58', 'Ninh Thuận', NULL, 'Tỉnh Ninh Thuận'),
('60', 'Bình Thuận', NULL, 'Tỉnh Bình Thuận'),
('62', 'Kon Tum', NULL, 'Tỉnh Kon Tum'),
('64', 'Gia Lai', NULL, 'Tỉnh Gia Lai'),
('66', 'Đắk Lắk', NULL, 'Tỉnh Đắk Lắk'),
('67', 'Đắk Nông', NULL, 'Tỉnh Đắk Nông'),
('68', 'Lâm Đồng', NULL, 'Tỉnh Lâm Đồng'),
('70', 'Bình Phước', NULL, 'Tỉnh Bình Phước'),
('72', 'Tây Ninh', NULL, 'Tỉnh Tây Ninh'),
('74', 'Bình Dương', NULL, 'Tỉnh Bình Dương'),
('75', 'Đồng Nai', NULL, 'Tỉnh Đồng Nai'),
('77', 'Bà Rịa - Vũng Tàu', NULL, 'Tỉnh Bà Rịa - Vũng Tàu'),
('79', 'Hồ Chí Minh', NULL, 'Thành phố Hồ Chí Minh'),
('80', 'Long An', NULL, 'Tỉnh Long An'),
('82', 'Tiền Giang', NULL, 'Tỉnh Tiền Giang'),
('83', 'Bến Tre', NULL, 'Tỉnh Bến Tre'),
('84', 'Trà Vinh', NULL, 'Tỉnh Trà Vinh'),
('86', 'Vĩnh Long', NULL, 'Tỉnh Vĩnh Long'),
('87', 'Đồng Tháp', NULL, 'Tỉnh Đồng Tháp'),
('89', 'An Giang', NULL, 'Tỉnh An Giang'),
('91', 'Kiên Giang', NULL, 'Tỉnh Kiên Giang'),
('92', 'Cần Thơ', NULL, 'Thành phố Cần Thơ'),
('93', 'Hậu Giang', NULL, 'Tỉnh Hậu Giang'),
('94', 'Sóc Trăng', NULL, 'Tỉnh Sóc Trăng'),
('95', 'Bạc Liêu', NULL, 'Tỉnh Bạc Liêu'),
('96', 'Cà Mau', NULL, 'Tỉnh Cà Mau');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`) VALUES
(1, 'director', 'Giám đốc'),
(2, 'accountant', 'Kế toán'),
(3, 'manager', 'Quản lý'),
(4, 'warehouse', 'Quản lý Kho'),
(5, 'production', 'Quản lý Sản xuất'),
(6, 'employee', 'Nhân viên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `salary_components`
--

CREATE TABLE `salary_components` (
  `id` int(11) NOT NULL,
  `component_code` varchar(30) NOT NULL,
  `component_name` varchar(150) NOT NULL,
  `component_name_en` varchar(150) DEFAULT NULL,
  `component_type` enum('earning','deduction','bonus') DEFAULT 'earning',
  `is_default` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `salary_components`
--

INSERT INTO `salary_components` (`id`, `component_code`, `component_name`, `component_name_en`, `component_type`, `is_default`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'basic', 'Lương cơ bản', 'Basic salary', 'earning', 1, 1, 1, '2026-03-10 12:14:03'),
(2, 'meal', 'Trợ cấp ăn uống', 'Meal allowance', 'earning', 1, 2, 1, '2026-03-10 12:14:03'),
(3, 'clothes', 'Trợ cấp trang phục', 'Clothes allowance', 'earning', 1, 3, 1, '2026-03-10 12:14:03'),
(4, 'phone', 'Trợ cấp điện thoại', 'Mobile allowance', 'earning', 1, 4, 1, '2026-03-10 12:14:03'),
(5, 'transport', 'Trợ cấp xăng xe, đi lại', 'Gas - travelling allowance', 'earning', 1, 5, 1, '2026-03-10 12:14:03'),
(6, 'performance', 'Thưởng hiệu quả công việc', 'Job effectiveness bonus', 'bonus', 1, 6, 1, '2026-03-10 12:14:03'),
(7, 'housing', 'Trợ cấp nhà ở', 'Housing allowance', 'earning', 0, 7, 1, '2026-03-10 12:14:03'),
(8, 'responsibility', 'Phụ cấp trách nhiệm', 'Responsibility allowance', 'earning', 0, 8, 1, '2026-03-10 12:14:03'),
(9, 'seniority', 'Phụ cấp thâm niên', 'Seniority allowance', 'earning', 0, 9, 1, '2026-03-10 12:14:03'),
(10, 'hazard', 'Phụ cấp độc hại, nguy hiểm', 'Hazard allowance', 'earning', 0, 10, 1, '2026-03-10 12:14:03'),
(11, 'attendance_bonus', 'Chuyên Cần', 'Attendance Bonus', 'bonus', 1, 99, 1, '2026-03-11 04:26:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shift_schedules`
--

CREATE TABLE `shift_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `employee_code`, `full_name`, `username`, `password_hash`, `email`, `phone`, `role_id`, `department_id`, `is_active`, `created_at`) VALUES
(1, 'GĐ', 'Đào Ngọc Minh Nam', 'giamdoc', '$2y$10$04KJryznjXI9jh7nj9vzXOh3ijL6amMIryZVqyRS8usjOwrWrZjwW', '', '', 1, 1, 1, '2026-03-10 06:57:59'),
(2, 'KT', 'Nguyễn Thị Vân Anh', 'ketoan', '$2y$10$emEr4RfAi1A..b3yVlpVq.mw83V9fV8Lgd3qHaAuFFxc5yzDIaltS', 'vananhnt1992@gmail.com', '0961334192', 2, 2, 0, '2026-03-10 06:57:59'),
(3, 'QL', 'Lương Văn Nghĩa', 'quanly', '$2y$10$emEr4RfAi1A..b3yVlpVq.mw83V9fV8Lgd3qHaAuFFxc5yzDIaltS', '', '', 3, 4, 0, '2026-03-10 06:57:59'),
(4, 'NV001', 'Hoàng Thị Xuân Mai', 'quanlykho', '$2y$10$M6erifMAruaNS0nmeeD6TeR2xoY73evbxGiwhz.XnJxWuAx8c4QlK', '', '0359258401', 6, 3, 1, '2026-03-10 06:57:59'),
(5, 'NV003', 'Đào Thị Liên', 'quanlysx', '$2y$10$emEr4RfAi1A..b3yVlpVq.mw83V9fV8Lgd3qHaAuFFxc5yzDIaltS', '', '', 6, 4, 1, '2026-03-10 06:57:59'),
(6, 'NV006', 'Nguyễn Thị Thu', 'nhanvien1', '$2y$10$emEr4RfAi1A..b3yVlpVq.mw83V9fV8Lgd3qHaAuFFxc5yzDIaltS', '', '', 6, 4, 1, '2026-03-10 06:57:59'),
(7, 'NV005', 'Nguyễn Thị Hương', 'nhanvien2', '$2y$10$zASMpVdS6edNlNCVwBmzXOowB340ETaoDye9jnh49Z4aLLdny97le', '', '', 6, 4, 1, '2026-03-10 06:57:59'),
(8, 'NV004', 'Lục Thị Quê', 'nhanvien3', '$2y$10$tqtGPhFApardPXfdvKxn1uvb26/ydhqkZfE8CWBx2CkMpzq8AdpfC', '', '', 6, 4, 1, '2026-03-11 03:30:28'),
(9, 'NV002', 'Đặng Thị Minh', 'nhanvien04', '$2y$10$4O.M2B36k9QzePT0jwyOAO3UPyT3va1TAdbDz5o3y1dw62VVIF3nm', '', '', 6, 4, 1, '2026-04-29 07:59:05'),
(10, 'NV009', 'Lương Văn Tình', 'nhanvien09', '$2y$10$5KYl4aIp99ONsFLY6S.vbeNciIoU9Gr6MKgWZmKi0zo5mBfu1Jn5y', '', '', 6, 4, 0, '2026-04-29 08:02:23'),
(11, 'NV007', 'Lương Văn Thảo', 'nhanvien10', '$2y$10$DK7j5amIwEv5vN6NUH/KFeQmZyJhRDTCMblPsyZww65QFIcEoI.s.', '', '', 6, 4, 0, '2026-04-29 08:04:02'),
(12, 'NV008', 'Lương Văn Tá', 'nhanvien11', '$2y$10$Jk7LxIa5CfpJ64J1SH.nwO96g.JjaAdgKH7mSKW/xoQdNB7QNkqWS', '', '', 6, 4, 1, '2026-04-29 08:06:03'),
(13, 'NV010', 'Nguyễn Thị Thu Hằng', 'nhanvien12', '$2y$10$CSI/kiRR7aQ2FqWqfz3MC.pGCFid8VmAyYT/OVwZaS78rNEktT4i2', '', '', 6, 4, 1, '2026-04-29 08:07:13'),
(14, 'NV011', 'Lê Thu Liên', 'NV011', '$2y$10$0jladwf14AJSZpUYRGlKju4KTzDEzrsQSGmBgFjR6rLrf3dOsTMki', '', '', 6, 4, 1, '2026-05-08 09:00:40'),
(15, 'NV012', 'Lục Văn Thủy', 'NV012', '$2y$10$9jSpO16v/zHNXtnXHTqXQOrqFIiBmmqRr4ivfyQKPtbdYVgo9nsJy', '', '', 6, 4, 1, '2026-05-08 09:01:02'),
(16, 'NV013', 'Lương Văn Hùng', 'NV013', '$2y$10$cQj64mbpAYqdTcXKN6N5cu0Y6VwE49D6pkczUARFhjmPkUtIdUSAO', '', '', 6, 4, 1, '2026-05-27 01:22:43'),
(17, 'NV014', 'Hoàng Văn Ngôn', 'NV014', '$2y$10$cLjSzX1IFWd.CQ/PV4OEvuNZE65dDRTlPf5DHw86VfZQwWhxL1ADC', '', '', 6, 4, 1, '2026-06-02 01:33:53'),
(18, 'dung', 'đào dũng', 'dung', '$2y$10$QCdrMA8VREHv3jgbqrFDCOYzhgpudMyiOlooCNeMGA/CrEOZ5rq9y', '', '', 1, 1, 1, '2026-06-29 15:13:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `status` enum('active','maintenance','disposed') DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `vehicle_name`, `brand`, `model`, `year`, `color`, `status`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '30H21778', 'xẻ tải', 'kia', NULL, NULL, NULL, 'active', NULL, 18, '2026-06-29 22:56:40', '2026-06-29 22:56:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `doc_type` enum('registration','insurance','maintenance') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `provider` varchar(200) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vehicle_fuel`
--

CREATE TABLE `vehicle_fuel` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `fuel_date` date NOT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `liters` decimal(8,2) DEFAULT NULL,
  `odometer` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vehicle_trips`
--

CREATE TABLE `vehicle_trips` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `trip_date` date NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `origin` varchar(200) DEFAULT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `km_start` int(11) DEFAULT NULL,
  `km_end` int(11) DEFAULT NULL,
  `toll_fee` decimal(15,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_imports`
--

CREATE TABLE `warehouse_imports` (
  `id` int(11) NOT NULL,
  `import_no` varchar(30) NOT NULL,
  `import_date` date NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity_sent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum('pending','partial','completed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_imports`
--

INSERT INTO `warehouse_imports` (`id`, `import_no`, `import_date`, `product_code_id`, `description`, `quantity`, `quantity_sent`, `note`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 'WI-20260311-0001', '2026-03-11', 1, NULL, 100000.00, 50000.00, '', 'partial', 1, '2026-03-11 15:02:58', '2026-03-11 15:03:09'),
(6, 'WI-20260630-0001', '2026-06-30', 4, NULL, 1000.00, 0.00, '', 'pending', 18, '2026-06-30 05:58:50', '2026-06-30 05:58:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_in`
--

CREATE TABLE `warehouse_in` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('open','processing','done') DEFAULT 'open',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_in`
--

INSERT INTO `warehouse_in` (`id`, `receipt_no`, `receipt_date`, `customer_id`, `note`, `status`, `created_by`, `created_at`) VALUES
(1, 'WI-20260628-001', '2026-06-28', 1, NULL, 'done', 2, '2026-06-28 22:54:48'),
(2, 'WI-20260630-001', '2026-06-30', 2, NULL, 'done', 18, '2026-06-30 12:07:48'),
(3, 'WI-20260630-002', '2026-06-30', 1, NULL, 'done', 18, '2026-06-30 12:19:58');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_in_items`
--

CREATE TABLE `warehouse_in_items` (
  `id` int(11) NOT NULL,
  `warehouse_in_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_in_items`
--

INSERT INTO `warehouse_in_items` (`id`, `warehouse_in_id`, `product_code_id`, `quantity`, `note`) VALUES
(1, 1, 2, 100.000, NULL),
(2, 2, 5, 10000.000, NULL),
(3, 3, 4, 50.000, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_items`
--

CREATE TABLE `warehouse_items` (
  `id` int(11) NOT NULL,
  `warehouse_in_id` int(11) DEFAULT NULL,
  `wo_process_id` int(11) DEFAULT NULL,
  `product_code_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `quantity_delivered` decimal(15,3) DEFAULT 0.000,
  `status` enum('done','waiting','delivered','rejected') DEFAULT 'done',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_items`
--

INSERT INTO `warehouse_items` (`id`, `warehouse_in_id`, `wo_process_id`, `product_code_id`, `customer_id`, `quantity`, `quantity_delivered`, `status`, `note`, `created_at`) VALUES
(1, 1, 1, 2, 1, 100.000, 100.000, 'done', NULL, '2026-06-28 22:55:18'),
(2, 2, 2, 5, 2, 5000.000, 5000.000, 'delivered', NULL, '2026-06-30 12:08:05'),
(3, 3, 3, 4, 1, 50.000, 50.000, 'delivered', NULL, '2026-06-30 12:20:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_out`
--

CREATE TABLE `warehouse_out` (
  `id` int(11) NOT NULL,
  `export_no` varchar(50) DEFAULT NULL,
  `export_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('draft','confirmed') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_out`
--

INSERT INTO `warehouse_out` (`id`, `export_no`, `export_date`, `customer_id`, `note`, `status`, `created_by`, `created_at`) VALUES
(1, 'WO-20260628-001', '2026-06-28', 1, NULL, 'confirmed', 2, '2026-06-28 22:55:41'),
(2, 'WO-20260630-001', '2026-06-30', 2, NULL, 'confirmed', 18, '2026-06-30 12:08:43'),
(3, 'WO-20260630-002', '2026-06-30', 1, NULL, 'confirmed', 18, '2026-06-30 12:20:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_out_items`
--

CREATE TABLE `warehouse_out_items` (
  `id` int(11) NOT NULL,
  `warehouse_out_id` int(11) NOT NULL,
  `warehouse_item_id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_out_items`
--

INSERT INTO `warehouse_out_items` (`id`, `warehouse_out_id`, `warehouse_item_id`, `product_code_id`, `quantity`, `note`) VALUES
(1, 1, 1, 2, 100.000, NULL),
(2, 2, 2, 5, 5000.000, NULL),
(3, 3, 3, 4, 50.000, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_stock`
--

CREATE TABLE `warehouse_stock` (
  `id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `category` enum('raw_material','consumable','office','equipment','other') NOT NULL DEFAULT 'raw_material',
  `qty_pending` decimal(15,2) DEFAULT 0.00,
  `qty_completed` decimal(15,2) DEFAULT 0.00,
  `qty_defect` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouse_stock`
--

INSERT INTO `warehouse_stock` (`id`, `product_code_id`, `category`, `qty_pending`, `qty_completed`, `qty_defect`, `updated_at`) VALUES
(1, 1, 'raw_material', 0.00, 0.00, 0.00, '2026-04-29 07:31:44'),
(9, 4, 'raw_material', 1000.00, 0.00, 0.00, '2026-06-30 05:58:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouse_stock_log`
--

CREATE TABLE `warehouse_stock_log` (
  `id` int(11) NOT NULL,
  `product_code_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `txn_type` enum('import','send_to_prod','return_completed','return_defect','return_pending','delivery') NOT NULL,
  `stock_type` enum('pending','completed','defect') NOT NULL,
  `qty_change` decimal(15,2) NOT NULL COMMENT 'Dương: nhập vào, Âm: xuất ra',
  `ref_table` varchar(50) DEFAULT NULL COMMENT 'Bảng nguồn tham chiếu',
  `ref_id` int(11) DEFAULT NULL COMMENT 'ID bản ghi nguồn',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử biến động tồn kho (traceback)';

--
-- Đang đổ dữ liệu cho bảng `warehouse_stock_log`
--

INSERT INTO `warehouse_stock_log` (`id`, `product_code_id`, `log_date`, `txn_type`, `stock_type`, `qty_change`, `ref_table`, `ref_id`, `note`, `created_by`, `created_at`) VALUES
(1, 1, '2026-03-11', 'send_to_prod', 'pending', -80000.00, 'production_receipts', 4, 'Chuyển SX: PR-20260311-0001', 1, '2026-03-11 14:10:15'),
(2, 1, '2026-03-11', 'delivery', 'completed', -2500.00, 'delivery_notes', 6, 'Giao hàng: DN-20260311-0001', 1, '2026-03-11 14:10:53'),
(3, 1, '2026-03-11', 'return_pending', 'pending', 80000.00, 'day_close_log', NULL, 'Chốt ngày 2026-03-11', 1, '2026-03-11 14:25:03'),
(5, 1, '2026-03-11', 'send_to_prod', 'pending', -100000.00, 'production_receipts', 5, 'Chuyển SX: PR-20260311-0001', 1, '2026-03-11 14:57:30'),
(6, 1, '2026-03-11', 'import', 'pending', 100000.00, 'warehouse_imports', 4, 'Nhập kho: WI-20260311-0001', 1, '2026-03-11 15:02:58'),
(7, 1, '2026-03-11', 'send_to_prod', 'pending', -50000.00, 'production_receipts', 6, 'Chuyển SX: PR-20260311-0001', 1, '2026-03-11 15:03:09'),
(8, 1, '2026-03-11', 'delivery', 'completed', -5000.00, 'delivery_notes', 7, 'Giao hàng: DN-20260311-0001', 1, '2026-03-11 15:07:34'),
(10, 4, '2026-06-30', 'import', 'pending', 1000.00, 'warehouse_imports', 6, 'Nhập kho: WI-20260630-0001', 18, '2026-06-30 05:58:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `work_shifts`
--

CREATE TABLE `work_shifts` (
  `id` int(11) NOT NULL,
  `shift_code` varchar(20) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `late_threshold` int(11) DEFAULT 15,
  `break_minutes` int(11) DEFAULT 60,
  `work_hours` decimal(4,2) DEFAULT 8.00,
  `ot_multiplier` decimal(3,2) DEFAULT 1.50,
  `weekend_multiplier` decimal(3,2) DEFAULT 2.00,
  `holiday_multiplier` decimal(3,2) DEFAULT 3.00,
  `is_night_shift` tinyint(1) NOT NULL DEFAULT 0,
  `color` varchar(20) DEFAULT '#0d6efd',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `work_shifts`
--

INSERT INTO `work_shifts` (`id`, `shift_code`, `shift_name`, `start_time`, `end_time`, `late_threshold`, `break_minutes`, `work_hours`, `ot_multiplier`, `weekend_multiplier`, `holiday_multiplier`, `is_night_shift`, `color`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'HANHCHINH', 'Hành chính', '07:00:00', '16:00:00', 15, 60, 8.00, 1.50, 2.00, 3.00, 0, '#0d6efd', 1, NULL, '2026-03-10 09:51:42'),
(2, 'CA_SANG', 'Ca sáng', '06:00:00', '14:00:00', 15, 30, 8.00, 1.50, 2.00, 3.00, 0, '#198754', 1, NULL, '2026-03-10 09:51:42'),
(3, 'CA_CHIEU', 'Ca chiều', '14:00:00', '22:00:00', 15, 30, 8.00, 1.50, 2.00, 3.00, 0, '#fd7e14', 1, NULL, '2026-03-10 09:51:42'),
(4, 'CA_DEM', 'Ca đêm', '22:00:00', '06:00:00', 15, 30, 8.00, 2.00, 2.00, 3.00, 0, '#6f42c1', 1, NULL, '2026-03-10 09:51:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wo_processes`
--

CREATE TABLE `wo_processes` (
  `id` int(11) NOT NULL,
  `warehouse_in_id` int(11) NOT NULL,
  `warehouse_in_item_id` int(11) DEFAULT NULL,
  `product_code_id` int(11) NOT NULL,
  `quantity_input` decimal(15,3) DEFAULT 0.000,
  `quantity_done` decimal(15,3) DEFAULT 0.000,
  `quantity_rejected` decimal(15,3) DEFAULT 0.000,
  `status` enum('processing','done') DEFAULT 'processing',
  `process_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wo_processes`
--

INSERT INTO `wo_processes` (`id`, `warehouse_in_id`, `warehouse_in_item_id`, `product_code_id`, `quantity_input`, `quantity_done`, `quantity_rejected`, `status`, `process_date`, `note`, `updated_by`, `updated_at`) VALUES
(1, 1, 1, 2, 100.000, 100.000, 0.000, 'done', '2026-06-28', NULL, 2, '2026-06-28 22:57:03'),
(2, 2, 2, 5, 10000.000, 10000.000, 0.000, 'done', '2026-06-30', NULL, 18, '2026-06-30 12:13:42'),
(3, 3, 3, 4, 50.000, 50.000, 0.000, 'done', '2026-06-30', NULL, 18, '2026-06-30 12:20:11');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `invoices_v`
--
DROP TABLE IF EXISTS `invoices_v`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ntnvncom`@`localhost` SQL SECURITY DEFINER VIEW `invoices_v`  AS SELECT `invoices`.`id` AS `id`, `invoices`.`invoice_no` AS `invoice_no`, `invoices`.`invoice_date` AS `invoice_date`, `invoices`.`due_date` AS `due_date`, `invoices`.`customer_id` AS `customer_id`, `invoices`.`total_amount` AS `total_amount`, `invoices`.`subtotal` AS `subtotal`, `invoices`.`vat_rate` AS `vat_rate`, `invoices`.`vat_amount` AS `vat_amount`, `invoices`.`note` AS `note`, `invoices`.`delivery_id` AS `delivery_id`, `invoices`.`status` AS `status`, `invoices`.`created_by` AS `created_by`, `invoices`.`confirmed_by` AS `confirmed_by`, `invoices`.`confirmed_at` AS `confirmed_at`, `invoices`.`created_at` AS `created_at`, `invoices`.`updated_at` AS `updated_at` FROM `invoices` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `prices`
--
DROP TABLE IF EXISTS `prices`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ntnvncom`@`localhost` SQL SECURITY DEFINER VIEW `prices`  AS SELECT `product_prices`.`id` AS `id`, `product_prices`.`product_code_id` AS `product_code_id`, `product_prices`.`unit_price` AS `unit_price`, `product_prices`.`effective_from` AS `effective_from`, `product_prices`.`note` AS `note`, `product_prices`.`created_by` AS `created_by`, `product_prices`.`created_at` AS `created_at` FROM `product_prices` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin_budgets`
--
ALTER TABLE `admin_budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_budget` (`budget_year`,`budget_month`,`category_id`),
  ADD KEY `fk_ab_category` (`category_id`);

--
-- Chỉ mục cho bảng `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aa_asset` (`asset_id`),
  ADD KEY `fk_aa_user` (`user_id`);

--
-- Chỉ mục cho bảng `attendance_audit_logs`
--
ALTER TABLE `attendance_audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `attendance_location_settings`
--
ALTER TABLE `attendance_location_settings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`work_date`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Chỉ mục cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Chỉ mục cho bảng `communes`
--
ALTER TABLE `communes`
  ADD PRIMARY KEY (`code`),
  ADD KEY `district_code` (`district_code`);

--
-- Chỉ mục cho bảng `company_assets`
--
ALTER TABLE `company_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`);

--
-- Chỉ mục cho bảng `company_ip_whitelist`
--
ALTER TABLE `company_ip_whitelist`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `company_location_config`
--
ALTER TABLE `company_location_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Chỉ mục cho bảng `cost_entries`
--
ALTER TABLE `cost_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry_date` (`entry_date`),
  ADD KEY `idx_cost_type` (`cost_type`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_customer_code` (`customer_code`);

--
-- Chỉ mục cho bảng `customer_prices`
--
ALTER TABLE `customer_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cp_product` (`product_code_id`),
  ADD KEY `idx_cp_cust_prod_date` (`customer_id`,`product_code_id`,`effective_date`);

--
-- Chỉ mục cho bảng `day_close_log`
--
ALTER TABLE `day_close_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_close_date` (`close_date`);

--
-- Chỉ mục cho bảng `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dp_debt` (`debt_id`),
  ADD KEY `idx_dp_date` (`payment_date`);

--
-- Chỉ mục cho bảng `debt_tracking`
--
ALTER TABLE `debt_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_debt_invoice` (`invoice_id`),
  ADD KEY `idx_debt_customer` (`customer_id`),
  ADD KEY `idx_debt_status` (`status`);

--
-- Chỉ mục cho bảng `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_no` (`delivery_no`),
  ADD KEY `fk_dl_customer` (`customer_id`),
  ADD KEY `fk_dl_wo` (`warehouse_out_id`),
  ADD KEY `fk_dl_user` (`created_by`);

--
-- Chỉ mục cho bảng `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dli_dl` (`delivery_id`),
  ADD KEY `fk_dli_pc` (`product_code_id`);

--
-- Chỉ mục cho bảng `delivery_notes`
--
ALTER TABLE `delivery_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_delivery_no` (`delivery_no`),
  ADD KEY `idx_delivery_date` (`delivery_date`),
  ADD KEY `idx_delivery_status` (`status`),
  ADD KEY `idx_delivery_customer` (`customer_id`);

--
-- Chỉ mục cho bảng `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dni_delivery_note` (`delivery_note_id`),
  ADD KEY `idx_dni_output` (`production_output_id`),
  ADD KEY `product_code_id` (`product_code_id`);

--
-- Chỉ mục cho bảng `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`code`),
  ADD KEY `province_code` (`province_code`);

--
-- Chỉ mục cho bảng `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_doctype_date` (`doc_type`,`doc_date`);

--
-- Chỉ mục cho bảng `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `permanent_province` (`permanent_province`),
  ADD KEY `temp_province` (`temp_province`);

--
-- Chỉ mục cho bảng `employee_salaries`
--
ALTER TABLE `employee_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Chỉ mục cho bảng `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `ethnicities`
--
ALTER TABLE `ethnicities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `expense_payments`
--
ALTER TABLE `expense_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ep_expense` (`expense_id`);

--
-- Chỉ mục cho bảng `expense_requests`
--
ALTER TABLE `expense_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `fk_er_category` (`category_id`),
  ADD KEY `fk_er_requested_by` (`requested_by`);

--
-- Chỉ mục cho bảng `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holiday_date` (`holiday_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_invoice_no` (`invoice_no`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_invoice_status` (`status`),
  ADD KEY `idx_invoice_customer` (`customer_id`),
  ADD KEY `fk_inv_delivery` (`delivery_id`),
  ADD KEY `fk_inv_user` (`created_by`);

--
-- Chỉ mục cho bảng `invoice_delivery_notes`
--
ALTER TABLE `invoice_delivery_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inv_dn` (`invoice_id`,`delivery_note_id`),
  ADD KEY `delivery_note_id` (`delivery_note_id`);

--
-- Chỉ mục cho bảng `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ii_invoice` (`invoice_id`),
  ADD KEY `idx_ii_dn` (`delivery_note_id`),
  ADD KEY `idx_ii_product` (`product_code_id`),
  ADD KEY `delivery_note_item_id` (`delivery_note_item_id`);

--
-- Chỉ mục cho bảng `inv_exports`
--
ALTER TABLE `inv_exports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `export_no` (`export_no`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Chỉ mục cho bảng `inv_imports`
--
ALTER TABLE `inv_imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `import_no` (`import_no`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `inv_items`
--
ALTER TABLE `inv_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Chỉ mục cho bảng `kpi_assignments`
--
ALTER TABLE `kpi_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assign` (`assign_date`,`user_id`),
  ADD KEY `fk_kpi_user` (`user_id`),
  ADD KEY `fk_kpi_manager` (`manager_id`);

--
-- Chỉ mục cho bảng `kpi_results`
--
ALTER TABLE `kpi_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_result` (`kpi_assignment_id`),
  ADD KEY `fk_result_assign` (`kpi_assignment_id`);

--
-- Chỉ mục cho bảng `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pay_inv` (`invoice_id`),
  ADD KEY `fk_pay_user` (`created_by`);

--
-- Chỉ mục cho bảng `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_period` (`period_year`,`period_month`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `locked_by` (`locked_by`);

--
-- Chỉ mục cho bảng `payroll_slips`
--
ALTER TABLE `payroll_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slip` (`period_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `production_outputs`
--
ALTER TABLE `production_outputs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_output_no` (`output_no`),
  ADD KEY `idx_output_date` (`output_date`),
  ADD KEY `idx_production_receipt` (`production_receipt_id`),
  ADD KEY `product_code_id` (`product_code_id`);

--
-- Chỉ mục cho bảng `production_receipts`
--
ALTER TABLE `production_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_receipt_no` (`receipt_no`),
  ADD KEY `idx_receipt_date` (`receipt_date`),
  ADD KEY `idx_warehouse_import` (`warehouse_import_id`),
  ADD KEY `product_code_id` (`product_code_id`);

--
-- Chỉ mục cho bảng `production_stock`
--
ALTER TABLE `production_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prod_stock` (`product_code_id`,`stock_date`);

--
-- Chỉ mục cho bảng `product_codes`
--
ALTER TABLE `product_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_product_code` (`product_code`),
  ADD KEY `idx_product_category` (`product_category`);

--
-- Chỉ mục cho bảng `product_prices`
--
ALTER TABLE `product_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_effective` (`product_code_id`,`effective_from`);

--
-- Chỉ mục cho bảng `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`code`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `component_code` (`component_code`);

--
-- Chỉ mục cho bảng `shift_schedules`
--
ALTER TABLE `shift_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`user_id`,`work_date`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Chỉ mục cho bảng `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`);

--
-- Chỉ mục cho bảng `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vd_vehicle` (`vehicle_id`);

--
-- Chỉ mục cho bảng `vehicle_fuel`
--
ALTER TABLE `vehicle_fuel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vf_vehicle` (`vehicle_id`);

--
-- Chỉ mục cho bảng `vehicle_trips`
--
ALTER TABLE `vehicle_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vt_vehicle` (`vehicle_id`);

--
-- Chỉ mục cho bảng `warehouse_imports`
--
ALTER TABLE `warehouse_imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_import_no` (`import_no`),
  ADD KEY `idx_import_date` (`import_date`),
  ADD KEY `idx_import_status` (`status`),
  ADD KEY `product_code_id` (`product_code_id`);

--
-- Chỉ mục cho bảng `warehouse_in`
--
ALTER TABLE `warehouse_in`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `fk_wi_customer` (`customer_id`),
  ADD KEY `fk_wi_created_by` (`created_by`);

--
-- Chỉ mục cho bảng `warehouse_in_items`
--
ALTER TABLE `warehouse_in_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wii_wi` (`warehouse_in_id`),
  ADD KEY `fk_wii_product` (`product_code_id`);

--
-- Chỉ mục cho bảng `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_witm_wi` (`warehouse_in_id`),
  ADD KEY `fk_witm_wop` (`wo_process_id`),
  ADD KEY `fk_witm_pc` (`product_code_id`),
  ADD KEY `fk_witm_cust` (`customer_id`);

--
-- Chỉ mục cho bảng `warehouse_out`
--
ALTER TABLE `warehouse_out`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `export_no` (`export_no`),
  ADD KEY `fk_wo_customer` (`customer_id`),
  ADD KEY `fk_wo_created_by` (`created_by`);

--
-- Chỉ mục cho bảng `warehouse_out_items`
--
ALTER TABLE `warehouse_out_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_woi_wo` (`warehouse_out_id`),
  ADD KEY `fk_woi_witm` (`warehouse_item_id`),
  ADD KEY `fk_woi_pc` (`product_code_id`);

--
-- Chỉ mục cho bảng `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wh_stock` (`product_code_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_product_category` (`product_code_id`,`category`);

--
-- Chỉ mục cho bảng `warehouse_stock_log`
--
ALTER TABLE `warehouse_stock_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_date` (`log_date`),
  ADD KEY `idx_log_product` (`product_code_id`),
  ADD KEY `idx_log_type` (`txn_type`);

--
-- Chỉ mục cho bảng `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shift_code` (`shift_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `wo_processes`
--
ALTER TABLE `wo_processes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wop_wi` (`warehouse_in_id`),
  ADD KEY `fk_wop_wii` (`warehouse_in_item_id`),
  ADD KEY `fk_wop_pc` (`product_code_id`),
  ADD KEY `fk_wop_user` (`updated_by`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin_budgets`
--
ALTER TABLE `admin_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `asset_assignments`
--
ALTER TABLE `asset_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `attendance_audit_logs`
--
ALTER TABLE `attendance_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT cho bảng `attendance_location_settings`
--
ALTER TABLE `attendance_location_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3454;

--
-- AUTO_INCREMENT cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `company_assets`
--
ALTER TABLE `company_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `company_ip_whitelist`
--
ALTER TABLE `company_ip_whitelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `company_location_config`
--
ALTER TABLE `company_location_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `cost_entries`
--
ALTER TABLE `cost_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `customer_prices`
--
ALTER TABLE `customer_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `day_close_log`
--
ALTER TABLE `day_close_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `debt_tracking`
--
ALTER TABLE `debt_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `delivery_items`
--
ALTER TABLE `delivery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `delivery_notes`
--
ALTER TABLE `delivery_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `document_sequences`
--
ALTER TABLE `document_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT cho bảng `employee_profiles`
--
ALTER TABLE `employee_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `employee_salaries`
--
ALTER TABLE `employee_salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT cho bảng `employee_shifts`
--
ALTER TABLE `employee_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT cho bảng `ethnicities`
--
ALTER TABLE `ethnicities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `expense_payments`
--
ALTER TABLE `expense_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `expense_requests`
--
ALTER TABLE `expense_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT cho bảng `invoice_delivery_notes`
--
ALTER TABLE `invoice_delivery_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `inv_exports`
--
ALTER TABLE `inv_exports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `inv_imports`
--
ALTER TABLE `inv_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `inv_items`
--
ALTER TABLE `inv_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `kpi_assignments`
--
ALTER TABLE `kpi_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `kpi_results`
--
ALTER TABLE `kpi_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT cho bảng `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=781;

--
-- AUTO_INCREMENT cho bảng `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `payroll_slips`
--
ALTER TABLE `payroll_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT cho bảng `production_outputs`
--
ALTER TABLE `production_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `production_receipts`
--
ALTER TABLE `production_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `production_stock`
--
ALTER TABLE `production_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `product_codes`
--
ALTER TABLE `product_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `product_prices`
--
ALTER TABLE `product_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `shift_schedules`
--
ALTER TABLE `shift_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `vehicle_fuel`
--
ALTER TABLE `vehicle_fuel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `vehicle_trips`
--
ALTER TABLE `vehicle_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `warehouse_imports`
--
ALTER TABLE `warehouse_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `warehouse_in`
--
ALTER TABLE `warehouse_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `warehouse_in_items`
--
ALTER TABLE `warehouse_in_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `warehouse_items`
--
ALTER TABLE `warehouse_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `warehouse_out`
--
ALTER TABLE `warehouse_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `warehouse_out_items`
--
ALTER TABLE `warehouse_out_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `warehouse_stock_log`
--
ALTER TABLE `warehouse_stock_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `work_shifts`
--
ALTER TABLE `work_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `wo_processes`
--
ALTER TABLE `wo_processes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `admin_budgets`
--
ALTER TABLE `admin_budgets`
  ADD CONSTRAINT `fk_ab_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`);

--
-- Các ràng buộc cho bảng `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD CONSTRAINT `fk_aa_asset` FOREIGN KEY (`asset_id`) REFERENCES `company_assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attendance_logs_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `work_shifts` (`id`);

--
-- Các ràng buộc cho bảng `communes`
--
ALTER TABLE `communes`
  ADD CONSTRAINT `communes_ibfk_1` FOREIGN KEY (`district_code`) REFERENCES `districts` (`code`);

--
-- Các ràng buộc cho bảng `customer_prices`
--
ALTER TABLE `customer_prices`
  ADD CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cp_product` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD CONSTRAINT `debt_payments_ibfk_1` FOREIGN KEY (`debt_id`) REFERENCES `debt_tracking` (`id`);

--
-- Các ràng buộc cho bảng `debt_tracking`
--
ALTER TABLE `debt_tracking`
  ADD CONSTRAINT `debt_tracking_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `debt_tracking_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `fk_dl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_dl_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dl_wo` FOREIGN KEY (`warehouse_out_id`) REFERENCES `warehouse_out` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD CONSTRAINT `fk_dli_dl` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dli_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `delivery_notes`
--
ALTER TABLE `delivery_notes`
  ADD CONSTRAINT `delivery_notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  ADD CONSTRAINT `delivery_note_items_ibfk_1` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`),
  ADD CONSTRAINT `delivery_note_items_ibfk_2` FOREIGN KEY (`production_output_id`) REFERENCES `production_outputs` (`id`),
  ADD CONSTRAINT `delivery_note_items_ibfk_3` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`province_code`) REFERENCES `provinces` (`code`);

--
-- Các ràng buộc cho bảng `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD CONSTRAINT `employee_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_profiles_ibfk_2` FOREIGN KEY (`permanent_province`) REFERENCES `provinces` (`code`),
  ADD CONSTRAINT `employee_profiles_ibfk_3` FOREIGN KEY (`temp_province`) REFERENCES `provinces` (`code`);

--
-- Các ràng buộc cho bảng `employee_salaries`
--
ALTER TABLE `employee_salaries`
  ADD CONSTRAINT `employee_salaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_salaries_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `salary_components` (`id`),
  ADD CONSTRAINT `employee_salaries_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `employee_salaries_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD CONSTRAINT `employee_shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `employee_shifts_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `work_shifts` (`id`),
  ADD CONSTRAINT `employee_shifts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `expense_payments`
--
ALTER TABLE `expense_payments`
  ADD CONSTRAINT `fk_ep_expense` FOREIGN KEY (`expense_id`) REFERENCES `expense_requests` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `expense_requests`
--
ALTER TABLE `expense_requests`
  ADD CONSTRAINT `fk_er_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `fk_er_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_inv_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_inv_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `invoice_delivery_notes`
--
ALTER TABLE `invoice_delivery_notes`
  ADD CONSTRAINT `invoice_delivery_notes_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_delivery_notes_ibfk_2` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`);

--
-- Các ràng buộc cho bảng `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_ii_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ii_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`),
  ADD CONSTRAINT `invoice_items_ibfk_4` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `inv_exports`
--
ALTER TABLE `inv_exports`
  ADD CONSTRAINT `inv_exports_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`),
  ADD CONSTRAINT `inv_exports_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `inv_exports_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `inv_imports`
--
ALTER TABLE `inv_imports`
  ADD CONSTRAINT `inv_imports_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`),
  ADD CONSTRAINT `inv_imports_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `kpi_results`
--
ALTER TABLE `kpi_results`
  ADD CONSTRAINT `fk_result_assign` FOREIGN KEY (`kpi_assignment_id`) REFERENCES `kpi_assignments` (`id`);

--
-- Các ràng buộc cho bảng `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `overtime_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payroll_periods_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payroll_periods_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payroll_periods_ibfk_4` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `payroll_slips`
--
ALTER TABLE `payroll_slips`
  ADD CONSTRAINT `payroll_slips_ibfk_1` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_slips_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `production_outputs`
--
ALTER TABLE `production_outputs`
  ADD CONSTRAINT `production_outputs_ibfk_1` FOREIGN KEY (`production_receipt_id`) REFERENCES `production_receipts` (`id`),
  ADD CONSTRAINT `production_outputs_ibfk_2` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `production_receipts`
--
ALTER TABLE `production_receipts`
  ADD CONSTRAINT `production_receipts_ibfk_1` FOREIGN KEY (`warehouse_import_id`) REFERENCES `warehouse_imports` (`id`),
  ADD CONSTRAINT `production_receipts_ibfk_2` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `product_prices`
--
ALTER TABLE `product_prices`
  ADD CONSTRAINT `product_prices_ibfk_1` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `shift_schedules`
--
ALTER TABLE `shift_schedules`
  ADD CONSTRAINT `shift_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shift_schedules_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `work_shifts` (`id`),
  ADD CONSTRAINT `shift_schedules_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Các ràng buộc cho bảng `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `fk_vd_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `vehicle_fuel`
--
ALTER TABLE `vehicle_fuel`
  ADD CONSTRAINT `fk_vf_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `vehicle_trips`
--
ALTER TABLE `vehicle_trips`
  ADD CONSTRAINT `fk_vt_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `warehouse_imports`
--
ALTER TABLE `warehouse_imports`
  ADD CONSTRAINT `warehouse_imports_ibfk_1` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`);

--
-- Các ràng buộc cho bảng `warehouse_in`
--
ALTER TABLE `warehouse_in`
  ADD CONSTRAINT `fk_wi_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wi_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `warehouse_in_items`
--
ALTER TABLE `warehouse_in_items`
  ADD CONSTRAINT `fk_wii_product` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`),
  ADD CONSTRAINT `fk_wii_wi` FOREIGN KEY (`warehouse_in_id`) REFERENCES `warehouse_in` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `warehouse_items`
--
ALTER TABLE `warehouse_items`
  ADD CONSTRAINT `fk_witm_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_witm_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`),
  ADD CONSTRAINT `fk_witm_wi` FOREIGN KEY (`warehouse_in_id`) REFERENCES `warehouse_in` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_witm_wop` FOREIGN KEY (`wo_process_id`) REFERENCES `wo_processes` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `warehouse_out`
--
ALTER TABLE `warehouse_out`
  ADD CONSTRAINT `fk_wo_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wo_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `warehouse_out_items`
--
ALTER TABLE `warehouse_out_items`
  ADD CONSTRAINT `fk_woi_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`),
  ADD CONSTRAINT `fk_woi_witm` FOREIGN KEY (`warehouse_item_id`) REFERENCES `warehouse_items` (`id`),
  ADD CONSTRAINT `fk_woi_wo` FOREIGN KEY (`warehouse_out_id`) REFERENCES `warehouse_out` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `warehouse_stock_log`
--
ALTER TABLE `warehouse_stock_log`
  ADD CONSTRAINT `fk_wsl_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD CONSTRAINT `work_shifts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `wo_processes`
--
ALTER TABLE `wo_processes`
  ADD CONSTRAINT `fk_wop_pc` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`),
  ADD CONSTRAINT `fk_wop_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wop_wi` FOREIGN KEY (`warehouse_in_id`) REFERENCES `warehouse_in` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wop_wii` FOREIGN KEY (`warehouse_in_item_id`) REFERENCES `warehouse_in_items` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
