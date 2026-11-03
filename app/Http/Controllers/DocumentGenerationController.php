<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Services\CompanyContextService;
use App\Services\Document\DocumentGenerationService;
use App\Services\Document\PlaceholderResolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DocumentGenerationController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $generationService,
        protected PlaceholderResolutionService $placeholderService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Document Control Hub Dashboard.
     */
    public function index(): View
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $stats = [
            'total_templates' => DocumentTemplate::count(),
            'total_generated' => GeneratedDocument::count(),
            'pending_approval' => GeneratedDocument::where('status', 'Pending Approval')->count(),
            'issued_documents' => GeneratedDocument::where('status', 'Issued')->count(),
        ];

        $recentGenerated = GeneratedDocument::with(['employee.department', 'template', 'generator'])
            ->latest()
            ->take(8)
            ->get();

        $pendingApprovals = GeneratedDocument::with(['employee.department', 'template', 'requester'])
            ->where('status', 'Pending Approval')
            ->latest()
            ->take(5)
            ->get();

        return view('documents.index', compact('stats', 'recentGenerated', 'pendingApprovals'));
    }

    /**
     * Document Generation Wizard (select template & employee).
     */
    public function create(Request $request): View
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $templates = DocumentTemplate::active()->orderBy('category')->orderBy('name')->get();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'Active')
            ->with(['department', 'designation'])
            ->orderBy('first_name')
            ->get();

        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = DocumentTemplate::find($request->template_id);
        }

        $selectedEmployee = null;
        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::find($request->employee_id);
        }

        return view('documents.generate', compact('templates', 'employees', 'selectedTemplate', 'selectedEmployee'));
    }

    /**
     * Process document generation request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'employee_id' => 'required|exists:employees,id',
            'effective_date' => 'nullable|date',
            'signatory_name' => 'nullable|string|max:150',
            'signatory_designation' => 'nullable|string|max:150',
            'remarks' => 'nullable|string',
        ]);

        $template = DocumentTemplate::findOrFail($request->template_id);
        $employee = Employee::findOrFail($request->employee_id);

        $customData = array_filter([
            'signatory_name' => $request->signatory_name,
            'signatory_designation' => $request->signatory_designation,
            'remarks' => $request->remarks,
        ]);

        $doc = $this->generationService->generateDocument(
            template: $template,
            employee: $employee,
            actor: $request->user(),
            customData: $customData,
            effectiveDate: $request->effective_date
        );

        if ($doc->status === 'Pending Approval') {
            return redirect()->route('admin.documents.approvals')
                ->with('info', "Document {$doc->document_number} generated and queued for required approval.");
        }

        return redirect()->route('admin.documents.show', $doc)
            ->with('success', "Document {$doc->document_number} issued and PDF generated successfully.");
    }

    /**
     * Show generated document details & snapshot preview.
     */
    public function show(Request $request, GeneratedDocument $document): View
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        $document->loadMissing(['employee.department', 'employee.designation', 'template', 'requester', 'generator', 'approver']);

        return view('documents.show', compact('document'));
    }

    /**
     * Document Approval Queue.
     */
    public function approvals(Request $request): View
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $query = GeneratedDocument::where('status', 'Pending Approval')
            ->with(['employee.department', 'employee.designation', 'template', 'requester']);

        $pendingDocuments = $query->latest()->paginate(15);

        return view('documents.approvals', compact('pendingDocuments'));
    }

    /**
     * Approve document action.
     */
    public function approve(Request $request, GeneratedDocument $document): RedirectResponse
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        $this->generationService->approveDocument($document, $request->user());

        return redirect()->back()
            ->with('success', "Document {$document->document_number} successfully approved and issued.");
    }

    /**
     * Reject document action.
     */
    public function reject(Request $request, GeneratedDocument $document): RedirectResponse
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $this->generationService->rejectDocument($document, $request->user(), $request->rejection_reason);

        return redirect()->back()
            ->with('success', "Document {$document->document_number} has been rejected.");
    }

    /**
     * Document History & Archive.
     */
    public function history(Request $request): View
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $query = GeneratedDocument::with(['employee.department', 'template', 'generator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', $search)
                  ->orWhere('title', 'like', $search)
                  ->orWhereHas('employee', function ($empQ) use ($search) {
                      $empQ->where('first_name', 'like', $search)
                           ->orWhere('last_name', 'like', $search)
                           ->orWhere('employee_code', 'like', $search);
                  });
            });
        }

        $documents = $query->latest()->paginate(15);

        return view('documents.history', compact('documents'));
    }

    /**
     * Securely download or stream generated PDF binary.
     */
    public function downloadPdf(Request $request, GeneratedDocument $document): Response
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        // Ensure PDF is generated and on disk
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

    /**
     * Cancel an issued or generated document.
     */
    public function cancel(Request $request, GeneratedDocument $document): RedirectResponse
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->generationService->authorizeAccess($document, $request->user(), $companyId);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $this->generationService->cancelDocument($document, $request->user(), $request->cancellation_reason);

        return redirect()->back()
            ->with('success', "Document {$document->document_number} cancelled.");
    }
}

