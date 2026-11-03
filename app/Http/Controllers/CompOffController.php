<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CompOffRecord;
use App\Models\Employee;
use App\Services\CompanyContextService;
use App\Services\CompOffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected CompOffService $compOffService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = CompOffRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee.department', 'attendanceRecord', 'approver']);

        if ($user->isEmployee() && !$user->isHr()) {
            $emp = $user->employee();
            if ($emp) {
                $query->where('employee_id', $emp->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderByDesc('qualifying_work_date')->paginate(15);

        return view('leaves.compoff.index', compact('records'));
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $employee = null;
        if ($user->isHr()) {
            $employees = Employee::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'Active')->get();
        } else {
            $employee = $user->employee();
            if (!$employee) {
                abort(403, 'Employee profile not associated with this user.');
            }
            $employees = collect([$employee]);
        }

        // Fetch qualifying attendance records on weekly off, holiday, or shutdown work
        $attendanceQuery = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Weekly Off', 'Holiday', 'Worked During Shutdown', 'Present'])
            ->where('actual_worked_minutes', '>=', 240);

        if ($employee) {
            $attendanceQuery->where('employee_id', $employee->id);
        }

        $eligibleAttendances = $attendanceQuery->orderByDesc('attendance_date')->take(50)->get();

        return view('leaves.compoff.create', compact('employees', 'eligibleAttendances', 'employee'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_record_id' => 'required|exists:attendance_records,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $attendance = AttendanceRecord::withoutGlobalScopes()->findOrFail($validated['attendance_record_id']);
        if ($attendance->company_id !== $companyId) {
            abort(404);
        }

        $result = $this->compOffService->requestCompOff(
            employee: $employee,
            attendance: $attendance,
            remarks: $validated['remarks']
        );

        if ($result['status'] !== 'Success') {
            return back()->withInput()->withErrors(['compoff_error' => $result['message']]);
        }

        return redirect()->route('leaves.compoff.index')
            ->with('success', 'Comp-Off credit request submitted successfully.');
    }

    public function approve(Request $request, CompOffRecord $compOff)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($compOff->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'approval_remarks' => 'nullable|string|max:255',
        ]);

        $result = $this->compOffService->approveCompOff(
            record: $compOff,
            approver: $user,
            remarks: $validated['approval_remarks']
        );

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['approval_error' => $result['message']]);
        }

        return back()->with('success', 'Comp-Off approved and balance credited to employee ledger successfully.');
    }

    public function reject(Request $request, CompOffRecord $compOff)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($compOff->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $result = $this->compOffService->rejectCompOff(
            record: $compOff,
            approver: $user,
            reason: $validated['reason']
        );

        return back()->with('success', 'Comp-Off request rejected.');
    }
}

