<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Services\CompanyContextService;
use App\Services\PayrollPreValidationService;
use App\Services\PayrollProcessingService;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function __construct(
        protected PayrollProcessingService $processingService,
        protected PayrollPreValidationService $preValidationService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Payroll Control Tower Dashboard.
     */
    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $company = Company::findOrFail($companyId);

        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = (int) $request->input('month', date('n'));

        $period = $this->processingService->getOrCreatePeriod($companyId, $selectedYear, $selectedMonth);

        $currentRun = PayrollRun::where('company_id', $companyId)
            ->where('payroll_period_id', $period->id)
            ->with(['processor', 'approver', 'approvalSteps.actor'])
            ->latest('id')
            ->first();

        $pastRuns = PayrollRun::where('company_id', $companyId)
            ->with(['payrollPeriod', 'processor', 'approver'])
            ->latest('id')
            ->paginate(10);

        return view('payroll.runs.index', compact('company', 'selectedYear', 'selectedMonth', 'period', 'currentRun', 'pastRuns'));
    }

    /**
     * Run Pre-Validation Gate check.
     */
    public function preValidate(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $results = $this->preValidationService->validatePayrollEligibility(
            $companyId,
            (int) $validated['year'],
            (int) $validated['month']
        );

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        return view('payroll.runs.pre_validate', compact('results'));
    }

    /**
     * Execute batch payroll calculation.
     */
    public function execute(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $company = Company::findOrFail($companyId);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $run = $this->processingService->executePayrollRun(
                $company,
                (int) $validated['year'],
                (int) $validated['month'],
                $request->user()
            );

            return redirect()->route('payroll.runs.show', $run)->with('success', "Payroll calculated successfully for batch {$run->run_batch_number}. Total processed: {$run->total_processed_employees} employees.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Detailed Run & Employee Ledger View.
     */
    public function show(PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $run->loadMissing(['payrollPeriod', 'processor', 'approver', 'approvalSteps.actor']);

        $employees = PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->with(['employee.department', 'employee.designation'])
            ->paginate(25);

        return view('payroll.runs.show', compact('run', 'employees'));
    }

    /**
     * Submit for approval.
     */
    public function submit(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $remarks = $request->input('remarks');
        $this->processingService->submitForApproval($run, $request->user(), $remarks);

        return back()->with('success', 'Payroll run submitted for approval.');
    }

    /**
     * Approve payroll run (HO Admin / Finance).
     */
    public function approve(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $remarks = $request->input('remarks');
        $this->processingService->approvePayrollRun($run, $request->user(), $remarks);

        return back()->with('success', 'Payroll run approved successfully. Loan EMIs and fine deductions booked.');
    }

    /**
     * Reject payroll run with mandatory remarks.
     */
    public function reject(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $request->validate(['remarks' => ['required', 'string', 'min:5']]);

        $this->processingService->rejectPayrollRun($run, $request->user(), $request->input('remarks'));

        return back()->with('warning', 'Payroll run rejected. Rejection remarks recorded.');
    }

    /**
     * Lock approved payroll run.
     */
    public function lock(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $this->processingService->lockPayrollRun($run, $request->user());

        return back()->with('success', 'Payroll run and period locked against edits.');
    }

    /**
     * Mark payroll run as disbursed.
     */
    public function disburse(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $reference = $request->input('payment_reference', 'NEFT-' . date('Ymd'));
        $this->processingService->markAsDisbursed($run, $reference);

        return back()->with('success', 'Payroll marked as disbursed. Payment reference recorded.');
    }
}

