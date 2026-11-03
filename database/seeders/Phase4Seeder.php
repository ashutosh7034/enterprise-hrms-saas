<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\SandwichLeavePolicy;
use App\Services\LeaveBalanceService;
use Illuminate\Database\Seeder;

class Phase4Seeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $balanceService = app(LeaveBalanceService::class);
        $currentYear = (int) date('Y');

        foreach ($companies as $company) {
            // 1. Seed Core Leave Types
            $leaveTypesData = [
                [
                    'code' => 'CL',
                    'name' => 'Casual Leave',
                    'description' => 'Casual leave for personal or urgent affairs.',
                    'is_paid' => true,
                    'annual_entitlement' => 12.00,
                    'monthly_accrual_rate' => 1.00,
                    'accrual_frequency' => 'Monthly',
                    'accrual_timing' => 'Start of Month',
                    'is_accrual_enabled' => true,
                    'requires_confirmation' => false,
                    'max_consecutive_days' => 3,
                    'min_notice_days' => 1,
                    'max_backdated_days' => 2,
                    'is_carry_forward_enabled' => false,
                    'carry_forward_limit' => 0.00,
                    'is_sandwich_enabled' => true,
                    'is_compoff' => false,
                    'is_applicable_in_notice_period' => false,
                    'status' => 'Active',
                    'effective_from' => '2026-01-01',
                ],
                [
                    'code' => 'SL',
                    'name' => 'Sick Leave',
                    'description' => 'Medical and health recovery absence.',
                    'is_paid' => true,
                    'annual_entitlement' => 12.00,
                    'monthly_accrual_rate' => 1.00,
                    'accrual_frequency' => 'Monthly',
                    'accrual_timing' => 'Start of Month',
                    'is_accrual_enabled' => true,
                    'requires_confirmation' => false,
                    'max_consecutive_days' => 7,
                    'min_notice_days' => 0,
                    'max_backdated_days' => 3,
                    'is_carry_forward_enabled' => false,
                    'carry_forward_limit' => 0.00,
                    'is_sandwich_enabled' => true,
                    'is_compoff' => false,
                    'is_applicable_in_notice_period' => true,
                    'max_notice_period_days' => 3.00,
                    'status' => 'Active',
                    'effective_from' => '2026-01-01',
                ],
                [
                    'code' => 'PL',
                    'name' => 'Privilege Leave',
                    'description' => 'Privilege / Earned leave subject to confirmation.',
                    'is_paid' => true,
                    'annual_entitlement' => 18.00,
                    'monthly_accrual_rate' => 1.50,
                    'accrual_frequency' => 'Monthly',
                    'accrual_timing' => 'End of Month',
                    'is_accrual_enabled' => true,
                    'requires_confirmation' => true,
                    'max_consecutive_days' => 14,
                    'min_notice_days' => 7,
                    'max_backdated_days' => 0,
                    'is_carry_forward_enabled' => true,
                    'carry_forward_limit' => 30.00,
                    'is_sandwich_enabled' => true,
                    'is_compoff' => false,
                    'is_applicable_in_notice_period' => false,
                    'status' => 'Active',
                    'effective_from' => '2026-01-01',
                ],
                [
                    'code' => 'LOP',
                    'name' => 'Loss of Pay',
                    'description' => 'Unpaid absence without pay credit.',
                    'is_paid' => false,
                    'annual_entitlement' => 0.00,
                    'monthly_accrual_rate' => 0.00,
                    'accrual_frequency' => 'None',
                    'accrual_timing' => 'Start of Month',
                    'is_accrual_enabled' => false,
                    'requires_confirmation' => false,
                    'allow_negative_balance' => true,
                    'max_negative_balance' => 90.00,
                    'is_sandwich_enabled' => true,
                    'is_compoff' => false,
                    'is_applicable_in_notice_period' => true,
                    'status' => 'Active',
                    'effective_from' => '2026-01-01',
                ],
                [
                    'code' => 'COMP_OFF',
                    'name' => 'Compensatory Off',
                    'description' => 'Earned compensation for off-day or holiday duty.',
                    'is_paid' => true,
                    'annual_entitlement' => 0.00,
                    'monthly_accrual_rate' => 0.00,
                    'accrual_frequency' => 'None',
                    'accrual_timing' => 'Start of Month',
                    'is_accrual_enabled' => false,
                    'requires_confirmation' => false,
                    'is_carry_forward_enabled' => false,
                    'is_sandwich_enabled' => false,
                    'is_compoff' => true,
                    'is_applicable_in_notice_period' => false,
                    'status' => 'Active',
                    'effective_from' => '2026-01-01',
                ],
            ];

            $createdTypes = [];
            foreach ($leaveTypesData as $data) {
                $createdTypes[$data['code']] = LeaveType::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $data['code']],
                    $data
                );
            }

            // 2. Seed Default Sandwich Policy
            SandwichLeavePolicy::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'policy_name' => 'Default Company Sandwich Policy',
                ],
                [
                    'is_enabled' => true,
                    'include_intervening_weekly_offs' => true,
                    'include_intervening_holidays' => true,
                    'priority' => 1,
                    'effective_from' => '2026-01-01',
                ]
            );

            // 3. Seed 2026 Holiday Calendar
            $holidays = [
                ['name' => 'Republic Day', 'code' => 'REP-DAY', 'date' => '2026-01-26', 'type' => 'National Holiday'],
                ['name' => 'Holi', 'code' => 'HOLI', 'date' => '2026-03-04', 'type' => 'Festival Holiday'],
                ['name' => 'Good Friday', 'code' => 'GOOD-FRI', 'date' => '2026-04-03', 'type' => 'Festival Holiday'],
                ['name' => 'Maharashtra Day', 'code' => 'MAH-DAY', 'date' => '2026-05-01', 'type' => 'Regional Holiday'],
                ['name' => 'Independence Day', 'code' => 'IND-DAY', 'date' => '2026-08-15', 'type' => 'National Holiday'],
                ['name' => 'Gandhi Jayanti', 'code' => 'GANDHI-JAY', 'date' => '2026-10-02', 'type' => 'National Holiday'],
                ['name' => 'Dussehra', 'code' => 'DUSSEHRA', 'date' => '2026-10-20', 'type' => 'Festival Holiday'],
                ['name' => 'Diwali', 'code' => 'DIWALI', 'date' => '2026-11-08', 'type' => 'Festival Holiday'],
                ['name' => 'Christmas Day', 'code' => 'XMAS', 'date' => '2026-12-25', 'type' => 'Festival Holiday'],
            ];

            foreach ($holidays as $h) {
                Holiday::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $h['name'],
                        'date' => $h['date'],
                    ],
                    [
                        'code' => $h['code'],
                        'holiday_type' => $h['type'],
                        'year' => 2026,
                        'is_ground_staff_applicable' => true,
                        'is_paid' => true,
                        'status' => 'Active',
                    ]
                );
            }

            // 4. Initialize Balances for Active Employees
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'Active')
                ->get();

            foreach ($employees as $employee) {
                // Initialize CL balance
                if (isset($createdTypes['CL'])) {
                    $balanceService->setOpeningBalance(
                        employee: $employee,
                        leaveType: $createdTypes['CL'],
                        days: 10.00,
                        year: $currentYear,
                        actor: null,
                        remarks: 'Initial opening balance for year ' . $currentYear
                    );
                }

                // Initialize SL balance
                if (isset($createdTypes['SL'])) {
                    $balanceService->setOpeningBalance(
                        employee: $employee,
                        leaveType: $createdTypes['SL'],
                        days: 8.00,
                        year: $currentYear,
                        actor: null,
                        remarks: 'Initial opening balance for year ' . $currentYear
                    );
                }

                // Initialize PL balance
                if (isset($createdTypes['PL'])) {
                    $balanceService->setOpeningBalance(
                        employee: $employee,
                        leaveType: $createdTypes['PL'],
                        days: 15.00,
                        year: $currentYear,
                        actor: null,
                        remarks: 'Initial opening balance for year ' . $currentYear
                    );
                }
            }
        }
    }
}

