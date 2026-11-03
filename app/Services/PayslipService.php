<?php

namespace App\Services;

use App\Models\PayrollRunEmployee;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PayslipService
{
    /**
     * Authorize user access to a payslip record.
     */
    public function authorizeAccess(PayrollRunEmployee $payslip, User $user, int $activeCompanyId): void
    {
        // 1. Cross-Company Guard
        if ($payslip->company_id !== $activeCompanyId) {
            throw new AuthorizationException("Cross-company payslip access is prohibited.");
        }

        // 2. Role-based check
        $roleName = $user->role?->name ?? '';

        if (in_array($roleName, ['ho_admin', 'hr_executive'])) {
            return; // HR and HO Admin have company-wide access
        }

        // 3. ESS Employee access: must own the record
        $employeeId = $user->employee()?->id;
        if ($employeeId && $employeeId === $payslip->employee_id) {
            return;
        }

        throw new AuthorizationException("You do not have permission to view or download this payslip.");
    }

    /**
     * Generate HTML view for a payslip.
     */
    public function renderHtml(PayrollRunEmployee $payslip): string
    {
        $payslip->loadMissing([
            'employee.department',
            'employee.designation',
            'employee.location',
            'company',
            'componentTransactions',
        ]);

        return view('payroll.payslips.pdf_template', ['payslip' => $payslip])->render();
    }

    /**
     * Generate PDF binary stream for a payslip using Dompdf.
     */
    public function generatePdf(PayrollRunEmployee $payslip): string
    {
        $html = $this->renderHtml($payslip);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
