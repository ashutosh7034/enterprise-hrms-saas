# Enterprise HRMS — Phase 4: Unresolved Business Clarifications & Policy Assumptions

## Overview
This document records business policy choices, default configurations, and assumptions made during Phase 4 implementation (Leave Engine, Sandwich Policies, Holiday Calendar, Comp-Off & Approvals). All configurable values are parameterized in the database or config rather than hardcoded.

---

### 1. Leave Accrual Timing & Rounding
- **Policy Question**: When are monthly leaves credited? Should partial-month joiners receive proportional leaves?
- **Implementation Decision**: 
  - Leave types support configurable accrual frequencies (`Monthly`, `Quarterly`, `Yearly`) and accrual timing (`Start of Month` vs `End of Month`).
  - Proportional accrual for mid-month joiners calculates days worked divided by total days in month multiplied by monthly accrual rate, rounded to 0.5 day intervals.
  - Accrual engine runs idempotently via `LeaveAccrualService` and `leave_accrual_batches` with unique keys per `(company_id, year, month, leave_type_id)`.

---

### 2. Confirmation-Based Allocation
- **Policy Question**: Which leave types require confirmation, and what happens during the probation period?
- **Implementation Decision**:
  - `LeaveType` has a boolean `requires_confirmation`.
  - For leave types like Privilege Leave / Earned Leave (`PL`/`EL`), accrual or allocation checks `employee->confirmation_date !== null && employee->confirmation_date <= now()`.
  - Casual Leave (`CL`) and Sick Leave (`SL`) are available during probation by default.

---

### 3. Sandwich Leave Policy Behavior
- **Policy Question**: When an employee takes leave on Friday and Monday with Saturday and Sunday as weekly offs, are intervening off days counted as leave?
- **Implementation Decision**:
  - Built an isolated, pure calculation engine `SandwichLeaveCalculationService`.
  - Sandwich policies are configurable per company and can be overridden per leave type, category, or location.
  - Configurable flags: `include_intervening_weekly_offs` and `include_intervening_holidays`.
  - The calculation returns a complete day-by-day audit breakdown explaining which intervening days were charged, so employees and approvers have 100% transparency.

---

### 4. Comp-Off Qualifying Duration and Expiry
- **Policy Question**: What constitutes qualifying work on a weekly off or holiday, and how long is Comp-Off valid?
- **Implementation Decision**:
  - Minimum qualifying work duration is configurable (default: 240 minutes / 4 hours for 0.5 day, 480 minutes / 8 hours for 1.0 day).
  - Validity is configurable with a default expiry of 60 days from the qualifying date.
  - Comp-Off can only be created from verified Phase 3 `attendance_records` on days categorized as `Weekly Off` or `Holiday`.
  - Unique constraint on `(company_id, employee_id, attendance_record_id)` prevents duplicate claims.

---

### 5. Multi-Tier Approval Workflow
- **Policy Question**: Must every leave request pass through Manager $\rightarrow$ HOD $\rightarrow$ HR Executive?
- **Implementation Decision**:
  - Workflow supports flexible routing:
    - If employee has a direct reporting manager, Manager review is required.
    - If HOD approval is configured for the department, it moves to HOD.
    - HR Executive or HO Admin has final sanctioning authority.
  - Cancellation: Pending applications can be withdrawn by the employee immediately (releasing reserved balance). Approved leaves require HR cancellation approval, which executes a balancing ledger transaction.

---

### 6. Notice Period Leave Applicability
- **Policy Question**: Can employees serving notice period take leaves? Does unapproved leave extend notice period?
- **Implementation Decision**:
  - `LeaveType` has `is_applicable_in_notice_period` and `max_notice_period_days`.
  - If an employee is in notice period (`status = 'Resigned'` or active notice dates), restricted leave types are rejected at application time unless an authorized HR Executive or HO Admin applies an explicit override with mandatory justification.
  - Notice period exit dates are not automatically extended, leaving final commercial/settlement terms to Phase 5 Full & Final (F&F).

---

### 7. Leave Period Locking & HO Unlock
- **Policy Question**: How is leave locked for payroll, and who can override closed periods?
- **Implementation Decision**:
  - HR Executive can lock a specific calendar month (`leave_period_locks`). Once locked, no new leave applications, approvals, or balance adjustments are permitted for that month.
  - Head Office (`ho_admin`) has sole authority to unlock a period, requiring a mandatory justification text that is permanently archived in `leave_period_unlock_histories` and system audit logs.

