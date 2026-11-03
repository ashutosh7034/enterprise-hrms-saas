# Enterprise HRMS — Phase 5 Security & Compliance Audit

## 1. Executive Summary

Phase 5 introduces financial data handling, salary calculation formulas, personal tax liabilities, and monetary disbursement operations. A rigorous code-level security audit was conducted to enforce multi-tenant isolation, authorization controls, input sanitization, and immutable audit trails.

---

## 2. Security Controls & Audit Findings

### 2.1 Formula Parsing Security (Zero `eval()` Policy)
- **Risk**: Dynamic calculation formulas in salary structure components (e.g., `BASIC * 0.50 + DA`) present severe Remote Code Execution (RCE) vectors if parsed using PHP's native `eval()`.
- **Implementation**: Implemented `App\Services\SalaryFormulaParser`, an in-memory tokenized arithmetic parser using the Shunting-Yard algorithm.
- **Verification**: Only numeric literals, basic arithmetic operators (`+`, `-`, `*`, `/`), parentheses, and valid component codes are permitted. Any malicious strings or function calls are strictly rejected during token validation.

### 2.2 Multi-Company Tenant Data Isolation
- **Risk**: Cross-tenant data leakage in payroll runs, employee salary structures, loan balances, or Full & Final exit settlements between Vishal Enterprise (`VE`) and Vrishal Eng. Pvt. Ltd. (`VRE`).
- **Implementation**:
  - All Phase 5 models (`SalaryStructure`, `EmployeeSalaryAssignment`, `PayrollRun`, `PayrollRunEmployee`, `EmployeeLoan`, `FnfSettlement`) use the `BelongsToCompany` trait.
  - Active company context is enforced via `CompanyContextService` and verified in controller authorizations.
  - Route-model binding automatically scopes lookups to the active tenant.
- **Verification**: Verified via `PayrollMultiCompanyIsolationTest` (75 assertions) and `PayslipSecurityAndGenerationTest`. Cross-company manipulated IDs return 403 Forbidden or 404 Not Found.

### 2.3 Role-Based Access Control (RBAC) & Principle of Least Privilege
- **Employee ESS**:
  - Allowed: View only own payslips (`payroll.payslips.show`), download own PDF (`payroll.payslips.download`), apply for own loan/advance (`payroll.loans.create`, `payroll.loans.store`).
  - Prohibited: Accessing or downloading other employees' payslips (tested: returns 403 Forbidden); modifying salary structures, running payroll batch execution, or viewing company-wide payroll registers (tested: returns 403 Forbidden).
- **Manager MSS**:
  - Allowed: Tier-1 review of subordinate loan applications (`payroll.loans.approve` at Manager level).
  - Prohibited: Executing payroll runs, locking payroll periods, or finalizing F&F settlements.
- **HR Executive**:
  - Allowed: Managing salary structure catalog, assigning structures, running pre-validation gate, executing batch payroll runs, submitting runs for review, preparing and verifying F&F draft settlements.
  - Prohibited: Final authorization of payroll disbursement or locking periods without HO Admin role.
- **HO Admin**:
  - Allowed: Final approval of payroll runs (`payroll.runs.approve`), locking periods (`payroll.runs.lock`), disbursing payouts (`payroll.runs.disburse`), approving and settling Full & Final exit dues (`payroll.fnf.approve`, `payroll.fnf.settle`).

### 2.4 Idempotency & Financial Concurrency Guards
- **Duplicate Run Prevention**: Payroll processing enforces database-level locks preventing duplicate batch executions for the same period. If an approved, locked, or disbursed run exists, subsequent executions are prohibited with explicit domain exceptions.
- **Deduction Booking Idempotency**: Loan EMI deductions and asset damage fine clearances are only executed once when a payroll run transitions from `Pending Approval` to `Approved`. Rejection or recalculation during draft stage does not mutate permanent employee loan balances.

### 2.5 Sensitive Data Protection & PDF Streaming
- Payslips and F&F statements are compiled on demand into memory streams via Dompdf without leaving unencrypted temporary files on public disk storage.
- File downloads stream directly with `Content-Type: application/pdf` and strict `Content-Disposition: attachment` headers.

