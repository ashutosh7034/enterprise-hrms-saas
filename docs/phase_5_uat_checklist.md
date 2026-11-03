# Enterprise HRMS — Phase 5 User Acceptance Testing (UAT) Checklist

## 1. Salary Structure & Component Master
- [x] Create standard salary component (Basic, HRA, DA, Special Allowance).
- [x] Configure component taxability, statutory flags, and sort order.
- [x] Create salary structure with mixed fixed, percentage, and formula-driven components.
- [x] Support Target OT fixed allowance structure flag (`is_target_ot = true`).
- [x] Assign salary structure to employee with effective start date and CTC.
- [x] Track salary revisions with automatic percentage increment computation.

## 2. Payroll Configuration & Statutory Compliance
- [x] Configure pay cycle (Monthly), cutoff day (25th), calculation basis (Calendar Days).
- [x] Configure Overtime hourly divisor (208) and statutory rate multiplier (2.0x).
- [x] Configure net pay rounding rules (Nearest Rupee, Floor, Ceiling).
- [x] PF Engine: Compute employee (12%) and employer (12%) contributions with ₹15,000 ceiling capping toggle.
- [x] ESI Engine: Enforce ₹21,000 gross wage ceiling with 0.75% employee and 3.25% employer contribution.
- [x] PT Engine: Resolve state-specific slabs (Maharashtra male/female threshold, February ₹300 special).
- [x] LWF Engine: Deduct Labour Welfare Fund based on configured frequency (Bi-Annual June/Dec).
- [x] TDS Engine: Project annual tax under New Tax Regime (FY 2024–25/2025–26) vs Old Tax Regime declarations.

## 3. Pre-Validation Gate & Control Tower
- [x] Scan employee roster for missing or unapproved salary structures.
- [x] Detect unresolved attendance regularizations, unapproved overtime, or pending leave requests.
- [x] Present interactive pre-validation control tower with eligible vs ineligible lists.
- [x] Execute batch payroll calculation across all eligible employees.
- [x] Prevent duplicate runs for periods with existing approved or locked batches.

## 4. Multi-Tier Payroll Approval Workflow
- [x] HR Executive submits calculated draft run for review.
- [x] HO Admin reviews aggregated gross, statutory deductions, net payout, and employer contributions.
- [x] Reject run with mandatory justification notes (reverting status to Draft).
- [x] Approve run: automatically books employee loan EMI deductions and clears queued asset damage fines.
- [x] Lock payroll run: locks run and marks period as Locked against retro modifications.
- [x] Disburse payroll: record NEFT / bank payout reference and mark employee payment status as Paid.

## 5. Employee Loans & Advances
- [x] Employee submits loan application via ESS portal.
- [x] Multi-tier approval: Manager review followed by HR / HO Admin final approval.
- [x] Loan disbursement generates exact monthly EMI amortization repayment schedule.
- [x] Integrate due monthly EMI installments into active payroll run deductions.
- [x] Foreclose loan early with single settlement, updating loan balance and waiving pending schedules.

## 6. Payslip Generation & ESS Security
- [x] Format standardized payslip number (`PS-{COMPANY}-{YYYYMM}-{EMPCODE}`).
- [x] Render enterprise-grade HTML payslip with company branding and detailed earnings/deductions.
- [x] Generate server-side PDF download via Dompdf.
- [x] Strict authorization: ESS employee can only view and download own payslips; cross-company access blocked.

## 7. Full & Final Settlement (F&F)
- [x] Capture resignation date, last working date, and notice period served days.
- [x] Compute unpaid salary for final active month.
- [x] Compute leave encashment for accrued Privilege Leave balance.
- [x] Compute statutory gratuity for employees with $\ge 5$ years continuous service.
- [x] Deduct notice period shortfall pay, outstanding loan balance, and asset damage fines.
- [x] Support multi-tier approval: HR Verification $\rightarrow$ Finance Approval $\rightarrow$ Settlement Payout.
- [x] Transition settled employee to `Resigned` status and mark liabilities cleared.
- [x] Generate printable F&F settlement statement PDF.

## 8. Payroll Reporting & Reconciliation
- [x] Detailed Payroll Register (pro-rated earnings, statutory itemizations, deductions, net pay).
- [x] Bank Payment Advice CSV export for bulk NEFT/RTGS batch processing.
- [x] PF ECR (Electronic Challan cum Return) CSV format.
- [x] ESI Monthly Contribution Return CSV format.
- [x] Month-over-Month (MoM) Payroll Variance Analysis Report identifying cost fluctuations.

