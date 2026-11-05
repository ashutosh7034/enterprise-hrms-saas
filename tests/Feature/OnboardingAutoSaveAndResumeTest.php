<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OnboardingDraft;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingAutoSaveAndResumeTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrUser = User::where('email', 'hr@vishal.com')->firstOrFail();
        $this->company = Company::where('code', 'VE')->firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);

        OnboardingDraft::withoutGlobalScopes()->where('draft_id', 'like', 'DRFT-%')->forceDelete();
    }

    public function test_auto_save_endpoint_creates_new_draft_with_empty_or_partial_fields(): void
    {
        $response = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'gender' => 'Female',
            'current_step' => 1,
            // Notice: first_name, last_name, phone are omitted to test permissive draft auto-saving
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['draft_id']);
        $this->assertEquals(2, $data['version']); // New draft saved initializes then increments to 2
        $this->assertEquals(1, $data['current_step']);

        $draft = OnboardingDraft::where('draft_id', $data['draft_id'])->first();
        $this->assertNotNull($draft);
        $this->assertEquals('Female', $draft->form_data['gender']);
        $this->assertEquals('draft', $draft->status);
    }

    public function test_auto_save_endpoint_updates_existing_draft_and_increments_version(): void
    {
        // 1. Create initial draft
        $createResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'first_name' => 'Aarav',
            'current_step' => 1,
        ]);
        $createResp->assertOk();
        $draftId = $createResp->json('draft_id');
        $initialVersion = $createResp->json('version');

        // 2. Subsequent auto-save updating names and advancing step
        $updateResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'draft_id' => $draftId,
            'draft_version' => $initialVersion,
            'first_name' => 'Aarav',
            'last_name' => 'Kapoor',
            'phone' => '9876543210',
            'current_step' => 2,
        ]);

        $updateResp->assertOk();
        $updateData = $updateResp->json();

        $this->assertTrue($updateData['success']);
        $this->assertEquals($draftId, $updateData['draft_id']);
        $this->assertEquals($initialVersion + 1, $updateData['version']);
        $this->assertEquals(2, $updateData['current_step']);

        $draft = OnboardingDraft::where('draft_id', $draftId)->first();
        $this->assertEquals('Aarav Kapoor', $draft->candidate_display_name);
        $this->assertEquals('9876543210', $draft->phone);
    }

    public function test_auto_save_concurrency_conflict_detection(): void
    {
        // 1. Create initial draft
        $createResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'first_name' => 'Simran',
        ]);
        $draftId = $createResp->json('draft_id');
        $v1 = $createResp->json('version');

        // 2. Tab 1 updates to v2
        $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'draft_id' => $draftId,
            'draft_version' => $v1,
            'first_name' => 'Simran Kaur',
        ])->assertOk();

        // 3. Tab 2 tries to save with stale version (v1)
        $conflictResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.auto-save'), [
            'draft_id' => $draftId,
            'draft_version' => $v1,
            'first_name' => 'Simran Sharma Stale',
        ]);

        $conflictResp->assertStatus(409);
        $this->assertTrue($conflictResp->json('conflict'));
    }

    public function test_upload_and_remove_staged_document_on_draft(): void
    {
        Storage::fake('public');

        // Create draft
        $draft = OnboardingDraft::create([
            'draft_id' => 'DRFT-TESTDOC123',
            'company_id' => $this->company->id,
            'first_name' => 'DocTest',
            'version' => 1,
            'status' => 'draft',
            'uploaded_files' => ['documents' => []],
        ]);

        // Upload a document attachment to the draft
        $file = UploadedFile::fake()->create('aadhaar_card.pdf', 300, 'application/pdf');

        $uploadResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.upload-file'), [
            'draft_id' => $draft->draft_id,
            'file' => $file,
            'type' => 'Aadhaar Card',
            'category' => 'Identity',
            'title' => 'UIDAI Card Scan',
        ]);

        $uploadResp->assertOk();
        $uploadData = $uploadResp->json();
        $this->assertTrue($uploadData['success']);
        $docId = $uploadData['document']['id'];

        $draft->refresh();
        $this->assertCount(1, $draft->uploaded_files['documents']);
        $this->assertEquals('Aadhaar Card', $draft->uploaded_files['documents'][0]['type']);

        // Remove document from the draft
        $removeResp = $this->actingAs($this->hrUser)->postJson(route('employees.drafts.remove-file'), [
            'draft_id' => $draft->draft_id,
            'doc_id' => $docId,
        ]);

        $removeResp->assertOk();
        $draft->refresh();
        $this->assertCount(0, $draft->uploaded_files['documents']);
    }

    public function test_onboarding_wizard_create_loads_and_hydrates_resumed_draft(): void
    {
        $draft = OnboardingDraft::create([
            'draft_id' => 'DRFT-RESUME999',
            'company_id' => $this->company->id,
            'first_name' => 'Pooja',
            'last_name' => 'Verma',
            'current_step' => 4,
            'completion_percentage' => 35,
            'version' => 3,
            'status' => 'draft',
            'last_saved_at' => now(),
            'form_data' => [
                'first_name' => 'Pooja',
                'last_name' => 'Verma',
                'phone' => '9988776655',
            ],
        ]);

        $response = $this->actingAs($this->hrUser)->get(route('employees.create', ['draft_id' => $draft->draft_id]));

        $response->assertOk();
        $response->assertSee('DRFT-RESUME999');
        $response->assertSee('Pooja');
        $response->assertSee('Save Draft Now');
        $response->assertSee('All Drafts');
    }

    public function test_onboarding_drafts_index_page_loads_with_tenant_isolation(): void
    {
        $draft1 = OnboardingDraft::create([
            'draft_id' => 'DRFT-VE-COMPANY',
            'company_id' => $this->company->id,
            'first_name' => 'CompanyVE',
            'candidate_name' => 'Company VE Candidate',
            'status' => 'draft',
            'last_saved_at' => now(),
        ]);

        // Another company's draft
        $otherCompany = Company::where('id', '!=', $this->company->id)->firstOrFail();
        $draft2 = OnboardingDraft::create([
            'draft_id' => 'DRFT-OTHER-COMPANY',
            'company_id' => $otherCompany->id,
            'first_name' => 'CompanyOther',
            'candidate_name' => 'Isolated Other Candidate',
            'status' => 'draft',
            'last_saved_at' => now(),
        ]);

        $response = $this->actingAs($this->hrUser)->get(route('employees.drafts.index'));

        $response->assertOk();
        $response->assertSee('DRFT-VE-COMPANY');
        $response->assertSee('Company VE Candidate');
        $response->assertDontSee('DRFT-OTHER-COMPANY');
    }

    public function test_delete_draft_removes_record(): void
    {
        $draft = OnboardingDraft::create([
            'draft_id' => 'DRFT-DEL-001',
            'company_id' => $this->company->id,
            'first_name' => 'DeleteMe',
            'status' => 'draft',
        ]);

        $deleteResp = $this->actingAs($this->hrUser)->delete(route('employees.drafts.destroy', $draft->draft_id));

        $deleteResp->assertRedirect(route('employees.drafts.index'));
        $this->assertSoftDeleted('onboarding_drafts', ['id' => $draft->id]);
    }

    public function test_final_submission_validates_mandatory_fields_and_converts_draft_to_active_employee(): void
    {
        $draft = OnboardingDraft::create([
            'draft_id' => 'DRFT-CONVERT999',
            'company_id' => $this->company->id,
            'first_name' => 'Siddharth',
            'last_name' => 'Rao',
            'status' => 'draft',
            'version' => 1,
        ]);

        $location = Location::where('company_id', $this->company->id)->firstOrFail();
        $department = Department::where('company_id', $this->company->id)->firstOrFail();
        $designation = Designation::firstOrFail();
        $category = Category::where('company_id', $this->company->id)->firstOrFail();

        $uniquePhone = '9' . str_pad((string)mt_rand(100000000, 999999999), 9, '0');

        $submitData = [
            'action' => 'finalize',
            'draft_id' => $draft->draft_id,
            'first_name' => 'Siddharth',
            'last_name' => 'Rao',
            'gender' => 'Male',
            'date_of_birth' => '1995-05-15',
            'phone' => $uniquePhone,
            'email' => 'siddharth.' . mt_rand(100, 999) . '@example.com',
            'joining_date' => now()->toDateString(),
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employee_category_id' => $category->id,
            'onboarding_channel' => 'Direct',
            'employment_type' => 'Permanent',
        ];

        $response = $this->actingAs($this->hrUser)->post(route('employees.store'), $submitData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Verify active employee was created
        $employee = Employee::where('company_id', $this->company->id)
            ->where('first_name', 'Siddharth')
            ->where('phone', $uniquePhone)
            ->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Active', $employee->status);

        // Verify draft was marked as submitted and linked to employee
        $draft->refresh();
        $this->assertEquals('submitted', $draft->status);
        $this->assertEquals($employee->id, $draft->employee_id);
    }
}
