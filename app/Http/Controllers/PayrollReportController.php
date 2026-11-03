<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Services\CompanyContextService;
use App\Services\PayrollReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportController extends Controller
{
    public function __construct(
        protected PayrollReportingService $reportingService,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $runs = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['Pre-Validation Passed', 'Pending Approval', 'Approved', 'Locked', 'Disbursed'])
            ->latest('id')
            ->get();

        return view('payroll.reports.index', compact('runs'));
    }

    public function register(PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $data = $this->reportingService->getPayrollRegister($run);

        return view('payroll.reports.register', compact('data', 'run'));
    }

    public function bankAdvice(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $records = $this->reportingService->getBankAdviceData($run);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv(
                "Bank_Advice_{$run->run_batch_number}.csv",
                ['Employee Code', 'Employee Name', 'Net Amount', 'Status', 'Reference'],
                $records->toArray()
            );
        }

        return view('payroll.reports.bank_advice', compact('run', 'records'));
    }

    public function pfReport(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $records = $this->reportingService->getPfReportData($run);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv(
                "PF_ECR_{$run->run_batch_number}.csv",
                ['Employee Code', 'Employee Name', 'Gross Wages', 'EPF Wages', 'EPS Wages', 'EE Share', 'ER Share'],
                $records->toArray()
            );
        }

        return view('payroll.reports.pf_report', compact('run', 'records'));
    }

    public function esiReport(Request $request, PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $records = $this->reportingService->getEsiReportData($run);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv(
                "ESI_Return_{$run->run_batch_number}.csv",
                ['Employee Code', 'Employee Name', 'Worked Days', 'Gross Wages', 'EE Contribution', 'ER Contribution'],
                $records->toArray()
            );
        }

        return view('payroll.reports.esi_report', compact('run', 'records'));
    }

    public function variance(PayrollRun $run)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $variance = $this->reportingService->getVarianceReport($run);

        return view('payroll.reports.variance', compact('run', 'variance'));
    }

    protected function exportCsv(string $filename, array $headers, array $data): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($data as $row) {
                fputcsv($handle, array_values((array) $row));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

