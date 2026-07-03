<?php
class PayrollEngine
{
    private PDO $pdo;

    const OT_WEEKDAY       = 1.5;
    const OT_WEEKEND       = 2.0;
    const OT_HOLIDAY       = 3.0;
    const OT_NIGHT         = 1.30; // Backward compat
    const OT_NIGHT_WEEKDAY = 2.1;  // OT đêm ngày thường
    const OT_NIGHT_WEEKEND = 2.7;  // OT đêm cuối tuần
    const OT_NIGHT_HOLIDAY = 3.9;  // OT đêm ngày lễ

    const NIGHT_SHIFT_MULTIPLIER = 0.30; // 30% phụ trội đêm trên lương cơ bản thực nhận

    const OT_MEAL_ALLOWANCE        = 14_000; // Trợ cấp ăn ca OT: 14.000đ/ngày
    const OT_MEAL_MIN_HOURS        = 3.0;    // OT từ 3h/ngày mới được ăn ca

    const PIT_BRACKETS = [
        [5_000_000,   0.05],
        [10_000_000,  0.10],
        [18_000_000,  0.15],
        [32_000_000,  0.20],
        [52_000_000,  0.25],
        [80_000_000,  0.30],
        [PHP_INT_MAX, 0.35],
    ];

    const SI_EMPLOYEE_RATE    = 0.105;
    const SI_COMPANY_RATE     = 0.215;
    const PERSONAL_DEDUCTION  = 15_500_000;
    const DEPENDANT_DEDUCTION = 6_200_000;
    const LATE_GRACE_MINUTES  = 5;
    const LATE_BLOCK_MINUTES  = 30;
    const WORK_HOURS_PER_DAY  = 8;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function calcWorkingDays(string $from, string $to): int
    {
        $count   = 0;
        $current = new DateTime($from);
        $end     = new DateTime($to);
        while ($current <= $end) {
            if ((int)$current->format('N') !== 7) $count++;
            $current->modify('+1 day');
        }
        return $count;
    }

    public function calculate(int $periodId, int $userId): array
    {
        $period  = $this->getPeriod($periodId);
        $profile = $this->getProfile($userId);
        $salary  = $this->getSalaryComponents($userId);

        if (empty($period)) {
            throw new \RuntimeException("Không tìm thấy kỳ lương #$periodId");
        }

        // ── Khoản cố định theo code ───────────────────────────────────
        $basicSalary    = (int)($salary['basic']            ?? 0);
        $mealAllow      = (int)($salary['meal']             ?? 0);
        $clothesAllow   = (int)($salary['clothes']          ?? 0);
        $phoneAllow     = (int)($salary['phone']            ?? 0);
        $transportAllow = (int)($salary['transport']        ?? 0);
        $housingAllow   = (int)($salary['housing']          ?? 0); // ✅ Trợ cấp nhà ở
        $performBonus   = (int)($salary['performance']      ?? 0);
        $attendBonus    = (int)($salary['attendance_bonus'] ?? 0);

        $totalSalaryNoAttend = 0;
        foreach ($salary['all_components'] as $comp) {
            if ($comp['component_code'] === 'attendance_bonus') continue;
            if (in_array($comp['component_type'], ['earning', 'bonus']))
                $totalSalaryNoAttend += (int)$comp['amount'];
        }

        $workingDays = (int)$period['working_days'];

        $salaryPerDay  = $workingDays > 0 ? round($totalSalaryNoAttend / $workingDays) : 0;
        $totalPerHour  = round($salaryPerDay / self::WORK_HOURS_PER_DAY);

        // Lương 1h = (Lương cơ bản + PC Trách nhiệm + PC Kỹ năng) / ngày công chuẩn / 8
        $responsibilityAllow = (int)($salary['responsibility_allowance'] ?? 0);
        $seniorityAllow      = (int)($salary['seniority_allowance']      ?? 0);
        $otBase              = $basicSalary + $responsibilityAllow + $seniorityAllow;
        $basicPerDay         = $workingDays > 0 ? round($otBase / $workingDays) : 0;
        $salaryPerHour       = round($basicPerDay / self::WORK_HOURS_PER_DAY);

        // ── Dữ liệu chấm công / nghỉ phép / OT ───────────────────────
        $att = $this->getAttendanceData($userId, $period['period_from'], $period['period_to']);
        $lv  = $this->getLeaveData($userId, $period['period_from'], $period['period_to']);
        $ot  = $this->getOTData($userId, $period['period_from'], $period['period_to']);

        $actualWorkdays     = (float)($att['actual_workdays']      ?? 0);
        $paidLeaveDays      = (float)($lv['paid_leave_days']        ?? 0);
        $otherPaidLeaveDays = (float)($lv['other_paid_leave_days']  ?? 0);
        $holidayPaidDays    = $this->getHolidayWorkDays($period['period_from'], $period['period_to'], $userId);
        $unpaidLeaveDays    = (float)($lv['unpaid_leave_days']      ?? 0);

        $totalLateMinutes  = (float)($att['total_late_minutes']  ?? 0);
        $totalEarlyMinutes = (float)($att['total_early_minutes'] ?? 0);
        $lateMinutes       = $totalLateMinutes + $totalEarlyMinutes;

        $otWeekdayHours      = (float)($ot['ot_weekday_hours']       ?? 0);
        $otWeekendHours      = (float)($ot['ot_weekend_hours']       ?? 0);
        $otHolidayHours      = (float)($ot['ot_holiday_hours']       ?? 0);
        $otNightHours        = (float)($ot['ot_night_hours']         ?? 0); // backward compat
        $otNightWeekdayHours = (float)($ot['ot_night_weekday_hours'] ?? 0);
        $otNightWeekendHours = (float)($ot['ot_night_weekend_hours'] ?? 0);
        $otNightHolidayHours = (float)($ot['ot_night_holiday_hours'] ?? 0);

        $isLateWarning   = ($lateMinutes > self::LATE_GRACE_MINUTES);
        $lateWarningNote = (string)($att['late_early_dates'] ?? '');

        $totalPaidDays = $actualWorkdays + $paidLeaveDays + $otherPaidLeaveDays + $holidayPaidDays;

        [$lateHours, $lateDeduction] = $this->calcLateDeduction($lateMinutes, $totalPerHour);

        // ── KPI ──────────────────────────────────────────────────────
        $kpiData      = $this->getKpiData($userId, $period['period_from'], $period['period_to']);
        $kpiDeduction = 0;
        $kpiBonus     = 0;
        $kpiOverDays  = 0;
        $kpiUnderDays = 0;

        foreach ($kpiData as $kDay) {
            $spd    = (float)$kDay['salary_per_day'];
            $actual = (float)$kDay['salary_actual'];

            if ((int)$kDay['is_deducted'] === 1) {
                $kpiDeduction += max(0, $spd - $actual);
                $kpiUnderDays++;
            } elseif ($actual > $spd) {
                $kpiBonus += ($actual - $spd);
                $kpiOverDays++;
            }
        }
        $kpiDeduction = (int)round($kpiDeduction);
        $kpiBonus     = (int)round($kpiBonus);

        // ── Tỷ lệ ngày ───────────────────────────────────────────────
        $basicReceived = $workingDays > 0
            ? round($basicSalary * ($totalPaidDays / $workingDays)) : 0;

        // ── Phụ trội làm đêm (30% theo giờ thực tế làm trong khung ca đêm) ─────
        $isNightShift     = $this->isNightShiftWorker($userId, $period['period_from'], $period['period_to']);
        $nightHoursActual = $this->getNightHoursWorked($userId, $period['period_from'], $period['period_to']);
        $nightShiftBonus  = $nightHoursActual > 0
            ? (int)round($salaryPerHour * $nightHoursActual * self::NIGHT_SHIFT_MULTIPLIER)
            : 0;

        $allowanceRatio = $workingDays > 0
            ? min(1.0, $totalPaidDays / $workingDays) : 0;

        // Ăn ca: chỉ ngày thực đi làm
        $mealRatio = $workingDays > 0
            ? min(1.0, $actualWorkdays / $workingDays) : 0;

        // ── Trợ cấp ăn ca OT: cộng thêm 14.000đ/ngày OT ≥ 3h ───────
        $otMealDays    = $this->getOTMealDays($userId, $period['period_from'], $period['period_to']);
        $otMealBonus   = $otMealDays * self::OT_MEAL_ALLOWANCE;

        // ── Trợ cấp thực nhận ────────────────────────────────────────
        $mealReceived      = round($mealAllow * $mealRatio) + $otMealBonus;
        $clothesReceived   = round($clothesAllow   * $allowanceRatio);
        $phoneReceived     = round($phoneAllow     * $allowanceRatio);
        $transportReceived = round($transportAllow * $allowanceRatio);
        $housingReceived   = round($housingAllow   * $allowanceRatio); // ✅ Trợ cấp nhà ở
        $performReceived   = round($performBonus   * $allowanceRatio);

        $otherComponentsReceived = 0;
        foreach ($salary['all_components'] as $comp) {
            $code = $comp['component_code'];
            if (in_array($code, [
                'basic', 'meal', 'clothes', 'phone',
                'transport', 'housing',                 // ✅ loại housing ra khỏi "other"
                'responsibility_allowance', 'seniority_allowance', // ✅ loại ra để không tính trùng vào OT base
                'performance', 'attendance_bonus'
            ])) continue;
            if (!in_array($comp['component_type'], ['earning', 'bonus'])) continue;
            $otherComponentsReceived += round((int)$comp['amount'] * $allowanceRatio);
        }

        // ── Chuyên cần ───────────────────────────────────────────────
        $kpiHasDeduction = $kpiDeduction > 0;
        $attendEligible  = ($allowanceRatio >= 1.0 && !$kpiHasDeduction);
        $attendReceived  = ($attendEligible && $attendBonus > 0) ? $attendBonus : 0;

        // ── OT ───────────────────────────────────────────────────────
        $otWeekdayAmt      = round($otWeekdayHours      * $salaryPerHour * self::OT_WEEKDAY);
        $otWeekendAmt      = round($otWeekendHours      * $salaryPerHour * self::OT_WEEKEND);
        $otHolidayAmt      = round($otHolidayHours      * $salaryPerHour * self::OT_HOLIDAY);
        $otNightWeekdayAmt = round($otNightWeekdayHours * $salaryPerHour * self::OT_NIGHT_WEEKDAY);
        $otNightWeekendAmt = round($otNightWeekendHours * $salaryPerHour * self::OT_NIGHT_WEEKEND);
        $otNightHolidayAmt = round($otNightHolidayHours * $salaryPerHour * self::OT_NIGHT_HOLIDAY);
        // Backward compat: tổng 3 loại đêm
        $otNightAmt        = $otNightWeekdayAmt + $otNightWeekendAmt + $otNightHolidayAmt;
        $totalOtAmt        = $otWeekdayAmt + $otWeekendAmt + $otHolidayAmt + $otNightAmt;

        // ── Phép tồn ─────────────────────────────────────────────────
        $periodYear     = (int)($period['period_year'] ?? (int)substr($period['period_from'], 0, 4));
        $leaveTotal     = $this->calcAnnualLeave($userId, $periodYear);
        $leaveUsed      = $this->getLeaveUsed($userId, $periodYear);
        $leaveRemaining = max(0, $leaveTotal - $leaveUsed);
        $leavePayout    = 0;

        // ── BHXH ─────────────────────────────────────────────────────
        $hasInsurance = (int)($profile['has_social_insurance'] ?? 0);
        $siEmployee   = $hasInsurance ? round($basicSalary * self::SI_EMPLOYEE_RATE) : 0;
        $siCompany    = $hasInsurance ? round($basicSalary * self::SI_COMPANY_RATE)  : 0;

        // ── Thuế TNCN ────────────────────────────────────────────────
        $dependants         = (int)($profile['dependants'] ?? 0);
        $dependantDeduction = $dependants * self::DEPENDANT_DEDUCTION;

        $grossForTax = $basicReceived
                     + $nightShiftBonus  // phụ trội làm đêm
                     + $mealReceived
                     + $clothesReceived
                     + $phoneReceived
                     + $transportReceived
                     + $housingReceived          // ✅ cộng nhà ở vào gross tính thuế
                     + $performReceived
                     + $otherComponentsReceived
                     + $attendReceived
                     + $totalOtAmt
                     + $kpiBonus;

        $taxableIncome = max(0,
            $grossForTax - $siEmployee
            - self::PERSONAL_DEDUCTION
            - $dependantDeduction
        );
        $pitAmount = $this->calcPIT($taxableIncome);

        $grossSalary = $grossForTax + $leavePayout;
        $netSalary   = max(0,
            $grossSalary
            - $siEmployee
            - $pitAmount
            - $lateDeduction
            - $kpiDeduction
        );

        // ── Ghi chú tự động ──────────────────────────────────────────
        $remarkParts = [];
        if ($nightShiftBonus > 0)
            $remarkParts[] = "Phụ trội đêm: +".number_format($nightShiftBonus)." đ (30% × ".number_format($nightHoursActual,1)."h × lương CB/giờ)";
        if ($otMealDays > 0)
            $remarkParts[] = "Ăn ca OT: +".number_format($otMealBonus)." đ ($otMealDays ngày OT ≥ 3h × ".number_format(self::OT_MEAL_ALLOWANCE)." đ)";
        if ($kpiBonus > 0)
            $remarkParts[] = "Thưởng KPI: +".number_format($kpiBonus)." đ ($kpiOverDays ngày vượt)";
        if ($kpiDeduction > 0)
            $remarkParts[] = "Trừ KPI: -".number_format($kpiDeduction)." đ ($kpiUnderDays ngày không đạt)";
        if ($lateDeduction > 0) {
            $note = "Trừ muộn/sớm: -".number_format($lateDeduction)." đ";
            if ($totalLateMinutes  > 0) $note .= " (trễ: {$totalLateMinutes}p";
            if ($totalEarlyMinutes > 0) $note .= ($totalLateMinutes > 0 ? ", " : " (")."về sớm: {$totalEarlyMinutes}p";
            if ($totalLateMinutes  > 0 || $totalEarlyMinutes > 0) $note .= ")";
            $remarkParts[] = $note;
        }
        if ($holidayPaidDays > 0)
            $remarkParts[] = "Nghỉ lễ: {$holidayPaidDays} ngày (hưởng lương)";
        if (!$attendEligible && $attendBonus > 0)
            $remarkParts[] = $kpiHasDeduction
                ? "Không có chuyên cần: bị trừ KPI"
                : "Không có chuyên cần: nghỉ không phép {$unpaidLeaveDays} ngày";
        if ($hasInsurance)
            $remarkParts[] = "BHXH NV: -".number_format($siEmployee)." đ (10.5% × lương CB)";

        return [
            'period_id'                  => $periodId,
            'user_id'                    => $userId,
            'basic_salary'               => $basicSalary,
            'working_days_standard'      => $workingDays,
            'salary_per_day'             => $salaryPerDay,
            'salary_per_hour'            => $totalPerHour,
            'basic_salary_per_hour'      => $salaryPerHour,
            'actual_workdays'            => $actualWorkdays,
            'paid_leave_days'            => $paidLeaveDays,
            'other_paid_leave_days'      => $otherPaidLeaveDays,
            'unpaid_leave_days'          => $unpaidLeaveDays,
            'late_early_hours'           => $lateHours,
            'late_early_deduction'       => $lateDeduction,
            'total_paid_days'            => $totalPaidDays,
            'basic_salary_received'      => $basicReceived,
            'meal_allowance'             => $mealAllow,
            'meal_received'              => $mealReceived,
            'ot_meal_days'               => $otMealDays,
            'ot_meal_bonus'              => $otMealBonus,
            'clothes_allowance'          => $clothesAllow,
            'clothes_received'           => $clothesReceived,
            'phone_allowance'            => $phoneAllow,
            'phone_received'             => $phoneReceived,
            'transport_allowance'        => $transportAllow,
            'transport_received'         => $transportReceived,
            'housing_allowance'          => $housingAllow,   // ✅
            'housing_received'           => $housingReceived, // ✅
            'performance_bonus'          => $performReceived,
            'attendance_bonus'           => $attendReceived,
            'attendance_bonus_eligible'  => $attendEligible ? 1 : 0,
            'ot_weekday_hours'           => $otWeekdayHours,
            'ot_weekend_hours'           => $otWeekendHours,
            'ot_holiday_hours'           => $otHolidayHours,
            'ot_night_hours'             => $otNightWeekdayHours + $otNightWeekendHours + $otNightHolidayHours, // backward compat
            'ot_night_weekday_hours'     => $otNightWeekdayHours,
            'ot_night_weekend_hours'     => $otNightWeekendHours,
            'ot_night_holiday_hours'     => $otNightHolidayHours,
            'ot_weekday_amount'          => $otWeekdayAmt,
            'ot_weekend_amount'          => $otWeekendAmt,
            'ot_holiday_amount'          => $otHolidayAmt,
            'ot_night_amount'            => $otNightAmt, // backward compat
            'ot_night_weekday_amount'    => $otNightWeekdayAmt,
            'ot_night_weekend_amount'    => $otNightWeekendAmt,
            'ot_night_holiday_amount'    => $otNightHolidayAmt,
            'total_ot_amount'            => $totalOtAmt,
            'kpi_bonus'                  => $kpiBonus,
            'kpi_over_days'              => $kpiOverDays,
            'kpi_under_days'             => $kpiUnderDays,
            'annual_leave_total'         => $leaveTotal,
            'annual_leave_used'          => $leaveUsed,
            'annual_leave_remaining'     => $leaveRemaining,
            'annual_leave_payout'        => $leavePayout,
            'other_income'               => $otherComponentsReceived,
            'adjustment'                 => 0,
            'other_bonus'                => 0,
            'night_shift_bonus'          => $nightShiftBonus,
            'is_night_shift'             => $isNightShift ? 1 : 0,
            'has_social_insurance'       => $hasInsurance,
            'si_employee'                => $siEmployee,
            'si_company'                 => $siCompany,
            'dependants'                 => $dependants,
            'personal_deduction'         => self::PERSONAL_DEDUCTION,
            'dependant_deduction'        => $dependantDeduction,
            'ot_exclude_pit'             => 0,
            'taxable_income'             => $taxableIncome,
            'pit_amount'                 => $pitAmount,
            'late_deduction'             => $lateDeduction,
            'kpi_deduction'              => $kpiDeduction,
            'gross_salary'               => $grossSalary,
            'advance_payment'            => 0,
            'net_salary'                 => $netSalary,
            'pit_adjustment'             => 0,
            'bank_transfer'              => $netSalary,
            'remark'                     => implode('; ', $remarkParts),
            'is_late_warning'            => $isLateWarning ? 1 : 0,
            'late_warning_note'          => $lateWarningNote,
            'manually_adjusted'          => 0,
        ];
    }

    public function getSalaryPerDay(int $userId, ?string $refDate = null): array
    {
        if ($refDate) {
            $stmt = $this->pdo->prepare("
                SELECT working_days FROM payroll_periods
                WHERE status != 'locked'
                  AND period_from <= ? AND period_to >= ?
                ORDER BY period_from DESC LIMIT 1
            ");
            $stmt->execute([$refDate, $refDate]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT working_days FROM payroll_periods
                WHERE status != 'locked'
                ORDER BY period_from DESC LIMIT 1
            ");
            $stmt->execute();
        }
        $period      = $stmt->fetch(PDO::FETCH_ASSOC);
        $workingDays = $period ? (int)$period['working_days'] : 26;

        $salary = $this->getSalaryComponents($userId);

        $totalNoAttend = 0;
        foreach ($salary['all_components'] as $comp) {
            if ($comp['component_code'] === 'attendance_bonus') continue;
            if (in_array($comp['component_type'], ['earning', 'bonus']))
                $totalNoAttend += (int)$comp['amount'];
        }

        $salaryPerDay = $workingDays > 0 ? round($totalNoAttend / $workingDays) : 0;

        return [
            'salary_per_day' => $salaryPerDay,
            'working_days'   => $workingDays,
            'total_salary'   => $totalNoAttend,
        ];
    }

    private function getOTMealDays(int $userId, string $from, string $to): int
    {
        try {
            $hStmt = $this->pdo->prepare(
                "SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?"
            );
            $hStmt->execute([$from, $to]);
            $holidays    = $hStmt->fetchAll(PDO::FETCH_COLUMN);
            $holidayList = empty($holidays)
                ? "'0000-00-00'"
                : implode(',', array_map(fn($d) => $this->pdo->quote($d), $holidays));

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS meal_days
                FROM (
                    SELECT ot_date, SUM(hours) AS total_hours
                    FROM overtime_requests
                    WHERE user_id = ?
                      AND status  = 'approved'
                      AND ot_date BETWEEN ? AND ?
                      AND DAYOFWEEK(ot_date) != 1
                      AND ot_date NOT IN ($holidayList)
                    GROUP BY ot_date
                    HAVING total_hours >= ?
                ) AS daily_ot
            ");
            $stmt->execute([$userId, $from, $to, self::OT_MEAL_MIN_HOURS]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log("getOTMealDays error uid=$userId: " . $e->getMessage());
            return 0;
        }
    }

    private function getKpiData(int $userId, string $from, string $to): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT kr.salary_actual, kr.salary_per_day,
                       kr.is_deducted,  ka.assign_date
                FROM kpi_results kr
                JOIN kpi_assignments ka ON kr.kpi_assignment_id = ka.id
                WHERE ka.user_id = ? AND ka.assign_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $from, $to]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("getKpiData error uid=$userId: " . $e->getMessage());
            return [];
        }
    }

    private function calcAnnualLeave(int $userId, int $periodYear): int
    {
        $stmt = $this->pdo->prepare("
            SELECT date_joined, annual_leave_total
            FROM employee_profiles WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $leavePerYear = (int)($row['annual_leave_total'] ?? 12);
        $dateJoined   = $row['date_joined'] ?? null;
        if (!$dateJoined || $periodYear <= 0) return $leavePerYear;

        $joined   = new DateTime($dateJoined);
        $joinYear = (int)$joined->format('Y');
        if ($joinYear < $periodYear) return $leavePerYear;
        if ($joinYear > $periodYear) return 0;

        $joinMonth    = (int)$joined->format('m');
        $joinDay      = (int)$joined->format('d');
        $startMonth   = ($joinDay > 1) ? $joinMonth + 1 : $joinMonth;
        $monthsWorked = max(0, 12 - $startMonth + 1);
        return (int)round($monthsWorked * $leavePerYear / 12);
    }

    private function getAttendanceData(int $userId, string $from, string $to): array
    {
        try {
            $hStmt = $this->pdo->prepare(
                "SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?"
            );
            $hStmt->execute([$from, $to]);
            $holidays    = $hStmt->fetchAll(PDO::FETCH_COLUMN);
            $holidayList = empty($holidays)
                ? "'0000-00-00'"
                : implode(',', array_map(fn($d) => $this->pdo->quote($d), $holidays));

            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(
                        CASE WHEN check_in IS NOT NULL
                             AND DAYOFWEEK(work_date) != 1
                             AND work_date NOT IN ($holidayList)
                        THEN 1 END
                    ) AS actual_workdays,
                    COALESCE(SUM(
                        CASE WHEN is_late = 1
                             AND DAYOFWEEK(work_date) != 1
                             AND work_date NOT IN ($holidayList)
                        THEN late_minutes ELSE 0 END
                    ), 0) AS total_late_minutes,
                    COALESCE(SUM(
                        CASE WHEN early_leave = 1
                             AND DAYOFWEEK(work_date) != 1
                             AND work_date NOT IN ($holidayList)
                        THEN early_leave_minutes ELSE 0 END
                    ), 0) AS total_early_minutes,
                    GROUP_CONCAT(
                        CASE WHEN (is_late = 1 OR early_leave = 1)
                             AND DAYOFWEEK(work_date) != 1
                             AND work_date NOT IN ($holidayList)
                        THEN CONCAT(
                            DATE_FORMAT(work_date, '%%d/%%m'),
                            CASE WHEN is_late = 1 AND early_leave = 1 THEN '(T+S)'
                                 WHEN is_late = 1   THEN '(T)'
                                 ELSE '(S)' END
                        ) END
                        ORDER BY work_date SEPARATOR ', '
                    ) AS late_early_dates
                FROM attendance_logs
                WHERE user_id = ? AND work_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $from, $to]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: $this->emptyAttendance();
        } catch (\Throwable $e) {
            error_log("getAttendanceData error uid=$userId: " . $e->getMessage());
            return $this->emptyAttendance();
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE: Số ngày lễ rơi vào Thứ 2–Thứ 7 (được hưởng lương)
    // Chỉ tính ngày lễ >= ngày vào làm của nhân viên (date_joined)
    // ─────────────────────────────────────────────────────────────────────
    private function getHolidayWorkDays(string $from, string $to, int $userId = 0): float
    {
        // Lấy ngày vào làm của nhân viên
        $joinedDate = null;
        if ($userId > 0) {
            $stmt = $this->pdo->prepare(
                "SELECT date_joined FROM employee_profiles WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $joinedDate = $row['date_joined'] ?? null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?"
        );
        $stmt->execute([$from, $to]);
        $holidays = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($holidays as $hDate) {
            // Bỏ qua Chủ Nhật
            if ((int)(new DateTime($hDate))->format('N') === 7) continue;
            // Bỏ qua ngày lễ trước ngày vào làm
            if ($joinedDate && $hDate < $joinedDate) continue;
            $count++;
        }
        return (float)$count;
    }

    private function getLeaveData(int $userId, string $from, string $to): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COALESCE(SUM(
                        CASE WHEN leave_type = 'annual' THEN
                            DATEDIFF(LEAST(end_date, :to1), GREATEST(start_date, :from1)) + 1
                        ELSE 0 END
                    ), 0) AS paid_leave_days,
                    COALESCE(SUM(
                        CASE WHEN leave_type IN ('sick', 'other') THEN
                            DATEDIFF(LEAST(end_date, :to2), GREATEST(start_date, :from2)) + 1
                        ELSE 0 END
                    ), 0) AS other_paid_leave_days,
                    COALESCE(SUM(
                        CASE WHEN leave_type = 'unpaid' THEN
                            DATEDIFF(LEAST(end_date, :to3), GREATEST(start_date, :from3)) + 1
                        ELSE 0 END
                    ), 0) AS unpaid_leave_days
                FROM leave_requests
                WHERE user_id  = :uid
                  AND status   = 'approved'
                  AND start_date <= :to4
                  AND end_date   >= :from4
            ");
            $stmt->execute([
                ':uid'   => $userId,
                ':from1' => $from, ':to1' => $to,
                ':from2' => $from, ':to2' => $to,
                ':from3' => $from, ':to3' => $to,
                ':from4' => $from, ':to4' => $to,
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: $this->emptyLeave();
        } catch (\Throwable $e) {
            error_log("getLeaveData error uid=$userId: " . $e->getMessage());
            return $this->emptyLeave();
        }
    }

    private function getOTData(int $userId, string $from, string $to): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN ot_type = 'weekday'       THEN hours ELSE 0 END), 0) AS ot_weekday_hours,
                    COALESCE(SUM(CASE WHEN ot_type = 'weekend'       THEN hours ELSE 0 END), 0) AS ot_weekend_hours,
                    COALESCE(SUM(CASE WHEN ot_type = 'holiday'       THEN hours ELSE 0 END), 0) AS ot_holiday_hours,
                    COALESCE(SUM(CASE WHEN ot_type = 'night_weekday' THEN hours ELSE 0 END), 0) AS ot_night_weekday_hours,
                    COALESCE(SUM(CASE WHEN ot_type = 'night_weekend' THEN hours ELSE 0 END), 0) AS ot_night_weekend_hours,
                    COALESCE(SUM(CASE WHEN ot_type = 'night_holiday' THEN hours ELSE 0 END), 0) AS ot_night_holiday_hours
                FROM overtime_requests
                WHERE user_id = ? AND status = 'approved'
                  AND ot_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $from, $to]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: $this->emptyOT();
        } catch (\Throwable $e) {
            error_log("getOTData error uid=$userId: " . $e->getMessage());
            return $this->emptyOT();
        }
    }

    private function getLeaveUsed(int $userId, int $year): float
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(total_days), 0)
                FROM leave_requests
                WHERE user_id = ? AND status = 'approved'
                  AND leave_type = 'annual' AND YEAR(start_date) = ?
            ");
            $stmt->execute([$userId, $year]);
            return (float)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log("getLeaveUsed error uid=$userId: " . $e->getMessage());
            return 0;
        }
    }

    private function calcLateDeduction(float $totalMinutes, int $salaryPerHour): array
    {
        if ($totalMinutes <= self::LATE_GRACE_MINUTES) return [0.0, 0];
        $over      = $totalMinutes - self::LATE_GRACE_MINUTES;
        $blocks    = (int)ceil($over / self::LATE_BLOCK_MINUTES);
        $lateHours = round($blocks * (self::LATE_BLOCK_MINUTES / 60), 2);
        $deduction = (int)round($blocks * ($salaryPerHour / 2));
        return [$lateHours, $deduction];
    }

    private function calcPIT(int $taxableIncome): int
    {
        if ($taxableIncome <= 0) return 0;
        $tax = 0; $prev = 0;
        foreach (self::PIT_BRACKETS as [$limit, $rate]) {
            if ($taxableIncome <= $prev) break;
            $tax += (min($taxableIncome, $limit) - $prev) * $rate;
            $prev = $limit;
            if ($taxableIncome <= $limit) break;
        }
        return (int)round($tax);
    }

    private function emptyAttendance(): array
    {
        return [
            'actual_workdays'     => 0,
            'total_late_minutes'  => 0,
            'total_early_minutes' => 0,
            'late_early_dates'    => '',
        ];
    }

    private function emptyLeave(): array
    {
        return [
            'paid_leave_days'       => 0,
            'other_paid_leave_days' => 0,
            'unpaid_leave_days'     => 0,
        ];
    }

    private function emptyOT(): array
    {
        return [
            'ot_weekday_hours'       => 0,
            'ot_weekend_hours'       => 0,
            'ot_holiday_hours'       => 0,
            'ot_night_weekday_hours' => 0,
            'ot_night_weekend_hours' => 0,
            'ot_night_holiday_hours' => 0,
        ];
    }

    private function getPeriod(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payroll_periods WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function getProfile(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile) return [];
        if (!isset($profile['annual_leave_total']))   $profile['annual_leave_total']   = 12;
        if (!isset($profile['dependants']))            $profile['dependants']           = 0;
        if (!isset($profile['has_social_insurance']))  $profile['has_social_insurance'] = 0;
        return $profile;
    }

    private function getSalaryComponents(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT sc.component_code, sc.component_type,
                   sc.component_name, es.amount
            FROM employee_salaries es
            JOIN salary_components sc ON es.component_id = sc.id
            WHERE es.user_id = ? AND es.is_active = 1
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['component_code']] = (int)$r['amount'];
        }
        $map['all_components'] = $rows;
        return $map;
    }

    private function isNightShiftWorker(int $userId, string $from, string $to): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ws.is_night_shift
                FROM employee_shifts es
                JOIN work_shifts ws ON es.shift_id = ws.id
                WHERE es.user_id = ?
                  AND es.effective_date <= ?
                  AND (es.end_date IS NULL OR es.end_date >= ?)
                ORDER BY es.effective_date DESC
                LIMIT 1
            ");
            $stmt->execute([$userId, $to, $from]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && (int)$row['is_night_shift'] === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getNightHoursWorked(int $userId, string $from, string $to): float
    {
        try {
            // Lấy ca của NV trong kỳ
            $stmt = $this->pdo->prepare("
                SELECT ws.is_night_shift
                FROM employee_shifts es
                JOIN work_shifts ws ON es.shift_id = ws.id
                WHERE es.user_id = ?
                  AND es.effective_date <= ?
                  AND (es.end_date IS NULL OR es.end_date >= ?)
                ORDER BY es.effective_date DESC
                LIMIT 1
            ");
            $stmt->execute([$userId, $to, $from]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !(int)$row['is_night_shift']) return 0.0;

            // Đếm ngày thực tế đi làm trong kỳ (đã loại CN + lễ)
            $hStmt = $this->pdo->prepare(
                "SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?"
            );
            $hStmt->execute([$from, $to]);
            $holidays    = $hStmt->fetchAll(PDO::FETCH_COLUMN);
            $holidayList = empty($holidays) ? "'0000-00-00'"
                : implode(',', array_map(fn($d) => $this->pdo->quote($d), $holidays));

            $stmt2 = $this->pdo->prepare("
                SELECT COUNT(*) FROM attendance_logs
                WHERE user_id = ?
                  AND check_in IS NOT NULL
                  AND work_date BETWEEN ? AND ?
                  AND DAYOFWEEK(work_date) != 1
                  AND work_date NOT IN ($holidayList)
            ");
            $stmt2->execute([$userId, $from, $to]);
            $days = (int)$stmt2->fetchColumn();

            return (float)($days * self::WORK_HOURS_PER_DAY);
        } catch (\Throwable $e) {
            error_log("getNightHoursWorked error uid=$userId: " . $e->getMessage());
            return 0.0;
        }
    }
}
