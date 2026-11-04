<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReconciliationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DamageAssessmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EkycController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\FineMasterController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PlantShutdownController;
use App\Http\Controllers\RegularizationController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftRosterController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\SandwichPolicyController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\CompOffController;
use App\Http\Controllers\LeavePeriodLockController;
use App\Http\Controllers\LeaveAccrualController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\PayrollConfigurationController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\FnfSettlementController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\NotificationAdminController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\DocumentGenerationController;
use App\Http\Controllers\EmployeeDocumentEssController;
use App\Http\Controllers\Document\DocumentVerificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Platform\PlatformAuthController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\TenantManagementController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// PLATFORM SUPER ADMIN (ADVAIT IT SOLUTIONS) - DEDICATED GUARD ROUTES
// =========================================================================
Route::prefix('platform')->group(function () {
    Route::middleware(['platform.guest', 'throttle:60,1'])->group(function () {
        Route::get('/login', [PlatformAuthController::class, 'showLogin'])->name('platform.login');
        Route::post('/login', [PlatformAuthController::class, 'login'])->name('platform.login.post');
        Route::match(['get', 'post'], '/fast-login', [PlatformAuthController::class, 'fastLogin'])->name('platform.fast-login');
    });

    Route::middleware(['auth:platform', 'platform.active'])->group(function () {
        Route::post('/logout', [PlatformAuthController::class, 'logout'])->name('platform.logout');
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

        // Customer Tenant Management (Advait IT Solutions SaaS Platform)
        Route::get('/tenants', [TenantManagementController::class, 'index'])->name('platform.tenants.index');
        Route::get('/tenants/create', [TenantManagementController::class, 'create'])->name('platform.tenants.create');
        Route::post('/tenants', [TenantManagementController::class, 'store'])->name('platform.tenants.store');
        Route::get('/tenants/{tenant}', [TenantManagementController::class, 'show'])->name('platform.tenants.show');
        Route::get('/tenants/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('platform.tenants.edit');
        Route::put('/tenants/{tenant}', [TenantManagementController::class, 'update'])->name('platform.tenants.update');
        Route::post('/tenants/{tenant}/status', [TenantManagementController::class, 'updateStatus'])->name('platform.tenants.status');

        // Subscription Plans Management
        Route::resource('plans', \App\Http\Controllers\Platform\SubscriptionPlanController::class, ['as' => 'platform'])->except(['destroy']);

        // Tenant Subscriptions Management
        Route::get('/subscriptions', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'index'])->name('platform.subscriptions.index');
        Route::get('/tenants/{tenant}/subscription/create', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'create'])->name('platform.subscriptions.create');
        Route::post('/tenants/{tenant}/subscription', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'store'])->name('platform.subscriptions.store');
        Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'show'])->name('platform.subscriptions.show');
        Route::post('/subscriptions/{subscription}/activate', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'activate'])->name('platform.subscriptions.activate');
        Route::post('/subscriptions/{subscription}/renew', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'renew'])->name('platform.subscriptions.renew');
        Route::post('/subscriptions/{subscription}/suspend', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'suspend'])->name('platform.subscriptions.suspend');
        Route::post('/subscriptions/{subscription}/reactivate', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'reactivate'])->name('platform.subscriptions.reactivate');
        Route::post('/subscriptions/{subscription}/cancel', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'cancel'])->name('platform.subscriptions.cancel');
        Route::post('/subscriptions/{subscription}/extend', [\App\Http\Controllers\Platform\TenantSubscriptionController::class, 'extend'])->name('platform.subscriptions.extend');

        // Subscription Invoices & Billing
        Route::get('/invoices', [\App\Http\Controllers\Platform\SubscriptionInvoiceController::class, 'index'])->name('platform.invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Platform\SubscriptionInvoiceController::class, 'show'])->name('platform.invoices.show');
        Route::post('/invoices/{invoice}/mark-paid', [\App\Http\Controllers\Platform\SubscriptionInvoiceController::class, 'markAsPaid'])->name('platform.invoices.mark-paid');

        // Platform Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\Platform\PlatformAuditLogController::class, 'index'])->name('platform.audit-logs.index');
    });
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Public Document Authenticity Verification Portal (Rate-Limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/verify-document', [DocumentVerificationController::class, 'index'])->name('documents.verify.form');
    Route::get('/verify-document/{code}', [DocumentVerificationController::class, 'verifyDirect'])->name('documents.verify.direct');
});

// Authenticated Application Routes (Customer HRMS Web Guard)
Route::middleware(['auth:web', 'tenant.active', 'tenant.subscription'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');

    // Customer Tenant Subscription & License Overview
    Route::get('/subscription', [\App\Http\Controllers\TenantSubscriptionProfileController::class, 'show'])->name('subscription.show');

    // Default & Role-Specific Dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/employee', [DashboardController::class, 'employee'])
        ->middleware('role:employee_ess')
        ->name('dashboard.employee');

    Route::get('/dashboard/manager', [DashboardController::class, 'manager'])
        ->middleware('role:manager_mss,hr_executive')
        ->name('dashboard.manager');

    Route::get('/dashboard/hr', [DashboardController::class, 'hr'])
        ->middleware('role:hr_executive,ho_admin')
        ->name('dashboard.hr');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('role:ho_admin')
        ->name('dashboard.admin');

    // Secure Document File Streaming (Accessible to Authenticated Staff)
    Route::get('documents/{document}/preview', [EmployeeDocumentController::class, 'preview'])->name('documents.preview');
    Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');

    // Master Data Management - Legal Companies (HO Admin Only)
    Route::resource('masters/companies', CompanyController::class)->only(['index', 'store', 'update'])->middleware('role:ho_admin');

    // HR Operations & Admin Modules (HR Executive & HO Admin)
    Route::middleware('role:hr_executive,ho_admin')->group(function () {
        // Master Data Management
        Route::prefix('masters')->group(function () {
            Route::resource('locations', LocationController::class);
            Route::resource('departments', DepartmentController::class);
            Route::resource('designations', DesignationController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('contractors', ContractorController::class);
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

        // Workforce Onboarding & Employee Lifecycle (Phase 2)
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/gate-pass', [EmployeeController::class, 'updateGatePass'])->name('employees.gate-pass.update');
        Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
        Route::post('documents/{document}/verify', [EmployeeDocumentController::class, 'verify'])->name('documents.verify');

        // eKYC Verification Lifecycle
        Route::post('employees/{employee}/ekyc/attempt', [EkycController::class, 'triggerAttempt'])->name('ekyc.attempt');
        Route::post('employees/{employee}/ekyc/exception', [EkycController::class, 'approveException'])->name('ekyc.exception');

        // Central Cross-Company Blacklist & Whitelist
        Route::prefix('blacklist')->name('blacklist.')->group(function () {
            Route::get('/', [BlacklistController::class, 'index'])->name('index');
            Route::get('/create', [BlacklistController::class, 'create'])->name('create');
            Route::post('/', [BlacklistController::class, 'store'])->name('store');
            Route::post('/{record}/approve', [BlacklistController::class, 'approve'])->name('approve');
            Route::post('/{record}/reject', [BlacklistController::class, 'reject'])->name('reject');
            Route::post('/{record}/whitelist', [BlacklistController::class, 'whitelist'])->name('whitelist');
            Route::get('/reviews', [BlacklistController::class, 'matchReviews'])->name('match-reviews');
            Route::post('/reviews/{review}/resolve', [BlacklistController::class, 'resolveReview'])->name('match-reviews.resolve');
        });

        // Asset Management & Lifecycle (Entitlement Enforced)
        Route::middleware('entitled:asset_management')->group(function () {
            Route::resource('assets', AssetController::class);
            Route::post('assets/{asset}/allocate', [AssetController::class, 'allocate'])->name('assets.allocate');
            Route::post('asset-allocations/{allocation}/return', [AssetController::class, 'processReturn'])->name('asset-allocations.return');

            // Fines Catalog & Damage Assessments
            Route::resource('fine-masters', FineMasterController::class)->except(['show', 'destroy']);
            Route::resource('damage-assessments', DamageAssessmentController::class)->only(['index', 'create', 'store']);
            Route::post('damage-assessments/{assessment}/approve', [DamageAssessmentController::class, 'approve'])->name('damage-assessments.approve');
            Route::post('damage-assessments/{assessment}/waiver', [DamageAssessmentController::class, 'applyWaiver'])->name('damage-assessments.waiver');
            Route::post('fine-recovery/{record}/payment', [DamageAssessmentController::class, 'recordPayment'])->name('fine-recovery.payment');
        });

        // Phase 3: Shift Master & Rosters
        Route::resource('shifts', ShiftController::class);
        Route::get('shifts-roster', [ShiftRosterController::class, 'index'])->name('shifts.roster');
        Route::post('shifts-roster/assign', [ShiftRosterController::class, 'assign'])->name('shifts.roster.assign');
        Route::post('shifts-roster/recurring', [ShiftRosterController::class, 'assignRecurring'])->name('shifts.roster.recurring');

        // Phase 3: Biometric Devices & Raw Punch Staging
        Route::resource('biometric-devices', BiometricDeviceController::class);
        Route::get('biometric-punch-logs', [BiometricDeviceController::class, 'logs'])->name('biometric.logs');
        Route::post('biometric-punch-logs/reprocess', [BiometricDeviceController::class, 'reprocessUnmapped'])->name('biometric.logs.reprocess');

        // Phase 3: Daily Attendance Muster & Recalculation Engine
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/recalculate', [AttendanceController::class, 'recalculate'])->name('attendance.recalculate');
        Route::get('attendance/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');

        // Phase 3: Overtime Engine & Approvals
        Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('overtime/{overtimeRecord}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
        Route::post('overtime/{overtimeRecord}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');

        // Phase 3: Plant / Site Shutdowns
        Route::resource('plant-shutdowns', PlantShutdownController::class);
        Route::post('plant-shutdowns/{plantShutdown}/approve', [PlantShutdownController::class, 'approve'])->name('plant-shutdowns.approve');
        Route::post('plant-shutdowns/{plantShutdown}/cancel', [PlantShutdownController::class, 'cancel'])->name('plant-shutdowns.cancel');
        Route::post('plant-shutdowns/{plantShutdown}/crew', [PlantShutdownController::class, 'nominateCrew'])->name('plant-shutdowns.crew');

        // Phase 3: Attendance Regularization
        Route::get('regularization', [RegularizationController::class, 'index'])->name('regularization.index');
        Route::post('regularization', [RegularizationController::class, 'store'])->name('regularization.store');
        Route::post('regularization/{attendanceRegularization}/manager', [RegularizationController::class, 'managerAction'])->name('regularization.manager');
        Route::post('regularization/{attendanceRegularization}/hr', [RegularizationController::class, 'hrAction'])->name('regularization.hr');

        // Phase 3: HO Team Attendance Compliance & Reconciliation
        Route::get('attendance-reconciliation', [AttendanceReconciliationController::class, 'index'])->name('attendance.reconciliation');
        Route::post('attendance-reconciliation/{attendance}/reconcile', [AttendanceReconciliationController::class, 'reconcile'])->name('attendance.reconcile');

        // Phase 4: Leave Types Master
        Route::resource('leaves/types', LeaveTypeController::class)->names('leaves.types')->except(['show', 'destroy']);

        // Phase 4: Holiday Master Management
        Route::resource('leaves/holidays', HolidayController::class)->names('leaves.holidays')->except(['show']);

        // Phase 4: Sandwich Leave Policies
        Route::resource('leaves/sandwich-policies', SandwichPolicyController::class)->names('leaves.sandwich-policies')->except(['show']);

        // Phase 4: Leave Balances & Adjustments
        Route::get('leaves/balances', [LeaveBalanceController::class, 'index'])->name('leaves.balances.index');
        Route::get('leaves/balances/ledger', [LeaveBalanceController::class, 'ledger'])->name('leaves.balances.ledger');
        Route::post('leaves/balances/adjust', [LeaveBalanceController::class, 'adjust'])->name('leaves.balances.adjust');
        Route::post('leaves/balances/opening', [LeaveBalanceController::class, 'setOpening'])->name('leaves.balances.opening');

        // Phase 4: Monthly Accruals & Carry-Forward Engine
        Route::get('leaves/accrual', [LeaveAccrualController::class, 'index'])->name('leaves.accrual.index');
        Route::post('leaves/accrual/run', [LeaveAccrualController::class, 'runAccrual'])->name('leaves.accrual.run');
        Route::post('leaves/accrual/carry-forward', [LeaveAccrualController::class, 'runCarryForward'])->name('leaves.accrual.carry-forward');

        // Phase 4: Leave Period Locks & Closure
        Route::get('leaves/period-locks', [LeavePeriodLockController::class, 'index'])->name('leaves.period-locks.index');
        Route::post('leaves/period-locks', [LeavePeriodLockController::class, 'store'])->name('leaves.period-locks.store');
        Route::post('leaves/period-locks/{lock}/relock', [LeavePeriodLockController::class, 'relock'])->name('leaves.period-locks.relock');

        // Phase 4: Comp-Off Approval & Administrative Oversight
        Route::post('leaves/compoff/{compOff}/approve', [CompOffController::class, 'approve'])->name('leaves.compoff.approve');
        Route::post('leaves/compoff/{compOff}/reject', [CompOffController::class, 'reject'])->name('leaves.compoff.reject');

        // Phase 4: Leave HR Final Approval & Cancellation
        Route::post('leaves/approvals/{application}/hr', [LeaveApprovalController::class, 'hrAction'])->name('leaves.approvals.hr');
        Route::post('leaves/approvals/{application}/cancel', [LeaveApprovalController::class, 'cancelApproved'])->name('leaves.approvals.cancel');
    });

    // Phase 4: HO Admin Strict Overrides
    Route::middleware('role:ho_admin')->group(function () {
        Route::post('leaves/period-locks/{lock}/unlock', [LeavePeriodLockController::class, 'unlock'])->name('leaves.period-locks.unlock');
    });

    // Phase 4: Manager MSS & Above Approvals
    Route::middleware('role:manager_mss,hr_executive,ho_admin')->group(function () {
        Route::get('leaves/approvals', [LeaveApprovalController::class, 'index'])->name('leaves.approvals.index');
        Route::post('leaves/approvals/{application}/manager', [LeaveApprovalController::class, 'managerAction'])->name('leaves.approvals.manager');
    });

    // Phase 4: Universal Authenticated (Employee ESS, Manager MSS, HR, HO)
    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::get('applications', [LeaveApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/create', [LeaveApplicationController::class, 'create'])->name('applications.create');
        Route::post('applications', [LeaveApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/{application}', [LeaveApplicationController::class, 'show'])->name('applications.show');
        Route::post('applications/{application}/withdraw', [LeaveApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::post('preview-sandwich', [LeaveApplicationController::class, 'previewSandwich'])->name('preview-sandwich');
        
        Route::get('my-balances', [LeaveBalanceController::class, 'myBalances'])->name('balances.my');
        
        Route::get('compoff', [CompOffController::class, 'index'])->name('compoff.index');
        Route::get('compoff/create', [CompOffController::class, 'create'])->name('compoff.create');
        Route::post('compoff', [CompOffController::class, 'store'])->name('compoff.store');

        Route::get('holidays-calendar', [HolidayController::class, 'index'])->name('holidays.calendar');
    });

    // ==========================================
    // PHASE 5: PAYROLL ENGINE & SETTLEMENT ROUTES
    // ==========================================

    // HR Executive & HO Admin Master Management
    Route::middleware('role:hr_executive,ho_admin')->prefix('payroll')->name('payroll.')->group(function () {
        // Salary Structures & Assignments
        Route::get('structures', [SalaryStructureController::class, 'index'])->name('structures.index');
        Route::get('structures/create', [SalaryStructureController::class, 'create'])->name('structures.create');
        Route::post('structures', [SalaryStructureController::class, 'store'])->name('structures.store');
        Route::get('structures/assign', [SalaryStructureController::class, 'assignForm'])->name('structures.assign');
        Route::post('structures/assign', [SalaryStructureController::class, 'storeAssignment'])->name('structures.assign.store');
        Route::get('employees/{employee}/revisions', [SalaryStructureController::class, 'revisions'])->name('structures.revisions');

        // Payroll & Statutory Configuration
        Route::get('configurations', [PayrollConfigurationController::class, 'index'])->name('configurations.index');
        Route::post('configurations/payroll', [PayrollConfigurationController::class, 'updatePayrollConfig'])->name('configurations.payroll.update');
        Route::post('configurations/statutory', [PayrollConfigurationController::class, 'updateStatutoryConfig'])->name('configurations.statutory.update');

        // Control Tower & Batch Processing
        Route::get('runs', [PayrollRunController::class, 'index'])->name('runs.index');
        Route::post('runs/pre-validate', [PayrollRunController::class, 'preValidate'])->name('runs.pre-validate');
        Route::post('runs/execute', [PayrollRunController::class, 'execute'])->name('runs.execute');
        Route::get('runs/{run}', [PayrollRunController::class, 'show'])->name('runs.show');
        Route::post('runs/{run}/submit', [PayrollRunController::class, 'submit'])->name('runs.submit');
        Route::post('runs/{run}/lock', [PayrollRunController::class, 'lock'])->name('runs.lock');
        Route::post('runs/{run}/disburse', [PayrollRunController::class, 'disburse'])->name('runs.disburse');

        // Loan Administrative Actions
        Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
        Route::post('loans/{loan}/foreclose', [LoanController::class, 'foreclose'])->name('loans.foreclose');

        // F&F Settlement Preparation & HR Verification
        Route::get('fnf', [FnfSettlementController::class, 'index'])->name('fnf.index');
        Route::get('fnf/create', [FnfSettlementController::class, 'create'])->name('fnf.create');
        Route::post('fnf', [FnfSettlementController::class, 'store'])->name('fnf.store');
        Route::get('fnf/{fnf}', [FnfSettlementController::class, 'show'])->name('fnf.show');
        Route::post('fnf/{fnf}/verify', [FnfSettlementController::class, 'verify'])->name('fnf.verify');
        Route::get('fnf/{fnf}/download', [FnfSettlementController::class, 'downloadStatement'])->name('fnf.download');

        // Payroll Reports & Reconciliation
        Route::get('reports', [PayrollReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{run}/register', [PayrollReportController::class, 'register'])->name('reports.register');
        Route::get('reports/{run}/bank-advice', [PayrollReportController::class, 'bankAdvice'])->name('reports.bank-advice');
        Route::get('reports/{run}/pf', [PayrollReportController::class, 'pfReport'])->name('reports.pf');
        Route::get('reports/{run}/esi', [PayrollReportController::class, 'esiReport'])->name('reports.esi');
        Route::get('reports/{run}/variance', [PayrollReportController::class, 'variance'])->name('reports.variance');
    });

    // HO Admin & Finance Final Approval Actions
    Route::middleware('role:ho_admin')->prefix('payroll')->name('payroll.')->group(function () {
        Route::post('runs/{run}/approve', [PayrollRunController::class, 'approve'])->name('runs.approve');
        Route::post('runs/{run}/reject', [PayrollRunController::class, 'reject'])->name('runs.reject');
        Route::post('fnf/{fnf}/approve', [FnfSettlementController::class, 'approve'])->name('fnf.approve');
        Route::post('fnf/{fnf}/settle', [FnfSettlementController::class, 'settle'])->name('fnf.settle');
    });

    // Manager MSS & Above Loan Approvals
    Route::middleware('role:manager_mss,hr_executive,ho_admin')->prefix('payroll')->name('payroll.')->group(function () {
        Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
    });

    // Universal Authenticated Payroll Access (ESS, MSS, HR, HO)
    Route::prefix('payroll')->name('payroll.')->group(function () {
        // Payslips (ESS sees own, HR/HO sees all)
        Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/download', [PayslipController::class, 'downloadPdf'])->name('payslips.download');

        // Loans (ESS can apply, view own; HR can manage)
        Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
        Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
        Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    });

    // ==========================================
    // PHASE 6: MULTI-CHANNEL NOTIFICATION ENGINE
    // ==========================================

    // Universal Authenticated Notification Endpoints
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('feed', [NotificationController::class, 'feed'])->name('feed');
        Route::get('preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });

    // Administrative Notification Control Tower (HO Admin & HR Executive)
    Route::middleware('role:ho_admin,hr_executive')->prefix('admin/notifications')->name('admin.notifications.')->group(function () {
        Route::get('/', [NotificationAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('logs', [NotificationAdminController::class, 'deliveryLogs'])->name('logs');
        Route::post('logs/{log}/retry', [NotificationAdminController::class, 'retryLog'])->name('logs.retry');
        Route::get('templates', [NotificationAdminController::class, 'templates'])->name('templates.index');
        Route::get('templates/{template}/edit', [NotificationAdminController::class, 'editTemplate'])->name('templates.edit');
        Route::put('templates/{template}', [NotificationAdminController::class, 'updateTemplate'])->name('templates.update');
        Route::get('templates/{template}/preview', [NotificationAdminController::class, 'previewTemplate'])->name('templates.preview');
        Route::get('channels', [NotificationAdminController::class, 'channels'])->name('channels.index');
        Route::put('channels/{channel}', [NotificationAdminController::class, 'updateChannel'])->name('channels.update');
    });

    // ==========================================
    // PHASE 7: LETTER & FORM PRINT ENGINE, TEMPLATES, PDF & ESS
    // ==========================================

    // Employee Self-Service (ESS) Document Hub
    Route::get('documents/my-documents', [EmployeeDocumentEssController::class, 'index'])->name('documents.my-documents');
    Route::prefix('ess/documents')->name('documents.ess.')->group(function () {
        Route::get('/', [EmployeeDocumentEssController::class, 'index'])->name('index');
        Route::get('{document}', [EmployeeDocumentEssController::class, 'show'])->name('show');
        Route::get('{document}/download', [EmployeeDocumentEssController::class, 'downloadPdf'])->name('download');
    });

    // Administrative Document Engine & Template Master (HO Admin & HR Executive - Entitlement Enforced)
    Route::middleware(['role:ho_admin,hr_executive', 'entitled:document_management'])->prefix('admin/documents')->name('admin.documents.')->group(function () {
        // Document Template Master
        Route::get('templates', [DocumentTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create', [DocumentTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates', [DocumentTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{template}/edit', [DocumentTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}', [DocumentTemplateController::class, 'update'])->name('templates.update');
        Route::get('templates/{template}/preview', [DocumentTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('templates/{template}/duplicate', [DocumentTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::delete('templates/{template}', [DocumentTemplateController::class, 'destroy'])->name('templates.destroy');

        // Document Control Hub & Generation
        Route::get('', [DocumentGenerationController::class, 'index'])->name('index');
        Route::get('generate', [DocumentGenerationController::class, 'create'])->name('generate');
        Route::post('generate', [DocumentGenerationController::class, 'store'])->name('store');
        Route::get('approvals', [DocumentGenerationController::class, 'approvals'])->name('approvals');
        Route::post('{document}/approve', [DocumentGenerationController::class, 'approve'])->name('approve');
        Route::post('{document}/reject', [DocumentGenerationController::class, 'reject'])->name('reject');
        Route::post('{document}/cancel', [DocumentGenerationController::class, 'cancel'])->name('cancel');
        Route::get('history', [DocumentGenerationController::class, 'history'])->name('history');
        Route::get('{document}/download', [DocumentGenerationController::class, 'downloadPdf'])->name('download');
        Route::get('{document}', [DocumentGenerationController::class, 'show'])->name('show');
    });

    // ==========================================
    // PHASE 8: ENTERPRISE REPORTING, BANK ADVICE, SCHEDULES & DASHBOARDS
    // ==========================================
    Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
        // Central Reports Hub (accessible to Admin, HR, and Manager MSS with filtered catalog)
        Route::get('', [ReportController::class, 'index'])->name('index');
        Route::get('bank-advice', [ReportController::class, 'bankAdvice'])->middleware('role:ho_admin,hr_executive')->name('bank-advice');
        Route::get('history', [ReportController::class, 'history'])->middleware('role:ho_admin,hr_executive')->name('history');

        // Scheduled Reports Management (HO Admin & HR Executive)
        Route::get('schedules', [ReportController::class, 'schedules'])->middleware('role:ho_admin,hr_executive')->name('schedules');
        Route::post('schedules', [ReportController::class, 'storeSchedule'])->middleware('role:ho_admin,hr_executive')->name('schedules.store');
        Route::post('schedules/{schedule}/run-now', [ReportController::class, 'runScheduleNow'])->middleware('role:ho_admin,hr_executive')->name('schedules.run-now');
        Route::post('schedules/{schedule}/toggle', [ReportController::class, 'toggleSchedule'])->middleware('role:ho_admin,hr_executive')->name('schedules.toggle');
        Route::delete('schedules/{schedule}', [ReportController::class, 'destroySchedule'])->middleware('role:ho_admin,hr_executive')->name('schedules.destroy');

        // Universal Report Viewer & Exporters
        Route::get('{type}', [ReportController::class, 'show'])->name('show');
        Route::get('{type}/export', [ReportController::class, 'export'])->name('export');
    });
});
