<?php

/**
 * ManualPayrollEngine — tính lương từ bảng manual_attendance.
 * Hoàn toàn độc lập với PayrollEngine.php, không kế thừa, không sửa.
 */
class ManualPayrollEngine
{
    private PDO $pdo;

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
    const OT_MEAL_ALLOWANCE   = 14_000;
    const OT_MEAL_MIN_HOURS   = 3.0;
    const WORK_HOURS_PER_DAY  = 8;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Kiểm tra xem nhân viên có dữ liệu manual_attendance cho kỳ này không
    // ─────────────────────────────────────────────────────────────────────
    public static function hasManualData(PDO $pdo, int $userId, int $periodYear, int $periodMonth): bool
    {
        try {
            $payPeriod = sprintf('%04d-%02d', $periodYear, $periodMonth);
            $stmt = $pdo->prepare(
                "SELECT 1 FROM manual_attendance WHERE user_id = ? AND pay_period = ? LIMIT 1"
            );
            $stmt->execute([$userId, $payPeriod]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log("ManualPayrollEngine::hasManualData error uid=$userId: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Phương thức tính lương chính
    // ─────────────────────────────────────────────────────────────────────
    public function calculate(int $periodId, int $userId): array
    {
        // ── Bước 1: Lấy dữ liệu ─────────────────────────────────────
        $period  = $this->getPeriod($periodId);
        $profile = $this->getProfile($userId);
        $salary  = $this->getSalaryComponents($userId);

        if (empty($period)) {
            throw new \RuntimeException("ManualPayrollEngine: Không tìm thấy kỳ lương #$periodId");
        }

        $payPeriod = sprintf('%04d-%02d', (int)$period['period_year'], (int)$period['period_month']);

        $stmt = $this->pdo->prepare(
            "SELECT * FROM manual_attendance WHERE user_id = ? AND pay_period = ? LIMIT 1"
        );
        $stmt->execute([$userId, $payPeriod]);
        $manual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$manual) {
            throw new \RuntimeException(
                "ManualPayrollEngine: Không có dữ liệu manual_attendance cho user #$userId kỳ $payPeriod"
            );
        }

        // ── Bước 2: Lấy các khoản lương cơ bản ─────────────────────
        $basicSalary         = (int)($salary['basic']                   ?? 0);
        $mealAllow           = (int)($salary['meal']                    ?? 0);
        $clothesAllow        = (int)($salary['clothes']                 ?? 0);
        $phoneAllow          = (int)($salary['phone']                   ?? 0);
        $transportAllow      = (int)($salary['transport']               ?? 0);
        $housingAllow        = (int)($salary['housing']                 ?? 0);
        $performBonus        = (int)($salary['performance']             ?? 0);
        $attendBonus         = (int)($salary['attendance_bonus']        ?? 0);
        $responsibilityAllow = (int)($salary['responsibility_allowance'] ?? 0);
        $seniorityAllow      = (int)($salary['seniority_allowance']     ?? 0);

        $workingDays = (int)$period['working_days'];

        // ── Bước 3: Tính lương theo giờ ──────────────────────────────
        // Lương/giờ tính OT = (basic + PC trách nhiệm + PC thâm niên) / working_days / 8
        $otBase        = $basicSalary + $responsibilityAllow + $seniorityAllow;
        $basicPerDay   = $workingDays > 0 ? (int)round($otBase / $workingDays) : 0;
        $salaryPerHour = (int)round($basicPerDay / self::WORK_HOURS_PER_DAY);

        // Ngày công & các loại ngày nghỉ từ manual_attendance
        $actualWorkdays     = (float)($manual['actual_work_days']    ?? 0);
        $paidLeaveDays      = (float)($manual['paid_leave_days']     ?? 0);
        $unpaidLeaveDays    = (float)($manual['unpaid_leave_days']   ?? 0);
        $holidayDays        = (float)($manual['holiday_days']        ?? 0);
        $insuranceLeaveDays = (float)($manual['insurance_leave_days'] ?? 0);
        $personalLeaveDays  = (float)($manual['personal_leave_days'] ?? 0);

        // Tổng ngày hưởng lương
        $totalPaidDays = $actualWorkdays + $paidLeaveDays + $holidayDays
                       + $insuranceLeaveDays + $personalLeaveDays;

        // ── Bước 4: Tính lương cơ bản thực nhận ─────────────────────
        $totalSalaryNoAttend = 0;
        foreach ($salary['all_components'] as $comp) {
            if ($comp['component_code'] === 'attendance_bonus') continue;
            if (in_array($comp['component_type'], ['earning', 'bonus']))
                $totalSalaryNoAttend += (int)$comp['amount'];
        }

        $salaryPerDay  = $workingDays > 0 ? (int)round($totalSalaryNoAttend / $workingDays) : 0;
        $basicReceived = $workingDays > 0
            ? (int)round($basicSalary * ($totalPaidDays / $workingDays))
            : 0;

        // ── Bước 5: Tính OT từ các cột hours_xxx ────────────────────
        $hours_100 = (float)($manual['hours_100'] ?? 0);
        $hours_130 = (float)($manual['hours_130'] ?? 0);
        $hours_150 = (float)($manual['hours_150'] ?? 0);
        $hours_200 = (float)($manual['hours_200'] ?? 0);
        $hours_300 = (float)($manual['hours_300'] ?? 0);
        $hours_210 = (float)($manual['hours_210'] ?? 0);
        $hours_270 = (float)($manual['hours_270'] ?? 0);
        $hours_390 = (float)($manual['hours_390'] ?? 0);

        // Phụ trội ca đêm = giờ_130 × salaryPerHour × 30%
        $nightShiftBonus = $hours_130 > 0
            ? (int)round($hours_130 * $salaryPerHour * 0.30)
            : 0;

        // OT amounts
        $otWeekdayAmt      = (int)round($hours_150 * $salaryPerHour * 1.5);
        $otHolidayAmt      = (int)round($hours_200 * $salaryPerHour * 2.0);
        $otWeekendAmt      = (int)round($hours_300 * $salaryPerHour * 3.0);
        $otNightWeekdayAmt = (int)round($hours_210 * $salaryPerHour * 2.1);
        $otNightWeekendAmt = (int)round($hours_270 * $salaryPerHour * 2.7);
        $otNightHolidayAmt = (int)round($hours_390 * $salaryPerHour * 3.9);

        $totalOtAmt = $otWeekdayAmt + $otHolidayAmt + $otWeekendAmt
                    + $otNightWeekdayAmt + $otNightWeekendAmt + $otNightHolidayAmt;

        // ── Bước 6: Trợ cấp (theo tỷ lệ ngày) ───────────────────────
        $allowanceRatio = $workingDays > 0
            ? min(1.0, $totalPaidDays / $workingDays)
            : 0.0;
        $mealRatio = $workingDays > 0
            ? min(1.0, $actualWorkdays / $workingDays)
            : 0.0;

        // Ăn ca OT: tổng giờ OT / 3 (làm tròn xuống) × 14.000đ
        $totalOtHours = $hours_150 + $hours_200 + $hours_300
                      + $hours_210 + $hours_270 + $hours_390;
        $otMealDays   = (int)floor($totalOtHours / self::OT_MEAL_MIN_HOURS);
        $otMealBonus  = $otMealDays * self::OT_MEAL_ALLOWANCE;

        $mealReceived           = (int)round($mealAllow * $mealRatio) + $otMealBonus;
        $clothesReceived        = (int)round($clothesAllow        * $allowanceRatio);
        $phoneReceived          = (int)round($phoneAllow          * $allowanceRatio);
        $transportReceived      = (int)round($transportAllow      * $allowanceRatio);
        $housingReceived        = (int)round($housingAllow        * $allowanceRatio);
        $responsibilityReceived = (int)round($responsibilityAllow * $allowanceRatio);
        $seniorityReceived      = (int)round($seniorityAllow      * $allowanceRatio);
        $performReceived        = (int)round($performBonus        * $allowanceRatio);

        // Chuyên cần: chỉ được nếu đủ ngày và không có nghỉ không phép
        $attendEligible = ($allowanceRatio >= 1.0 && $unpaidLeaveDays == 0);
        $attendReceived = $attendEligible ? $attendBonus : 0;

        // Other components
        $otherComponentsReceived = 0;
        $excludedCodes = [
            'basic', 'meal', 'clothes', 'phone', 'transport', 'housing',
            'responsibility_allowance', 'seniority_allowance',
            'performance', 'attendance_bonus',
        ];
        foreach ($salary['all_components'] as $comp) {
            if (in_array($comp['component_code'], $excludedCodes)) continue;
            if (!in_array($comp['component_type'], ['earning', 'bonus'])) continue;
            $otherComponentsReceived += (int)round((int)$comp['amount'] * $allowanceRatio);
        }

        // ── Bước 7: BHXH + Thuế ─────────────────────────────────────
        $hasInsurance = (int)($profile['has_social_insurance'] ?? 0);
        $siEmployee   = $hasInsurance ? (int)round($basicSalary * self::SI_EMPLOYEE_RATE) : 0;
        $siCompany    = $hasInsurance ? (int)round($basicSalary * self::SI_COMPANY_RATE)  : 0;

        $dependants         = (int)($profile['dependants'] ?? 0);
        $dependantDeduction = $dependants * self::DEPENDANT_DEDUCTION;

        $grossForTax = $basicReceived
                     + $responsibilityReceived
                     + $seniorityReceived
                     + $nightShiftBonus
                     + $mealReceived
                     + $clothesReceived
                     + $phoneReceived
                     + $transportReceived
                     + $housingReceived
                     + $performReceived
                     + $otherComponentsReceived
                     + $attendReceived
                     + $totalOtAmt;

        $taxableIncome = max(0,
            $grossForTax - $siEmployee - self::PERSONAL_DEDUCTION - $dependantDeduction
        );
        $pitAmount = $this->calcPIT($taxableIncome);

        $grossSalary = $grossForTax;
        $netSalary   = max(0, $grossSalary - $siEmployee - $pitAmount);

        // ── Bước 8: Ghi chú tự động ──────────────────────────────────
        $remarkParts = ['[Tính tay]'];
        if ($nightShiftBonus > 0) {
            $remarkParts[] = "Phụ trội đêm: +" . number_format($nightShiftBonus) . " đ (30% × " . number_format($hours_130, 1) . "h)";
        }
        if ($otMealBonus > 0) {
            $remarkParts[] = "Ăn ca OT: +" . number_format($otMealBonus) . " đ ($otMealDays ngày OT ≥ 3h × " . number_format(self::OT_MEAL_ALLOWANCE) . " đ)";
        }
        if ($responsibilityReceived > 0) {
            $remarkParts[] = "PC Trách nhiệm: +" . number_format($responsibilityReceived) . " đ";
        }
        if ($seniorityReceived > 0) {
            $remarkParts[] = "PC Thâm niên: +" . number_format($seniorityReceived) . " đ";
        }
        if ($hasInsurance) {
            $remarkParts[] = "BHXH NV: -" . number_format($siEmployee) . " đ (10.5% × lương CB)";
        }

        // ── Bước 9: Return array ─────────────────────────────────────
        return [
            'period_id'                         => $periodId,
            'user_id'                           => $userId,
            'basic_salary'                      => $basicSalary,
            'working_days_standard'             => $workingDays,
            'salary_per_day'                    => $salaryPerDay,
            'salary_per_hour'                   => $salaryPerHour,
            'basic_salary_per_hour'             => $salaryPerHour,
            'actual_workdays'                   => $actualWorkdays,
            'paid_leave_days'                   => $paidLeaveDays,
            'other_paid_leave_days'             => $holidayDays + $insuranceLeaveDays + $personalLeaveDays,
            'unpaid_leave_days'                 => $unpaidLeaveDays,
            'late_early_hours'                  => 0,
            'late_early_deduction'              => 0,
            'total_paid_days'                   => $totalPaidDays,
            'basic_salary_received'             => $basicReceived,
            'meal_allowance'                    => $mealAllow,
            'meal_received'                     => $mealReceived,
            'ot_meal_days'                      => $otMealDays,
            'ot_meal_bonus'                     => $otMealBonus,
            'clothes_allowance'                 => $clothesAllow,
            'clothes_received'                  => $clothesReceived,
            'phone_allowance'                   => $phoneAllow,
            'phone_received'                    => $phoneReceived,
            'transport_allowance'               => $transportAllow,
            'transport_received'                => $transportReceived,
            'housing_allowance'                 => $housingAllow,
            'housing_received'                  => $housingReceived,
            'responsibility_allowance'          => $responsibilityAllow,
            'responsibility_allowance_received' => $responsibilityReceived,
            'seniority_allowance'               => $seniorityAllow,
            'seniority_allowance_received'      => $seniorityReceived,
            'performance_bonus'                 => $performReceived,
            'attendance_bonus'                  => $attendReceived,
            'attendance_bonus_eligible'         => $attendEligible ? 1 : 0,
            'ot_weekday_hours'                  => $hours_150,
            'ot_weekend_hours'                  => $hours_300,
            'ot_holiday_hours'                  => $hours_200,
            'ot_night_hours'                    => $hours_210 + $hours_270 + $hours_390,
            'ot_night_weekday_hours'            => $hours_210,
            'ot_night_weekend_hours'            => $hours_270,
            'ot_night_holiday_hours'            => $hours_390,
            'ot_weekday_amount'                 => $otWeekdayAmt,
            'ot_weekend_amount'                 => $otWeekendAmt,
            'ot_holiday_amount'                 => $otHolidayAmt,
            'ot_night_amount'                   => $otNightWeekdayAmt + $otNightWeekendAmt + $otNightHolidayAmt,
            'ot_night_weekday_amount'           => $otNightWeekdayAmt,
            'ot_night_weekend_amount'           => $otNightWeekendAmt,
            'ot_night_holiday_amount'           => $otNightHolidayAmt,
            'total_ot_amount'                   => $totalOtAmt,
            'kpi_bonus'                         => 0,
            'kpi_over_days'                     => 0,
            'kpi_under_days'                    => 0,
            'annual_leave_total'                => 0,
            'annual_leave_used'                 => 0,
            'annual_leave_remaining'            => 0,
            'annual_leave_payout'               => 0,
            'other_income'                      => $otherComponentsReceived,
            'adjustment'                        => 0,
            'other_bonus'                       => 0,
            'night_shift_bonus'                 => $nightShiftBonus,
            'is_night_shift'                    => $hours_130 > 0 ? 1 : 0,
            'has_social_insurance'              => $hasInsurance,
            'si_employee'                       => $siEmployee,
            'si_company'                        => $siCompany,
            'dependants'                        => $dependants,
            'personal_deduction'                => self::PERSONAL_DEDUCTION,
            'dependant_deduction'               => $dependantDeduction,
            'ot_exclude_pit'                    => 0,
            'taxable_income'                    => $taxableIncome,
            'pit_amount'                        => $pitAmount,
            'late_deduction'                    => 0,
            'kpi_deduction'                     => 0,
            'gross_salary'                      => $grossSalary,
            'advance_payment'                   => 0,
            'net_salary'                        => $netSalary,
            'pit_adjustment'                    => 0,
            'bank_transfer'                     => $netSalary,
            'remark'                            => implode('; ', $remarkParts),
            'is_late_warning'                   => 0,
            'late_warning_note'                 => '',
            'manually_adjusted'                 => 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private helpers (độc lập với PayrollEngine)
    // ─────────────────────────────────────────────────────────────────────

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
        if (!isset($profile['annual_leave_total']))  $profile['annual_leave_total']  = 12;
        if (!isset($profile['dependants']))           $profile['dependants']          = 0;
        if (!isset($profile['has_social_insurance'])) $profile['has_social_insurance'] = 0;
        return $profile;
    }

    private function getSalaryComponents(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT es.id, es.component_id, es.custom_name, es.custom_name_en,
                   es.amount, es.component_type, es.approval_status,
                   sc.component_code, sc.component_name,
                   sc.component_name_en AS sc_name_en,
                   sc.component_type AS sc_type
            FROM employee_salaries es
            LEFT JOIN salary_components sc ON es.component_id = sc.id
            WHERE es.user_id = ? AND es.is_active = 1
            ORDER BY
                CASE WHEN es.approval_status = 'approved' THEN 0 ELSE 1 END ASC,
                es.id ASC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by component_id: keep approved if available, else pending
        $grouped = [];
        $custom  = [];

        foreach ($rows as $r) {
            if ($r['component_id']) {
                $cid = (int)$r['component_id'];
                if (!isset($grouped[$cid])) {
                    $grouped[$cid] = $r;
                }
            } else {
                $custom[] = $r;
            }
        }

        $finalRows = array_values($grouped);

        $customGrouped = [];
        foreach ($custom as $r) {
            $key = strtolower(trim($r['custom_name'] ?? ''));
            if ($key === '') {
                $finalRows[] = $r;
            } elseif (!isset($customGrouped[$key])) {
                $customGrouped[$key] = $r;
            }
        }
        foreach ($customGrouped as $r) {
            $finalRows[] = $r;
        }

        $map = [];
        foreach ($finalRows as $r) {
            $code = $r['component_code'] ?? null;
            if ($code) {
                $map[$code] = (int)$r['amount'];
            }
        }
        $map['all_components'] = $finalRows;
        return $map;
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
}
