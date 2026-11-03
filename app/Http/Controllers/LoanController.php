<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\CompanyContextService;
use App\Services\LoanManagementService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(
        protected LoanManagementService $loanService,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $user = $request->user();
        $roleName = $user->role?->name ?? '';

        $query = EmployeeLoan::where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'approver']);

        if ($roleName === 'employee_ess') {
            $employeeId = $user->employee()?->id;
            if (!$employeeId) {
                return view('payroll.loans.index', ['loans' => collect(), 'isEss' => true]);
            }
            $query->where('employee_id', $employeeId);
        }

        $loans = $query->latest('id')->paginate(15);
        $isEss = ($roleName === 'employee_ess');

        return view('payroll.loans.index', compact('loans', 'isEss'));
    }

    public function create(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $user = $request->user();
        $roleName = $user->role?->name ?? '';

        $employees = [];
        if ($roleName !== 'employee_ess') {
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active')
                ->orderBy('first_name')
                ->get();
        }

        return view('payroll.loans.create', compact('employees', 'roleName'));
    }

    public function store(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $user = $request->user();
        $roleName = $user->role?->name ?? '';

        $validated = $request->validate([
            'employee_id' => [$roleName === 'employee_ess' ? 'nullable' : 'required', 'exists:employees,id'],
            'loan_type' => ['required', 'in:Personal Loan,Salary Advance,Emergency Medical Advance,Festival Advance'],
            'principal_amount' => ['required', 'numeric', 'min:500', 'max:500000'],
            'tenure_months' => ['required', 'integer', 'min:1', 'max:36'],
            'interest_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = ($roleName === 'employee_ess')
            ? $user->employee()
            : Employee::withoutGlobalScopes()->where('company_id', $companyId)->findOrFail($validated['employee_id']);

        if (!$employee) {
            return back()->with('error', 'Employee record not found for this user.');
        }

        $this->loanService->applyLoan($employee, $validated);

        return redirect()->route('payroll.loans.index')->with('success', 'Loan / Advance application submitted successfully.');
    }

    public function show(EmployeeLoan $loan)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($loan->company_id !== $companyId) {
            abort(404);
        }

        $loan->loadMissing(['employee.department', 'employee.designation', 'approver', 'emiSchedules']);

        return view('payroll.loans.show', compact('loan'));
    }

    public function approve(Request $request, EmployeeLoan $loan)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($loan->company_id !== $companyId) {
            abort(404);
        }

        $roleName = $request->user()->role?->name ?? '';
        $level = in_array($roleName, ['ho_admin', 'hr_executive']) ? 'HR' : 'Manager';

        $this->loanService->approveLoan($loan, $request->user(), $level);

        return back()->with('success', "Loan approved at {$level} level.");
    }

    public function disburse(Request $request, EmployeeLoan $loan)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($loan->company_id !== $companyId) {
            abort(404);
        }

        $disbursedDate = $request->input('disbursed_date', now()->toDateString());
        $this->loanService->disburseLoan($loan, $disbursedDate);

        return back()->with('success', 'Loan disbursed and monthly EMI amortization schedule generated.');
    }

    public function foreclose(Request $request, EmployeeLoan $loan)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($loan->company_id !== $companyId) {
            abort(404);
        }

        $remarks = $request->input('remarks');
        $this->loanService->forecloseLoan($loan, $request->user(), $remarks);

        return back()->with('success', 'Loan settled / foreclosed successfully.');
    }

    public function reject(Request $request, EmployeeLoan $loan)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($loan->company_id !== $companyId) {
            abort(404);
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'min:5']]);

        $this->loanService->rejectLoan($loan, $request->user(), $request->input('rejection_reason'));

        return back()->with('warning', 'Loan application rejected.');
    }
}
