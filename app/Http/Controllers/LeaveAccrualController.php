<?php

namespace App\Http\Controllers;

use App\Models\LeaveAccrualBatch;
use App\Models\LeaveCarryForwardBatch;
use App\Models\LeaveType;
use App\Services\CompanyContextService;
use App\Services\LeaveAccrualService;
use App\Services\LeaveCarryForwardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveAccrualController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LeaveAccrualService $accrualService,
        protected LeaveCarryForwardService $carryForwardService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $accrualBatches = LeaveAccrualBatch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['leaveType', 'executor'])
            ->orderByDesc('id')
            ->paginate(10);

        $carryForwardBatches = LeaveCarryForwardBatch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['leaveType', 'executor'])
            ->orderByDesc('id')
            ->paginate(10);

        $leaveTypes = LeaveType::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        return view('leaves.accrual.index', compact('accrualBatches', 'carryForwardBatches', 'leaveTypes'));
    }

    public function runAccrual(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'leave_type_id' => 'nullable|exists:leave_types,id',
        ]);

        $result = $this->accrualService->processMonthlyAccrual(
            companyId: $companyId,
            year: (int) $validated['year'],
            month: (int) $validated['month'],
            leaveTypeId: $validated['leave_type_id'] ? (int) $validated['leave_type_id'] : null,
            actor: Auth::user()
        );

        if ($result['status'] === 'Already Processed') {
            return back()->with('info', "Accrual for {$validated['year']}-{$validated['month']} was already completed previously (Batch {$result['batch']->batch_number}). Idempotency guaranteed.");
        }

        return back()->with('success', "Monthly accrual successfully executed! Credited {$result['credited_count']} employees with {$result['total_days']} total days.");
    }

    public function runCarryForward(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'from_year' => 'required|integer|min:2020|max:2030',
            'to_year' => 'required|integer|min:2020|max:2030|different:from_year',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'is_dry_run' => 'nullable|boolean',
        ]);

        $dryRun = $request->boolean('is_dry_run');

        $result = $this->carryForwardService->processYearEnd(
            companyId: $companyId,
            fromYear: (int) $validated['from_year'],
            toYear: (int) $validated['to_year'],
            leaveTypeId: $validated['leave_type_id'] ? (int) $validated['leave_type_id'] : null,
            dryRun: $dryRun,
            actor: Auth::user()
        );

        $msg = $dryRun 
            ? "Dry-Run Simulation Completed: {$result['total_processed']} records evaluated, {$result['total_carried_forward']} days eligible for carry-forward, {$result['total_expired']} days to expire."
            : "Year-End Carry Forward Executed: {$result['total_processed']} records updated, {$result['total_carried_forward']} days carried forward, {$result['total_expired']} days expired in ledger.";

        return back()->with('success', $msg);
    }
}

