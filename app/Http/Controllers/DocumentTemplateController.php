<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\Employee;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use App\Services\Document\PlaceholderResolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService,
        protected PlaceholderResolutionService $placeholderService,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display a listing of document templates.
     */
    public function index(Request $request): View
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $query = DocumentTemplate::query()
            ->with(['creator', 'company'])
            ->withCount('versions', 'generatedDocuments');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search)
                  ->orWhere('document_type', 'like', $search);
            });
        }

        $templates = $query->orderBy('category')->orderBy('name')->paginate(15);
        $categories = [
            'Employee Lifecycle',
            'Employee Request',
            'HR Compliance',
            'Employee Forms',
            'Leave & Attendance Forms',
            'Payroll & Finance Forms',
            'Administrative Forms',
        ];

        return view('documents.templates.index', compact('templates', 'categories'));
    }

    /**
     * Show form for creating a new document template.
     */
    public function create(): View
    {
        $placeholders = $this->placeholderService->getPlaceholderRegistry();
        $categories = [
            'Employee Lifecycle',
            'Employee Request',
            'HR Compliance',
            'Employee Forms',
            'Leave & Attendance Forms',
            'Payroll & Finance Forms',
            'Administrative Forms',
        ];

        return view('documents.templates.create', compact('placeholders', 'categories'));
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'document_type' => 'required|string|max:100',
            'code' => 'required|string|max:50|alpha_dash|unique:document_templates,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'requires_approval' => 'boolean',
            'is_sensitive_salary' => 'boolean',
            'header_footer_enabled' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['created_by'] = $request->user()->id;
        $validated['version'] = 1;
        $validated['status'] = 'Active';
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_sensitive_salary'] = $request->boolean('is_sensitive_salary');
        $validated['header_footer_enabled'] = $request->boolean('header_footer_enabled', true);

        $template = DocumentTemplate::create($validated);

        DocumentTemplateVersion::create([
            'document_template_id' => $template->id,
            'version_number' => 1,
            'content_html' => $template->content_html,
            'change_summary' => 'Initial version',
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->log(
            action: 'TEMPLATE_CREATED',
            model: $template,
            newValues: ['code' => $template->code, 'name' => $template->name]
        );

        return redirect()->route('admin.documents.templates.index')
            ->with('success', "Template '{$template->name}' created successfully.");
    }

    /**
     * Show form for editing an existing template.
     */
    public function edit(DocumentTemplate $template): View
    {
        $template->loadMissing(['versions.creator', 'creator']);
        $placeholders = $this->placeholderService->getPlaceholderRegistry();
        $categories = [
            'Employee Lifecycle',
            'Employee Request',
            'HR Compliance',
            'Employee Forms',
            'Leave & Attendance Forms',
            'Payroll & Finance Forms',
            'Administrative Forms',
        ];

        return view('documents.templates.edit', compact('template', 'placeholders', 'categories'));
    }

    /**
     * Update an existing template and create a new version if content changed.
     */
    public function update(Request $request, DocumentTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'document_type' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'change_summary' => 'nullable|string|max:500',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'requires_approval' => 'boolean',
            'is_sensitive_salary' => 'boolean',
            'header_footer_enabled' => 'boolean',
            'status' => 'required|in:Draft,Pending Approval,Approved,Active,Inactive,Archived',
        ]);

        $oldContent = $template->content_html;
        $newContent = $validated['content_html'];
        $hasContentChanged = (trim($oldContent) !== trim($newContent));

        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_sensitive_salary'] = $request->boolean('is_sensitive_salary');
        $validated['header_footer_enabled'] = $request->boolean('header_footer_enabled', true);

        if ($hasContentChanged) {
            $newVersionNumber = $template->version + 1;
            $validated['version'] = $newVersionNumber;

            DocumentTemplateVersion::create([
                'document_template_id' => $template->id,
                'version_number' => $newVersionNumber,
                'content_html' => $newContent,
                'change_summary' => $request->input('change_summary', "Revision to version {$newVersionNumber}"),
                'created_by' => $request->user()->id,
            ]);
        }

        $template->update($validated);

        $this->auditLogService->log(
            action: 'TEMPLATE_UPDATED',
            model: $template,
            newValues: ['name' => $template->name, 'version' => $template->version, 'status' => $template->status]
        );

        return redirect()->route('admin.documents.templates.edit', $template)
            ->with('success', "Template '{$template->name}' updated successfully (Version {$template->version}).");
    }

    /**
     * Preview template rendered with resolved placeholders.
     */
    public function preview(Request $request, DocumentTemplate $template): View
    {
        $company = $template->company ?? $request->user()->company;
        $employee = null;

        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
        }

        if (!$employee) {
            $employee = Employee::where('company_id', $company->id)->first() ?? Employee::first();
        }

        $previewHtml = '';
        if ($employee) {
            $previewHtml = $this->placeholderService->resolve(
                $template->content_html,
                $company,
                $employee,
                $request->user(),
                [
                    'document_number' => 'DOC-PREVIEW-00001',
                    'verification_code' => 'PREVIEW-CODE-001',
                    'document_date' => now()->toDateString(),
                    'effective_date' => now()->toDateString(),
                ]
            );
        }

        return view('documents.templates.preview', compact('template', 'employee', 'previewHtml'));
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(DocumentTemplate $template, Request $request): RedirectResponse
    {
        $newCode = $template->code . '_COPY_' . rand(100, 999);
        $newTemplate = $template->replicate([
            'version', 'status', 'created_at', 'updated_at'
        ]);

        $newTemplate->code = $newCode;
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->version = 1;
        $newTemplate->status = 'Draft';
        $newTemplate->created_by = $request->user()->id;
        $newTemplate->save();

        DocumentTemplateVersion::create([
            'document_template_id' => $newTemplate->id,
            'version_number' => 1,
            'content_html' => $newTemplate->content_html,
            'change_summary' => "Duplicated from {$template->code}",
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.documents.templates.edit', $newTemplate)
            ->with('success', "Template duplicated successfully as draft.");
    }

    /**
     * Delete / archive template.
     */
    public function destroy(DocumentTemplate $template): RedirectResponse
    {
        $name = $template->name;
        $template->delete();

        $this->auditLogService->log(
            action: 'TEMPLATE_DELETED',
            model: $template
        );

        return redirect()->route('admin.documents.templates.index')
            ->with('success', "Template '{$name}' deleted.");
    }
}
