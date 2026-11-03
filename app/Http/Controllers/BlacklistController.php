<?php

namespace App\Http\Controllers;

use App\Models\CentralBlacklistRecord;
use App\Models\Company;
use App\Models\IdentityMatchReview;
use App\Services\BlacklistMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlacklistController extends Controller
{
    public function __construct(
        protected BlacklistMatchingService $blacklistService
    ) {}

    public function index(Request $request)
    {
        $query = CentralBlacklistRecord::with(['previousCompany', 'requestedBy', 'approvedBy', 'whitelistedBy']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('pan_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('aadhaar_masked', 'like', "%{$search}%")
                  ->orWhere('previous_employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('reason_category')) {
            $query->where('reason_category', $request->input('reason_category'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $records = $query->latest('id')->paginate(15)->withQueryString();

        $activeCount = CentralBlacklistRecord::where('is_active', true)->where('approval_status', 'Approved')->count();
        $pendingCount = CentralBlacklistRecord::where('approval_status', 'Pending Review')->count();
        $whitelistedCount = CentralBlacklistRecord::whereNotNull('whitelisted_at')->count();
        $pendingReviewsCount = IdentityMatchReview::where('status', 'Pending Review')->count();

        return view('blacklist.index', compact('records', 'activeCount', 'pendingCount', 'whitelistedCount', 'pendingReviewsCount'));
    }

    public function create()
    {
        $companies = Company::where('status', 'Active')->get();
        return view('blacklist.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'date_of_birth' => ['nullable', 'date'],
            'pan_number' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'aadhaar_number' => ['nullable', 'string', 'regex:/^[0-9\s]{12,14}$/'],
            'phone' => ['nullable', 'string', 'max:25'],
            'previous_employee_code' => ['nullable', 'string', 'max:50'],
            'previous_company_id' => ['nullable', 'exists:companies,id'],
            'reason_category' => [
                'required',
                'in:Serious Misconduct,Financial / Asset Misconduct,Absconding,Policy Termination,Fraudulent Documents,Identity Fraud,Other'
            ],
            'evidence_notes' => ['required', 'string', 'min:10'],
        ]);

        $record = $this->blacklistService->submitRequest($validated, Auth::user());

        return redirect()->route('blacklist.index')
            ->with('success', "Blacklist request for '{$record->name}' submitted. Awaiting HO Admin authorization.");
    }

    public function approve(CentralBlacklistRecord $record)
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized: Only HO Admin can approve blacklist records.');
        }

        $this->blacklistService->approveRequest($record, Auth::user());

        return redirect()->route('blacklist.index')
            ->with('success', "Blacklist entry for '{$record->name}' has been approved and activated cross-company.");
    }

    public function reject(Request $request, CentralBlacklistRecord $record)
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized: Only HO Admin can reject blacklist records.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->blacklistService->rejectRequest($record, Auth::user(), $validated['rejection_reason']);

        return redirect()->route('blacklist.index')
            ->with('success', "Blacklist request for '{$record->name}' was rejected.");
    }

    public function whitelist(Request $request, CentralBlacklistRecord $record)
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized: Only HO Admin can whitelist a blacklisted individual.');
        }

        $validated = $request->validate([
            'whitelist_justification' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'whitelist_justification.required' => 'A mandatory justification (min 10 characters) is required to whitelist an individual.',
        ]);

        $this->blacklistService->whitelistRecord($record, Auth::user(), $validated['whitelist_justification']);

        return redirect()->route('blacklist.index')
            ->with('success', "Individual '{$record->name}' has been Whitelisted / Cleared. Audit trail preserved.");
    }

    public function matchReviews(Request $request)
    {
        $query = IdentityMatchReview::with(['employee.company', 'blacklistRecord.previousCompany', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('match_type')) {
            $query->where('match_type', $request->input('match_type'));
        }

        $reviews = $query->latest('id')->paginate(15)->withQueryString();

        return view('blacklist.match_reviews', compact('reviews'));
    }

    public function resolveReview(Request $request, IdentityMatchReview $review)
    {
        $validated = $request->validate([
            'resolution' => ['required', 'in:Confirmed Match,False Positive,Requires More Information'],
            'resolution_remarks' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->blacklistService->resolveReview(
            $review,
            Auth::user(),
            $validated['resolution'],
            $validated['resolution_remarks']
        );

        return redirect()->route('blacklist.match-reviews')
            ->with('success', "Identity match review resolved as '{$validated['resolution']}'.");
    }
}
