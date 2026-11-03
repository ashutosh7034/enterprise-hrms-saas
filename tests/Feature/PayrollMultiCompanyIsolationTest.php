<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FnfSettlement;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\PayrollProcessingService;
use Tests\TestCase;

class PayrollMultiCompanyIsolationTest extends TestCase
{
    protected Company $veCompany;
    protected Company $vreCompany;
    protected User $veAdmin;
    protected ?User $vreAdmin = null;
    protected CompanyContextService $contextService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->veCompany = Company::where('code', 'VE')->firstOrFail();
        $this->vreCompany = Company::where('code', 'VRE')->firstOrFail();

        $this->veAdmin = User::where('company_id', $this->veCompany->id)
            ->whereHas('role', fn($q) => $q->where('name', 'ho_admin'))
            ->first() ?? User::where('email', 'admin@vishal.com')->first();

        $user = User::where('company_id', $this->vreCompany->id)
            ->whereHas('role', fn($q) => $q->where('name', 'ho_admin'))
            ->first();

        if (!$user) {
            $hoRole = \App\Models\Role::where('name', 'ho_admin')->first();
            $user = User::create([
                'company_id' => $this->vreCompany->id,
                'role_id' => $hoRole?->id,
                'name' => 'VRE HO Admin',
                'email' => 'admin.vre.' . rand(1000, 9999) . '@vrishal.com',
                'password' => bcrypt('Password@123'),
                'is_active' => true,
            ]);
        }
        $this->vreAdmin = $user;

        $this->contextService = app(CompanyContextService::class);
    }

    public function test_salary_structures_are_strictly_isolated_by_company(): void
    {
        // 1. In VE context
        $this->contextService->setActiveCompanyId($this->veCompany->id);
        $veStructures = SalaryStructure::all();
        $this->assertNotEmpty($veStructures);
        foreach ($veStructures as $s) {
            $this->assertEquals($this->veCompany->id, $s->company_id);
            $this->assertNotEquals($this->vreCompany->id, $s->company_id);
        }

        // 2. In VRE context
        $this->contextService->setActiveCompanyId($this->vreCompany->id);
        $vreStructures = SalaryStructure::all();
        foreach ($vreStructures as $s) {
            $this->assertEquals($this->vreCompany->id, $s->company_id);
            $this->assertNotEquals($this->veCompany->id, $s->company_id);
        }
    }

    public function test_payroll_runs_and_periods_are_isolated_by_company(): void
    {
        $this->contextService->setActiveCompanyId($this->veCompany->id);
        $veRuns = PayrollRun::all();
        foreach ($veRuns as $r) {
            $this->assertEquals($this->veCompany->id, $r->company_id);
        }

        $this->contextService->setActiveCompanyId($this->vreCompany->id);
        $vreRuns = PayrollRun::all();
        foreach ($vreRuns as $r) {
            $this->assertEquals($this->vreCompany->id, $r->company_id);
        }
    }

    public function test_loans_and_fnf_settlements_are_isolated_by_company(): void
    {
        $this->contextService->setActiveCompanyId($this->veCompany->id);
        $veLoans = EmployeeLoan::all();
        foreach ($veLoans as $l) {
            $this->assertEquals($this->veCompany->id, $l->company_id);
        }

        $veFnf = FnfSettlement::all();
        foreach ($veFnf as $f) {
            $this->assertEquals($this->veCompany->id, $f->company_id);
        }

        $this->contextService->setActiveCompanyId($this->vreCompany->id);
        $vreLoans = EmployeeLoan::all();
        foreach ($vreLoans as $l) {
            $this->assertEquals($this->vreCompany->id, $l->company_id);
        }

        $vreFnf = FnfSettlement::all();
        foreach ($vreFnf as $f) {
            $this->assertEquals($this->vreCompany->id, $f->company_id);
        }
    }

    public function test_cross_company_run_access_blocked_via_controller(): void
    {
        $veRun = PayrollRun::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if ($veRun) {
            // Attempting to access VE run with VRE user
            $response = $this->actingAs($this->vreAdmin)
                ->withSession(['active_company_id' => $this->vreCompany->id])
                ->get(route('payroll.runs.show', $veRun));

            $this->assertContains($response->status(), [403, 404]);
        }
    }
}
