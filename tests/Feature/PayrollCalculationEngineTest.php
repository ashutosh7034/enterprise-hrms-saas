<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineMaster;
use App\Models\FineRecoveryRecord;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\OvertimeRecord;
use App\Models\PayrollConfiguration;
use App\Models\PayrollStatutoryConfiguration;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\LoanManagementService;
use App\Services\PayrollCalculationService;
use App\Services\PayrollConfigurationService;
use Tests\TestCase;

class PayrollCalculationEngineTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected SalaryStructure $structure;
    protected EmployeeSalaryAssignment $assignment;
    protected PayrollCalculationService $calcService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        app(PayrollConfigurationService::class)->ensureDefaultPtSlabs($this->company->id);
        PayrollStatutoryConfiguration::where('company_id', $this->company->id)->update(['pf_restrict_to_ceiling' => true]);

        $location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->company->id))->first();
        $category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $rand = rand(10000, 99999);
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-CALC-' . $rand,
            'first_name' => 'Amit',
            'last_name' => 'Patil',
            'gender' => 'Male',
            'phone' => '91' . $rand . '111',
            'date_of_birth' => '1991-03-12',
            'date_of_joining' => '2022-04-01',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'amit.' . $rand . '@vishal.com',
        ]);

        $this->structure = SalaryStructure::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'STD-CALC-TEST'],
            ['name' => 'Standard Calc Test Structure', 'currency' => 'INR', 'is_active' => true]
        );

        $this->assignment = EmployeeSalaryAssignment::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $this->structure->id,
            'ctc_annual' => 600000.00,
            'gross_monthly' => 50000.00,
            'component_values_json' => [
                'BASIC' => 25000.00,
                'HRA' => 12500.00,
                'DA' => 5000.00,
                'SPECIAL' => 7500.00,
            ],
            'effective_from_date' => '2026-01-01',
            'status' => 'Approved',
            'is_pf_eligible' => true,
            'is_esi_eligible' => false,
            'is_target_ot' => false,
            'target_ot_amount' => 0.00,
        ]);

        $this->calcService = app(PayrollCalculationService::class);
    }

    public function test_full_month_salary_calculation(): void
    {
        // April 2026 (30 days), no absent / LOP records
        $result = $this->calcService->calculateEmployeePayroll($this->employee, 2026, 4, $this->assignment);

        $this->assertEquals(30.0, $result['calendar_days']);
        $this->assertEquals(30.0, $result['paid_days']);
        $this->assertEquals(0.0, $result['loss_of_pay_days']);
        $this->assertEquals(25000.00, $result['basic_salary']);
        $this->assertEquals(12500.00, $result['hra']);
        $this->assertEquals(5000.00, $result['da']);
        $this->assertEquals(7500.00, $result['allowances_amount']);
        $this->assertEquals(50000.00, $result['gross_earnings']);

        // PF on capped ceiling 15000: 1800.00
        $this->assertEquals(1800.00, $result['pf_employee']);
        // PT for Maharashtra > 10000 is 200.00 in April
        $this->assertEquals(200.00, $result['professional_tax']);
        // Net Salary = 50000 - 1800 - 200 = 48000.00
        $this->assertEquals(48000.00, $result['net_salary']);

        // Check itemized transactions
        $this->assertNotEmpty($result['transactions']);
        $codes = collect($result['transactions'])->pluck('code')->all();
        $this->assertContains('BASIC', $codes);
        $this->assertContains('HRA', $codes);
        $this->assertContains('PF_EMP', $codes);
        $this->assertContains('PT', $codes);
    }

    public function test_prorata_salary_with_lop_days(): void
    {
        // Add 10 Absent records in April 2026
        for ($day = 1; $day <= 10; $day++) {
            AttendanceRecord::create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'attendance_date' => sprintf('2026-04-%02d', $day),
                'status' => 'Absent',
                'source' => 'Manual',
            ]);
        }

        $result = $this->calcService->calculateEmployeePayroll($this->employee, 2026, 4, $this->assignment);

        $this->assertEquals(30.0, $result['calendar_days']);
        $this->assertEquals(10.0, $result['loss_of_pay_days']);
        $this->assertEquals(20.0, $result['paid_days']);

        // Pro-rata factor = 20 / 30 = 0.666666...
        // Basic: 25000 * 20 / 30 = 16,666.67
        $this->assertEquals(16666.67, $result['basic_salary']);
        // HRA: 12500 * 20 / 30 = 8,333.33
        $this->assertEquals(8333.33, $result['hra']);
        // Gross: 50000 * 20 / 30 = 33,333.33
        $this->assertEquals(33333.33, $result['gross_earnings']);
    }

    public function test_target_ot_vs_biometric_hourly_ot_calculation(): void
    {
        // 1. Target OT configured
        $this->assignment->update([
            'is_target_ot' => true,
            'target_ot_amount' => 4500.00,
        ]);

        $resultTarget = $this->calcService->calculateEmployeePayroll($this->employee, 2026, 4, $this->assignment);
        $this->assertEquals(4500.00, $resultTarget['overtime_amount']);
        $this->assertEquals(0.00, $resultTarget['overtime_hours']);
        $this->assertEquals(54500.00, $resultTarget['gross_earnings']);

        // 2. Hourly Overtime with biometric records
        $this->assignment->update([
            'is_target_ot' => false,
            'target_ot_amount' => 0.00,
        ]);

        // Create 10 hours approved OT (600 minutes) in April 2026
        $att = AttendanceRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-04-15',
            'status' => 'Present',
            'source' => 'Biometric',
        ]);

        OvertimeRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'attendance_record_id' => $att->id,
            'ot_date' => '2026-04-15',
            'actual_minutes' => 600,
            'approved_minutes' => 600,
            'status' => 'Approved',
        ]);

        $resultHourly = $this->calcService->calculateEmployeePayroll($this->employee, 2026, 4, $this->assignment);
        $this->assertEquals(10.0, $resultHourly['overtime_hours']);

        // Rate = (Basic 25000 + DA 5000) / 208 = 30000 / 208 = 144.230769
        // Amount = 10 * 144.230769 * 2.0 (statutory double rate multiplier) = 2,884.62
        $this->assertEquals(2884.62, $resultHourly['overtime_amount']);
        $this->assertEquals(52884.62, $resultHourly['gross_earnings']);
    }

    public function test_payroll_deduction_of_loan_emi_and_fine_recovery(): void
    {
        // 1. Create an active loan using LoanManagementService disbursed on 2026-03-31
        $loanService = app(LoanManagementService::class);
        $adminUser = User::first();
        $loan = $loanService->applyLoan($this->employee, [
            'loan_type' => 'Emergency Medical Advance',
            'principal_amount' => 30000.00,
            'tenure_months' => 6,
            'interest_rate_percent' => 0.00,
            'reason' => 'Emergency medical expense',
        ]);
        $loanService->approveLoan($loan, $adminUser, 'HR');
        $loanService->disburseLoan($loan, '2026-03-31');

        // 2. Create fine recovery queued for Phase 5
        $assessment = \App\Models\AssetDamageAssessment::first();
        FineRecoveryRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'damage_assessment_id' => $assessment?->id ?? 1,
            'installment_number' => 1,
            'amount' => 1200.00,
            'payment_channel' => 'Payroll Deduction Queue',
            'status' => 'Queued for Phase 5 Payroll',
            'remarks' => 'Damaged safety helmet',
        ]);

        $result = $this->calcService->calculateEmployeePayroll($this->employee, 2026, 4, $this->assignment);

        $this->assertEquals(5000.00, $result['loan_emi_deduction']);
        $this->assertEquals(1200.00, $result['fine_recovery_deduction']);
        $this->assertEquals(6200.00, $result['other_deductions']);

        // Net Salary = 50000 - 1800 (PF) - 200 (PT) - 5000 (EMI) - 1200 (Fine) = 41800.00
        $this->assertEquals(41800.00, $result['net_salary']);

        $codes = collect($result['transactions'])->pluck('code')->all();
        $this->assertContains('LOAN_EMI', $codes);
        $this->assertContains('FINE_REC', $codes);
    }
}
