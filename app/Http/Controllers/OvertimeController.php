<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRecord;
use App\Services\CompanyContextService;
use App\Services\OvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected OvertimeService $overtimeService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = OvertimeRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'shift', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'Pending');
        }

        if ($request->filled('date')) {
            $query->where('ot_date', $request->date);
        }

        $records = $query->orderByDesc('ot_date')->paginate(20);

        return view('overtime.index', compact('records'));
    }

    public function approve(Request $request, OvertimeRecord $overtimeRecord)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($overtimeRecord->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'approved_minutes' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        $this->overtimeService->approve(
            ot: $overtimeRecord,
            approver: Auth::user(),
            approvedMinutes: $validated['approved_minutes'] ?? null,
            remarks: $validated['remarks'] ?? null
        );

        return back()->with('success', 'Overtime approved successfully.');
    }

    public function reject(Request $request, OvertimeRecord $overtimeRecord)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($overtimeRecord->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $this->overtimeService->reject(
            ot: $overtimeRecord,
            rejecter: Auth::user(),
            reason: $validated['rejection_reason']
        );

        return back()->with('success', 'Overtime request rejected.');
    }
}

