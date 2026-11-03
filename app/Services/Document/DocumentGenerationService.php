<?php

namespace App\Services\Document;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentGenerationService
{
    public function __construct(
        protected PlaceholderResolutionService $placeholderService,
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService,
        protected CompanyContextService $companyContextService
    ) {}

    /**
     * Generate a new document from a template for an employee.
     */
    public function generateDocument(
        DocumentTemplate $template,
        Employee $employee,
        User $actor,
        array $customData = [],
        ?string $effectiveDate = null
    ): GeneratedDocument {
        return DB::transaction(function () use ($template, $employee, $actor, $customData, $effectiveDate) {
            $company = $employee->company ?? Company::findOrFail($this->companyContextService->getActiveCompanyId());
            $now = Carbon::now();
            $year = $now->year;
            $effDate = $effectiveDate ? Carbon::parse($effectiveDate) : $now;

            // 1. Generate deterministic sequential Document Number
            $seq = GeneratedDocument::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereYear('created_at', $year)
                ->count() + 1;
            $docNumber = sprintf('DOC-%s-%d-%05d', strtoupper($company->code), $year, $seq);

            // 2. Generate unique tamper-evident verification code
            $verificationCode = sprintf('%s-DOC-%s', strtoupper($company->code), strtoupper(Str::random(10)));

            // 3. Prepare extra data for placeholder substitution
            $mergeData = array_merge([
                'document_number' => $docNumber,
                'verification_code' => $verificationCode,
                'document_date' => $now->toDateString(),
                'effective_date' => $effDate->toDateString(),
            ], $customData);

            // 4. Resolve template content HTML
            $contentHtml = $this->placeholderService->resolve(
                $template->content_html,
                $company,
                $employee,
                $actor,
                $mergeData
            );

            // 5. Build immutable metadata snapshot
            $metadataSnapshot = [
                'employee' => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'code' => $employee->employee_code,
                    'department' => $employee->department?->name,
                    'designation' => $employee->designation?->name,
                    'location' => $employee->location?->name,
                ],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'code' => $company->code,
                ],
                'template' => [
                    'id' => $template->id,
                    'code' => $template->code,
                    'version' => $template->version,
                    'name' => $template->name,
                ],
                'generated_by' => [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'role' => $actor->role?->name,
                ],
                'custom_inputs' => $customData,
                'timestamp' => $now->toIso8601String(),
            ];

            // 6. Determine approval state
            $requiresApproval = $template->requires_approval;
            $status = $requiresApproval ? 'Pending Approval' : 'Issued';

            $document = GeneratedDocument::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'template_id' => $template->id,
                'template_version_id' => $template->versions()->first()?->id,
                'document_number' => $docNumber,
                'verification_code' => $verificationCode,
                'category' => $template->category,
                'document_type' => $template->document_type,
                'title' => $template->name,
                'content_snapshot_html' => $contentHtml,
                'metadata_snapshot_json' => $metadataSnapshot,
                'status' => $status,
                'effective_date' => $effDate,
                'requested_by' => $actor->id,
                'generated_by' => $actor->id,
                'approved_by' => $requiresApproval ? null : $actor->id,
                'approved_at' => $requiresApproval ? null : $now,
                'issued_at' => $requiresApproval ? null : $now,
            ]);

            // 7. If issued immediately, compile and store PDF binary
            if ($status === 'Issued') {
                $this->savePdf($document);

                // Notify employee
                $this->notifyEmployeeOfDocument($document, 'issued');
            } else {
                // Notify HR/Approvers of pending document
                $this->notifyApproversOfPending($document);
            }

            // 8. Audit trail
            $this->auditLogService->log(
                action: 'DOCUMENT_GENERATED',
                model: $document,
                newValues: [
                    'document_number' => $docNumber,
                    'status' => $status,
                    'template_code' => $template->code,
                    'employee_id' => $employee->id,
                ]
            );

            return $document;
        });
    }

    /**
     * Approve a pending document and generate its PDF binary.
     */
    public function approveDocument(GeneratedDocument $doc, User $approver): GeneratedDocument
    {
        if ($doc->status !== 'Pending Approval') {
            throw new \InvalidArgumentException("Document cannot be approved from current state: {$doc->status}");
        }

        $now = Carbon::now();
        $doc->update([
            'status' => 'Issued',
            'approved_by' => $approver->id,
            'approved_at' => $now,
            'issued_at' => $now,
        ]);

        $this->savePdf($doc);

        $this->auditLogService->log(
            action: 'DOCUMENT_APPROVED',
            model: $doc,
            newValues: [
                'document_number' => $doc->document_number,
                'approved_by' => $approver->id,
                'status' => 'Issued',
            ]
        );

        $this->notifyEmployeeOfDocument($doc, 'approved');

        return $doc;
    }

    /**
     * Reject a pending document.
     */
    public function rejectDocument(GeneratedDocument $doc, User $actor, string $reason): GeneratedDocument
    {
        if ($doc->status !== 'Pending Approval') {
            throw new \InvalidArgumentException("Document cannot be rejected from current state: {$doc->status}");
        }

        $doc->update([
            'status' => 'Rejected',
            'approved_by' => $actor->id,
            'approved_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);

        $this->auditLogService->log(
            action: 'DOCUMENT_REJECTED',
            model: $doc,
            newValues: [
                'document_number' => $doc->document_number,
                'rejected_by' => $actor->id,
                'reason' => $reason,
                'status' => 'Rejected',
            ]
        );

        return $doc;
    }

    /**
     * Cancel an existing document.
     */
    public function cancelDocument(GeneratedDocument $doc, User $actor, string $reason): GeneratedDocument
    {
        $doc->update([
            'status' => 'Cancelled',
            'rejection_reason' => $reason,
        ]);

        $this->auditLogService->log(
            action: 'DOCUMENT_CANCELLED',
            model: $doc,
            newValues: [
                'document_number' => $doc->document_number,
                'cancelled_by' => $actor->id,
                'reason' => $reason,
                'status' => 'Cancelled',
            ]
        );

        return $doc;
    }

    /**
     * Render PDF binary string for a document using Dompdf.
     */
    public function renderPdfBinary(GeneratedDocument $doc): string
    {
        $doc->loadMissing(['employee.department', 'employee.designation', 'company']);

        $html = view('documents.pdf_wrapper', ['document' => $doc])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Save PDF binary into private storage disk and update document attributes.
     */
    public function savePdf(GeneratedDocument $doc): string
    {
        $pdfBinary = $this->renderPdfBinary($doc);
        $year = Carbon::parse($doc->created_at ?? now())->year;
        $relativePath = "documents/{$doc->company_id}/{$year}/{$doc->document_number}.pdf";

        Storage::disk('local')->put($relativePath, $pdfBinary);

        $sizeKb = (int) max(1, round(strlen($pdfBinary) / 1024));
        $doc->update([
            'pdf_file_path' => $relativePath,
            'file_size_kb' => $sizeKb,
        ]);

        return $relativePath;
    }

    /**
     * Authorize access to view or download a generated document.
     */
    public function authorizeAccess(GeneratedDocument $doc, User $user, int $activeCompanyId): void
    {
        // 1. Multi-Company Isolation Guard
        if ($doc->company_id !== $activeCompanyId) {
            throw new AuthorizationException("Cross-company document access is strictly prohibited.");
        }

        $roleName = $user->role?->name ?? '';

        // 2. Head Office Admin & HR Executive have full company access
        if (in_array($roleName, ['ho_admin', 'hr_executive'])) {
            return;
        }

        // 3. Manager MSS: can access if employee is subordinate or in same department
        if ($roleName === 'manager_mss') {
            $managerEmp = $user->employee();
            if ($managerEmp) {
                if ($doc->employee?->reporting_manager_id === $managerEmp->id ||
                    $doc->employee?->department_id === $managerEmp->department_id) {
                    return;
                }
            }
        }

        // 4. Employee ESS: can ONLY access own documents that are formally Issued
        $employee = $user->employee();
        if ($employee && $employee->id === $doc->employee_id) {
            if (in_array($doc->status, ['Issued', 'Generated'])) {
                return;
            }
            throw new AuthorizationException("Pending, draft, or rejected documents cannot be accessed by employee self-service.");
        }

        throw new AuthorizationException("You do not have permission to view or download this document.");
    }

    /**
     * Notification helper when a document is issued.
     */
    protected function notifyEmployeeOfDocument(GeneratedDocument $doc, string $event): void
    {
        try {
            $user = User::where('employee_code', $doc->employee?->employee_code)->first();
            if ($user) {
                $this->notificationService->sendDirect(
                    user: $user,
                    category: 'ESS',
                    title: "Document Issued: {$doc->title}",
                    body: "An official document ({$doc->document_number}) has been issued to your personnel records and is available for download in My Documents.",
                    actionUrl: "/documents/my-documents",
                    channels: ['in_app', 'email']
                );
            }
        } catch (\Throwable $e) {
            // Suppress notification delivery failures so generation transaction succeeds
        }
    }

    /**
     * Notification helper when a document requires approval.
     */
    protected function notifyApproversOfPending(GeneratedDocument $doc): void
    {
        try {
            $hrUsers = User::where('company_id', $doc->company_id)
                ->whereHas('role', fn($q) => $q->whereIn('name', ['hr_executive', 'ho_admin']))
                ->get();

            foreach ($hrUsers as $hrUser) {
                $this->notificationService->sendDirect(
                    user: $hrUser,
                    category: 'Approvals',
                    title: "Document Pending Approval: {$doc->document_number}",
                    body: "A {$doc->title} for {$doc->employee?->full_name} ({$doc->employee?->employee_code}) requires authorization.",
                    actionUrl: "/admin/documents/approvals",
                    channels: ['in_app']
                );
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    }
}
