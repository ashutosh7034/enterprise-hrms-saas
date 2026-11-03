<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Services\CompanyContextService;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LeaveBalanceService $balanceService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $year = (int) ($request->year ?? now()->format('Y'));

        $query = LeaveBalance::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->with(['employee.department', 'leaveType']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->whereHas('employee', function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('employee_code', 'like', $term);
            });
        }

        $balances = $query->paginate(20);

        $leaveTypes = LeaveType::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $employees = Employee::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'Active')->get();

        return view('leaves.balances.index', compact('balances', 'leaveTypes', 'employees', 'year'));
    }

    public function myBalances(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();
        $employee = $user->employee();

        if (!$employee) {
            abort(403, 'Employee profile not associated with this user.');
        }

        $year = (int) ($request->year ?? now()->format('Y'));

        $balances = LeaveBalance::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        $transactions = LeaveBalanceTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->whereYear('transaction_date', $year)
            ->with('leaveType')
            ->orderByDesc('id')
            ->paginate(15);

        return view('leaves.balances.my', compact('employee', 'balances', 'transactions', 'year'));
    }

    public function ledger(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = LeaveBalanceTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['employee', 'leaveType', 'creator']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderByDesc('id')->paginate(25);

        $employees = Employee::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $leaveTypes = LeaveType::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('leaves.balances.ledger', compact('transactions', 'employees', 'leaveTypes'));
    }

    public function adjust(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:2100',
            'adjustment_type' => 'required|in:Credit,Debit',
            'days' => 'required|numeric|min:0.5|max:365',
            'reason' => 'required|string|max:500',
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $leaveType = LeaveType::withoutGlobalScopes()->findOrFail($validated['leave_type_id']);
        if ($leaveType->company_id !== $companyId) {
            abort(404);
        }

        $this->balanceService->manualAdjustment(
            employee: $employee,
            leaveType: $leaveType,
            adjustmentType: $validated['adjustment_type'],
            days: (float) $validated['days'],
            year: (int) $validated['year'],
            reason: $validated['reason'],
            actor: Auth::user()
        );

        return back()->with('success', "Balance {$validated['adjustment_type']} adjustment of {$validated['days']} days recorded in ledger successfully.");
    }

    public function setOpening(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:2100',
            'days' => 'required|numeric|min:0|max:365',
            'remarks' => 'nullable|string|max:255',
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $leaveType = LeaveType::withoutGlobalScopes()->findOrFail($validated['leave_type_id']);
        if ($leaveType->company_id !== $companyId) {
            abort(404);
        }

        $this->balanceService->setOpeningBalance(
            employee: $employee,
            leaveType: $leaveType,
            days: (float) $validated['days'],
            year: (int) $validated['year'],
            actor: Auth::user(),
            remarks: $validated['remarks']
        );

        return back()->with('success', 'Opening balance updated and logged in ledger successfully.');
    }
}

