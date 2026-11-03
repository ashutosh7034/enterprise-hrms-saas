<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Services\CompanyContextService;
use App\Services\Document\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EmployeeDocumentEssController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $generationService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * ESS "My Documents" listing for employee.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee();
        $companyId = $this->contextService->getActiveCompanyId();

        if (!$employee) {
            return view('documents.ess.index', [
                'documents' => collect(),
                'employee' => null,
            ]);
        }

        $query = GeneratedDocument::where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['Issued', 'Generated'])
            ->with(['company', 'template']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('document_number', 'like', $search)
                  ->orWhere('document_type', 'like', $search);
            });
        }

        $documents = $query->latest('issued_at')->paginate(12);

        return view('documents.ess.index', compact('documents', 'employee'));
    }

    /**
     * View an issued document in ESS portal.
     */
    public function show(Request $request, GeneratedDocument $document): View
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        return view('documents.ess.show', compact('document'));
    }

    /**
     * Download issued document PDF in ESS.
     */
    public function downloadPdf(Request $request, GeneratedDocument $document): Response
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        if (empty($document->pdf_file_path) || !Storage::disk('local')->exists($document->pdf_file_path)) {
            $this->generationService->savePdf($document);
            $document->refresh();
        }

        $filePath = Storage::disk('local')->path($document->pdf_file_path);
        $fileName = "{$document->document_number}.pdf";

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

