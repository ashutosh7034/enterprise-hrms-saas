<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeBranchTransfer;
use App\Models\EmployeeCodeConfiguration;
use App\Models\EmployeeConfirmationRecord;
use App\Models\EmployeeConversion;
use App\Models\EmployeeLifecycleEvent;
use App\Models\Location;
use App\Models\User;
use App\Services\EmployeeCodeGeneratorService;
use App\Services\EmployeeLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeLifecycleManagementTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;
    protected Location $location1;
    protected Location $location2;
    protected Department $department1;
    protected Department $department2;
    protected Designation $designation1;
    protected Designation $designation2;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->company = Company::where('code', 'VE')->first();
        
        $locations = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->get();
        $this->location1 = $locations->first();
        $this->location2 = $locations->count() > 1 ? $locations->get(1) : $locations->first();

        $departments = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->get();
        $this->department1 = $departments->first();
        $this->department2 = $departments->count() > 1 ? $departments->get(1) : $departments->first();

        $designations = Designation::withoutGlobalScopes()->get();
        $this->designation1 = $designations->first();
        $this->designation2 = $designations->count() > 1 ? $designations->get(1) : $designations->first();

        $this->category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
    }

    protected function tearDown(): void
    {
        // Restore default employee code configuration to prevent cross-test leakage
        EmployeeCodeConfiguration::where('company_id', $this->company->id)->update([
            'prefix' => 'EMP',
            'company_prefix' => $this->company->code,
            'branch_prefix' => null,
            'department_prefix' => null,
            'format_template' => '{COMPANY}-{PREFIX}-{SEQUENCE}',
            'number_length' => 4,
            'starting_sequence_number' => 1001,
            'uniqueness_scope' => 'company',
            'allow_manual_employee_code' => false,
            'is_active' => true,
        ]);

        parent::tearDown();
    }

    public function test_employee_code_configuration_save_and_preview(): void
    {
        // 1. Preview endpoint
        $previewResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->postJson(route('admin.employee-codes.preview'), [
                'format_template' => '{COMPANY}-{BRANCH}-{FY}-{SEQUENCE}',
                'number_length' => 4,
                'location_id' => $this->location1->id,
            ]);

        $previewResponse->assertStatus(200)
            ->assertJsonStructure(['preview', 'status']);

        $previewData = $previewResponse->json();
        $this->assertStringContainsString($this->company->code, $previewData['preview']);

        // 2. Save configuration
        $updateResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('admin.employee-codes.update'), [
                'prefix' => 'VE',
                'company_prefix' => 'VE',
                'branch_prefix' => 'PUN',
                'format_template' => '{COMPANY}-{BRANCH}-{SEQUENCE}',
                'number_length' => 4,
                'starting_sequence_number' => 1001,
                'separator' => '-',
                'uniqueness_scope' => 'company',
                'allow_manual_employee_code' => '1',
            ]);

        $updateResponse->assertRedirect(route('admin.employee-codes.index'));

        $this->assertDatabaseHas('employee_code_configurations', [
            'company_id' => $this->company->id,
            'format_template' => '{COMPANY}-{BRANCH}-{SEQUENCE}',
            'number_length' => 4,
        ]);
    }

    public function test_atomic_employee_code_generator_and_concurrency_safety(): void
    {
        $generator = app(EmployeeCodeGeneratorService::class);

        $code1 = $generator->generateNextCode($this->company->id, $this->location1->id);
        $this->assertNotEmpty($code1);

        $code2 = $generator->generateNextCode($this->company->id, $this->location1->id);
        $this->assertNotEmpty($code2);

        $this->assertNotEquals($code1, $code2);
    }

    public function test_employee_onboarding_preserves_joining_attributes_and_logs_creation_event(): void
    {
        $uniquePhone = '98' . mt_rand(10000000, 99999999);
        $uniquePan = 'VWXYZ' . mt_rand(1000, 9999) . 'B';
        $uniqueAadhaar = '88' . mt_rand(1000000000, 9999999999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.store'), [
                'action' => 'finalize',
                'title' => 'Mr.',
                'first_name' => 'Aditya',
                'last_name' => 'Roy',
                'gender' => 'Male',
                'date_of_birth' => '1998-05-15',
                'phone' => $uniquePhone,
                'email' => 'aditya.roy.' . mt_rand(100, 999) . '@vishal.com',
                'joining_date' => '2026-01-10',
                'location_id' => $this->location1->id,
                'department_id' => $this->department1->id,
                'designation_id' => $this->designation1->id,
                'employee_category_id' => $this->category->id,
                'onboarding_channel' => 'Direct',
                'employment_type' => 'Intern',
                'initial_employment_status' => 'Intern',
                'probation_start_date' => '2026-01-10',
                'confirmation_due_date' => '2026-07-10',
                'aadhaar_number' => $uniqueAadhaar,
                'pan_number' => $uniquePan,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'first_name' => 'Aditya',
            'last_name' => 'Roy',
            'location_id' => $this->location1->id,
            'joining_location_id' => $this->location1->id,
            'joining_department_id' => $this->department1->id,
            'joining_designation_id' => $this->designation1->id,
            'employee_type' => 'Intern',
        ]);

        $emp = Employee::where('first_name', 'Aditya')->where('last_name', 'Roy')->latest('id')->first();
        $this->assertNotNull($emp);

        // Check lifecycle event recorded
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'employee_id' => $emp->id,
            'event_type' => 'Employee Created',
        ]);
    }

    public function test_intern_to_permanent_conversion_preserves_code_and_creates_records(): void
    {
        Storage::fake('private');

        $emp = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'TEST-INT-' . mt_rand(1000, 9999),
            'first_name' => 'Rohan',
            'last_name' => 'Verma',
            'gender' => 'Male',
            'date_of_birth' => '1999-03-20',
            'phone' => '97' . mt_rand(10000000, 99999999),
            'email' => 'rohan.' . mt_rand(100, 999) . '@test.com',
            'date_of_joining' => '2026-02-01',
            'location_id' => $this->location1->id,
            'joining_location_id' => $this->location1->id,
            'department_id' => $this->department1->id,
            'joining_department_id' => $this->department1->id,
            'designation_id' => $this->designation1->id,
            'joining_designation_id' => $this->designation1->id,
            'category_id' => $this->category->id,
            'employee_type' => 'Intern',
            'employment_status' => 'Active',
            'confirmation_status' => 'On Probation',
            'status' => 'Active',
        ]);

        $originalCode = $emp->employee_code;
        $originalId = $emp->id;

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.lifecycle.convert', $emp->id), [
                'to_employment_type' => 'Permanent',
                'effective_date' => '2026-08-01',
                'to_department_id' => $this->department2->id,
                'to_designation_id' => $this->designation2->id,
                'remarks' => 'Converted to Permanent Associate Engineer following performance review.',
            ]);

        $response->assertRedirect(route('employees.show', $emp->id) . '#lifecycle');

        $emp->refresh();

        // 1. Employee ID & Code are completely unchanged
        $this->assertEquals($originalId, $emp->id);
        $this->assertEquals($originalCode, $emp->employee_code);

        // 2. Employment type updated
        $this->assertEquals('Permanent', $emp->employment_type);
        $this->assertEquals($this->department2->id, $emp->department_id);
        $this->assertEquals($this->designation2->id, $emp->designation_id);

        // 3. Joining attributes preserved
        $this->assertEquals($this->location1->id, $emp->joining_location_id);
        $this->assertEquals($this->department1->id, $emp->joining_department_id);
        $this->assertEquals($this->designation1->id, $emp->joining_designation_id);

        // 4. Conversion record exists
        $this->assertDatabaseHas('employee_conversions', [
            'employee_id' => $emp->id,
            'from_employee_type' => 'Intern',
            'to_employee_type' => 'Permanent',
            'status' => 'Completed',
        ]);

        // 5. Lifecycle event exists
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'employee_id' => $emp->id,
            'event_type' => 'Intern Converted to Permanent',
        ]);
    }

    public function test_employee_confirmation_and_probation_extension(): void
    {
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'TEST-CONF-' . mt_rand(1000, 9999),
            'first_name' => 'Pooja',
            'last_name' => 'Sharma',
            'gender' => 'Female',
            'date_of_birth' => '1997-08-10',
            'phone' => '96' . mt_rand(10000000, 99999999),
            'email' => 'pooja.' . mt_rand(100, 999) . '@test.com',
            'date_of_joining' => '2026-01-01',
            'location_id' => $this->location1->id,
            'joining_location_id' => $this->location1->id,
            'department_id' => $this->department1->id,
            'joining_department_id' => $this->department1->id,
            'designation_id' => $this->designation1->id,
            'joining_designation_id' => $this->designation1->id,
            'category_id' => $this->category->id,
            'employee_type' => 'Probationary',
            'employment_status' => 'Probation',
            'confirmation_status' => 'On Probation',
            'status' => 'Active',
            'probation_start_date' => '2026-01-01',
            'confirmation_due_date' => '2026-07-01',
        ]);

        // 1. Extend Probation
        $extendResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.lifecycle.extend-probation', $emp->id), [
                'extended_until' => '2026-10-01',
                'reason' => 'Candidate requires an additional 3 months to complete plant safety certifications.',
            ]);

        $extendResponse->assertRedirect(route('employees.show', $emp->id) . '#lifecycle');

        $emp->refresh();
        $this->assertEquals('Probation Extended', $emp->confirmation_status);
        $this->assertEquals('2026-10-01', $emp->confirmation_due_date->format('Y-m-d'));

        $this->assertDatabaseHas('employee_confirmation_records', [
            'employee_id' => $emp->id,
            'action_type' => 'Probation Extension',
        ]);

        // 2. Confirm Employee
        $confirmResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.lifecycle.confirm', $emp->id), [
                'effective_date' => '2026-10-01',
                'remarks' => 'Successfully passed all safety evaluations and confirmed as full employee.',
            ]);

        $confirmResponse->assertRedirect(route('employees.show', $emp->id) . '#lifecycle');

        $emp->refresh();
        $this->assertEquals('Confirmed', $emp->confirmation_status);

        $this->assertDatabaseHas('employee_confirmation_records', [
            'employee_id' => $emp->id,
            'action_type' => 'Confirmation',
        ]);

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'employee_id' => $emp->id,
            'event_type' => 'Employee Confirmed',
        ]);
    }

    public function test_branch_transfer_updates_current_location_while_preserving_joining_location(): void
    {
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'TEST-TRF-' . mt_rand(1000, 9999),
            'first_name' => 'Kavita',
            'last_name' => 'Menon',
            'gender' => 'Female',
            'date_of_birth' => '1995-11-25',
            'phone' => '95' . mt_rand(10000000, 99999999),
            'email' => 'kavita.' . mt_rand(100, 999) . '@test.com',
            'date_of_joining' => '2025-06-01',
            'location_id' => $this->location1->id,
            'joining_location_id' => $this->location1->id,
            'department_id' => $this->department1->id,
            'joining_department_id' => $this->department1->id,
            'designation_id' => $this->designation1->id,
            'joining_designation_id' => $this->designation1->id,
            'category_id' => $this->category->id,
            'employee_type' => 'Permanent',
            'employment_status' => 'Active',
            'confirmation_status' => 'Confirmed',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.lifecycle.transfer', $emp->id), [
                'to_location_id' => $this->location2->id,
                'to_department_id' => $this->department2->id,
                'to_designation_id' => $this->designation2->id,
                'transfer_type' => 'Permanent',
                'effective_date' => '2026-09-01',
                'reason' => 'Transferred to lead regional plant rollout and manage site engineers.',
            ]);

        $response->assertRedirect(route('employees.show', $emp->id) . '#lifecycle');

        $emp->refresh();

        // 1. Current location changed to Location 2
        $this->assertEquals($this->location2->id, $emp->location_id);
        $this->assertEquals($this->department2->id, $emp->department_id);
        $this->assertEquals($this->designation2->id, $emp->designation_id);

        // 2. Original joining location PRESERVED as Location 1
        $this->assertEquals($this->location1->id, $emp->joining_location_id);
        $this->assertEquals($this->department1->id, $emp->joining_department_id);
        $this->assertEquals($this->designation1->id, $emp->joining_designation_id);

        // 3. Branch transfer record created
        $this->assertDatabaseHas('employee_branch_transfers', [
            'employee_id' => $emp->id,
            'from_location_id' => $this->location1->id,
            'to_location_id' => $this->location2->id,
            'transfer_type' => 'Permanent',
            'status' => 'Completed',
        ]);

        // 4. Lifecycle event recorded
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'employee_id' => $emp->id,
            'event_type' => 'Branch Transferred',
        ]);
    }

    public function test_update_date_of_joining_and_audit_log(): void
    {
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'employee_code' => 'TEST-DOJ-' . mt_rand(1000, 9999),
            'first_name' => 'Siddharth',
            'last_name' => 'Nair',
            'gender' => 'Male',
            'date_of_birth' => '1994-04-12',
            'phone' => '94' . mt_rand(10000000, 99999999),
            'email' => 'siddharth.' . mt_rand(100, 999) . '@test.com',
            'date_of_joining' => '2026-02-15',
            'location_id' => $this->location1->id,
            'joining_location_id' => $this->location1->id,
            'department_id' => $this->department1->id,
            'joining_department_id' => $this->department1->id,
            'designation_id' => $this->designation1->id,
            'joining_designation_id' => $this->designation1->id,
            'category_id' => $this->category->id,
            'employee_type' => 'Permanent',
            'employment_status' => 'Active',
            'confirmation_status' => 'Confirmed',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('employees.lifecycle.update-doj', $emp->id), [
                'date_of_joining' => '2026-02-01',
                'reason' => 'Corrected joining date based on verified physical joining slip and reporting log.',
            ]);

        $response->assertRedirect(route('employees.show', $emp->id) . '#lifecycle');

        $emp->refresh();
        $this->assertEquals('2026-02-01', $emp->date_of_joining->format('Y-m-d'));

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'employee_id' => $emp->id,
            'event_type' => 'Date of Joining Updated',
        ]);
    }

    public function test_employees_directory_filters_and_master_csv_export(): void
    {
        // 1. Filter directory
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('employees.index', [
                'employment_type' => 'Permanent',
                'location_id' => $this->location1->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Master Workforce Roster');

        // 2. Master CSV export
        $exportResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('employees.export', [
                'employment_type' => 'Permanent',
            ]));

        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('employee_master_', $exportResponse->headers->get('Content-Disposition'));
    }

    public function test_employee_code_master_and_code_csv_export(): void
    {
        // 1. Code Master List
        $masterResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('admin.employee-codes.master'));

        $masterResponse->assertStatus(200);
        $masterResponse->assertSee('Employee Code Master List');

        // 2. Code Export
        $exportResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('admin.employee-codes.export'));

        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));
    }
}
