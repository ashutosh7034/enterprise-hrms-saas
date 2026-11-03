# Enterprise HRMS — Phase 4 Implementation Report
## Leave Engine, Sandwich Policies, Holiday Calendar, Comp-Off & Leave Approvals

**Date**: 2026-11-02  
**Status**: COMPLETE & VERIFIED  
**Test Pass Rate**: 100% (98 tests, 457 assertions passing)  
**Entities Covered**: Vishal Enterprise (`VE`), Vrishal Eng. Pvt. Ltd. (`VRE`)

---

## 1. Executive Summary
Phase 4 implements a complete enterprise Leave Management Engine extending the existing Laravel 13, PHP 8.3, and MySQL HRMS architecture. Key business capabilities implemented include configurable leave types, immutable balance ledgers, idempotent monthly accruals, confirmation-based allocations, year-end carry-forward with dry-run support, site-specific holiday calendars, side-effect free sandwich leave calculation, comp-off accrual linked to verified Phase 3 attendance, multi-stage approval workflows (ESS $\rightarrow$ MSS $\rightarrow$ HR), notice period restrictions with HR override, and monthly leave period locking with HO Admin override unlocking.

---

## 2. Core Implemented Modules

### 2.1 Module A: Leave Type Master
- Master CRUD for company-specific leave types (`CL`, `SL`, `PL`, `LOP`, `COMP_OFF`, `ML`, `PL_PAT`).
- Quotas, monthly accrual rates, accrual frequencies (`Monthly`, `Quarterly`, `Yearly`), and accrual timing (`Start of Month` vs `End of Month`).
- Advance notice rules, backdated application thresholds, consecutive days limits, and overdraft permissions.
- Scoping per employee category, location, and department.

### 2.2 Module B: Leave Balance & Immutable Ledger
- Dedicated `leave_balances` model tracking opening, accrued, consumed, reserved, carry forward, expired, and adjustments.
- Dedicated `leave_balance_transactions` table recording all balance changes with before/after snapshots, transaction types, and unique UUIDs.
- `LeaveBalanceService` ensures atomic reservations during application submission, releasing on rejection/withdrawal, and final deductions upon approval.

### 2.3 Module C: Monthly Accrual & Year-End Carry-Forward
- `LeaveAccrualService`: Idempotent batch-tracked accruals per company, year, and month. Pro-rated for mid-month joiners.
- Confirmation-based allocation: Allocates privilege leaves automatically upon confirmed status.
- `LeaveCarryForwardService`: Automated calculation of carried forward quota up to configurable limits, expiring uncarried balances.
- Artisan CLI Command: `php artisan leave:carry-forward --company=VE --year=2025 --dry-run`.

### 2.4 Module D: Holiday Master & Site-Specific Calendars
- Company-wide holidays and location/site-specific holidays (e.g. Pune vs Sanand).
- Department, category, and ground-staff site applicability.
- Full calendar visualization and management.

### 2.5 Module E: Sandwich Leave Policy Engine
- `SandwichLeaveCalculationService`: Side-effect free engine evaluating whether intervening weekly offs or holidays between leaves are counted toward leave deduction.
- Priority-based policy resolution (Company Default, Site Override, Department Override, Leave Type Override).
- Returns day-by-day deduction breakdown with reasons for complete employee and HR transparency.

### 2.6 Module F: Comp-Off Accrual & Availing
- Linked directly to Phase 3 `attendance_records` for qualifying work on weekly offs, holidays, or shutdown maintenance.
- Enforces minimum 4-hour (240 min) duration for 0.5 day credit and 8-hour (480 min) for 1.0 day credit.
- Automatic 60-day expiry enforcement. Duplicate claims for the same attendance record are strictly blocked.

### 2.7 Module G: Leave Application & Multi-Level Approval
- Employee ESS: Submit leave application with real-time AJAX sandwich deduction preview, reason, and half-day options.
- Manager MSS: Review direct reports' requests with approval, rejection, or send-back decisions. Self-approval is strictly prohibited.
- HR Final Sanction: Final sanction atomically consumes reserved balance and writes ledger record.
- Cancellation: Approved leaves can be cancelled through HR with automated balance restoration.

### 2.8 Module H: Notice Period Restrictions & HR Override
- Automatically detects employees in notice period (`status = 'Resigned'` or active notice dates).
- Restricts disallowed leave types unless authorized HR Executive or HO Admin applies an override with mandatory justification.

### 2.9 Module I: Leave Period Closure & Head Office Unlock
- Monthly HR closure locking (`leave_period_locks`).
- Prevents new applications, balance edits, or retroactive approvals in locked periods.
- Head Office (`ho_admin`) sole authority to unlock with mandatory justification, permanently recorded in `leave_period_unlock_histories`.

---

## 3. Implemented Architecture Summary

| Component | Files Created / Updated |
|---|---|
| **Migrations** | `2026_11_02_000032_...` through `...000039_...` (8 additive migrations) |
| **Models** | `LeaveType`, `LeaveBalance`, `LeaveBalanceTransaction`, `LeaveAccrualBatch`, `LeaveCarryForwardBatch`, `Holiday`, `SandwichLeavePolicy`, `CompOffRecord`, `LeaveApplication`, `LeaveApprovalStep`, `LeavePeriodLock`, `LeavePeriodUnlockHistory` |
| **Services** | `LeaveBalanceService`, `LeaveAccrualService`, `LeaveCarryForwardService`, `HolidayCalendarService`, `SandwichLeaveCalculationService`, `CompOffService`, `LeavePeriodLockService`, `LeaveApplicationService` |
| **Controllers** | `LeaveTypeController`, `HolidayController`, `SandwichPolicyController`, `LeaveBalanceController`, `LeaveApplicationController`, `LeaveApprovalController`, `CompOffController`, `LeavePeriodLockController`, `LeaveAccrualController`, `DashboardController` |
| **Views** | 16 dedicated Blade templates across `resources/views/leaves/` and updated sidebar navigation |
| **Tests** | 9 new test suites covering leave types, balance ledger, accrual, sandwich, comp-off, workflow, notice period, locks, and multi-company isolation |

