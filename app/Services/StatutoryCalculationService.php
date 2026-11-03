<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\EmployeeTaxDeclaration;
use App\Models\EmployeeTaxProjection;
use App\Models\PayrollPtSlab;
use App\Models\PayrollStatutoryConfiguration;

class StatutoryCalculationService
{
    /**
     * Compute Provident Fund (PF) for employee and employer.
     */
    public function calculatePf(
        EmployeeSalaryAssignment $assignment,
        float $basic,
        float $da,
        PayrollStatutoryConfiguration $config
    ): array {
        if (!$assignment->is_pf_eligible) {
            return ['employee_pf' => 0.00, 'employer_pf' => 0.00, 'epf_wages' => 0.00];
        }

        $epfWages = $basic + $da;
        $wageCeiling = (float) $config->pf_wage_ceiling;

        $applicableWages = $config->pf_restrict_to_ceiling
            ? min($epfWages, $wageCeiling)
            : $epfWages;

        $employeeRate = ((float) $config->pf_employee_rate) / 100.0;
        $employerRate = ((float) $config->pf_employer_rate) / 100.0;

        $employeePf = round($applicableWages * $employeeRate, 2);
        $employerPf = round($applicableWages * $employerRate, 2);

        return [
            'employee_pf' => $employeePf,
            'employer_pf' => $employerPf,
            'epf_wages' => $epfWages,
            'applicable_wages' => $applicableWages,
        ];
    }

    /**
     * Compute Employee State Insurance (ESI).
     */
    public function calculateEsi(
        EmployeeSalaryAssignment $assignment,
        float $grossEarnings,
        float $overtimeAmount,
        PayrollStatutoryConfiguration $config
    ): array {
        if (!$assignment->is_esi_eligible) {
            return ['employee_esi' => 0.00, 'employer_esi' => 0.00, 'is_covered' => false];
        }

        $ceilingWages = $config->esi_include_overtime_in_ceiling
            ? $grossEarnings
            : ($grossEarnings - $overtimeAmount);

        $wageCeiling = (float) $config->esi_wage_ceiling;

        if ($ceilingWages > $wageCeiling) {
            return ['employee_esi' => 0.00, 'employer_esi' => 0.00, 'is_covered' => false];
        }

        $employeeRate = ((float) $config->esi_employee_rate) / 100.0;
        $employerRate = ((float) $config->esi_employer_rate) / 100.0;

        $employeeEsi = ceil($grossEarnings * $employeeRate * 100) / 100.0; // ESI rounding rule: round up to nearest paise
        $employerEsi = ceil($grossEarnings * $employerRate * 100) / 100.0;

        return [
            'employee_esi' => round($employeeEsi, 2),
            'employer_esi' => round($employerEsi, 2),
            'is_covered' => true,
        ];
    }

    /**
     * Compute Professional Tax (PT).
     */
    public function calculateProfessionalTax(
        Employee $employee,
        float $grossEarnings,
        int $month
    ): float {
        $state = $employee->location?->state ?? 'Maharashtra';
        $gender = $employee->gender ?? 'Male';

        $slab = PayrollPtSlab::where('company_id', $employee->company_id)
            ->where('state', $state)
            ->where(function ($q) use ($gender) {
                $q->where('gender', $gender)->orWhere('gender', 'All');
            })
            ->where('min_monthly_salary', '<=', $grossEarnings)
            ->where(function ($q) use ($grossEarnings) {
                $q->whereNull('max_monthly_salary')
                  ->orWhere('max_monthly_salary', '>=', $grossEarnings);
            })
            ->orderByDesc('min_monthly_salary')
            ->first();

        if (!$slab) {
            return 0.00;
        }

        if ($slab->special_month && $slab->special_month === $month && $slab->special_tax_amount !== null) {
            return (float) $slab->special_tax_amount;
        }

        return (float) $slab->tax_amount;
    }

    /**
     * Compute Labour Welfare Fund (LWF).
     */
    public function calculateLwf(int $month, PayrollStatutoryConfiguration $config): array
    {
        $frequency = $config->lwf_deduction_frequency;

        if ($frequency === 'Bi-Annual June/Dec' && !in_array($month, [6, 12])) {
            return ['employee_lwf' => 0.00, 'employer_lwf' => 0.00];
        }

        return [
            'employee_lwf' => (float) $config->lwf_employee_amount,
            'employer_lwf' => (float) $config->lwf_employer_amount,
        ];
    }

    /**
     * Compute Income Tax / Monthly TDS.
     */
    public function calculateMonthlyTds(
        Employee $employee,
        float $grossMonthlyEarnings,
        int $month,
        int $year
    ): float {
        // Financial Year resolution (April to March)
        $fyStart = $month >= 4 ? $year : ($year - 1);
        $fyEnd = $fyStart + 1;
        $financialYear = "{$fyStart}-{$fyEnd}";

        $declaration = EmployeeTaxDeclaration::where('employee_id', $employee->id)
            ->where('financial_year', $financialYear)
            ->first();

        $regime = $declaration ? $declaration->tax_regime : 'New Regime';
        $annualGross = $grossMonthlyEarnings * 12.0;

        if ($regime === 'New Regime') {
            // New Regime FY 24-25 / FY 25-26
            $standardDeduction = 75000.00;
            $taxableIncome = max(0.00, $annualGross - $standardDeduction);

            // Slabs
            $tax = 0.00;
            if ($taxableIncome > 700000.00) { // Section 87A rebate exempts taxable income up to 7 Lakhs
                if ($taxableIncome > 1500000.00) {
                    $tax += ($taxableIncome - 1500000.00) * 0.30;
                    $tax += 300000.00 * 0.20; // 12-15L
                    $tax += 200000.00 * 0.15; // 10-12L
                    $tax += 300000.00 * 0.10; // 7-10L
                    $tax += 400000.00 * 0.05; // 3-7L
                } elseif ($taxableIncome > 1200000.00) {
                    $tax += ($taxableIncome - 1200000.00) * 0.20;
                    $tax += 200000.00 * 0.15;
                    $tax += 300000.00 * 0.10;
                    $tax += 400000.00 * 0.05;
                } elseif ($taxableIncome > 1000000.00) {
                    $tax += ($taxableIncome - 1000000.00) * 0.15;
                    $tax += 300000.00 * 0.10;
                    $tax += 400000.00 * 0.05;
                } elseif ($taxableIncome > 700000.00) {
                    $tax += ($taxableIncome - 700000.00) * 0.10;
                    $tax += 400000.00 * 0.05;
                }
            }
        } else {
            // Old Regime
            $standardDeduction = 50000.00;
            $exemptions = $declaration ? (
                min(150000.00, (float) $declaration->sec_80c_amount)
                + min(50000.00, (float) $declaration->sec_80d_amount)
                + min(200000.00, (float) $declaration->sec_24_home_loan_interest)
                + (float) $declaration->other_exemptions_amount
            ) : 0.00;

            $taxableIncome = max(0.00, $annualGross - $standardDeduction - $exemptions);
            $tax = 0.00;

            if ($taxableIncome > 500000.00) { // Section 87A rebate exempts taxable income up to 5 Lakhs
                if ($taxableIncome > 1000000.00) {
                    $tax += ($taxableIncome - 1000000.00) * 0.30;
                    $tax += 500000.00 * 0.20; // 5-10L
                    $tax += 250000.00 * 0.05; // 2.5-5L
                } elseif ($taxableIncome > 500000.00) {
                    $tax += ($taxableIncome - 500000.00) * 0.20;
                    $tax += 250000.00 * 0.05;
                }
            }
        }

        // Add 4% Health & Education Cess
        $totalTaxLiability = round($tax * 1.04, 2);
        $monthlyTds = round($totalTaxLiability / 12.0, 2);

        // Update or store projection
        EmployeeTaxProjection::updateOrCreate(
            ['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'financial_year' => $financialYear],
            [
                'projected_annual_gross' => $annualGross,
                'standard_deduction' => $standardDeduction,
                'net_taxable_income' => $taxableIncome,
                'projected_annual_tax' => $tax,
                'cess_amount' => round($tax * 0.04, 2),
                'net_annual_tax_liability' => $totalTaxLiability,
                'remaining_months' => 12,
                'monthly_tds_calculated' => $monthlyTds,
            ]
        );

        return $monthlyTds;
    }
}

