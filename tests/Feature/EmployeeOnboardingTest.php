<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CentralBlacklistRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeGatePassHistory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeOnboardingTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;
    protected Location $location;
    protected Department $department;
    protected Designation $designation;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->company = Company::where('code', 'VE')->first();
        $this->location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->company->id))->first();
        $this->category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
    }

    public function test_can_onboard_new_candidate_with_auto_generated_code(): void
    {
        $uniquePhone = '99' . mt_rand(10000000, 99999999);
        $uniquePan = 'ABCDE' . mt_rand(1000, 9999) . 'Z';
        $uniqueAadhaar = '99' . mt_rand(1000000000, 9999999999);
        $gatePass = 'GP-TEST-' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'first_name' => 'Rajesh',
                'last_name' => 'Kulkarni',
                'gender' => 'Male',
                'date_of_birth' => '1992-05-10',
                'phone' => $uniquePhone,
                'email' => 'rajesh.k@example.com',
                'joining_date' => now()->toDateString(),
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'employee_category_id' => $this->category->id,
                'onboarding_channel' => 'Direct',
                'gate_pass_number' => $gatePass,
                'aadhaar_number' => $uniqueAadhaar,
                'pan_number' => $uniquePan,
                'address' => 'Plot 55, MIDC Bhosari, Pune',
            ]);

        $response->assertRedirect();
        
        $employee = Employee::withoutGlobalScopes()
            ->where('phone', $uniquePhone)
            ->first();

        $this->assertNotNull($employee);
        $this->assertStringStartsWith('VE-EMP-', $employee->employee_code);
        $this->assertEquals('Rajesh', $employee->first_name);
        $this->assertEquals($gatePass, $employee->gate_pass_number);
        
        // Aadhaar security compliance: raw number is NEVER stored
        $last4 = substr($uniqueAadhaar, -4);
        $this->assertEquals('XXXX-XXXX-' . $last4, $employee->aadhaar_masked);
        $this->assertEquals(hash('sha256', $uniqueAadhaar), $employee->aadhaar_hash);
        $this->assertStringNotContainsString($uniqueAadhaar, json_encode($employee->toArray()));

        // Gate pass history recorded
        $history = EmployeeGatePassHistory::where('employee_id', $employee->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($gatePass, $history->new_gate_pass);
    }

    public function test_duplicate_candidate_in_same_company_is_rejected(): void
    {
        $existing = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'first_name' => 'Duplicate',
                'last_name' => 'Candidate',
                'gender' => 'Male',
                'date_of_birth' => '1990-01-01',
                'phone' => $existing->phone, // Duplicate phone
                'joining_date' => now()->toDateString(),
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'employee_category_id' => $this->category->id,
                'onboarding_channel' => 'Direct',
            ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_candidate_flagged_on_central_blacklist_is_halted_immediately(): void
    {
        // Ramesh Kumar is seeded on Central Blacklist with PAN ABCDE1234F
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'first_name' => 'Ramesh',
                'last_name' => 'Kumar',
                'gender' => 'Male',
                'date_of_birth' => '1988-06-15',
                'phone' => '9876543210',
                'joining_date' => now()->toDateString(),
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'employee_category_id' => $this->category->id,
                'onboarding_channel' => 'Direct',
                'pan_number' => 'ABCDE1234F', // Exact Blacklist match
            ]);

        $response->assertSessionHasErrors(['first_name']);
        $response->assertSessionHas('danger');
    }

    public function test_gate_pass_update_validates_uniqueness_and_logs_history(): void
    {
        $emp1 = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $newPass = 'GP-NEW-' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.gate-pass.update', $emp1->id), [
                'gate_pass_number' => $newPass,
                'reason' => 'Damaged RFID badge replacement',
            ]);

        $response->assertRedirect();
        
        $emp1->refresh();
        $this->assertEquals($newPass, $emp1->gate_pass_number);

        $latestHistory = EmployeeGatePassHistory::where('employee_id', $emp1->id)->latest('id')->first();
        $this->assertEquals($newPass, $latestHistory->new_gate_pass);
        $this->assertEquals('Damaged RFID badge replacement', $latestHistory->reason);
    }
}
