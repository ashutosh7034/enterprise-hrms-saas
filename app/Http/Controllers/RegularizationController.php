<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\Employee;
use App\Services\CompanyContextService;
use App\Services\RegularizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegularizationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected RegularizationService $regularizationService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'applicant', 'manager', 'hr']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['Pending Manager', 'Pending HR']);
        }

        $user = Auth::user();
        if ($user && $user->isManager() && !$user->isHr()) {
            $managerEmp = $user->employee();
            if ($managerEmp) {
                $query->whereHas('employee', fn($eq) => $eq->where('reporting_manager_id', $managerEmp->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $regularizations = $query->orderByDesc('attendance_date')->paginate(15);

        $employeeQuery = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active');

        if ($user && $user->isManager() && !$user->isHr()) {
            $managerEmp = $user->employee();
            if ($managerEmp) {
                $employeeQuery->where('reporting_manager_id', $managerEmp->id);
            } else {
                $employeeQuery->whereRaw('1 = 0');
            }
        }

        $employees = $employeeQuery->get();

        return view('regularization.index', compact('regularizations', 'employees'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'regularization_type' => 'required|in:Missing IN Punch,Missing OUT Punch,Late Arrival,Early Departure,Business Travel / On Duty,Biometric Device Failure,Attendance Correction',
            'requested_in_time' => 'nullable|date',
            'requested_out_time' => 'nullable|date|after_or_equal:requested_in_time',
            'reason' => 'required|string|max:500',
        ]);

        $this->regularizationService->applyRegularization(
            companyId: $companyId,
            data: $validated,
            applicant: Auth::user()
        );

        return back()->with('success', 'Attendance regularization request submitted successfully.');
    }

    public function managerAction(Request $request, AttendanceRegularization $attendanceRegularization)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($attendanceRegularization->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => 'required|in:Approve,Reject,Send Back',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->regularizationService->managerReview(
            reg: $attendanceRegularization,
            manager: Auth::user(),
            decision: $validated['decision'],
            remarks: $validated['remarks'] ?? null
        );

        return back()->with('success', "Regularization marked as {$validated['decision']}.");
    }

    public function hrAction(Request $request, AttendanceRegularization $attendanceRegularization)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($attendanceRegularization->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => 'required|in:Approve,Reject',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->regularizationService->hrApproval(
            reg: $attendanceRegularization,
            hrUser: Auth::user(),
            decision: $validated['decision'],
            remarks: $validated['remarks'] ?? null
        );

        return back()->with('success', "Regularization {$validated['decision']}d by HR and attendance muster updated.");
    }
}

