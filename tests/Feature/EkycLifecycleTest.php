<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeEkycAttempt;
use App\Models\User;
use Tests\TestCase;

class EkycLifecycleTest extends TestCase
{
    protected User $hrUser;
    protected User $hoAdmin;
    protected Company $company;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first();
        $this->company = Company::where('code', 'VE')->first();
        
        // Pick or create test employee
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        if (!$this->employee) {
            $loc = \App\Models\Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $dept = \App\Models\Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $desig = \App\Models\Designation::withoutGlobalScopes()->first();
            $cat = \App\Models\Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $this->employee = Employee::create([
                'company_id' => $this->company->id,
                'employee_code' => 'VE-EMP-1001',
                'first_name' => 'Amit',
                'last_name' => 'Sharma',
                'gender' => 'Male',
                'date_of_birth' => '1990-04-12',
                'phone' => '9811223344',
                'date_of_joining' => '2025-01-15',
                'location_id' => $loc->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'category_id' => $cat->id,
                'status' => 'Active',
                'ekyc_status' => 'Pending',
            ]);
        }
        $this->employee->update([
            'ekyc_status' => 'Pending',
            'ekyc_resubmission_deadline' => null,
        ]);
        EmployeeEkycAttempt::where('employee_id', $this->employee->id)->delete();
    }

    public function test_successful_ekyc_attempt_marks_employee_verified(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'auto',
            ]);

        $response->assertRedirect();
        
        $this->employee->refresh();
        $this->assertEquals('Verified', $this->employee->ekyc_status);
        $this->assertNull($this->employee->ekyc_resubmission_deadline);

        $attempt = EmployeeEkycAttempt::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals('Success', $attempt->result);
    }

    public function test_failed_attempt_activates_grace_period_with_14_day_deadline(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'fail_name_mismatch',
                'grace_days' => 14,
            ]);

        $response->assertRedirect();
        
        $this->employee->refresh();
        $this->assertEquals('Resubmission Required', $this->employee->ekyc_status);
        $this->assertNotNull($this->employee->ekyc_resubmission_deadline);
        $this->assertEquals(now()->addDays(14)->toDateString(), $this->employee->ekyc_resubmission_deadline->toDateString());

        $attempt = EmployeeEkycAttempt::where('employee_id', $this->employee->id)->first();
        $this->assertEquals('Failed', $attempt->result);
        $this->assertStringContainsString('name does not match', strtolower($attempt->failure_reason));
    }

    public function test_three_failed_attempts_locks_into_verification_failed(): void
    {
        // 1st Attempt
        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'fail_name_mismatch',
            ]);

        // 2nd Attempt
        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'fail_dob_mismatch',
            ]);

        // 3rd Attempt
        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'force_failure',
            ]);

        $this->employee->refresh();
        $this->assertEquals('Verification Failed', $this->employee->ekyc_status);
        $this->assertEquals(3, $this->employee->ekycAttempts()->count());

        // 4th Attempt should fail with error message requiring HO exception
        $response4 = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.attempt', $this->employee->id), [
                'mode' => 'Simulated eKYC',
                'scenario' => 'auto',
            ]);

        $response4->assertSessionHasErrors(['ekyc']);
    }

    public function test_ho_admin_can_approve_exception_override(): void
    {
        $this->employee->update(['ekyc_status' => 'Verification Failed']);

        $response = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.exception', $this->employee->id), [
                'exception_remarks' => 'In-person physical verification completed by Head Office HR director with original government stamped documents.',
            ]);

        $response->assertRedirect();

        $this->employee->refresh();
        $this->assertEquals('Exceptionally Verified', $this->employee->ekyc_status);

        $attempt = EmployeeEkycAttempt::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertEquals('Exception Approved', $attempt->result);
        $this->assertEquals($this->hoAdmin->id, $attempt->verified_by);
    }

    public function test_hr_executive_cannot_approve_exception_override(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ekyc.exception', $this->employee->id), [
                'exception_remarks' => 'Unauthorized HR attempt.',
            ]);

        $response->assertStatus(403);
    }
}
