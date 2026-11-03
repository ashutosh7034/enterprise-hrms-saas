<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Services\CompanyContextService;
use App\Services\LeaveApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LeaveApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = LeaveApplication::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'leaveType']);

        if ($user->isHr()) {
            // HR sees applications at HR stage or Manager stage
            $query->whereIn('status', ['Pending HR', 'Pending Manager']);
        } elseif ($user->isManager()) {
            // Manager sees only their direct team members
            $managerEmp = $user->employee();
            if ($managerEmp) {
                $query->whereHas('employee', function ($q) use ($managerEmp) {
                    $q->where('reporting_manager_id', $managerEmp->id);
                })->where('status', 'Pending Manager');
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $pendingApplications = $query->orderBy('created_at')->paginate(15);

        return view('leaves.approvals.index', compact('pendingApplications'));
    }

    public function managerAction(Request $request, LeaveApplication $application)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($application->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => 'required|in:Approve,Reject,Send Back',
            'remarks' => 'nullable|string|max:500',
        ]);

        $result = $this->applicationService->managerAction(
            application: $application,
            decision: $validated['decision'],
            manager: $user,
            remarks: $validated['remarks']
        );

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['approval_error' => $result['message']]);
        }

        return redirect()->route('leaves.approvals.index')
            ->with('success', "Application {$application->application_number} processed with decision: {$validated['decision']}.");
    }

    public function hrAction(Request $request, LeaveApplication $application)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($application->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => 'required|in:Approve,Reject',
            'remarks' => 'nullable|string|max:500',
        ]);

        $result = $this->applicationService->hrAction(
            application: $application,
            decision: $validated['decision'],
            hr: $user,
            remarks: $validated['remarks']
        );

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['approval_error' => $result['message']]);
        }

        return redirect()->route('leaves.approvals.index')
            ->with('success', "Application {$application->application_number} officially sanctioned with decision: {$validated['decision']}.");
    }

    public function cancelApproved(Request $request, LeaveApplication $application)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($application->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $result = $this->applicationService->cancelApprovedLeave(
            application: $application,
            actor: $user,
            reason: $validated['cancellation_reason']
        );

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['cancellation_error' => $result['message']]);
        }

        return back()->with('success', "Approved leave application {$application->application_number} cancelled and balance restored in ledger.");
    }
}

