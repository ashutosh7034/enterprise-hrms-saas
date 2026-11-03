<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\NotificationChannelConfiguration;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // 1. Channel Configurations (defaulting to Mock for safety)
            $channels = [
                ['channel' => 'in_app', 'is_enabled' => true, 'is_mock' => false, 'provider_name' => 'database'],
                ['channel' => 'email', 'is_enabled' => true, 'is_mock' => true, 'provider_name' => 'log'],
                ['channel' => 'sms', 'is_enabled' => true, 'is_mock' => true, 'provider_name' => 'mock'],
                ['channel' => 'whatsapp', 'is_enabled' => true, 'is_mock' => true, 'provider_name' => 'mock'],
            ];

            foreach ($channels as $chan) {
                NotificationChannelConfiguration::firstOrCreate(
                    ['company_id' => $company->id, 'channel' => $chan['channel']],
                    [
                        'is_enabled' => $chan['is_enabled'],
                        'is_mock' => $chan['is_mock'],
                        'provider_name' => $chan['provider_name'],
                        'settings' => ['daily_limit' => 5000, 'rate_limit_per_minute' => 60],
                    ]
                );
            }

            // 2. Default Templates across Categories
            $templates = [
                // Onboarding
                [
                    'code' => 'ONBOARDING_WELCOME',
                    'name' => 'Welcome to Company',
                    'category' => 'Onboarding',
                    'event_type' => 'employee.created',
                    'channel' => 'in_app',
                    'subject' => 'Welcome to {company_name}',
                    'body_template' => 'Hello {employee_name}, welcome to {company_name}! Your employee code is {employee_code}. Please complete your onboarding steps.',
                    'action_url' => '/dashboard',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'ONBOARDING_WELCOME',
                    'name' => 'Welcome Email',
                    'category' => 'Onboarding',
                    'event_type' => 'employee.created',
                    'channel' => 'email',
                    'subject' => 'Welcome to {company_name} - Workforce Profile Activated',
                    'body_template' => 'Dear {employee_name}, welcome aboard {company_name}. Your profile ({employee_code}) has been created in HRMS. Access your ESS dashboard at {action_url}.',
                    'action_url' => '/dashboard',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'EKYC_RESUBMISSION',
                    'name' => 'eKYC Resubmission Notice',
                    'category' => 'Onboarding',
                    'event_type' => 'ekyc.resubmission_required',
                    'channel' => 'in_app',
                    'subject' => 'Action Required: eKYC Resubmission Needed',
                    'body_template' => 'Your recent eKYC attempt was rejected. Reason: {details}. Please re-upload documents before deadline {deadline}.',
                    'action_url' => '/ekyc',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'EKYC_RESUBMISSION',
                    'name' => 'eKYC Resubmission SMS',
                    'category' => 'Onboarding',
                    'event_type' => 'ekyc.resubmission_required',
                    'channel' => 'sms',
                    'subject' => null,
                    'body_template' => '{company_name} Alert: eKYC verification needs correction. Please resubmit documents at {action_url} before {deadline}.',
                    'action_url' => '/ekyc',
                    'is_mandatory' => true,
                ],

                // Attendance & Regularization
                [
                    'code' => 'REGULARIZATION_SUBMITTED',
                    'name' => 'Regularization Request Submitted',
                    'category' => 'Attendance',
                    'event_type' => 'attendance.regularization_submitted',
                    'channel' => 'in_app',
                    'subject' => 'Attendance Regularization Submitted',
                    'body_template' => 'Regularization for {attendance_date} ({regularization_type}) submitted for manager approval.',
                    'action_url' => '/attendance/regularizations',
                    'is_mandatory' => false,
                ],
                [
                    'code' => 'REGULARIZATION_APPROVED',
                    'name' => 'Regularization Approved',
                    'category' => 'Attendance',
                    'event_type' => 'attendance.regularization_approved',
                    'channel' => 'in_app',
                    'subject' => 'Attendance Regularization Approved',
                    'body_template' => 'Your regularization request for {attendance_date} has been approved by HR. Muster updated to {new_status}.',
                    'action_url' => '/attendance',
                    'is_mandatory' => false,
                ],
                [
                    'code' => 'REGULARIZATION_REJECTED',
                    'name' => 'Regularization Rejected',
                    'category' => 'Attendance',
                    'event_type' => 'attendance.regularization_rejected',
                    'channel' => 'in_app',
                    'subject' => 'Attendance Regularization Rejected',
                    'body_template' => 'Your regularization request for {attendance_date} was rejected. Reason: {remarks}.',
                    'action_url' => '/attendance',
                    'is_mandatory' => false,
                ],

                // Shift & Shutdown
                [
                    'code' => 'SHIFT_ASSIGNMENT',
                    'name' => 'Shift Schedule Notification',
                    'category' => 'Shift',
                    'event_type' => 'shift.assigned',
                    'channel' => 'in_app',
                    'subject' => 'New Shift Schedule Assigned',
                    'body_template' => 'You have been assigned to shift {shift_name} ({shift_timing}) from {start_date} to {end_date}.',
                    'action_url' => '/shifts',
                    'is_mandatory' => false,
                ],
                [
                    'code' => 'SHUTDOWN_APPROVED',
                    'name' => 'Plant Shutdown Notice',
                    'category' => 'Shift',
                    'event_type' => 'shutdown.approved',
                    'channel' => 'in_app',
                    'subject' => 'Approved Plant Shutdown Notice',
                    'body_template' => 'Plant shutdown {shutdown_code} ({reason}) is scheduled from {start_date} to {end_date}. Status: {status}.',
                    'action_url' => '/shutdowns',
                    'is_mandatory' => true,
                ],

                // Leaves
                [
                    'code' => 'LEAVE_SUBMITTED',
                    'name' => 'Leave Application Submitted',
                    'category' => 'Leave',
                    'event_type' => 'leave.submitted',
                    'channel' => 'in_app',
                    'subject' => 'Leave Application {application_number} Submitted',
                    'body_template' => 'Your {leave_type} request for {calculated_days} days ({start_date} to {end_date}) has been submitted.',
                    'action_url' => '/leaves/applications',
                    'is_mandatory' => false,
                ],
                [
                    'code' => 'LEAVE_APPROVED',
                    'name' => 'Leave Application Approved',
                    'category' => 'Leave',
                    'event_type' => 'leave.approved',
                    'channel' => 'in_app',
                    'subject' => 'Leave Application Approved',
                    'body_template' => 'Your {leave_type} application ({start_date} to {end_date}) has been approved by HR.',
                    'action_url' => '/leaves/applications',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'LEAVE_APPROVED',
                    'name' => 'Leave Approval WhatsApp',
                    'category' => 'Leave',
                    'event_type' => 'leave.approved',
                    'channel' => 'whatsapp',
                    'subject' => null,
                    'body_template' => 'Hi {employee_name}, your {leave_type} leave from {start_date} to {end_date} has been APPROVED by {company_name}.',
                    'action_url' => '/leaves/applications',
                    'is_mandatory' => false,
                ],
                [
                    'code' => 'LEAVE_REJECTED',
                    'name' => 'Leave Application Rejected',
                    'category' => 'Leave',
                    'event_type' => 'leave.rejected',
                    'channel' => 'in_app',
                    'subject' => 'Leave Application Rejected',
                    'body_template' => 'Your {leave_type} application was rejected. Remarks: {remarks}.',
                    'action_url' => '/leaves/applications',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'LEAVE_LOCKED',
                    'name' => 'Leave Period Closure Notice',
                    'category' => 'Leave',
                    'event_type' => 'leave.period_locked',
                    'channel' => 'in_app',
                    'subject' => 'Leave Period Locked: {period_month}',
                    'body_template' => 'The leave period for {period_month} has been locked for payroll processing. Pending leaves cannot be submitted for this period.',
                    'action_url' => '/leaves/applications',
                    'is_mandatory' => true,
                ],

                // Payroll
                [
                    'code' => 'PAYSLIP_AVAILABLE',
                    'name' => 'Monthly Payslip Generated',
                    'category' => 'Payroll',
                    'event_type' => 'payroll.payslip_available',
                    'channel' => 'in_app',
                    'subject' => 'Payslip Available for {period_month}',
                    'body_template' => 'Your payslip for {period_month} has been generated. Net Pay: ₹{net_pay}. View or download statement now.',
                    'action_url' => '/payroll/payslips',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'PAYSLIP_AVAILABLE',
                    'name' => 'Payslip Notification Email',
                    'category' => 'Payroll',
                    'event_type' => 'payroll.payslip_available',
                    'channel' => 'email',
                    'subject' => 'Payslip for {period_month} - {company_name}',
                    'body_template' => 'Dear {employee_name}, your salary statement for {period_month} has been disbursed. Net Salary: ₹{net_pay}. Access your payslip at {action_url}.',
                    'action_url' => '/payroll/payslips',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'PAYSLIP_AVAILABLE',
                    'name' => 'Payslip SMS Alert',
                    'category' => 'Payroll',
                    'event_type' => 'payroll.payslip_available',
                    'channel' => 'sms',
                    'subject' => null,
                    'body_template' => '{company_name} Salary Alert: Your net salary of Rs {net_pay} for {period_month} is processed. Check HRMS portal.',
                    'action_url' => '/payroll/payslips',
                    'is_mandatory' => false,
                ],

                // Loans
                [
                    'code' => 'LOAN_APPROVED',
                    'name' => 'Loan Application Approved',
                    'category' => 'Loans',
                    'event_type' => 'loan.approved',
                    'channel' => 'in_app',
                    'subject' => 'Loan Application {loan_number} Approved',
                    'body_template' => 'Your loan of ₹{principal_amount} has been approved. Monthly EMI of ₹{monthly_installment} will start from {start_period}.',
                    'action_url' => '/payroll/loans',
                    'is_mandatory' => true,
                ],

                // F&F Settlement
                [
                    'code' => 'FNF_SETTLED',
                    'name' => 'Full & Final Settlement Finalized',
                    'category' => 'Settlement',
                    'event_type' => 'fnf.settled',
                    'channel' => 'in_app',
                    'subject' => 'F&F Settlement Finalized',
                    'body_template' => 'Your Full & Final Settlement has been processed. Net Settlement: ₹{net_payable}. Statement is ready for review.',
                    'action_url' => '/payroll/fnf',
                    'is_mandatory' => true,
                ],
                [
                    'code' => 'FNF_SETTLED',
                    'name' => 'F&F Settlement Email',
                    'category' => 'Settlement',
                    'event_type' => 'fnf.settled',
                    'channel' => 'email',
                    'subject' => 'Full and Final Settlement Statement - {company_name}',
                    'body_template' => 'Dear {employee_name}, your Full & Final Settlement with {company_name} has been completed. Net Settlement Amount: ₹{net_payable}.',
                    'action_url' => '/payroll/fnf',
                    'is_mandatory' => true,
                ],
            ];

            foreach ($templates as $tpl) {
                NotificationTemplate::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $tpl['code'],
                        'channel' => $tpl['channel'],
                    ],
                    $tpl
                );
            }
        }
    }
}

