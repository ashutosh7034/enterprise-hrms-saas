<?php

namespace App\Listeners;

use App\Events\AttendanceRegularizationDecidedEvent;
use App\Events\AttendanceRegularizationSubmittedEvent;
use App\Events\EmployeeCreatedEvent;
use App\Events\EkycResubmissionRequiredEvent;
use App\Events\FnfSettlementEvent;
use App\Events\LeaveApplicationDecidedEvent;
use App\Events\LeaveApplicationSubmittedEvent;
use App\Events\LeavePeriodLockedEvent;
use App\Events\LoanApplicationEvent;
use App\Events\PayrollCycleEvent;
use App\Events\PayslipGeneratedEvent;
use App\Events\PlantShutdownApprovedEvent;
use App\Events\PlantShutdownCancelledEvent;
use App\Events\ShiftAssignedEvent;
use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class HrmsNotificationSubscriber
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    protected function resolveUserForEmployee(Employee $employee): ?User
    {
        $user = User::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where(function ($q) use ($employee) {
                if ($employee->employee_code) {
                    $q->where('employee_code', $employee->employee_code);
                }
                if ($employee->email) {
                    $q->orWhere('email', $employee->email);
                }
            })
            ->first();

        return $user;
    }

    public function handleEmployeeCreated(EmployeeCreatedEvent $event): void
    {
        $user = $this->resolveUserForEmployee($event->employee);
        if (!$user) {
            return;
        }

        $this->notificationService->notify(
            user: $user,
            templateCode: 'ONBOARDING_WELCOME',
            params: [
                'employee_name' => $event->employee->full_name,
                'employee_code' => $event->employee->employee_code,
            ],
            idempotencyKey: "emp-created-{$event->employee->id}",
            employee: $event->employee
        );
    }

    public function handleEkycResubmissionRequired(EkycResubmissionRequiredEvent $event): void
    {
        $user = $this->resolveUserForEmployee($event->employee);
        if (!$user) {
            return;
        }

        $this->notificationService->notify(
            user: $user,
            templateCode: 'EKYC_RESUBMISSION',
            params: [
                'details' => $event->remarks ?? 'Document clarity or mismatch',
                'deadline' => $event->deadline ?? now()->addDays(7)->toDateString(),
            ],
            idempotencyKey: "ekyc-resub-{$event->employee->id}-" . date('Ymd'),
            employee: $event->employee
        );
    }

    public function handleAttendanceRegularizationSubmitted(AttendanceRegularizationSubmittedEvent $event): void
    {
        $employee = $event->regularization->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if ($user) {
            $this->notificationService->notify(
                user: $user,
                templateCode: 'REGULARIZATION_SUBMITTED',
                params: [
                    'attendance_date' => $event->regularization->attendance_date?->toDateString() ?? date('Y-m-d'),
                    'regularization_type' => $event->regularization->regularization_type,
                ],
                idempotencyKey: "reg-sub-{$event->regularization->id}",
                employee: $employee
            );
        }
    }

    public function handleAttendanceRegularizationDecided(AttendanceRegularizationDecidedEvent $event): void
    {
        $employee = $event->regularization->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $isApproved = in_array(strtolower($event->decision), ['approved', 'approve']);
        $template = $isApproved ? 'REGULARIZATION_APPROVED' : 'REGULARIZATION_REJECTED';

        $this->notificationService->notify(
            user: $user,
            templateCode: $template,
            params: [
                'attendance_date' => $event->regularization->attendance_date?->toDateString() ?? date('Y-m-d'),
                'new_status' => $event->regularization->status,
                'remarks' => $event->remarks ?? 'Reviewed by HR',
            ],
            idempotencyKey: "reg-dec-{$event->regularization->id}-{$event->decision}",
            employee: $employee
        );
    }

    public function handleShiftAssigned(ShiftAssignedEvent $event): void
    {
        $employee = $event->assignment->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $shift = $event->assignment->shift;

        $this->notificationService->notify(
            user: $user,
            templateCode: 'SHIFT_ASSIGNMENT',
            params: [
                'shift_name' => $shift?->name ?? 'Standard Shift',
                'shift_timing' => ($shift?->start_time ?? '09:00') . ' - ' . ($shift?->end_time ?? '18:00'),
                'start_date' => $event->assignment->start_date?->toDateString() ?? date('Y-m-d'),
                'end_date' => $event->assignment->end_date?->toDateString() ?? 'Ongoing',
            ],
            idempotencyKey: "shift-assign-{$event->assignment->id}",
            employee: $employee
        );
    }

    public function handlePlantShutdownApproved(PlantShutdownApprovedEvent $event): void
    {
        $shutdown = $event->shutdown;
        // Notify HR and Managers of the affected company
        $admins = User::withoutGlobalScopes()
            ->where('company_id', $shutdown->company_id)
            ->whereHas('role', fn($q) => $q->whereIn('name', ['ho_admin', 'hr_executive', 'manager_mss']))
            ->get();

        foreach ($admins as $adminUser) {
            $this->notificationService->notify(
                user: $adminUser,
                templateCode: 'SHUTDOWN_APPROVED',
                params: [
                    'shutdown_code' => $shutdown->shutdown_code,
                    'reason' => $shutdown->reason,
                    'start_date' => $shutdown->start_date?->toDateString() ?? '',
                    'end_date' => $shutdown->end_date?->toDateString() ?? '',
                    'status' => $shutdown->status,
                ],
                idempotencyKey: "shutdown-app-{$shutdown->id}-user-{$adminUser->id}"
            );
        }
    }

    public function handlePlantShutdownCancelled(PlantShutdownCancelledEvent $event): void
    {
        Log::info("Plant shutdown cancelled event received for {$event->shutdown->shutdown_code}");
    }

    public function handleLeaveApplicationSubmitted(LeaveApplicationSubmittedEvent $event): void
    {
        $employee = $event->application->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if ($user) {
            $this->notificationService->notify(
                user: $user,
                templateCode: 'LEAVE_SUBMITTED',
                params: [
                    'application_number' => $event->application->application_number ?? "APP-{$event->application->id}",
                    'leave_type' => $event->application->leaveType?->name ?? 'Leave',
                    'calculated_days' => $event->application->calculated_days,
                    'start_date' => $event->application->start_date?->toDateString() ?? '',
                    'end_date' => $event->application->end_date?->toDateString() ?? '',
                ],
                idempotencyKey: "leave-sub-{$event->application->id}",
                employee: $employee
            );
        }
    }

    public function handleLeaveApplicationDecided(LeaveApplicationDecidedEvent $event): void
    {
        $employee = $event->application->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $isApproved = in_array(strtolower($event->decision), ['approved', 'approve']);
        $template = $isApproved ? 'LEAVE_APPROVED' : 'LEAVE_REJECTED';

        $this->notificationService->notify(
            user: $user,
            templateCode: $template,
            params: [
                'leave_type' => $event->application->leaveType?->name ?? 'Leave',
                'start_date' => $event->application->start_date?->toDateString() ?? '',
                'end_date' => $event->application->end_date?->toDateString() ?? '',
                'remarks' => $event->remarks ?? 'Processed by Approver',
            ],
            idempotencyKey: "leave-dec-{$event->application->id}-{$event->decision}",
            employee: $employee
        );
    }

    public function handleLeavePeriodLocked(LeavePeriodLockedEvent $event): void
    {
        $companyId = $event->lock->company_id;
        $users = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get();

        foreach ($users as $u) {
            $this->notificationService->notify(
                user: $u,
                templateCode: 'LEAVE_LOCKED',
                params: [
                    'period_month' => $event->lock->period_month,
                ],
                idempotencyKey: "leave-lock-{$event->lock->id}-u-{$u->id}"
            );
        }
    }

    public function handlePayrollCycle(PayrollCycleEvent $event): void
    {
        Log::info("Payroll cycle event: {$event->action} for run {$event->payrollRun->run_code}");
    }

    public function handlePayslipGenerated(PayslipGeneratedEvent $event): void
    {
        $payslip = $event->payslipRecord;
        $employee = $payslip->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $this->notificationService->notify(
            user: $user,
            templateCode: 'PAYSLIP_AVAILABLE',
            params: [
                'period_month' => $payslip->payrollRun?->payrollPeriod?->period_month ?? date('Y-m'),
                'net_pay' => number_format((float) ($payslip->net_pay ?? 0), 2),
            ],
            idempotencyKey: "payslip-gen-{$payslip->id}",
            employee: $employee
        );
    }

    public function handleLoanApplication(LoanApplicationEvent $event): void
    {
        if ($event->action !== 'approved') {
            return;
        }

        $employee = $event->loan->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $this->notificationService->notify(
            user: $user,
            templateCode: 'LOAN_APPROVED',
            params: [
                'loan_number' => $event->loan->loan_number ?? "LN-{$event->loan->id}",
                'principal_amount' => number_format((float) $event->loan->principal_amount, 2),
                'monthly_installment' => number_format((float) $event->loan->monthly_installment, 2),
                'start_period' => $event->loan->disbursement_period ?? date('Y-m'),
            ],
            idempotencyKey: "loan-app-{$event->loan->id}",
            employee: $employee
        );
    }

    public function handleFnfSettlement(FnfSettlementEvent $event): void
    {
        $employee = $event->settlement->employee;
        if (!$employee) {
            return;
        }

        $user = $this->resolveUserForEmployee($employee);
        if (!$user) {
            return;
        }

        $this->notificationService->notify(
            user: $user,
            templateCode: 'FNF_SETTLED',
            params: [
                'net_payable' => number_format((float) $event->settlement->net_payable, 2),
            ],
            idempotencyKey: "fnf-settled-{$event->settlement->id}-{$event->action}",
            employee: $employee
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            EmployeeCreatedEvent::class => 'handleEmployeeCreated',
            EkycResubmissionRequiredEvent::class => 'handleEkycResubmissionRequired',
            AttendanceRegularizationSubmittedEvent::class => 'handleAttendanceRegularizationSubmitted',
            AttendanceRegularizationDecidedEvent::class => 'handleAttendanceRegularizationDecided',
            ShiftAssignedEvent::class => 'handleShiftAssigned',
            PlantShutdownApprovedEvent::class => 'handlePlantShutdownApproved',
            PlantShutdownCancelledEvent::class => 'handlePlantShutdownCancelled',
            LeaveApplicationSubmittedEvent::class => 'handleLeaveApplicationSubmitted',
            LeaveApplicationDecidedEvent::class => 'handleLeaveApplicationDecided',
            LeavePeriodLockedEvent::class => 'handleLeavePeriodLocked',
            PayrollCycleEvent::class => 'handlePayrollCycle',
            PayslipGeneratedEvent::class => 'handlePayslipGenerated',
            LoanApplicationEvent::class => 'handleLoanApplication',
            FnfSettlementEvent::class => 'handleFnfSettlement',
        ];
    }
}

