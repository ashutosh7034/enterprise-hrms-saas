<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AuditLogService $auditLogService
    ) {}

    public function store(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Max 5MB
            'remarks' => ['nullable', 'string'],
        ], [
            'file.max' => 'Uploaded document cannot exceed 5MB.',
            'file.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileSize = $uploadedFile->getSize();
        $mimeType = $uploadedFile->getMimeType();

        // Store privately
        $path = $uploadedFile->store("private/employee_documents/{$companyId}/{$employee->id}", 'local');

        $fileSizeKb = max(1, (int) ceil($fileSize / 1024));

        DB::transaction(function () use ($employee, $validated, $path, $originalName, $fileSizeKb, $mimeType) {
            $user = Auth::user();

            // Find existing document or create new
            $doc = EmployeeDocument::where('employee_id', $employee->id)
                ->where('document_type', $validated['document_type'])
                ->first();

            if ($doc) {
                $newVersion = $doc->version + 1;
                $doc->update([
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_size_kb' => $fileSizeKb,
                    'mime_type' => $mimeType,
                    'version' => $newVersion,
                    'verification_status' => 'Pending',
                    'rejection_reason' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'uploaded_by' => $user->id,
                ]);
            } else {
                $newVersion = 1;
                $doc = EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'document_type' => $validated['document_type'],
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_size_kb' => $fileSizeKb,
                    'mime_type' => $mimeType,
                    'version' => 1,
                    'verification_status' => 'Pending',
                    'uploaded_by' => $user->id,
                ]);
            }

            // Record Version entry
            EmployeeDocumentVersion::create([
                'employee_document_id' => $doc->id,
                'version' => $newVersion,
                'file_path' => $path,
                'original_name' => $originalName,
                'file_size_kb' => $fileSizeKb,
                'mime_type' => $mimeType,
                'uploaded_by' => $user->id,
            ]);

            $this->auditLogService->log(
                action: 'Employee Document Uploaded',
                entityType: 'EmployeeDocument',
                entityId: $doc->id,
                oldValues: [],
                newValues: [
                    'employee_code' => $employee->employee_code,
                    'type' => $doc->document_type,
                    'version' => $newVersion,
                    'original_name' => $originalName,
                ],
                companyId: $employee->company_id,
                actor: $user
            );
        });

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "Document {$validated['document_type']} uploaded successfully.");
    }

    public function preview(EmployeeDocument $document)
    {
        $this->authorizeDocumentAccess($document);

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Document file not found on secure storage.');
        }

        $fullPath = Storage::disk('local')->path($document->file_path);

        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }

    public function download(EmployeeDocument $document)
    {
        $this->authorizeDocumentAccess($document);

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Document file not found on secure storage.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function verify(Request $request, EmployeeDocument $document)
    {
        $this->authorizeDocumentAccess($document);

        $validated = $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'rejection_reason' => ['nullable', 'required_if:action,reject', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $isVerified = ($validated['action'] === 'verify');
        $oldStatus = $document->verification_status;

        $document->update([
            'verification_status' => $isVerified ? 'Verified' : 'Rejected',
            'rejection_reason' => $isVerified ? null : $validated['rejection_reason'],
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->auditLogService->log(
            action: $isVerified ? 'Employee Document Verified' : 'Employee Document Rejected',
            entityType: 'EmployeeDocument',
            entityId: $document->id,
            oldValues: ['status' => $oldStatus],
            newValues: [
                'status' => $document->verification_status,
                'verified_by' => $user->name,
                'rejection_reason' => $document->rejection_reason,
            ],
            companyId: $document->company_id,
            actor: $user
        );

        return redirect()->route('employees.show', $document->employee_id)
            ->with('success', "Document status updated to {$document->verification_status}.");
    }

    protected function authorizeDocumentAccess(EmployeeDocument $document): void
    {
        $user = Auth::user();

        // HO Admin has global clearance
        if ($user->isHoAdmin()) {
            return;
        }

        // Active company boundary check
        $activeCompanyId = $this->companyContext->getActiveCompanyId();
        if ($document->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized access: document belongs to another company.');
        }

        // HR Executive has access within company
        if ($user->isHrExecutive()) {
            return;
        }

        abort(403, 'Unauthorized access to employee document.');
    }
}
