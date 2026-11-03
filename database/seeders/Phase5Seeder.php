<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\LoanManagementService;
use App\Services\PayrollConfigurationService;
use App\Services\SalaryStructureService;
use Illuminate\Database\Seeder;

class Phase5Seeder extends Seeder
{
    public function run(): void
    {
        $structureService = app(SalaryStructureService::class);
        $configService = app(PayrollConfigurationService::class);
        $loanService = app(LoanManagementService::class);

        $companies = Company::all();
        $adminUser = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first()
            ?? User::first();

        foreach ($companies as $company) {
            // 1. Ensure Standard Salary Components
            $structureService->ensureStandardComponents($company);

            // 2. Ensure Configurations
            $configService->getActivePayrollConfig($company->id);
            $configService->getActiveStatutoryConfig($company->id);

            // 3. Create Standard Salary Structures
            $stdStructure = SalaryStructure::firstOrCreate(
                ['company_id' => $company->id, 'code' => "STD-{$company->code}"],
                [
                    'name' => "Standard {$company->name} Staff Structure",
                    'description' => '50% Basic, 25% HRA, 10% DA, 15% Special Allowance with standard statutory deductions',
                    'is_target_ot' => false,
                    'target_ot_amount' => 0.00,
                    'is_active' => true,
                ]
            );

            $targetOtStructure = SalaryStructure::firstOrCreate(
                ['company_id' => $company->id, 'code' => "TGT-OT-{$company->code}"],
                [
                    'name' => "Target OT Structure - {$company->code}",
                    'description' => 'Fixed ₹3,000 monthly overtime allowance for site maintenance crew',
                    'is_target_ot' => true,
                    'target_ot_amount' => 3000.00,
                    'is_active' => true,
                ]
            );

            // 4. Assign Salary to Active Employees
            $employees = Employee::withoutGlobalScopes()->where('company_id', $company->id)->take(5)->get();
            foreach ($employees as $idx => $emp) {
                $chosenStructure = ($idx === 1) ? $targetOtStructure : $stdStructure;
                $gross = 35000.00 + ($idx * 10000.00); // 35k, 45k, 55k, etc.

                $structureService->assignSalaryStructure(
                    $emp,
                    $chosenStructure,
                    [
                        'effective_from_date' => '2026-01-01',
                        'gross_monthly' => $gross,
                        'ctc_annual' => $gross * 12.0,
                        'component_values' => [
                            'BASIC' => round($gross * 0.50, 2),
                            'HRA' => round($gross * 0.25, 2),
                            'DA' => round($gross * 0.10, 2),
                            'SPECIAL' => round($gross * 0.15, 2),
                        ],
                        'is_pf_eligible' => true,
                        'is_esi_eligible' => ($gross <= 21000.00),
                        'revision_notes' => 'Initial Salary Baseline Setup',
                    ],
                    $adminUser,
                    autoApprove: true
                );

                // Create a sample loan for employee 0
                if ($idx === 0) {
                    $existingLoan = EmployeeLoan::where('employee_id', $emp->id)->first();
                    if (!$existingLoan) {
                        $loan = $loanService->applyLoan($emp, [
                            'loan_type' => 'Salary Advance',
                            'principal_amount' => 30000.00,
                            'tenure_months' => 6,
                            'interest_rate_percent' => 0.00,
                            'reason' => 'Home festival advance request',
                        ]);
                        $loanService->approveLoan($loan, $adminUser, 'HR');
                        $loanService->disburseLoan($loan, '2026-01-05');
                    }
                }
            }
        }
    }
}

