<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FnfSettlement;
use App\Services\CompanyContextService;
use App\Services\FullAndFinalSettlementService;
use Illuminate\Http\Request;

class FnfSettlementController extends Controller
{
    public function __construct(
        protected FullAndFinalSettlementService $fnfService,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $settlements = FnfSettlement::where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'verifier', 'approver'])
            ->latest('id')
            ->paginate(15);

        return view('payroll.fnf.index', compact('settlements'));
    }

    public function create()
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Active', 'Resigned'])
            ->orderBy('first_name')
            ->get();

        return view('payroll.fnf.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'resignation_date' => ['required', 'date'],
            'last_working_date' => ['required', 'date', 'after_or_equal:resignation_date'],
            'notice_period_required_days' => ['nullable', 'integer', 'min:0'],
            'notice_period_served_days' => ['nullable', 'integer', 'min:0'],
            'unpaid_salary_days' => ['nullable', 'numeric', 'min:0'],
            'leave_encashment_days' => ['nullable', 'numeric', 'min:0'],
            'force_gratuity' => ['nullable', 'boolean'],
            'other_deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::withoutGlobalScopes()->where('company_id', $companyId)->findOrFail($validated['employee_id']);

        $settlement = $this->fnfService->prepareDraftSettlement($employee, $validated, $request->user());

        return redirect()->route('payroll.fnf.show', $settlement)->with('success', 'Full & Final draft statement prepared successfully.');
    }

    public function show(FnfSettlement $fnf)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($fnf->company_id !== $companyId) {
            abort(404);
        }

        $fnf->loadMissing(['employee.department', 'employee.designation', 'employee.location', 'components', 'verifier', 'approver']);

        return view('payroll.fnf.show', compact('fnf'));
    }

    public function verify(Request $request, FnfSettlement $fnf)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($fnf->company_id !== $companyId) {
            abort(404);
        }

        $this->fnfService->finalizeSettlement($fnf, $request->user(), 'Verify');

        return back()->with('success', 'F&F settlement marked as HR Verified.');
    }

    public function approve(Request $request, FnfSettlement $fnf)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($fnf->company_id !== $companyId) {
            abort(404);
        }

        $this->fnfService->finalizeSettlement($fnf, $request->user(), 'Approve');

        return back()->with('success', 'F&F settlement approved by Finance.');
    }

    public function settle(Request $request, FnfSettlement $fnf)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($fnf->company_id !== $companyId) {
            abort(404);
        }

        $this->fnfService->finalizeSettlement($fnf, $request->user(), 'Settle');

        return back()->with('success', 'F&F settlement fully completed. Loans closed and employee exit status finalized.');
    }

    public function downloadStatement(Request $request, FnfSettlement $fnf)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($fnf->company_id !== $companyId) {
            abort(404);
        }

        $pdfBinary = $this->fnfService->generateStatementPdf($fnf);
        $filename = "FNF-Statement-{$fnf->settlement_number}.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

