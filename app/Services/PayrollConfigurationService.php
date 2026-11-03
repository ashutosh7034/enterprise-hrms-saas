<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollConfiguration;
use App\Models\PayrollPtSlab;
use App\Models\PayrollStatutoryConfiguration;

class PayrollConfigurationService
{
    /**
     * Get or create effective active payroll configuration for a company.
     */
    public function getActivePayrollConfig(int $companyId): PayrollConfiguration
    {
        return PayrollConfiguration::where('company_id', $companyId)
            ->where('is_active', true)
            ->latest('effective_from_date')
            ->first() ?? $this->createDefaultPayrollConfig($companyId);
    }

    /**
     * Get or create effective active statutory configuration for a company.
     */
    public function getActiveStatutoryConfig(int $companyId): PayrollStatutoryConfiguration
    {
        return PayrollStatutoryConfiguration::where('company_id', $companyId)
            ->where('is_active', true)
            ->latest('effective_from_date')
            ->first() ?? $this->createDefaultStatutoryConfig($companyId);
    }

    /**
     * Initialize default payroll configuration.
     */
    public function createDefaultPayrollConfig(int $companyId): PayrollConfiguration
    {
        return PayrollConfiguration::create([
            'company_id' => $companyId,
            'effective_from_date' => '2026-01-01',
            'attendance_calculation_basis' => 'Actual Calendar Days',
            'payroll_cutoff_day' => 25,
            'pay_day_of_month' => 1,
            'lop_deduction_basis' => 'Calendar Days',
            'ot_hourly_divisor' => 208, // Standard 26 days * 8 hours
            'ot_multiplier' => 2.00,
            'net_pay_rounding_rule' => 'Nearest Rupee',
            'is_active' => true,
        ]);
    }

    /**
     * Initialize default statutory configuration.
     */
    public function createDefaultStatutoryConfig(int $companyId): PayrollStatutoryConfiguration
    {
        $config = PayrollStatutoryConfiguration::create([
            'company_id' => $companyId,
            'effective_from_date' => '2026-01-01',
            'pf_employee_rate' => 12.00,
            'pf_employer_rate' => 12.00,
            'pf_wage_ceiling' => 15000.00,
            'pf_restrict_to_ceiling' => true,
            'esi_employee_rate' => 0.75,
            'esi_employer_rate' => 3.25,
            'esi_wage_ceiling' => 21000.00,
            'esi_include_overtime_in_ceiling' => false,
            'lwf_employee_amount' => 12.00,
            'lwf_employer_amount' => 36.00,
            'lwf_deduction_frequency' => 'Bi-Annual June/Dec',
            'is_active' => true,
        ]);

        $this->ensureDefaultPtSlabs($companyId);

        return $config;
    }

    /**
     * Ensure standard Maharashtra & Gujarat Professional Tax (PT) slabs.
     */
    public function ensureDefaultPtSlabs(int $companyId): void
    {
        $existing = PayrollPtSlab::where('company_id', $companyId)->count();
        if ($existing > 0) {
            return;
        }

        // Maharashtra Slabs (Men)
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Maharashtra',
            'gender' => 'Male',
            'min_monthly_salary' => 0.00,
            'max_monthly_salary' => 7500.00,
            'tax_amount' => 0.00,
        ]);
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Maharashtra',
            'gender' => 'Male',
            'min_monthly_salary' => 7501.00,
            'max_monthly_salary' => 10000.00,
            'tax_amount' => 175.00,
        ]);
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Maharashtra',
            'gender' => 'Male',
            'min_monthly_salary' => 10001.00,
            'max_monthly_salary' => null,
            'tax_amount' => 200.00,
            'special_month' => 2, // February
            'special_tax_amount' => 300.00,
        ]);

        // Maharashtra Slabs (Women: Exempt up to 25,000)
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Maharashtra',
            'gender' => 'Female',
            'min_monthly_salary' => 0.00,
            'max_monthly_salary' => 25000.00,
            'tax_amount' => 0.00,
        ]);
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Maharashtra',
            'gender' => 'Female',
            'min_monthly_salary' => 25001.00,
            'max_monthly_salary' => null,
            'tax_amount' => 200.00,
            'special_month' => 2,
            'special_tax_amount' => 300.00,
        ]);

        // Gujarat Slabs (All)
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Gujarat',
            'gender' => 'All',
            'min_monthly_salary' => 0.00,
            'max_monthly_salary' => 12000.00,
            'tax_amount' => 0.00,
        ]);
        PayrollPtSlab::create([
            'company_id' => $companyId,
            'state' => 'Gujarat',
            'gender' => 'All',
            'min_monthly_salary' => 12001.00,
            'max_monthly_salary' => null,
            'tax_amount' => 200.00,
        ]);
    }
}

