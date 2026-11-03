# Enterprise HRMS — Phase 4 Security & Multi-Company Audit

**Audit Date**: 2026-11-02  
**Auditor**: Senior Application Security Engineer & Lead Architect  
**Audit Target**: Phase 4 Leave Engine, Sandwich Policies, Comp-Off, Balances & Approvals  
**Result**: PASSED — 0 Vulnerabilities Identified

---

## 1. Multi-Company Tenancy Security
- **Cross-Company Model Scoping**: All Phase 4 models (`LeaveType`, `LeaveBalance`, `LeaveBalanceTransaction`, `Holiday`, `SandwichLeavePolicy`, `CompOffRecord`, `LeaveApplication`, `LeavePeriodLock`) utilize the `BelongsToCompany` trait.
- **Cross-Company Access Verification**:
  - Manipulated route IDs for Company 2 (`VRE`) accessed by a user in Company 1 (`VE`) return `404 Not Found`. Verified in `LeaveMultiCompanyIsolationTest::test_cross_company_leave_type_edit_is_blocked`.
  - Cross-company leave application submissions for employees belonging to another company are blocked. Verified in `LeaveMultiCompanyIsolationTest::test_cross_company_application_submission_is_blocked`.
  - Leave balance administration screen only renders employee balances for the active session company.

---

## 2. Authentication & RBAC Authorization Checks
- **Role Verification**:
  - `employee_ess`: Limited strictly to viewing their own balance (`leaves/my-balances`), submitting their own applications (`leaves/applications/create`), withdrawing their pending requests, and viewing company holidays. Cannot access leave types master, balance adjustment modal, period locks, or approval queues.
  - `manager_mss`: Limited to reviewing leave applications of direct reporting subordinates (`leaves/approvals`). Cannot view or approve applications outside their company. Self-approval is strictly prevented (`LeaveApplicationService` rejects if approver is applicant).
  - `hr_executive`: Authorized to manage leave types, holiday calendars, process monthly accruals, record manual balance adjustments, review/approve comp-off claims, and lock leave periods for their active company. Cannot execute cross-company administrative actions.
  - `ho_admin`: Sole authority to unlock closed leave periods with mandatory justification text. Can switch company contexts and monitor cross-company compliance.

---

## 3. Data Integrity & Ledger Immutability
- **Balance Ledger**: All balance modifications (Opening Balance, Monthly Accruals, Approval Deductions, Cancellations, Adjustments, Comp-Off Credits) must write to `leave_balance_transactions`. Balances are never modified directly without an accompanying ledger transaction.
- **Idempotency Guarantees**:
  - Monthly accruals utilize unique transaction UUIDs: `ACCR-{company_id}-{employee_id}-{leave_type_id}-{year}-{month}`. Double processing is automatically detected and skipped.
  - Comp-Off credits enforce unique database constraint on `(company_id, employee_id, attendance_record_id)`.
- **Database Row Locks**: Balance reservations and deductions utilize `lockForUpdate()` within database transactions to eliminate race conditions during concurrent leave submissions.

---

## 4. Notice Period & Period Lock Enforcement
- **Notice Period Bypass Protection**: An employee in notice period cannot submit leave for restricted leave types unless an HR Executive provides an authorized override with non-empty justification.
- **Closed Period Lock Protection**: When a leave period is marked `is_locked = true` in `leave_period_locks`, application submission, balance adjustment, and approval are rejected server-side.
- **Audit Immutability**: All unlock actions record the HO Admin user, timestamp, and justification in `leave_period_unlock_histories`.

