<?php

namespace App\Http\Controllers;

use App\Models\PayrollConfiguration;
use App\Models\PayrollPtSlab;
use App\Models\PayrollStatutoryConfiguration;
use App\Services\CompanyContextService;
use App\Services\PayrollConfigurationService;
use Illuminate\Http\Request;

class PayrollConfigurationController extends Controller
{
    public function __construct(
        protected PayrollConfigurationService $configService,
        protected CompanyContextService $contextService
    ) {}

    public function index()
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $payrollConfig = $this->configService->getActivePayrollConfig($companyId);
        $statutoryConfig = $this->configService->getActiveStatutoryConfig($companyId);
        $ptSlabs = PayrollPtSlab::where('company_id', $companyId)->orderBy('state')->orderBy('min_monthly_salary')->get();

        return view('payroll.configurations.index', compact('payrollConfig', 'statutoryConfig', 'ptSlabs'));
    }

    public function updatePayrollConfig(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'attendance_calculation_basis' => ['required', 'in:Actual Calendar Days,Standard 30 Days,Standard 26 Working Days'],
            'payroll_cutoff_day' => ['required', 'integer', 'min:1', 'max:31'],
            'pay_day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'ot_hourly_divisor' => ['required', 'integer', 'min:100', 'max:300'],
            'ot_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
            'net_pay_rounding_rule' => ['required', 'in:Nearest Rupee,Floor,Ceiling'],
        ]);

        $config = $this->configService->getActivePayrollConfig($companyId);
        $config->update($validated);

        return redirect()->route('payroll.configurations.index')->with('success', 'Payroll configuration updated successfully.');
    }

    public function updateStatutoryConfig(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'pf_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_wage_ceiling' => ['required', 'numeric', 'min:0'],
            'pf_restrict_to_ceiling' => ['nullable', 'boolean'],
            'esi_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_wage_ceiling' => ['required', 'numeric', 'min:0'],
            'lwf_employee_amount' => ['required', 'numeric', 'min:0'],
            'lwf_employer_amount' => ['required', 'numeric', 'min:0'],
            'lwf_deduction_frequency' => ['required', 'in:Monthly,Bi-Annual June/Dec'],
        ]);

        $validated['pf_restrict_to_ceiling'] = !empty($request->pf_restrict_to_ceiling);

        $config = $this->configService->getActiveStatutoryConfig($companyId);
        $config->update($validated);

        return redirect()->route('payroll.configurations.index')->with('success', 'Statutory configuration updated successfully.');
    }
}

