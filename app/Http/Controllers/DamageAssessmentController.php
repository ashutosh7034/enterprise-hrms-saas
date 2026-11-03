<?php

namespace App\Http\Controllers;

use App\Models\AssetAllocation;
use App\Models\AssetDamageAssessment;
use App\Models\FineMaster;
use App\Models\FineRecoveryRecord;
use App\Services\AssetManagementService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DamageAssessmentController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AssetManagementService $assetService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $assessmentsQuery = AssetDamageAssessment::with([
            'allocation.asset',
            'allocation.employee',
            'assessedByUser',
            'approvedByUser',
            'fineMaster',
            'recoveryRecords',
        ])->where('company_id', $companyId);

        if ($request->filled('status')) {
            $assessmentsQuery->where('status', $request->input('status'));
        }

        $assessments = $assessmentsQuery->latest('id')->paginate(10, ['*'], 'assessments_page');

        $recoveryQuery = FineRecoveryRecord::with([
            'assessment.allocation.asset',
            'employee',
            'recordedByUser',
        ])->where('company_id', $companyId);

        if ($request->filled('recovery_status')) {
            $recoveryQuery->where('status', $request->input('recovery_status'));
        }

        $recoveries = $recoveryQuery->latest('id')->paginate(10, ['*'], 'recovery_page');

        $pendingApprovals = AssetDamageAssessment::where('company_id', $companyId)->where('status', 'Pending Approval')->count();
        $queuedForPayroll = FineRecoveryRecord::where('company_id', $companyId)->where('status', 'Queued for Phase 5 Payroll')->count();
        $totalRecovered = FineRecoveryRecord::where('company_id', $companyId)->where('status', 'Paid')->sum('amount');

        return view('fines.damage_assessments', compact(
            'assessments', 'recoveries', 'pendingApprovals', 'queuedForPayroll', 'totalRecovered'
        ));
    }

    public function create(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $allocations = AssetAllocation::with(['asset', 'employee'])
            ->where('company_id', $companyId)
            ->latest('id')
            ->get();

        $fineMasters = FineMaster::where('company_id', $companyId)->where('status', 'Active')->get();
        $selectedAllocationId = $request->query('allocation_id');

        return view('fines.create_assessment', compact('allocations', 'fineMasters', 'selectedAllocationId'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'asset_allocation_id' => [
                'required',
                Rule::exists('asset_allocations', 'id')->where('company_id', $companyId),
            ],
            'fine_master_id' => ['nullable', 'exists:fine_masters,id'],
            'assessed_amount' => ['required', 'numeric', 'min:0.01'],
            'depreciation_strategy' => ['required', 'in:Straight-Line,Written-Down Value,Fixed Assessment'],
            'recovery_method' => ['required', 'in:Direct Payment,Payroll Queue'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:12'],
            'condition_evidence' => ['nullable', 'string'],
            'investigation_notes' => ['nullable', 'string'],
        ]);

        $allocation = AssetAllocation::with('asset')->findOrFail($validated['asset_allocation_id']);

        $assessment = $this->assetService->assessDamage($allocation, $validated, Auth::user());

        return redirect()->route('damage-assessments.index')
            ->with('success', "Damage assessment of ₹" . number_format($assessment->assessed_amount, 2) . " logged for asset {$allocation->asset->asset_code}. Awaiting authorization.");
    }

    public function approve(AssetDamageAssessment $assessment)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($assessment->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        try {
            $this->assetService->approveAssessment($assessment, Auth::user());

            return redirect()->route('damage-assessments.index')
                ->with('success', "Damage assessment #{$assessment->id} approved. Recovery schedule generated.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['approval' => $e->getMessage()]);
        }
    }

    public function applyWaiver(Request $request, AssetDamageAssessment $assessment)
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized: Only HO Admin can authorize fine waivers.');
        }

        $validated = $request->validate([
            'waived_amount' => ['required', 'numeric', 'min:0.01'],
            'waiver_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->assetService->applyWaiver(
                $assessment,
                (float) $validated['waived_amount'],
                $validated['waiver_reason'],
                Auth::user()
            );

            return redirect()->route('damage-assessments.index')
                ->with('success', "Fine waiver of ₹" . number_format($validated['waived_amount'], 2) . " applied to assessment #{$assessment->id}.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['waiver' => $e->getMessage()]);
        }
    }

    public function recordPayment(Request $request, FineRecoveryRecord $record)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($record->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_channel' => ['required', 'in:Direct Payment,Payroll Deduction Queue'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->assetService->recordPayment(
                $record,
                (float) $validated['amount'],
                $validated['payment_channel'],
                $validated['receipt_reference'] ?? null,
                $validated['remarks'] ?? null,
                Auth::user()
            );

            return redirect()->route('damage-assessments.index')
                ->with('success', "Payment of ₹" . number_format($validated['amount'], 2) . " recorded for installment #{$record->installment_number}.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
}

