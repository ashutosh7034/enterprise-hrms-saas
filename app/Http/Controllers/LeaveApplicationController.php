<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\CompanyContextService;
use App\Services\LeaveApplicationService;
use App\Services\SandwichLeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LeaveApplicationService $applicationService,
        protected SandwichLeaveCalculationService $sandwichService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = LeaveApplication::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee.department', 'leaveType']);

        // Role-based scoping
        if ($user->isEmployee() && !$user->isHr()) {
            $employee = $user->employee();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isManager() && !$user->isHr()) {
            $managerEmp = $user->employee();
            if ($managerEmp) {
                $query->whereHas('employee', function ($q) use ($managerEmp) {
                    $q->where('reporting_manager_id', $managerEmp->id);
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $applications = $query->orderByDesc('id')->paginate(15);
        $leaveTypes = LeaveType::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'Active')->get();

        return view('leaves.applications.index', compact('applications', 'leaveTypes'));
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $employees = [];
        $selectedEmployee = null;

        if ($user->isHr()) {
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active')
                ->get();
        } else {
            $selectedEmployee = $user->employee();
            if (!$selectedEmployee) {
                abort(403, 'Employee profile not linked to current user.');
            }
        }

        $leaveTypes = LeaveType::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        return view('leaves.applications.create', compact('employees', 'selectedEmployee', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|in:First Half,Second Half',
            'reason' => 'required|string|max:1000',
            'is_notice_period_override' => 'nullable|boolean',
            'notice_period_justification' => 'nullable|string|max:500',
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        // If not HR, employee can only apply for themselves
        if (!$user->isHr()) {
            $myEmp = $user->employee();
            if (!$myEmp || $myEmp->id !== $employee->id) {
                abort(403, 'You can only apply for your own leaves.');
            }
        }

        $leaveType = LeaveType::withoutGlobalScopes()->findOrFail($validated['leave_type_id']);
        if ($leaveType->company_id !== $companyId) {
            abort(404);
        }

        $validated['is_half_day'] = $request->boolean('is_half_day');
        $validated['is_notice_period_override'] = $request->boolean('is_notice_period_override');

        $result = $this->applicationService->applyLeave(
            employee: $employee,
            leaveType: $leaveType,
            data: $validated,
            applicant: $user
        );

        if ($result['status'] !== 'Success') {
            return back()->withInput()->withErrors(['leave_error' => $result['message']]);
        }

        return redirect()->route('leaves.applications.show', $result['application']->id)
            ->with('success', 'Leave application submitted successfully. Application Number: ' . $result['application']->application_number);
    }

    public function show(LeaveApplication $application)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($application->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        // ESS scope restriction
        if ($user->isEmployee() && !$user->isHr() && !$user->isManager()) {
            $myEmp = $user->employee();
            if (!$myEmp || $myEmp->id !== $application->employee_id) {
                abort(403);
            }
        }

        $application->load(['employee.department', 'employee.designation', 'leaveType', 'approvalSteps.approver']);

        return view('leaves.applications.show', compact('application'));
    }

    public function previewSandwich(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            return response()->json(['error' => 'Employee does not belong to active company.'], 404);
        }

        $leaveType = LeaveType::withoutGlobalScopes()->findOrFail($validated['leave_type_id']);
        if ($leaveType->company_id !== $companyId) {
            return response()->json(['error' => 'Leave Type does not belong to active company.'], 404);
        }

        $calc = $this->sandwichService->calculateLeaveDays(
            employee: $employee,
            leaveType: $leaveType,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            isHalfDay: (bool) ($validated['is_half_day'] ?? false)
        );

        return response()->json($calc);
    }

    public function withdraw(LeaveApplication $application)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($application->company_id !== $companyId) {
            abort(404);
        }

        $result = $this->applicationService->withdrawApplication($application, $user);

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['withdraw_error' => $result['message']]);
        }

        return back()->with('success', 'Leave application successfully withdrawn and reserved balance released.');
    }
}

