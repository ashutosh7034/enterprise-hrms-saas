<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\DocumentVerificationLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\DocumentGenerationService;
use Tests\TestCase;

class PublicDocumentVerificationAndSecurityTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected User $adminUser;
    protected DocumentGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generationService = app(DocumentGenerationService::class);
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::where('company_id', $this->company->id)->first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $this->adminUser = User::where('role_id', $adminRole->id)->first();
    }

    public function test_public_verification_page_loads_without_authentication(): void
    {
        $response = $this->get(route('documents.verify.form'));

        $response->assertStatus(200);
        $response->assertSee('Document Verification Portal');
    }

    public function test_valid_verification_code_returns_authentic_record_status(): void
    {
        $template = DocumentTemplate::where('company_id', $this->company->id)
            ->where('requires_approval', false)
            ->first();

        $doc = $this->generationService->generateDocument(
            template: $template,
            employee: $this->employee,
            actor: $this->adminUser
        );

        $response = $this->get(route('documents.verify.direct', ['code' => $doc->verification_code]));

        $response->assertStatus(200);
        $response->assertSee('AUTHENTIC DIGITAL RECORD VERIFIED');
        $response->assertSee($doc->document_number);
        $response->assertSee($this->company->name);

        $this->assertDatabaseHas('document_verification_logs', [
            'generated_document_id' => $doc->id,
            'verification_code_queried' => $doc->verification_code,
            'result_status' => 'Valid',
        ]);
    }

    public function test_invalid_verification_code_returns_invalid_status_and_logs_query(): void
    {
        $fakeCode = 'INVALID-CODE-999';

        $response = $this->get(route('documents.verify.direct', ['code' => $fakeCode]));

        $response->assertStatus(200);
        $response->assertSee('VERIFICATION STATUS: INVALID');

        $this->assertDatabaseHas('document_verification_logs', [
            'generated_document_id' => null,
            'verification_code_queried' => $fakeCode,
            'result_status' => 'Invalid',
        ]);
    }

    public function test_zero_pii_leakage_on_public_verification_endpoint(): void
    {
        $template = DocumentTemplate::where('company_id', $this->company->id)
            ->where('is_sensitive_salary', true)
            ->first() ?? DocumentTemplate::first();

        $doc = $this->generationService->generateDocument(
            template: $template,
            employee: $this->employee,
            actor: $this->adminUser
        );

        $response = $this->get(route('documents.verify.direct', ['code' => $doc->verification_code]));

        $content = $response->getContent();

        // 1. Recipient full name must be masked
        $this->assertStringNotContainsString($this->employee->full_name, $content);

        // 2. Sensitive personal data must NEVER appear
        if (!empty($this->employee->phone)) {
            $this->assertStringNotContainsString($this->employee->phone, $content);
        }
        if (!empty($this->employee->address)) {
            $this->assertStringNotContainsString($this->employee->address, $content);
        }

        // 3. Financial figures must NEVER appear in public verification
        $this->assertStringNotContainsString('CTC', $content);
        $this->assertStringNotContainsString('Gross Salary', $content);
        $this->assertStringNotContainsString('Basic Pay', $content);
    }

    public function test_revoked_document_shows_revoked_status_in_verification(): void
    {
        $template = DocumentTemplate::where('company_id', $this->company->id)->first();

        $doc = $this->generationService->generateDocument(
            template: $template,
            employee: $this->employee,
            actor: $this->adminUser
        );

        // Cancel document
        $this->generationService->cancelDocument($doc, $this->adminUser, 'Testing cancellation');

        $response = $this->get(route('documents.verify.direct', ['code' => $doc->verification_code]));

        $response->assertStatus(200);
        $response->assertSee('VERIFICATION STATUS: REVOKED');

        $this->assertDatabaseHas('document_verification_logs', [
            'generated_document_id' => $doc->id,
            'result_status' => 'Revoked',
        ]);
    }
}
