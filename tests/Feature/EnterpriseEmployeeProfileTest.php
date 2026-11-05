<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankDetail;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeProfileHistory;
use App\Models\EmployeeSkill;
use App\Models\EmployeeSkillHistory;
use App\Models\EmployeeStatutoryDetail;
use App\Models\EmployeeVisaDetail;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseEmployeeProfileTest extends TestCase
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

    public function test_can_create_employee_via_16_section_wizard_and_persist_all_relations(): void
    {
        $uniquePhone = '98' . mt_rand(10000000, 99999999);
        $uniquePan = 'VWXYZ' . mt_rand(1000, 9999) . 'A';
        $uniqueAadhaar = '88' . mt_rand(1000000000, 9999999999);
        $gatePass = 'GP-ENT-' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'action' => 'finalize',
                'title' => 'Mr.',
                'first_name' => 'Vikram',
                'middle_name' => 'Aditya',
                'last_name' => 'Sharma',
                'gender' => 'Male',
                'date_of_birth' => '1990-08-15',
                'nationality' => 'Indian',
                'blood_group' => 'B+',
                'phone' => $uniquePhone,
                'email' => 'vikram.sharma.' . mt_rand(100, 999) . '@example.com',
                'joining_date' => '2023-01-10',
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'designation_id' => $this->designation->id,
                'employee_category_id' => $this->category->id,
                'onboarding_channel' => 'Direct',
                'gate_pass_number' => $gatePass,
                'ticket_number' => 'TKT-9901',
                'pan_number' => $uniquePan,
                'aadhaar_number' => $uniqueAadhaar,

                // Address fields
                'curr_flat' => 'Flat 402, Lotus Towers',
                'curr_street' => 'MG Road',
                'curr_area' => 'Shivajinagar',
                'curr_city' => 'Pune',
                'curr_dist' => 'Pune',
                'curr_state' => 'Maharashtra',
                'curr_pin' => '411005',
                'permanent_same_as_current' => 1,

                // Statutory fields
                'uan_number' => '100900800700',
                'pf_number' => 'MH/PUN/0045/001',
                'esic_number' => '31000123450001',

                // Banking fields
                'bank_name' => 'State Bank of India',
                'bank_branch' => 'Pune Main',
                'bank_ifsc' => 'SBIN0000454',
                'bank_account_number' => '12345678901234',
                'bank_passbook_name' => 'Vikram Aditya Sharma',

                // Visa fields
                'passport_number' => 'Z9876543',
                'passport_issue_date' => '2020-01-01',
                'passport_expiry_date' => '2030-01-01',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $employee = Employee::withoutGlobalScopes()
            ->where('phone', $uniquePhone)
            ->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Vikram', $employee->first_name);
        $this->assertEquals('Sharma', $employee->last_name);
        $this->assertEquals('TKT-9901', $employee->ticket_number);

        // Verify address record was created
        $this->assertDatabaseHas('employee_addresses', [
            'employee_id' => $employee->id,
            'current_city' => 'Pune',
            'current_pincode' => '411005',
            'permanent_same_as_current' => 1,
        ]);

        // Verify statutory record was created
        $this->assertDatabaseHas('employee_statutory_details', [
            'employee_id' => $employee->id,
            'uan_number' => '100900800700',
            'pf_number' => 'MH/PUN/0045/001',
        ]);

        // Verify bank details were created and masked
        $bank = EmployeeBankDetail::where('employee_id', $employee->id)->first();
        $this->assertNotNull($bank);
        $this->assertEquals('State Bank of India', $bank->bank_name);
        $this->assertStringEndsWith('1234', $bank->masked_account_number);

        // Verify visa details were created
        $this->assertDatabaseHas('employee_visa_details', [
            'employee_id' => $employee->id,
            'passport_number' => 'Z9876543',
        ]);
    }

    public function test_can_save_candidate_as_draft_without_requiring_all_fields(): void
    {
        $uniquePhone = '97' . mt_rand(10000000, 99999999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'action' => 'draft',
                'first_name' => 'IncompleteDraftCandidate',
                'phone' => $uniquePhone,
                'onboarding_channel' => 'Direct',
            ]);

        $response->assertRedirect();

        $employee = Employee::withoutGlobalScopes()
            ->where('phone', $uniquePhone)
            ->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Draft', $employee->onboarding_status);
        $this->assertEquals('Draft', $employee->status);
    }

    public function test_can_update_dossier_and_generates_change_history_entry(): void
    {
        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->assertNotNull($employee);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->put(route('employees.update', $employee->id), [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'gender' => $employee->gender,
                'date_of_birth' => $employee->date_of_birth?->format('Y-m-d') ?? '1995-01-01',
                'phone' => $employee->phone,
                'email' => $employee->email,
                'joining_date' => $employee->joining_date?->format('Y-m-d') ?? now()->toDateString(),
                'location_id' => $employee->location_id,
                'department_id' => $employee->department_id,
                'designation_id' => $employee->designation_id,
                'employee_category_id' => $employee->category_id,
                'employment_status' => 'Active',
                'display_name' => 'Revised Display Name ' . mt_rand(10, 99),
                'change_reason' => 'Annual Performance Review Profile Update',
                'curr_city' => 'Bengaluru',
                'curr_pin' => '560001',
            ]);

        $response->assertRedirect();

        // Verify profile history entry was created
        $this->assertDatabaseHas('employee_profile_histories', [
            'employee_id' => $employee->id,
            'change_reason' => 'Annual Performance Review Profile Update',
            'changed_by' => $this->hrUser->id,
        ]);
    }

    public function test_can_manage_repeatable_family_members(): void
    {
        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        // Add family member
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.family.add', $employee->id), [
                'full_name' => 'Suman Sharma',
                'relationship' => 'Spouse',
                'date_of_birth' => '1993-04-12',
                'phone' => '9822001122',
                'is_emergency_contact' => 1,
                'is_dependent' => 1,
            ]);

        $response->assertRedirect();

        $member = EmployeeFamilyMember::where('employee_id', $employee->id)
            ->where('name', 'Suman Sharma')
            ->first();

        $this->assertNotNull($member);
        $this->assertEquals('Spouse', $member->relationship);
        $this->assertTrue((bool)$member->is_emergency_contact);

        // Delete family member
        $delResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->delete(route('employees.family.delete', [$employee->id, $member->id]));

        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('employee_family_members', ['id' => $member->id]);
    }

    public function test_can_manage_repeatable_educations(): void
    {
        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.education.add', $employee->id), [
                'degree_level' => 'Bachelor',
                'qualification_name' => 'Bachelor of Technology',
                'institute_name' => 'Pune University',
                'specialization' => 'Mechanical Engineering',
                'year_of_passing' => 2016,
                'percentage_or_cgpa' => 78.5,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $edu = EmployeeEducation::where('employee_id', $employee->id)
            ->where('qualification_name', 'Bachelor of Technology')
            ->first();

        $this->assertNotNull($edu);
        $this->assertEquals('Pune University', $edu->institute_name);
        $this->assertEquals('Verified', $edu->verification_status);

        // Delete education
        $delResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->delete(route('employees.education.delete', [$employee->id, $edu->id]));

        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('employee_educations', ['id' => $edu->id]);
    }

    public function test_can_manage_skills_matrix_and_verifies_immutable_history_ledger(): void
    {
        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $skillName = 'Precision CNC Machining ' . mt_rand(100, 999);

        // Add Skill
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.skills.add', $employee->id), [
                'skill_name' => $skillName,
                'skill_category' => 'Technical',
                'proficiency_level' => 'Advanced',
                'years_of_experience' => 4.5,
                'is_certified' => 1,
                'remarks' => 'Certified precision lathe and CNC mill operator',
            ]);

        $response->assertRedirect();

        $skill = EmployeeSkill::where('employee_id', $employee->id)
            ->where('skill_name', $skillName)
            ->first();

        $this->assertNotNull($skill);
        $this->assertEquals('Advanced', $skill->proficiency_level);
        $this->assertTrue((bool)$skill->is_certified);

        // Verify immutable skill history ledger entry
        $this->assertDatabaseHas('employee_skill_histories', [
            'employee_skill_id' => $skill->id,
            'new_level' => 'Advanced',
            'changed_by' => $this->hrUser->id,
        ]);
    }

    public function test_ess_profile_lifecycle_and_review_workflow(): void
    {
        // 1. Create a linked employee + user
        $uniquePhone = '96' . mt_rand(10000000, 99999999);
        $email = 'ess.tester.' . mt_rand(1000, 9999) . '@example.com';

        $employee = Employee::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'ESS-' . mt_rand(10000, 99999),
            'first_name' => 'Anil',
            'last_name' => 'Deshmukh',
            'gender' => 'Male',
            'date_of_birth' => '1994-06-20',
            'phone' => $uniquePhone,
            'email' => $email,
            'date_of_joining' => now()->toDateString(),
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'category_id' => $this->category->id,
            'onboarding_channel' => 'Direct',
            'employment_status' => 'Active',
            'onboarding_status' => 'Draft',
        ]);

        $user = User::factory()->create([
            'email' => $email,
            'company_id' => $this->company->id,
            'phone' => $uniquePhone,
        ]);

        // 2. Employee views ESS profile
        $viewRes = $this->actingAs($user)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('ess.profile.show'));

        $viewRes->assertStatus(200);

        // 3. Employee updates permitted fields
        $updateRes = $this->actingAs($user)
            ->withSession(['active_company_id' => $this->company->id])
            ->put(route('ess.profile.update'), [
                'display_name' => 'Anil D.',
                'personal_phone' => $uniquePhone,
                'emergency_contact_name' => 'Sunita Deshmukh',
                'emergency_contact_phone' => '9800112233',
                'curr_city' => 'Pune',
            ]);

        $updateRes->assertRedirect(route('ess.profile.show'));
        $employee->refresh();
        $this->assertEquals('Anil D.', $employee->display_name);

        // 4. Employee submits for HR review
        $submitRes = $this->actingAs($user)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('ess.profile.submit'));

        $submitRes->assertRedirect(route('ess.profile.show'));
        $employee->refresh();
        $this->assertEquals('Submitted for Review', $employee->onboarding_status);

        // 5. HR requests corrections
        $corrRes = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.request-correction', $employee->id), [
                'correction_notes' => 'Please provide complete permanent address and Aadhaar card copy.',
            ]);

        $corrRes->assertRedirect(route('employees.show', $employee->id));
        $employee->refresh();
        $this->assertEquals('Corrections Requested', $employee->onboarding_status);
        $this->assertEquals('Please provide complete permanent address and Aadhaar card copy.', $employee->review_notes);

        // 6. HR approves profile
        $apprRes = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.approve', $employee->id), [
                'remarks' => 'All profile data verified and approved by HR operations.',
            ]);

        $apprRes->assertRedirect(route('employees.show', $employee->id));
        $employee->refresh();
        $this->assertEquals('Approved', $employee->onboarding_status);
        $this->assertNotNull($employee->reviewed_at);
        $this->assertEquals($this->hrUser->id, $employee->reviewed_by);
    }

    public function test_can_provision_ess_portal_access_for_employee(): void
    {
        $uniquePhone = '95' . mt_rand(10000000, 99999999);
        $portalEmail = 'portal.user.' . mt_rand(1000, 9999) . '@example.com';

        $employee = Employee::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'ACC-' . mt_rand(10000, 99999),
            'first_name' => 'Pradeep',
            'last_name' => 'Jadhav',
            'gender' => 'Male',
            'date_of_birth' => '1991-03-14',
            'date_of_joining' => now()->toDateString(),
            'phone' => $uniquePhone,
            'email' => $portalEmail,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'category_id' => $this->category->id,
            'onboarding_channel' => 'Direct',
            'employment_status' => 'Active',
            'onboarding_status' => 'Approved',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.provision-access', $employee->id), [
                'email' => $portalEmail,
                'password' => 'SecurePass123!@#',
            ]);

        $response->assertRedirect(route('employees.show', $employee->id));

        $user = User::where('email', $portalEmail)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Pradeep Jadhav', $user->name);
        $this->assertEquals($this->company->id, $user->company_id);

        // Verify linkage
        $employee->refresh();
        $this->assertEquals($user->id, $employee->userAccount?->id);
    }

    public function test_compensation_segregation_and_masking(): void
    {
        $uniquePhone = '94' . mt_rand(10000000, 99999999);
        $employee = Employee::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'BNK-' . mt_rand(10000, 99999),
            'first_name' => 'Kavita',
            'last_name' => 'Patil',
            'gender' => 'Female',
            'date_of_birth' => '1993-11-25',
            'date_of_joining' => now()->toDateString(),
            'phone' => $uniquePhone,
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'category_id' => $this->category->id,
            'onboarding_channel' => 'Direct',
            'employment_status' => 'Active',
        ]);

        $bank = EmployeeBankDetail::create([
            'employee_id' => $employee->id,
            'bank_name' => 'HDFC Bank',
            'bank_branch' => 'Kothrud, Pune',
            'bank_ifsc' => 'HDFC0001234',
            'bank_account_number' => '50100234567890',
            'bank_account_type' => 'Salary',
        ]);

        $this->assertStringEndsWith('7890', $bank->masked_account_number);
        $this->assertStringNotContainsString('5010023456', $bank->masked_account_number);
    }
}
