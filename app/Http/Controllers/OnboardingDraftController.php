<?php

namespace App\Http\Controllers;

use App\Exceptions\ConcurrentDraftEditException;
use App\Models\OnboardingDraft;
use App\Services\CompanyContextService;
use App\Services\OnboardingDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingDraftController extends Controller
{
    public function __construct(
        protected OnboardingDraftService $draftService,
        protected CompanyContextService $companyContext
    ) {}

    /**
     * Display a listing of onboarding drafts for the active company.
     */
    public function index(Request $request): View
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $search = $request->input('q');
        $status = $request->input('status', 'draft');

        $query = OnboardingDraft::where('company_id', $companyId)
            ->with(['location', 'department', 'creator', 'updater']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('draft_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $drafts = $query->orderByDesc('last_saved_at')->paginate(15)->withQueryString();

        $stats = [
            'total' => OnboardingDraft::where('company_id', $companyId)->count(),
            'active_drafts' => OnboardingDraft::where('company_id', $companyId)->where('status', 'draft')->count(),
            'submitted' => OnboardingDraft::where('company_id', $companyId)->where('status', 'submitted')->count(),
        ];

        return view('employees.drafts.index', compact('drafts', 'search', 'status', 'stats'));
    }

    /**
     * AJAX Auto-Save Endpoint.
     */
    public function autoSave(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();

        $draftId = $request->input('draft_id');
        $expectedVersion = $request->filled('draft_version') ? (int)$request->input('draft_version') : null;
        $payload = $request->all();

        try {
            $draft = $this->draftService->saveDraft(
                payload: $payload,
                draftId: $draftId,
                companyId: $companyId,
                tenantId: $tenantId,
                user: Auth::user(),
                expectedVersion: $expectedVersion
            );

            return response()->json([
                'success' => true,
                'draft_id' => $draft->draft_id,
                'version' => $draft->version,
                'last_saved_at' => $draft->last_saved_at ? $draft->last_saved_at->format('h:i A') : now()->format('h:i A'),
                'saved_timestamp' => $draft->last_saved_at ? $draft->last_saved_at->toIso8601String() : now()->toIso8601String(),
                'current_step' => $draft->current_step,
                'completion_percentage' => $draft->completion_percentage,
                'candidate_name' => $draft->candidate_display_name,
                'photo_url' => $draft->uploaded_files['profile_photo']['url'] ?? null,
                'uploaded_documents' => $draft->uploaded_files['documents'] ?? [],
            ]);
        } catch (ConcurrentDraftEditException $e) {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'message' => $e->getMessage(),
                'current_version' => $e->currentVersion,
                'last_saved_at' => $e->lastSavedAt,
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'conflict' => false,
                'message' => 'Auto-save failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an individual staged document directly to the draft.
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'draft_id' => ['required', 'string'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'type' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'expiry' => ['nullable', 'date'],
        ]);

        $companyId = $this->companyContext->getActiveCompanyId();

        try {
            $result = $this->draftService->uploadStagedDocument(
                draftId: $request->input('draft_id'),
                file: $request->file('file'),
                metadata: [
                    'type' => $request->input('type', 'General'),
                    'category' => $request->input('category', 'Identity'),
                    'title' => $request->input('title'),
                    'expiry' => $request->input('expiry'),
                ],
                companyId: $companyId,
                user: Auth::user()
            );

            return response()->json([
                'success' => true,
                'document' => $result['doc'],
                'version' => $result['version'],
                'last_saved_at' => $result['last_saved_at'],
                'completion_percentage' => $result['completion_percentage'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a staged document from the draft.
     */
    public function removeFile(Request $request): JsonResponse
    {
        $request->validate([
            'draft_id' => ['required', 'string'],
            'doc_id' => ['required', 'string'],
        ]);

        $companyId = $this->companyContext->getActiveCompanyId();

        $success = $this->draftService->removeStagedDocument(
            draftId: $request->input('draft_id'),
            docUniqueId: $request->input('doc_id'),
            companyId: $companyId,
            user: Auth::user()
        );

        return response()->json(['success' => $success]);
    }

    /**
     * Preview draft details in JSON.
     */
    public function show(string $draftId): JsonResponse
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $draft = OnboardingDraft::where('company_id', $companyId)
            ->where('draft_id', $draftId)
            ->with(['location', 'department', 'creator', 'updater'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'draft' => [
                'draft_id' => $draft->draft_id,
                'candidate_name' => $draft->candidate_display_name,
                'first_name' => $draft->first_name,
                'last_name' => $draft->last_name,
                'email' => $draft->email,
                'phone' => $draft->phone,
                'location' => $draft->location?->name,
                'department' => $draft->department?->name,
                'current_step' => $draft->current_step,
                'completion_percentage' => $draft->completion_percentage,
                'status' => $draft->status,
                'created_by' => $draft->creator?->name,
                'last_saved_at' => $draft->last_saved_at?->format('d M Y, h:i A'),
                'form_data' => $draft->form_data,
                'uploaded_files' => $draft->uploaded_files,
            ],
        ]);
    }

    /**
     * Soft delete a draft.
     */
    public function destroy(string $draftId): RedirectResponse
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $this->draftService->deleteDraft($draftId, $companyId, Auth::user());

        return redirect()->route('employees.drafts.index')
            ->with('success', "Onboarding draft {$draftId} has been successfully deleted.");
    }
}

