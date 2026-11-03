<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\PlaceholderResolutionService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class PlaceholderResolutionTest extends TestCase
{
    protected PlaceholderResolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlaceholderResolutionService::class);
    }

    public function test_placeholder_registry_contains_all_taxonomies(): void
    {
        $registry = $this->service->getPlaceholderRegistry();

        $this->assertArrayHasKey('Company', $registry);
        $this->assertArrayHasKey('Employee Profile', $registry);
        $this->assertArrayHasKey('Compensation & Payroll (Privileged)', $registry);
        $this->assertArrayHasKey('Document & Verification', $registry);

        $this->assertArrayHasKey('{employee_name}', $registry['Employee Profile']);
        $this->assertArrayHasKey('{company_name}', $registry['Company']);
        $this->assertArrayHasKey('{gross_salary}', $registry['Compensation & Payroll (Privileged)']);
        $this->assertArrayHasKey('{verification_code}', $registry['Document & Verification']);
    }

    public function test_resolves_standard_company_and_employee_placeholders(): void
    {
        $company = Company::where('code', 'VE')->first() ?? Company::first();
        $employee = Employee::where('company_id', $company->id)->first();

        $template = "Hello {employee_name}, welcome to {company_name}! Your code is {employee_code}.";

        $result = $this->service->resolve(
            templateHtml: $template,
            company: $company,
            employee: $employee
        );

        $this->assertStringContainsString($employee->full_name, $result);
        $this->assertStringContainsString($company->name, $result);
        $this->assertStringContainsString($employee->employee_code, $result);
        $this->assertStringNotContainsString('{employee_name}', $result);
        $this->assertStringNotContainsString('{company_name}', $result);
    }

    public function test_escapes_html_characters_safely(): void
    {
        $company = Company::where('code', 'VE')->first() ?? Company::first();
        $employee = Employee::where('company_id', $company->id)->first();

        $template = "Custom note: {xss_attempt}";
        $extraData = ['xss_attempt' => '<script>alert("hacked")</script>'];

        $result = $this->service->resolve(
            templateHtml: $template,
            company: $company,
            employee: $employee,
            extraData: $extraData
        );

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;hacked&quot;)&lt;/script&gt;', $result);
    }

    public function test_unauthorized_user_throws_exception_on_sensitive_salary_placeholder(): void
    {
        $this->expectException(AuthorizationException::class);

        $company = Company::where('code', 'VE')->first() ?? Company::first();
        $employee = Employee::where('company_id', $company->id)->first();

        // Create or get ESS user
        $essRole = Role::where('name', 'employee_ess')->first();
        $user = User::where('role_id', $essRole->id)->first();

        $template = "Your monthly pay is {gross_salary} and basic is {basic_salary}.";

        $this->service->resolve(
            templateHtml: $template,
            company: $company,
            employee: $employee,
            actor: $user
        );
    }

    public function test_authorized_hr_user_resolves_sensitive_salary_placeholder(): void
    {
        $company = Company::where('code', 'VE')->first() ?? Company::first();
        $employee = Employee::where('company_id', $company->id)->first();

        // Get HR or HO Admin user
        $hrRole = Role::whereIn('name', ['hr_executive', 'ho_admin'])->first();
        $user = User::where('role_id', $hrRole->id)->first();

        $template = "Your monthly pay is {gross_salary}.";

        $result = $this->service->resolve(
            templateHtml: $template,
            company: $company,
            employee: $employee,
            actor: $user
        );

        $this->assertStringNotContainsString('{gross_salary}', $result);
        $this->assertStringContainsString('₹', $result);
    }
}

