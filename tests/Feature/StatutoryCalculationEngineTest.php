<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\EmployeeTaxDeclaration;
use App\Models\Location;
use App\Models\PayrollPtSlab;
use App\Models\PayrollStatutoryConfiguration;
use App\Models\SalaryStructure;
use App\Services\StatutoryCalculationService;
use Tests\TestCase;

class StatutoryCalculationEngineTest extends TestCase
{
    protected Company $company;
    protected Employee $employeeMale;
    protected Employee $employeeFemale;
    protected EmployeeSalaryAssignment $assignment;
    protected PayrollStatutoryConfiguration $statConfig;
    protected StatutoryCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        app(\App\Services\PayrollConfigurationService::class)->ensureDefaultPtSlabs($this->company->id);

        $location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->company->id))->first();
        $category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $rand = rand(10000, 99999);
        $this->employeeMale = Employee::create([
            'company_id' => $this->company->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-STAT-M-' . $rand,
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'gender' => 'Male',
            'phone' => '99' . $rand . '123',
            'date_of_birth' => '1990-01-01',
            'date_of_joining' => '2023-01-01',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'ramesh.' . $rand . '@vishal.com',
        ]);

        $this->employeeFemale = Employee::create([
            'company_id' => $this->company->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-STAT-F-' . $rand,
            'first_name' => 'Pooja',
            'last_name' => 'Sharma',
            'gender' => 'Female',
            'phone' => '98' . $rand . '456',
            'date_of_birth' => '1992-05-15',
            'date_of_joining' => '2023-02-01',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'pooja.' . $rand . '@vishal.com',
        ]);

        $structure = SalaryStructure::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'EXEC-TEST'],
            ['name' => 'Executive Test Structure', 'currency' => 'INR', 'is_active' => true]
        );

        $this->assignment = EmployeeSalaryAssignment::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employeeMale->id,
            'salary_structure_id' => $structure->id,
            'ctc_annual' => 600000.00,
            'gross_monthly' => 50000.00,
            'effective_from_date' => '2026-01-01',
            'status' => 'Approved',
            'is_pf_eligible' => true,
            'is_esi_eligible' => true,
        ]);

        $this->statConfig = PayrollStatutoryConfiguration::firstOrCreate(
            ['company_id' => $this->company->id],
            [
                'pf_employee_rate' => 12.00,
                'pf_employer_rate' => 12.00,
                'pf_wage_ceiling' => 15000.00,
                'pf_restrict_to_ceiling' => true,
                'pf_eps_rate' => 8.33,
                'pf_eps_ceiling' => 1250.00,
                'pf_edli_rate' => 0.50,
                'pf_admin_rate' => 0.50,
                'esi_employee_rate' => 0.75,
                'esi_employer_rate' => 3.25,
                'esi_wage_ceiling' => 21000.00,
                'esi_include_overtime_in_ceiling' => false,
                'lwf_employee_amount' => 12.00,
                'lwf_employer_amount' => 36.00,
                'lwf_deduction_frequency' => 'Bi-Annual June/Dec',
                'standard_deduction_new_regime' => 75000.00,
                'standard_deduction_old_regime' => 50000.00,
                'section_87a_rebate_limit_new' => 700000.00,
                'section_87a_rebate_limit_old' => 500000.00,
            ]
        );

        $this->service = new StatutoryCalculationService();
    }

    public function test_pf_calculation_capped_vs_uncapped(): void
    {
        // 1. Basic <= 15000: e.g. 12000
        $result = $this->service->calculatePf($this->assignment, 12000.00, 0.00, $this->statConfig);
        $this->assertEquals(1440.00, $result['employee_pf']);
        $this->assertEquals(1440.00, $result['employer_pf']);
        $this->assertEquals(12000.00, $result['applicable_wages']);

        // 2. Basic > 15000 with ceiling enabled: e.g. 25000
        $this->statConfig->update(['pf_restrict_to_ceiling' => true]);
        $resultCapped = $this->service->calculatePf($this->assignment, 25000.00, 0.00, $this->statConfig);
        $this->assertEquals(1800.00, $resultCapped['employee_pf']);
        $this->assertEquals(1800.00, $resultCapped['employer_pf']);
        $this->assertEquals(15000.00, $resultCapped['applicable_wages']);

        // 3. Basic > 15000 with ceiling disabled: uncapped
        $this->statConfig->update(['pf_restrict_to_ceiling' => false]);
        $resultUncapped = $this->service->calculatePf($this->assignment, 25000.00, 0.00, $this->statConfig);
        $this->assertEquals(3000.00, $resultUncapped['employee_pf']);
        $this->assertEquals(3000.00, $resultUncapped['employer_pf']);
        $this->assertEquals(25000.00, $resultUncapped['applicable_wages']);

        // 4. Ineligible
        $this->assignment->update(['is_pf_eligible' => false]);
        $resultIneligible = $this->service->calculatePf($this->assignment, 25000.00, 0.00, $this->statConfig);
        $this->assertEquals(0.00, $resultIneligible['employee_pf']);
    }

    public function test_esi_calculation_threshold_and_rates(): void
    {
        // 1. Covered gross <= 21000: e.g. 18000
        $result = $this->service->calculateEsi($this->assignment, 18000.00, 0.00, $this->statConfig);
        $this->assertTrue($result['is_covered']);
        $this->assertEquals(135.00, $result['employee_esi']); // 18000 * 0.75%
        $this->assertEquals(585.00, $result['employer_esi']); // 18000 * 3.25%

        // 2. Exceeding wage ceiling: gross 25000
        $resultExceeded = $this->service->calculateEsi($this->assignment, 25000.00, 0.00, $this->statConfig);
        $this->assertFalse($resultExceeded['is_covered']);
        $this->assertEquals(0.00, $resultExceeded['employee_esi']);
        $this->assertEquals(0.00, $resultExceeded['employer_esi']);

        // 3. Overtime inclusion rule: gross 22000 including 3000 OT -> base wages 19000 <= 21000
        $this->statConfig->update(['esi_include_overtime_in_ceiling' => false]);
        $resultWithOt = $this->service->calculateEsi($this->assignment, 22000.00, 3000.00, $this->statConfig);
        $this->assertTrue($resultWithOt['is_covered']);
        // When covered, ESI is calculated on total gross including OT: 22000 * 0.75% = 165
        $this->assertEquals(165.00, $resultWithOt['employee_esi']);
    }

    public function test_professional_tax_maharashtra_gender_and_february_special(): void
    {
        // Male earning 15000 in normal month (e.g. Month 5 - May)
        $ptMayMale = $this->service->calculateProfessionalTax($this->employeeMale, 15000.00, 5);
        $this->assertEquals(200.00, $ptMayMale);

        // Male earning 15000 in February (Month 2) -> Special ₹300 tax
        $ptFebMale = $this->service->calculateProfessionalTax($this->employeeMale, 15000.00, 2);
        $this->assertEquals(300.00, $ptFebMale);

        // Female earning 20000 in normal month -> Maharashtra exempts females up to 25,000
        $ptMayFemale = $this->service->calculateProfessionalTax($this->employeeFemale, 20000.00, 5);
        $this->assertEquals(0.00, $ptMayFemale);

        // Female earning 35000 in normal month -> > 25000 pays 200
        $ptMayHighFemale = $this->service->calculateProfessionalTax($this->employeeFemale, 35000.00, 5);
        $this->assertEquals(200.00, $ptMayHighFemale);

        // Female earning 35000 in February -> Special ₹300 tax
        $ptFebHighFemale = $this->service->calculateProfessionalTax($this->employeeFemale, 35000.00, 2);
        $this->assertEquals(300.00, $ptFebHighFemale);
    }

    public function test_lwf_deduction_frequency(): void
    {
        $this->statConfig->update([
            'lwf_deduction_frequency' => 'Bi-Annual June/Dec',
            'lwf_employee_amount' => 12.00,
            'lwf_employer_amount' => 36.00,
        ]);

        // Month 5 (May): No deduction
        $lwfMay = $this->service->calculateLwf(5, $this->statConfig);
        $this->assertEquals(0.00, $lwfMay['employee_lwf']);
        $this->assertEquals(0.00, $lwfMay['employer_lwf']);

        // Month 6 (June): Deducted
        $lwfJune = $this->service->calculateLwf(6, $this->statConfig);
        $this->assertEquals(12.00, $lwfJune['employee_lwf']);
        $this->assertEquals(36.00, $lwfJune['employer_lwf']);

        // Month 12 (December): Deducted
        $lwfDec = $this->service->calculateLwf(12, $this->statConfig);
        $this->assertEquals(12.00, $lwfDec['employee_lwf']);
        $this->assertEquals(36.00, $lwfDec['employer_lwf']);
    }

    public function test_tds_new_regime_and_old_regime(): void
    {
        // 1. New regime with income <= 7,00,000 rebate: 50,000 monthly = 6,00,000 annual
        // Taxable income = 600000 - 75000 = 525000 <= 700000 rebate -> 0 tax
        $tdsRebate = $this->service->calculateMonthlyTds($this->employeeMale, 50000.00, 5, 2026);
        $this->assertEquals(0.00, $tdsRebate);

        // 2. New regime with high income: 1,50,000 monthly = 18,00,000 annual
        // Taxable income = 1800000 - 75000 = 1725000
        // Slabs:
        // 3-7L (4L @ 5%) = 20,000
        // 7-10L (3L @ 10%) = 30,000
        // 10-12L (2L @ 15%) = 30,000
        // 12-15L (3L @ 20%) = 60,000
        // >15L (2.25L @ 30%) = 67,500
        // Total Base Tax = 2,07,500 + 4% cess (8,300) = 2,15,800
        // Monthly TDS = 215800 / 12 = 17,983.33
        $tdsHigh = $this->service->calculateMonthlyTds($this->employeeMale, 150000.00, 5, 2026);
        $this->assertEquals(17983.33, $tdsHigh);

        // 3. Old Regime declaration
        EmployeeTaxDeclaration::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employeeFemale->id,
            'financial_year' => '2026-2027',
            'tax_regime' => 'Old Regime',
            'sec_80c_amount' => 150000.00,
            'sec_80d_amount' => 25000.00,
            'status' => 'Verified',
        ]);

        // Monthly 80,000 = 9,60,000 annual
        // Standard deduction: 50,000
        // 80C + 80D = 1,75,000
        // Net Taxable = 960000 - 50000 - 175000 = 735000
        // Old regime slabs:
        // 2.5L - 5L (2.5L @ 5%) = 12,500
        // 5L - 7.35L (2.35L @ 20%) = 47,000
        // Total Base Tax = 59,500 + 4% cess (2,380) = 61,880
        // Monthly TDS = 61880 / 12 = 5156.67
        $tdsOld = $this->service->calculateMonthlyTds($this->employeeFemale, 80000.00, 5, 2026);
        $this->assertEquals(5156.67, $tdsOld);
    }
}
