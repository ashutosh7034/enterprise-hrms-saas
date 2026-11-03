<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\DocumentGenerationService;
use Tests\TestCase;

class DocumentApprovalAndEssTest extends TestCase
{
    protected User $adminUser;
    protected User $essUser;
    protected User $otherEssUser;
    protected Employee $employee1;
    protected Employee $employee2;
    protected Company $company;
    protected DocumentTemplate $approvalTemplate;
    protected DocumentGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generationService = app(DocumentGenerationService::class);
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $essRole = Role::where('name', 'employee_ess')->first();

        $this->adminUser = User::where('role_id', $adminRole->id)->first();
        $this->essUser = User::where('role_id', $essRole->id)->first();

        // Ensure employees are linked to users
        $employees = Employee::where('company_id', $this->company->id)->take(2)->get();
        $this->employee1 = $employees[0];
        $this->employee2 = $employees[1] ?? $employees[0];

        // Link essUser to employee1
        $this->essUser->employee_code = $this->employee1->employee_code;
        $this->essUser->save();

        $this->approvalTemplate = DocumentTemplate::where('company_id', $this->company->id)
            ->where('requires_approval', true)
            ->first();
    }

    public function test_template_requiring_approval_starts_in_pending_approval_state(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->approvalTemplate,
            employee: $this->employee1,
            actor: $this->adminUser
        );

        $this->assertEquals('Pending Approval', $doc->status);
        $this->assertNull($doc->issued_at);
        $this->assertNull($doc->approved_at);
    }

    public function test_ess_user_cannot_view_pending_approval_documents(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->approvalTemplate,
            employee: $this->employee1,
            actor: $this->adminUser
        );

        // ESS accesses own document while still pending approval -> must be rejected (403)
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('documents.ess.show', $doc));

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_pending_document_and_issue_it(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->approvalTemplate,
            employee: $this->employee1,
            actor: $this->adminUser
        );

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('admin.documents.approve', $doc));

        $response->assertStatus(302);

        $doc->refresh();
        $this->assertEquals('Issued', $doc->status);
        $this->assertNotNull($doc->issued_at);
        $this->assertEquals($this->adminUser->id, $doc->approved_by);
        $this->assertNotEmpty($doc->pdf_file_path);
    }

    public function test_admin_can_reject_pending_document_with_reason(): void
    {
        $doc = $this->generationService->generateDocument(
            template: $this->approvalTemplate,
            employee: $this->employee1,
            actor: $this->adminUser
        );

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('admin.documents.reject', $doc), [
                'rejection_reason' => 'Salary scale requires HO revision prior to issue.',
            ]);

        $response->assertStatus(302);

        $doc->refresh();
        $this->assertEquals('Rejected', $doc->status);
        $this->assertEquals('Salary scale requires HO revision prior to issue.', $doc->rejection_reason);
    }

    public function test_ess_user_can_view_and_download_own_issued_documents(): void
    {
        $template = DocumentTemplate::where('company_id', $this->company->id)
            ->where('requires_approval', false)
            ->first();

        $doc = $this->generationService->generateDocument(
            template: $template,
            employee: $this->employee1,
            actor: $this->adminUser
        );

        $this->assertEquals('Issued', $doc->status);

        // ESS views own document
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('documents.ess.show', $doc));

        $response->assertStatus(200);
        $response->assertSee($doc->title);

        // ESS downloads own document
        $downloadResponse = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('documents.ess.download', $doc));

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_ess_user_cannot_view_or_download_another_employees_document(): void
    {
        // Document generated for employee2
        $template = DocumentTemplate::where('company_id', $this->company->id)
            ->where('requires_approval', false)
            ->first();

        $docOther = $this->generationService->generateDocument(
            template: $template,
            employee: $this->employee2,
            actor: $this->adminUser
        );

        // essUser (linked to employee1) tries to access employee2's document
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('documents.ess.show', $docOther));

        $response->assertStatus(403);

        $downloadResponse = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('documents.ess.download', $docOther));

        $downloadResponse->assertStatus(403);
    }
}

