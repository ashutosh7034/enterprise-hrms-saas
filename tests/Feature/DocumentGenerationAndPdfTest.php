<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\DocumentGenerationService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentGenerationAndPdfTest extends TestCase
{
    protected User $adminUser;
    protected Company $companyVE;
    protected Company $companyVRE;
    protected Employee $employeeVE;
    protected DocumentTemplate $templateVE;
    protected DocumentGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generationService = app(DocumentGenerationService::class);
        $this->companyVE = Company::where('code', 'VE')->first() ?? Company::first();
        $this->companyVRE = Company::where('code', 'VRE')->first() ?? Company::skip(1)->first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $this->adminUser = User::where('role_id', $adminRole->id)->first();

        $this->employeeVE = Employee::where('company_id', $this->companyVE->id)->first();
        $this->templateVE = DocumentTemplate::where('company_id', $this->companyVE->id)
            ->where('requires_approval', false)
            ->first();
    }

    public function test_admin_can_generate_document_with_unique_number_and_verification_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->companyVE->id])
            ->post(route('admin.documents.store'), [
                'template_id' => $this->templateVE->id,
                'employee_id' => $this->employeeVE->id,
                'effective_date' => now()->toDateString(),
                'signatory_name' => 'Dr. R. K. Sharma',
                'signatory_designation' => 'Executive Director',
            ]);

        $response->assertStatus(302);

        $document = GeneratedDocument::where('employee_id', $this->employeeVE->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($document);
        $this->assertStringStartsWith('DOC-VE-', $document->document_number);
        $this->assertStringStartsWith('VE-DOC-', $document->verification_code);
        $this->assertEquals('Issued', $document->status);
        $this->assertNotNull($document->issued_at);
        $this->assertNotNull($document->approved_at);
    }

    public function test_document_generation_freezes_immutable_snapshot_html_and_metadata(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->templateVE,
            employee: $this->employeeVE,
            actor: $this->adminUser,
            customData: ['special_token' => 'IMMUTABLE_TEST_TOKEN']
        );

        $this->assertNotEmpty($doc->content_snapshot_html);
        $this->assertStringContainsString($this->employeeVE->full_name, $doc->content_snapshot_html);
        $this->assertStringContainsString($this->companyVE->name, $doc->content_snapshot_html);

        $metadata = $doc->metadata_snapshot_json;
        $this->assertIsArray($metadata);
        $this->assertEquals($this->employeeVE->id, $metadata['employee']['id']);
        $this->assertEquals($this->companyVE->id, $metadata['company']['id']);
        $this->assertEquals($this->adminUser->id, $metadata['generated_by']['id']);
    }

    public function test_pdf_binary_is_generated_and_saved_in_private_storage(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->templateVE,
            employee: $this->employeeVE,
            actor: $this->adminUser
        );

        $this->assertNotEmpty($doc->pdf_file_path);
        $this->assertTrue(Storage::disk('local')->exists($doc->pdf_file_path));
        $this->assertGreaterThan(0, $doc->file_size_kb);

        $fileContent = Storage::disk('local')->get($doc->pdf_file_path);
        $this->assertStringStartsWith('%PDF-', $fileContent);
    }

    public function test_authorized_user_can_download_generated_pdf(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->templateVE,
            employee: $this->employeeVE,
            actor: $this->adminUser
        );

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->companyVE->id])
            ->get(route('admin.documents.download', $doc));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cross_company_document_download_is_forbidden(): void
    {
        // Document belongs to Company VE
        $docVE = $this->generationService->generateDocument(
            template: $this->templateVE,
            employee: $this->employeeVE,
            actor: $this->adminUser
        );

        // Try downloading while in Company VRE context
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->companyVRE->id])
            ->get(route('admin.documents.download', $docVE));

        // Expect 403 or 404 due to BelongsToCompany global scope
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}

