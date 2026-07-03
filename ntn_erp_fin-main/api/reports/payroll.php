<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();
requireRole('director', 'accountant');

$pdo = getDBConnection();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$filterTs = strtotime($dateFrom);
if ($filterTs === false) {
    $filterTs = time();
}
$filterYear  = (int)date('Y', $filterTs);
$filterMonth = (int)date('m', $filterTs);

// ── KPI: Nhân sự ──────────────────────────────────────────────────────────
$totalActive  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$hasInsurance = (int)$pdo->query("
    SELECT COUNT(*) FROM employee_profiles WHERE has_social_insurance = 1
")->fetchColumn();

// ── KPI: Lương kỳ này ────────────────────────────────────────────────────
$stmtPayroll = $pdo->prepare("
    SELECT
        COUNT(ps.id)                                                            AS so_phieu,
        COALESCE(SUM(ps.basic_salary_received), 0)                             AS tong_luong_cb,
        COALESCE(SUM(ps.meal_received
                   + ps.clothes_received
                   + ps.phone_received
                   + ps.transport_received
                   + ps.housing_received), 0)                                  AS tong_phu_cap,
        COALESCE(SUM(ps.ot_weekday_amount
                   + ps.ot_weekend_amount
                   + ps.ot_holiday_amount
                   + COALESCE(ps.ot_night_amount, 0)), 0)                      AS tong_ot,
        COALESCE(SUM(ps.performance_bonus
                   + ps.attendance_bonus
                   + ps.kpi_bonus), 0)                                         AS tong_thuong,
        COALESCE(SUM(ps.gross_salary), 0)                                      AS tong_gross,
        COALESCE(SUM(ps.si_employee), 0)                                       AS tong_bhxh_nv,
        COALESCE(SUM(ps.si_company), 0)                                        AS tong_bhxh_cty,
        COALESCE(SUM(ps.pit_amount), 0)                                        AS tong_thue,
        COALESCE(SUM(ps.net_salary), 0)                                        AS tong_net,
        COALESCE(SUM(ps.gross_salary + ps.si_company), 0)                      AS tong_chi_phi_luong,
        COALESCE(SUM(ps.ot_weekday_hours), 0)                                  AS gio_ot_thuong,
        COALESCE(SUM(ps.ot_weekend_hours), 0)                                  AS gio_ot_cuoi_tuan,
        COALESCE(SUM(ps.ot_holiday_hours), 0)                                  AS gio_ot_le,
        COALESCE(SUM(COALESCE(ps.ot_night_hours, 0)), 0)                       AS gio_ot_dem,
        COALESCE(SUM(ps.kpi_deduction), 0)                                     AS tong_tru_kpi,
        COALESCE(SUM(ps.late_early_deduction), 0)                              AS tong_tru_muon
    FROM payroll_slips ps
    JOIN payroll_periods pp ON pp.id = ps.period_id
    WHERE pp.status IN ('approved','locked')
      AND pp.period_year = ?
      AND pp.period_month = ?
");
$stmtPayroll->execute([$filterYear, $filterMonth]);
$kpi = $stmtPayroll->fetch(PDO::FETCH_ASSOC) ?: [];

// ── Bảng lương theo phòng ban ─────────────────────────────────────────────
$byDept = $pdo->prepare("
    SELECT
        COALESCE(d.name, 'Chưa phân phòng') AS dept_name,
        COUNT(ps.id)                                                           AS so_nv,
        COALESCE(SUM(ps.gross_salary), 0)                                     AS tong_gross,
        COALESCE(SUM(ps.si_company), 0)                                       AS tong_bhxh_cty,
        COALESCE(SUM(ps.gross_salary + ps.si_company), 0)                     AS tong_chi_phi,
        COALESCE(SUM(ps.net_salary), 0)                                       AS tong_net,
        COALESCE(SUM(ps.ot_weekday_amount
                   + ps.ot_weekend_amount
                   + ps.ot_holiday_amount
                   + COALESCE(ps.ot_night_amount, 0)), 0)                     AS tong_ot
    FROM payroll_slips ps
    JOIN payroll_periods pp ON pp.id = ps.period_id
    JOIN users u ON u.id = ps.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE pp.status IN ('approved','locked')
      AND pp.period_year = ?
      AND pp.period_month = ?
    GROUP BY d.id
    ORDER BY tong_chi_phi DESC
");
$byDept->execute([$filterYear, $filterMonth]);
$deptRows = $byDept->fetchAll();

// ── Biểu đồ: Chi phí lương 12 tháng gần nhất ────────────────────────────
$chartPayroll12 = $pdo->query("
    SELECT CONCAT(pp.period_year, '-', LPAD(pp.period_month, 2, '0')) AS month,
           SUM(ps.gross_salary + ps.si_company)  AS tong_chi_phi,
           SUM(ps.net_salary)                    AS tong_net,
           SUM(ps.si_company)                    AS tong_bhxh_cty,
           COUNT(ps.id)                          AS so_nv
    FROM payroll_slips ps
    JOIN payroll_periods pp ON pp.id = ps.period_id
    WHERE pp.status IN ('approved','locked')
      AND (pp.period_year * 100 + pp.period_month) >=
          (YEAR(DATE_SUB(CURDATE(), INTERVAL 11 MONTH)) * 100 + MONTH(DATE_SUB(CURDATE(), INTERVAL 11 MONTH)))
    GROUP BY pp.period_year, pp.period_month
    ORDER BY month ASC
")->fetchAll();

// ── Chấm công tháng: tổng hợp ────────────────────────────────────────────
$stmtAtt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT user_id)              AS so_nv_cham_cong,
        COALESCE(SUM(is_late), 0)            AS so_lan_muon,
        COALESCE(SUM(late_minutes), 0)       AS tong_phut_muon,
        COALESCE(SUM(early_leave), 0)        AS so_lan_ve_som,
        COALESCE(SUM(early_leave_minutes), 0) AS tong_phut_ve_som,
        COALESCE(SUM(work_hours), 0)         AS tong_gio_lam
    FROM attendance_logs
    WHERE work_date BETWEEN ? AND ?
");
$stmtAtt->execute([$dateFrom, $dateTo]);
$attKpi = $stmtAtt->fetch(PDO::FETCH_ASSOC) ?: [];

// ── OT tháng ─────────────────────────────────────────────────────────────
$stmtOT = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN ot_type='weekday' THEN hours ELSE 0 END), 0) AS gio_ot_thuong,
        COALESCE(SUM(CASE WHEN ot_type='weekend' THEN hours ELSE 0 END), 0) AS gio_ot_cuoi_tuan,
        COALESCE(SUM(CASE WHEN ot_type='holiday' THEN hours ELSE 0 END), 0) AS gio_ot_le,
        COALESCE(SUM(CASE WHEN ot_type='night'   THEN hours ELSE 0 END), 0) AS gio_ot_dem,
        COALESCE(SUM(hours), 0)                                              AS tong_gio_ot,
        COUNT(DISTINCT user_id)                                              AS so_nv_ot
    FROM overtime_requests
    WHERE status = 'approved'
      AND ot_date BETWEEN ? AND ?
");
$stmtOT->execute([$dateFrom, $dateTo]);
$otKpi = $stmtOT->fetch(PDO::FETCH_ASSOC) ?: [];

// ── Nghỉ phép tháng ───────────────────────────────────────────────────────
$stmtLeave = $pdo->prepare("
    SELECT
        leave_type,
        COUNT(*) AS so_don,
        COALESCE(SUM(total_days), 0) AS tong_ngay
    FROM leave_requests
    WHERE status = 'approved'
      AND start_date <= ? AND end_date >= ?
    GROUP BY leave_type
");
$stmtLeave->execute([$dateTo, $dateFrom]);
$leaveRows = $stmtLeave->fetchAll();

// ── Danh sách nhân viên đi muộn nhiều nhất tháng ────────────────────────
$stmtLateEmp = $pdo->prepare("
    SELECT u.full_name, u.employee_code,
           COALESCE(d.name, 'Chưa phân phòng') AS dept_name,
           COUNT(*) AS so_lan_muon,
           COALESCE(SUM(al.late_minutes), 0) AS tong_phut_muon
    FROM attendance_logs al
    JOIN users u ON u.id = al.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE al.is_late = 1
      AND al.work_date BETWEEN ? AND ?
    GROUP BY al.user_id
    ORDER BY so_lan_muon DESC, tong_phut_muon DESC
    LIMIT 10
");
$stmtLateEmp->execute([$dateFrom, $dateTo]);
$lateEmployees = $stmtLateEmp->fetchAll();

// ── Top OT nhiều nhất ────────────────────────────────────────────────────
$stmtTopOT = $pdo->prepare("
    SELECT u.full_name, u.employee_code,
           COALESCE(d.name, 'Chưa phân phòng') AS dept_name,
           COALESCE(SUM(ot.hours), 0) AS tong_gio_ot,
           COUNT(*) AS so_ngay_ot
    FROM overtime_requests ot
    JOIN users u ON u.id = ot.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE ot.status = 'approved'
      AND ot.ot_date BETWEEN ? AND ?
    GROUP BY ot.user_id
    ORDER BY tong_gio_ot DESC
    LIMIT 10
");
$stmtTopOT->execute([$dateFrom, $dateTo]);
$topOT = $stmtTopOT->fetchAll();

// ── Phân bố nhân viên theo phòng ban ─────────────────────────────────────
$deptDist = $pdo->query("
    SELECT COALESCE(d.name, 'Chưa phân phòng') AS dept_name,
           COUNT(u.id) AS so_nv
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.is_active = 1
    GROUP BY d.id
    ORDER BY so_nv DESC
")->fetchAll();

echo json_encode([
    'ok'    => true,
    'kpi'   => array_merge($kpi, [
        'total_active'  => $totalActive,
        'has_insurance' => $hasInsurance,
    ]),
    'att_kpi'           => $attKpi,
    'ot_kpi'            => $otKpi,
    'leave_summary'     => $leaveRows,
    'by_dept'           => $deptRows,
    'chart_12months'    => $chartPayroll12,
    'late_employees'    => $lateEmployees,
    'top_ot'            => $topOT,
    'dept_distribution' => $deptDist,
], JSON_UNESCAPED_UNICODE);
