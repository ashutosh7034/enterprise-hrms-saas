<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class DocumentTemplateManagementTest extends TestCase
{
    protected User $adminUser;
    protected User $essUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $adminRole = Role::where('name', 'ho_admin')->first();
        $essRole = Role::where('name', 'employee_ess')->first();

        $this->adminUser = User::where('role_id', $adminRole->id)->first();
        $this->essUser = User::where('role_id', $essRole->id)->first();
    }

    public function test_admin_can_view_document_templates_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('admin.documents.templates.index', ['search' => 'Offer']));

        $response->assertStatus(200);
        $response->assertSee('Document Template Master');
        $response->assertSee('Offer of Employment Letter');
    }

    public function test_admin_can_create_document_template_with_initial_version(): void
    {
        $code = 'LETTER_TEST_' . rand(1000, 9999);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('admin.documents.templates.store'), [
                'category' => 'Employee Lifecycle',
                'document_type' => 'Test Letter',
                'code' => $code,
                'name' => 'Automated Test Template',
                'description' => 'Test description',
                'content_html' => '<p>Dear {employee_name}, this is a test template for {company_name}.</p>',
                'requires_approval' => '1',
                'is_sensitive_salary' => '0',
                'header_footer_enabled' => '1',
                'effective_from' => '2026-01-01',
            ]);

        $response->assertRedirect(route('admin.documents.templates.index'));

        $template = DocumentTemplate::where('code', $code)->first();
        $this->assertNotNull($template);
        $this->assertEquals(1, $template->version);
        $this->assertTrue($template->requires_approval);
        $this->assertCount(1, $template->versions);
        $this->assertEquals(1, $template->versions->first()->version_number);
    }

    public function test_admin_can_edit_template_and_auto_increment_version_when_content_changes(): void
    {
        $template = DocumentTemplate::where('code', 'like', 'LETTER_CONFIRMATION%')->first();

        $this->assertNotNull($template);
        $oldVersion = $template->version;

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->put(route('admin.documents.templates.update', $template), [
                'category' => $template->category,
                'document_type' => $template->document_type,
                'name' => $template->name . ' (Updated)',
                'description' => 'Updated description',
                'content_html' => $template->content_html . '<!-- New content change -->',
                'change_summary' => 'Added extra disclaimer clause',
                'status' => 'Active',
                'requires_approval' => '1',
                'is_sensitive_salary' => '0',
                'header_footer_enabled' => '1',
            ]);

        $response->assertRedirect(route('admin.documents.templates.edit', $template));

        $template->refresh();
        $this->assertEquals($oldVersion + 1, $template->version);
        $this->assertDatabaseHas('document_template_versions', [
            'document_template_id' => $template->id,
            'version_number' => $oldVersion + 1,
            'change_summary' => 'Added extra disclaimer clause',
        ]);
    }

    public function test_admin_can_duplicate_template_as_draft(): void
    {
        $template = DocumentTemplate::where('code', 'like', 'LETTER_OFFER%')->first();
        $this->assertNotNull($template);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('admin.documents.templates.duplicate', $template));

        $response->assertStatus(302);

        $this->assertDatabaseHas('document_templates', [
            'name' => $template->name . ' (Copy)',
            'status' => 'Draft',
        ]);
    }

    public function test_ess_user_forbidden_from_managing_templates(): void
    {
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('admin.documents.templates.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_preview_template(): void
    {
        $template = DocumentTemplate::first();

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('admin.documents.templates.preview', $template));

        $response->assertStatus(200);
        $response->assertSee('Preview: ' . $template->name);
    }
}
