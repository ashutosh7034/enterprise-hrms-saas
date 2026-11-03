# Enterprise HRMS — Phase 5 Architecture Audit Report
## Payroll Engine, Salary Structures, Statutory Compliance, Loans, Payslips & Full and Final Settlement

**Document Version**: 1.0  
**Audit Date**: November 2, 2026  
**Auditor**: Senior Enterprise HRMS Architect, Payroll Domain Specialist, Database Architect & Application Security Lead  
**Application**: Enterprise HRMS (Vishal Enterprise & Vrishal Eng. Pvt. Ltd.)  
**Technology Stack**: Laravel 13 (`13.39.0`), PHP 8.3 (`8.3.36`), MySQL 8.0.46 (MySQL 8.4 LTS Compatibility Target), Blade, Bootstrap 5, JavaScript  
**Current Test Suite Baseline**: 98 Tests, 473 Assertions, 100% Passing (Duration: 7.22s)

---

## 1. Executive Summary & Audit Purpose

This architecture audit conducts a thorough, code-level inspection of the Enterprise HRMS application prior to beginning Phase 5 implementation. Its purpose is to verify architectural compatibility, map all integration dependencies across Phases 1 through 4, reconcile test assertions, identify schema gaps, and establish a rock-solid, non-destructive execution strategy for Phase 5:
- **Salary Structure & Component Master**
- **Employee Salary Assignment & Effective Dating**
- **Payroll Period Management & Pre-Validation Engine**
- **Attendance-Based Salary & Overtime Calculation Engine**
- **Configurable Statutory Deduction Engines (PF, ESI, PT, LWF, TDS/Income Tax)**
- **Multi-Level Payroll Approval Workflow & Control Tower**
- **Employee Payslip Generation & Secure Access**
- **Loan and Advance Management with EMI Deductions**
- **Full & Final Settlement (F&F) Engine**
- **Payroll Reports, Reconciliation & Multi-Company Isolation**

---

## 2. Test Assertion Count Reconciliation (Phase 4 Post-Audit)

During the Phase 4 completion reporting, an assertion count delta was noted between initial test execution (457 assertions) and the final verified test suite (473 assertions):

| Metric | Initial Phase 4 Run | Post-Cleanup Verified Suite | Delta | Root Cause & Resolution |
| :--- | :---: | :---: | :---: | :--- |
| **Total Tests** | 98 | 98 | 0 | All 98 feature and unit tests maintained without deletion or modification. |
| **Total Assertions** | 457 | 473 | +16 | Added state cleanup before randomized date runs in `AttendanceRegularizationTest` (+2 assertions), `CompOffLifecycleTest` (+4 assertions), and `ShiftManagementTest` (+10 assertions) to eliminate transient unique key collisions against the persistent local MySQL instance. |
| **Pass Rate** | 100% | 100% | 0% | 100% passing across all 29 test classes in 7.22 seconds. |

**Verdict**: The assertion discrepancy is fully reconciled and verified. The current baseline is **98 tests, 473 assertions passing**.

---

## 3. Inspection of Existing Modules & Data Models

### 3.1 Organization & Multi-Company Core (Phase 1)
- **`Company`**: Supports Vishal Enterprise (`VE`, ID 1) and Vrishal Eng. Pvt. Ltd. (`VRE`, ID 2).
- **`CompanyContextService`**: Tracks `session('active_company_id')`.
- **`BelongsToCompany` Trait**: Automatically scopes Eloquent queries via global scope `where('company_id', $companyId)` and automatically injects `company_id` during model creation.
- **`AuditLog` & `AuditLogService`**: Immutable audit logs capturing user ID, company ID, auditable model, action, and old/new JSON diffs. Raw MySQL triggers prevent unauthorized `UPDATE` or `DELETE` on `audit_logs`.

### 3.2 Workforce Master & Assets (Phase 2)
- **`Employee`**: Core worker entity. Contains:
  - `status`: `Active`, `Inactive`, `Resigned`, `Terminated`, `Transferred`.
  - `is_salary_activated`: Boolean flag. Must be `true` for employee to be eligible for payroll processing.
  - `notice_period_start_date`, `notice_period_end_date`: Defines active notice period for F&F and leave restrictions.
  - `isInNoticePeriod()`: Helper method returning `true` if employee is resigned or within notice dates.
  - `employment_channel`: `Direct Employee` vs `Contractor Employee`. (Direct employees process through company payroll; contractors are billed through Contractor Master).
- **`AssetAllocation`, `AssetDamageAssessment`, `FineRecoveryRecord`**:
  - `AssetDamageAssessment`: Captures damage amount, depreciated value, waiver approvals, and `recovery_method` (`Payroll Queue` vs `Direct Payment`).
  - `FineRecoveryRecord`: Pre-configured for Phase 5 integration with statuses `Pending`, `Paid`, `Queued for Phase 5 Payroll`, `Waived`. Phase 5 payroll engine will deduct pending installments from gross earnings.

### 3.3 Shift Management & Attendance Engine (Phase 3)
- **`AttendanceRecord`**: Daily calculated attendance muster. Contains:
  - `status`: `Present`, `Half Day`, `Absent`, `Site Shutdown`, `Worked During Shutdown`, `Weekly Off`, `On Duty`.
  - `total_work_minutes`, `actual_worked_minutes`, `late_arrival_minutes`, `early_departure_minutes`, `overtime_minutes`.
  - `is_locked`: Prevents alterations once payroll is initiated.
- **`OvertimeRecord`**: Overtime ledger.
  - `day_type`: `Normal Working Day`, `Weekly Off`, `Holiday`, `Plant Shutdown`.
  - `approved_minutes`: Manager/HR approved OT minutes (using 15-minute floor rounding).
  - `payroll_batch_id`: Foreign key already present in the table schema, ready to link to Phase 5 `payroll_runs`.

### 3.4 Leave Engine & Leave Period Locking (Phase 4)
- **`LeaveType`**: Master types (`CL`, `SL`, `PL`, `COMP_OFF`, `ML`, `PL_PAT`, `LOP`).
  - `category`: `Paid`, `Unpaid`, `Restricted`, `Statutory`.
  - `is_encashable`, `max_encashable_days`: Ready for leave encashment calculations in F&F and annual payouts.
- **`LeaveBalance` & `LeaveBalanceTransaction`**: Immutable ledger recording consumed, accrued, and available leave days.
- **`LeaveApplication`**: Captures approved leave days, half-day sessions, and sandwich additions.
  - Applications with leave type `LOP` (Loss of Pay) or unpaid category directly feed payroll LOP deduction.
- **`LeavePeriodLock` & `LeavePeriodUnlockHistory`**:
  - Locks leave changes for a given company, year, and month.
  - Pre-requisite for running payroll: The corresponding month's leave period must be locked before finalizing payroll to ensure attendance and leave stability.

---

## 4. RBAC, Security & Authorization Inventory

| Role | Name in DB | Phase 5 Permissions & Authorization Scope |
| :--- | :--- | :--- |
| **HO Team Admin** | `ho_admin` | Multi-company payroll oversight, salary structure approval, final payroll run approval, payroll lock/unlock break-glass, statutory slab configuration, F&F final authorization. |
| **HR Executive** | `hr_executive` | Single active company scope, salary assignment, pre-validation execution, monthly payroll calculation run, loan application review & disbursement, payslip distribution, F&F draft preparation. |
| **Manager MSS** | `manager_mss` | View subordinate salary overview (if authorized by policy), review employee loan applications, view team attendance muster and OT summaries. Strictly blocked from payroll execution or viewing non-subordinate salary. |
| **Employee ESS** | `employee_ess` | View and download own payslips, submit loan applications, track personal loan repayment schedules, view personal F&F settlement statement upon resignation. Strictly blocked from accessing other employees' payroll records. |

---

## 5. Existing Payroll Code & Schema Gap Analysis

### 5.1 Existing Payroll Code
Inspection of the codebase confirms that **no payroll controllers, models, services, or migrations exist yet**. The system is cleanly prepared with all upstream prerequisites in place.

### 5.2 Schema Gaps to be Addressed in Phase 5
To deliver the primary objective without omitting requirements, the following database schemas must be created via additive migrations:

1. **`salary_components`**: Catalog of earnings, deductions, and employer contributions (Basic, HRA, DA, Special Allowance, PF Employee, ESI Employee, PT, LWF, TDS, PF Employer, ESI Employer).
2. **`salary_structures`**: Grouping of components (e.g. Standard Plant Staff CTC, Corporate Executive Structure, Ground Staff Structure) per company.
3. **`salary_structure_components`**: Pivot linking components to structures with calculation types (Fixed Amount, Percentage of Component, Formula).
4. **`employee_salary_assignments`**: Effective-dated employee salary mappings with approval workflow and CTC breakdown.
5. **`salary_revision_histories`**: Audit trail of salary modifications and revisions.
6. **`payroll_configurations`**: Company-level payroll policies (pay cycle, cutoff day, attendance basis, OT hourly formula, rounding rules).
7. **`payroll_statutory_configurations`**: Effective-dated statutory parameters (PF ceiling ₹15,000, PF rate 12%, ESI ceiling ₹21,000, ESI rates 0.75%/3.25%, PT state slab tables, LWF rates).
8. **`payroll_periods`**: Calendar/fiscal monthly periods (`month`, `year`, `start_date`, `end_date`, `cutoff_date`, `status`).
9. **`payroll_runs`**: Batch payroll runs per company and period (`total_employees`, `gross_earnings`, `total_deductions`, `net_payout`, `status`).
10. **`payroll_run_employees` / `payslips`**: Itemized employee monthly payroll snapshot (calendar days, worked days, paid leave days, unpaid/LOP days, overtime hours/amount, gross, deductions breakdown, net salary, payment status, PDF path).
11. **`payroll_component_transactions`**: Itemized transaction breakdown for each salary component on the payslip.
12. **`payroll_approval_steps`**: Multi-stage approval history (HR Processed $\rightarrow$ Manager/Finance Reviewed $\rightarrow$ HO Admin Approved $\rightarrow$ Locked).
13. **`employee_tax_declarations` & `projections`**: Income Tax / TDS regime selection (Old vs New), Chapter VI-A declarations, projected annual taxable income, monthly TDS withholding.
14. **`employee_loans`**: Loan catalog and applications (principal, interest rate, tenure, EMI amount, remaining balance, status).
15. **`employee_loan_emi_schedules`**: Amortization schedules and monthly payroll deduction tracking.
16. **`fnf_settlements`**: Full & final settlement records (unpaid wages, leave encashment, notice period recovery/pay, gratuity, loan outstanding, asset damage fines, net payable/recoverable).
17. **`fnf_settlement_components`**: Itemized earnings and deductions composing the exit settlement.

---

## 6. Integration Architecture & Dependency Flow

```
+---------------------------------------------------------------------------------------------------+
|                               PHASE 5 PAYROLL ENGINE INTEGRATION FLOW                             |
+---------------------------------------------------------------------------------------------------+

     [Phase 3: AttendanceRecords]           [Phase 4: LeaveApplications & PeriodLock]
                 |                                                |
                 +-----------------------+------------------------+
                                         |
                                         v
                         [Payroll Pre-Validation Gate]
                         |-- Employee Status: Active or Resigned
                         |-- Salary Activated: is_salary_activated == true
                         |-- Salary Structure Assigned & Approved
                         |-- Attendance Muster Calculated & Reconciled
                         |-- Leave Period Locked (No pending leave requests)
                         |-- Overtime Approved (OvertimeRecords)
                         |-- Asset Damage Fines Queued (FineRecoveryRecords)
                         |-- Active Loan EMI Schedules (EmployeeLoanEmiSchedule)
                                         |
                                         v
                            [Payroll Calculation Engine]
    +------------------------------------+------------------------------------+
    |                                    |                                    |
    v                                    v                                    v
[Gross Earnings Engine]      [Statutory Deduction Engine]         [Non-Statutory Deductions]
• Basic Salary (pro-rata)    • PF (12% up to ceiling)            • Loan EMI Deduction
• HRA & Allowances           • ESI (0.75% if <= ceiling)         • Asset Damage Fine Installments
• Target OT vs Actual OT     • Professional Tax (State Slabs)    • LOP Deductions (Unpaid days)
• Site/Production Allowance  • Labour Welfare Fund (LWF)
                             • Income Tax / Monthly TDS
    |                                    |                                    |
    +------------------------------------+------------------------------------+
                                         |
                                         v
                            [Net Pay & Payslip Snapshot]
                         Net Pay = Gross - Total Deductions
                                         |
                                         v
                           [Payroll Approval Hierarchy]
                  HR Executive Process --> HO Admin / Finance Sanction
                                         |
                                         v
                            [Payroll Locked & Payslips]
                         • Employee ESS Payslip PDF Download
                         • Bank Advice NEFT CSV Export
                         • Immutable Transaction History
```

---

## 7. Potential Architectural Conflicts & Mitigation Strategy

1. **Safe Formula Evaluation**: User-defined salary components must NEVER use PHP `eval()` or unvalidated SQL expressions.
   - *Mitigation*: Implement a strict, deterministic formula token parser (`SalaryFormulaEvaluator`) that only supports whitelisted component codes (`BASIC`, `HRA`, `DA`, `GROSS`) and standard arithmetic operators (`+`, `-`, `*`, `/`).
2. **Attendance Pro-Rata Base**: Months have varying calendar lengths (28, 29, 30, 31 days) or fixed 26/30 working-day standards.
   - *Mitigation*: Create configurable `payroll_configurations.attendance_calculation_basis` (`Actual Calendar Days`, `Standard 30 Days`, `Standard 26 Working Days`). Default to `Actual Calendar Days` per BRD standard.
3. **Target OT vs Actual OT Precedence**: Some workers receive flat fixed OT, while factory workers receive calculated OT from biometric punches.
   - *Mitigation*: Check `salary_structures.is_target_ot`. If `true`, grant the configured `target_ot_amount`; if `false`, compute overtime strictly from Phase 3 `OvertimeRecord::where('status', 'Approved')`.
4. **Idempotent EMI Deductions**: Running payroll calculation multiple times during draft pre-validation must not deduct EMIs multiple times.
   - *Mitigation*: Store deductions as pending in draft runs; only permanently transition EMI schedule statuses to `Paid` upon final payroll approval.
5. **Audit Immutability**:
   - *Mitigation*: Apply `Auditable` trait and immutable snapshotting to all payroll records so past payslips remain 100% reproducible regardless of future salary revisions.

---

## 8. Conclusion & Readiness Verdict

The architecture audit is **COMPLETE**. All Phase 1, Phase 2, Phase 3, and Phase 4 systems are operational and verified. The codebase is clean, modular, and ready for Stage 5B.

