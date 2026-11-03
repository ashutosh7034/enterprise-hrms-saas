<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\CompanyContextService;
use App\Services\Ekyc\EkycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class EkycController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EkycService $ekycService
    ) {}

    public function triggerAttempt(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'mode' => ['required', 'in:Simulated eKYC,Manual Verification'],
            'scenario' => ['nullable', 'string', 'in:auto,fail_name_mismatch,fail_dob_mismatch,fail_unclear_doc,force_failure'],
            'approved' => ['nullable', 'boolean'],
            'failure_reason' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'grace_days' => ['nullable', 'integer', 'min:7', 'max:21'],
        ]);

        try {
            $attempt = $this->ekycService->processAttempt(
                $employee,
                $validated['mode'],
                $validated,
                Auth::user()
            );

            if ($attempt->result === 'Success') {
                return redirect()->route('employees.show', $employee->id)
                    ->with('success', "eKYC verification completed successfully (Attempt #{$attempt->attempt_number}).");
            } else {
                \App\Events\EkycResubmissionRequiredEvent::dispatch(
                    $employee,
                    $attempt->failure_reason,
                    $attempt->resubmission_due_date ? (string) $attempt->resubmission_due_date : null
                );

                return redirect()->route('employees.show', $employee->id)
                    ->with('warning', "eKYC attempt #{$attempt->attempt_number} failed: {$attempt->failure_reason}. Grace period is active until {$attempt->resubmission_due_date}.");
            }
        } catch (InvalidArgumentException $e) {
            return redirect()->route('employees.show', $employee->id)
                ->withErrors(['ekyc' => $e->getMessage()]);
        }
    }

    public function approveException(Request $request, Employee $employee)
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized: Only HO Admin can approve eKYC exceptions.');
        }

        $validated = $request->validate([
            'exception_remarks' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'exception_remarks.required' => 'A mandatory justification remark (min 10 characters) is required for HO Admin exception approval.',
        ]);

        try {
            $this->ekycService->approveException(
                $employee,
                Auth::user(),
                $validated['exception_remarks']
            );

            return redirect()->route('employees.show', $employee->id)
                ->with('success', "HO Admin exception override approved. Employee is now marked as 'Exceptionally Verified'.");
        } catch (InvalidArgumentException $e) {
            return redirect()->route('employees.show', $employee->id)
                ->withErrors(['ekyc' => $e->getMessage()]);
        }
    }
}

