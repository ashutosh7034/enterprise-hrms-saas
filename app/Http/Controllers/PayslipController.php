<?php

namespace App\Http\Controllers;

use App\Models\PayrollRunEmployee;
use App\Services\CompanyContextService;
use App\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __construct(
        protected PayslipService $payslipService,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $user = $request->user();
        $roleName = $user->role?->name ?? '';

        $query = PayrollRunEmployee::where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'payrollRun']);

        // ESS: Filter only to own employee records
        if ($roleName === 'employee_ess') {
            $employeeId = $user->employee()?->id;
            if (!$employeeId) {
                return view('payroll.payslips.index', ['payslips' => collect(), 'isEss' => true]);
            }
            $query->where('employee_id', $employeeId);
        } else {
            // HR / Admin filters
            if ($request->filled('year')) {
                $query->where('year', (int) $request->year);
            }
            if ($request->filled('month')) {
                $query->where('month', (int) $request->month);
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', (int) $request->employee_id);
            }
        }

        $payslips = $query->latest('year')->latest('month')->paginate(20);
        $isEss = ($roleName === 'employee_ess');

        return view('payroll.payslips.index', compact('payslips', 'isEss'));
    }

    public function show(Request $request, PayrollRunEmployee $payslip)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->payslipService->authorizeAccess($payslip, $request->user(), $companyId);

        $payslip->loadMissing([
            'employee.department',
            'employee.designation',
            'employee.location',
            'company',
            'componentTransactions',
        ]);

        return view('payroll.payslips.show', compact('payslip'));
    }

    public function downloadPdf(Request $request, PayrollRunEmployee $payslip)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $this->payslipService->authorizeAccess($payslip, $request->user(), $companyId);

        $pdfBinary = $this->payslipService->generatePdf($payslip);
        $filename = "Payslip-{$payslip->payslip_number}.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
