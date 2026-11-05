<?php

namespace App\Http\Controllers;

use App\Services\BulkEmployeeImportService;
use App\Services\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkEmployeeImportController extends Controller
{
    public function __construct(
        protected BulkEmployeeImportService $importService,
        protected CompanyContextService $companyContext
    ) {}

    /**
     * Show dedicated Bulk Employee Import page.
     */
    public function index()
    {
        $this->authorizeAdmin();

        $companyId = $this->companyContext->getActiveCompanyId();
        return view('employees.bulk_import', compact('companyId'));
    }

    /**
     * Download Excel or CSV import template with sample data.
     */
    public function downloadTemplate(string $format)
    {
        $this->authorizeAdmin();

        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xlsx'])) {
            $format = 'csv';
        }

        $companyId = $this->companyContext->getActiveCompanyId();
        return $this->importService->downloadTemplate($format, $companyId);
    }

    /**
     * Upload and validate file (AJAX).
     */
    public function validateFile(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'], // 10MB max
        ], [
            'file.required' => 'Please select a CSV or Excel file to upload.',
            'file.mimes' => 'The uploaded file must be a .csv, .xlsx, or .xls file.',
            'file.max' => 'The file size must not exceed 10MB.',
        ]);

        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();

        $result = $this->importService->parseAndValidate($request->file('file'), $companyId, $tenantId);

        return response()->json($result);
    }

    /**
     * Confirm and execute import for validated batch (AJAX).
     */
    public function confirmImport(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'batch_token' => ['required', 'string'],
        ]);

        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();
        $actor = Auth::user();

        $result = $this->importService->commitBatch($request->input('batch_token'), $companyId, $tenantId, $actor);

        return response()->json($result);
    }

    /**
     * Download CSV report of validation errors for a batch.
     */
    public function exportErrors(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $token = $request->input('batch_token');
        $cached = Cache::get("import_batch_{$token}");
        $errors = $cached['errors'] ?? [];

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_import_errors_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($errors) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Row Number', 'Employee Name', 'Validation Error / Reason']);
            foreach ($errors as $err) {
                fputcsv($handle, [
                    $err['row'] ?? '—',
                    $err['name'] ?? '—',
                    $err['message'] ?? 'Unknown Error',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized. Bulk Employee Import is restricted to HR Executives and Administrators.');
        }
    }
}

