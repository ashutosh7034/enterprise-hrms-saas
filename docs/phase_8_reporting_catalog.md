# PHASE 8: ENTERPRISE REPORTING CATALOG & REGULATORY SPECIFICATION

**Enterprise Human Resource Management System (HRMS)**  
**Target Entities:** Vishal Enterprise (`VE`) & Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Document Reference:** `DOCS-PH8-CAT-001`  
**Date:** November 2026  
**Total Reports:** 53 Active Reports across 7 Functional Domains  

---

## 1. Domain Taxonomy & Overview

The Enterprise HRMS reporting engine provides 53 distinct report definitions designed to satisfy operational management, internal financial audits, statutory factory inspections, and tax withholding compliance in the state of Maharashtra, India.

| Domain Category | Report Count | Primary User Personas | Storage & Delivery Format |
|---|---|---|---|
| 1. Workforce & Organization | 12 | HO Admin, HR Executive, Manager MSS (Scoped) | CSV, Excel, PDF |
| 2. Workforce Lifecycle & Compliance | 7 | HR Executive, Compliance Officer | CSV, Excel, PDF |
| 3. Leave & Time Off | 7 | HR Executive, Manager MSS, Employee ESS | CSV, Excel, PDF |
| 4. Attendance & Operations | 10 | Plant GM, HR Executive, Factory Inspector | CSV, Excel, PDF |
| 5. Payroll & Compensation | 10 | HO Admin, Finance Lead, Payroll Officer | CSV, Excel, PDF (Gated) |
| 6. Statutory Compliance | 6 | Statutory Auditor, Tax Consultant, HR Head | CSV, Excel, PDF (ECR format) |
| 7. Disbursement & Banking | 1 | Finance Officer, HO Admin | CSV (NEFT), PDF |

---

## 2. Domain 1: Workforce & Organization Reports (12 Reports)

### 2.1 `MASTER_EMPLOYEE` (Master Employee Directory)
- **Description:** Complete employee master roster with personal, contact, organizational, and categorical details.
- **Headers:** `Code`, `Full Name`, `Department`, `Designation`, `Location`, `Category`, `Channel`, `Manager`, `Joining Date`, `Status`, `eKYC Status`, `Email`, `Phone`.
- **RBAC:** HO Admin, HR Executive (All); Manager MSS (Scoped to reporting line).
- **Filters:** Company, Department, Designation, Location, Category, Employment Status.

### 2.2 `ACTIVE_EMPLOYEES` (Active Employees Register)
- **Description:** Real-time register of all confirmed and probationary personnel currently on active service.
- **Headers:** Identical to Master Employee, pre-filtered for `status = 'Active'`.

### 2.3 `INACTIVE_EMPLOYEES` (Separated & Inactive Staff)
- **Description:** Historic roster of resigned, retired, terminated, or absconded workforce.
- **Headers:** `Code`, `Full Name`, `Department`, `Designation`, `Location`, `Joined On`, `Status`, `Last Working Day`.

### 2.4 `NEW_JOINERS` (New Joiners Register)
- **Description:** New recruits onboarded within a defined date range for induction tracking.
- **Headers:** `Code`, `Full Name`, `Department`, `Designation`, `Location`, `Joining Date`, `Probation End`, `Status`.

### 2.5 `PROBATION_DUE` (Probation Evaluation Due)
- **Description:** Employees approaching probation completion within 30 days or overdue for confirmation.
- **Headers:** `Code`, `Full Name`, `Department`, `Designation`, `Manager`, `Joined On`, `Probation End`, `Days Remaining`, `Evaluation Status`.

### 2.6 `CONFIRMATION_DUE` (Confirmation Status Register)
- **Description:** Tracking ledger for confirmation letter issuance and service regularization.
- **Headers:** Identical to Probation Due with confirmation evaluation notes.

### 2.7 `HEADCOUNT_DEPARTMENT` (Department Headcount Summary)
- **Description:** Summary headcount and percentage share by department for capacity planning.
- **Headers:** `Department Code`, `Department Name`, `Active Headcount`, `Percentage of Total`.

### 2.8 `HEADCOUNT_DESIGNATION` (Designation Distribution)
- **Description:** Role-wise distribution across engineering, administrative, and shop-floor positions.
- **Headers:** `Designation Code`, `Designation Title`, `Active Count`, `Share`.

### 2.9 `HEADCOUNT_LOCATION` (Location / Branch Headcount)
- **Description:** Physical site headcount split between head offices, regional branches, and manufacturing plants.
- **Headers:** `Location Code`, `Location Name`, `State`, `Active Count`, `Share`.

### 2.10 `HEADCOUNT_CATEGORY` (Staff vs Worker Categories)
- **Description:** Breakdown of permanent staff, shop-floor workers, apprentices, and casual labor.
- **Headers:** `Category Code`, `Category Name`, `Active Headcount`, `Proportion`.

### 2.11 `CONTRACTOR_VS_DIRECT` (Employment Channel Mix)
- **Description:** High-level strategic split between direct company employees and third-party contractor personnel.
- **Headers:** `Employment Channel`, `Headcount`, `Percentage`.

### 2.12 `ATTRITION_REPORT` (Workforce Attrition Register)
- **Description:** Resignations and terminations over a rolling 12-month period with average tenure calculation.
- **Headers:** `Code`, `Full Name`, `Department`, `Designation`, `Separation Type`, `Joined Date`, `Exit Date`, `Tenure (Months)`.

---

## 3. Domain 2: Workforce Lifecycle & Compliance Reports (7 Reports)

1. **`ONBOARDING_PIPELINE`:** Tracks candidates in Draft, Offer Issued, and Pre-Joining stages with expected joining dates.
2. **`EKYC_STATUS`:** Aadhaar hash, masked Aadhaar (`XXXX-XXXX-1234`), PAN status, and 30-day grace period deadlines.
3. **`DOCUMENT_COMPLIANCE`:** Mandatory document upload audit (Proof of ID, Address, Prior Experience, Educational Certificates).
4. **`ASSET_ALLOCATION`:** Serialized asset tracking (Laptops, mobile devices, tools, safety gear) currently assigned to staff.
5. **`ASSET_CLEARANCE`:** Return condition verification and clearance sign-off during employee offboarding.
6. **`FINE_DAMAGE_RECOVERY`:** Disciplinary fines, machinery damage recovery installments, and payroll recovery channels.
7. **`SEPARATION_PIPELINE`:** Active notice period tracking, scheduled last working days, and supervisor relieving approval status.

---

## 4. Domain 3: Leave & Time Off Reports (7 Reports)

1. **`LEAVE_BALANCE_LEDGER`:** Opening balance, annual accrual, consumed, encashed, and closing balance per leave type (CL, SL, PL).
2. **`LEAVE_UTILIZATION`:** Itemized listing of approved leave instances with date ranges, total days, and employee justifications.
3. **`LEAVE_APPLICATION_STATUS`:** Operational pipeline of pending, approved, and rejected leave requests across approval tiers.
4. **`LEAVE_ENCASHMENT`:** Encashment applications, eligible leave days, per-day wage rates, and final payout computations.
5. **`COMP_OFF_REGISTER`:** Compensatory off days earned from holiday/weekend duty, redemption logs, and 60-day expiry tracking.
6. **`SANDWICH_LEAVE_IMPACT`:** Leaves where weekend/holiday sandwich deduction policy was automatically triggered.
7. **`LEAVE_PERIOD_LOCK`:** Audit log of locked monthly leave cycles, preventing retro-modifications, and justification for unlock events.

---

## 5. Domain 4: Attendance & Operations Reports (10 Reports)

1. **`DAILY_MUSTER_ROLL`:** Factory Form II compliant statutory muster roll displaying in/out times, total hours, shift, and status.
2. **`MONTHLY_ATTENDANCE_SUMMARY`:** Monthly roll matrix displaying P, A, WO, PH, HD, LOP days, and Total Paid Days.
3. **`MISSING_PUNCH_EXCEPTIONS`:** Single punch occurrences (missing in-punch or missing out-punch) flagged for regularization.
4. **`LATE_COMING_EARLY_GOING`:** Grace period violations, late arrival minutes, and early exit durations.
5. **`OVERTIME_REGISTER`:** Normal overtime hours (1.5x) vs Holiday overtime hours (2.0x) with manager sign-off.
6. **`SHIFT_ROSTER_SCHEDULE`:** Weekly and monthly shift assignments across General, First, Second, and Night shifts.
7. **`REGULARIZATION_AUDIT`:** Attendance regularization requests, employee justifications, and manager/HR approvals.
8. **`PLANT_SHUTDOWN_LOG`:** Planned maintenance and emergency plant shutdowns, affected workers, and compensation treatment.
9. **`BIOMETRIC_DEVICE_STATUS`:** Health status of ZKTeco and IP biometric terminals (online/offline, IP, port, last heartbeat).
10. **`BIOMETRIC_PUNCH_RAW`:** Raw event stream of biometric transactions before automated deduplication and pairing.

---

## 6. Domain 5: Payroll & Compensation Reports (10 Reports)

1. **`MONTHLY_PAYROLL_REGISTER`:** Comprehensive salary breakdown: Basic, HRA, DA, Allowances, Gross, PF, ESI, PT, TDS, Total Deductions, Net Payout.
2. **`EMPLOYEE_SALARY_STATEMENT`:** Individual itemized salary statement for payroll audits and reconciliation.
3. **`DEPARTMENT_WAGE_SUMMARY`:** Department-level payroll costs, employer contributions, and organizational burden.
4. **`GROSS_NET_VARIANCE`:** Month-on-month variance analysis highlighting gross additions, attrition reductions, and net payout shifts.
5. **`PAYROLL_RECONCILIATION`:** Batch run verification, locking timestamps, approval steps, and disbursement totals.
6. **`PAYSLIP_GENERATION_STATUS`:** Tracking of generated, published, and viewed payslips.
7. **`LOAN_ADVANCE_LEDGER`:** Principal sanctioned, monthly EMI recovery, interest rates, and remaining balances.
8. **`FULL_FINAL_SETTLEMENT`:** Gratuity, leave encashment, notice period shortfalls, loan balance recoveries, and net F&F payout.
9. **`ARREARS_PAYMENT_LOG`:** Retroactive salary adjustments and arrears paid in historical cycles.
10. **`SALARY_HOLD_RELEASE`:** Salaries placed on hold due to missing compliance/documentation and subsequent release logs.

---

## 7. Domain 6: Statutory Compliance Reports (6 Reports)

1. **`STATUTORY_PF_ECR`:** Electronic Challan Return (ECR) for EPFO: EPF Wages (capped at ₹15,000), EPS Wages, Employee 12%, Employer 8.33% EPS, Employer 3.67% EPF.
2. **`STATUTORY_ESI_FORM5`:** ESIC Form 5 monthly contribution return: Gross wages, Employee 0.75%, Employer 3.25%, and total remittance.
3. **`STATUTORY_PT_RETURN`:** Maharashtra Professional Tax return based on monthly salary slabs (₹175 for women ₹10,001–₹25,000, ₹200 standard, ₹300 February adjustment).
4. **`STATUTORY_LWF_RETURN`:** Maharashtra Labour Welfare Fund bi-annual return (June and December contributions).
5. **`STATUTORY_TDS_WITHHOLDING`:** Section 192 income tax withholding register tracking monthly deductions under Old and New tax regimes.
6. **`STATUTORY_SUMMARY`:** Consolidated compliance dashboard summarizing monthly corporate liability for PF, ESI, PT, LWF, and TDS.

---

## 8. Domain 7: Disbursement & Banking (1 Report)

### 8.1 `BANK_TRANSFER_ADVICE`
- **Description:** NEFT / RTGS salary disbursement statement for bank portal batch uploads.
- **Columns:** `Employee Code`, `Employee Name`, `Department`, `Bank Name`, `Account Number`, `IFSC Code`, `Net Amount (INR)`, `Payment Reference`, `Disbursement Status`.
- **Sensitive Data Masking:** Non-finance staff view masked accounts (`XXXXXX1234`). Only HO Admin and authorized Finance officers view unmasked bank credentials.

