# Final Enterprise HRMS Report Catalog
**Authoritative Enterprise Reporting Registry & Taxonomy**  
**Framework:** Laravel 13 (`13.39.0`) | **PHP:** 8.3.16 | **Database:** MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target)  
**Entities:** Vishal Enterprise (`VE`) & Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Document Reference:** `DOCS-CATALOG-FINAL-001`  
**Date:** November 2, 2026  

---

## 1. Domain Summary & Reconciliation

The reporting engine (`App\Services\Reporting\ReportingService`) implements **exactly 53 reports across 7 functional HR and compliance domains**.

| Domain # | Functional Domain | Implemented Reports | Primary Target Audience | Export Formats |
|:---:|:---|:---:|:---|:---:|
| **1** | Workforce & Organization | 12 | HO Admin, HR Executive, Operations | CSV, Excel, PDF |
| **2** | Workforce Lifecycle & Compliance | 7 | HR Executive, Compliance Auditor | CSV, Excel, PDF |
| **3** | Leave & Time Off | 7 | HR Executive, Manager MSS, Employee ESS | CSV, Excel, PDF |
| **4** | Attendance & Operations | 10 | Plant Managers, Factory Inspectors, HR | CSV, Excel, PDF |
| **5** | Payroll & Compensation | 10 | HO Admin, HR Executive, Treasury | CSV, Excel, PDF |
| **6** | Statutory Compliance | 6 | Compliance Officer, EPFO/ESIC Auditors | CSV, Excel, PDF |
| **7** | Disbursement & Banking | 1 | Treasury, Corporate Banking (NEFT/RTGS) | CSV, Excel, PDF |
| **TOTAL** | **Enterprise HRMS Catalog** | **53** | **Multi-Role Governed (Page 94 Compliant)** | **All 3 Formats** |

---

## 2. Complete Authoritative 53-Report Registry

| # | Report ID | Report Name | Domain | Route | Authorized Roles | Dynamic Filters | Formats | Primary Data Source | Verification Status |
|:---:|:---|:---|:---|:---|:---|:---|:---:|:---|:---:|
| 1 | `MASTER_EMPLOYEE` | Master Employee Directory | Workforce & Organization | `/admin/reports/MASTER_EMPLOYEE` | `ho_admin`, `hr_executive` | Dept, Desig, Loc, Cat, Status, Search | CSV, XLS, PDF | `employees`, `departments`, `locations` | **VERIFIED** |
| 2 | `ACTIVE_EMPLOYEES` | Active Employees Register | Workforce & Organization | `/admin/reports/ACTIVE_EMPLOYEES` | `ho_admin`, `hr_executive` | Dept, Location, Search | CSV, XLS, PDF | `employees` (`status = 'Active'`) | **VERIFIED** |
| 3 | `INACTIVE_EMPLOYEES` | Separated & Inactive Staff | Workforce & Organization | `/admin/reports/INACTIVE_EMPLOYEES` | `ho_admin`, `hr_executive` | Dept, Separation Date, Search | CSV, XLS, PDF | `employees` (`status IN (Inactive,Resigned,Terminated)`) | **VERIFIED** |
| 4 | `NEW_JOINERS` | New Joiners Register | Workforce & Organization | `/admin/reports/NEW_JOINERS` | `ho_admin`, `hr_executive` | Date range (`date_from`, `date_to`), Dept | CSV, XLS, PDF | `employees` (`date_of_joining`) | **VERIFIED** |
| 5 | `PROBATION_DUE` | Probation Evaluation Due | Workforce & Organization | `/admin/reports/PROBATION_DUE` | `ho_admin`, `hr_executive` | Days threshold (30 days), Dept | CSV, XLS, PDF | `employees` (`probation_end_date`) | **VERIFIED** |
| 6 | `CONFIRMATION_DUE` | Confirmation Status Register | Workforce & Organization | `/admin/reports/CONFIRMATION_DUE` | `ho_admin`, `hr_executive` | Dept, Status | CSV, XLS, PDF | `employees` (`confirmation_date`) | **VERIFIED** |
| 7 | `HEADCOUNT_DEPARTMENT` | Department Headcount Summary | Workforce & Organization | `/admin/reports/HEADCOUNT_DEPARTMENT` | `ho_admin`, `hr_executive`, `manager_mss` | Company scope | CSV, XLS, PDF | `departments`, `employees` aggregation | **VERIFIED** |
| 8 | `HEADCOUNT_DESIGNATION` | Designation Distribution | Workforce & Organization | `/admin/reports/HEADCOUNT_DESIGNATION` | `ho_admin`, `hr_executive`, `manager_mss` | Department | CSV, XLS, PDF | `designations`, `employees` aggregation | **VERIFIED** |
| 9 | `HEADCOUNT_LOCATION` | Location / Branch Headcount | Workforce & Organization | `/admin/reports/HEADCOUNT_LOCATION` | `ho_admin`, `hr_executive` | Company scope | CSV, XLS, PDF | `locations`, `employees` aggregation | **VERIFIED** |
| 10 | `HEADCOUNT_CATEGORY` | Staff vs Worker Categories | Workforce & Organization | `/admin/reports/HEADCOUNT_CATEGORY` | `ho_admin`, `hr_executive` | Location, Category | CSV, XLS, PDF | `categories`, `employees` aggregation | **VERIFIED** |
| 11 | `CONTRACTOR_VS_DIRECT` | Employment Channel Mix | Workforce & Organization | `/admin/reports/CONTRACTOR_VS_DIRECT` | `ho_admin`, `hr_executive` | Contractor ID, Dept | CSV, XLS, PDF | `employees` (`employment_channel`, `contractor_id`) | **VERIFIED** |
| 12 | `ATTRITION_REPORT` | Workforce Attrition Register | Workforce & Organization | `/admin/reports/ATTRITION_REPORT` | `ho_admin`, `hr_executive` | Year, Month, Dept | CSV, XLS, PDF | `employees` exit history | **VERIFIED** |
| 13 | `ONBOARDING_PIPELINE` | Onboarding Progress Pipeline | Workforce Lifecycle & Compliance | `/admin/reports/ONBOARDING_PIPELINE` | `ho_admin`, `hr_executive` | Stage status, Date range | CSV, XLS, PDF | `employees` onboarding stages | **VERIFIED** |
| 14 | `EKYC_STATUS` | Aadhaar / PAN eKYC Audit | Workforce Lifecycle & Compliance | `/admin/reports/EKYC_STATUS` | `ho_admin`, `hr_executive` | Status (Pending, Verified, Grace) | CSV, XLS, PDF | `employee_ekyc_attempts`, `employees` | **VERIFIED** |
| 15 | `DOCUMENT_COMPLIANCE` | Mandatory Document Compliance | Workforce Lifecycle & Compliance | `/admin/reports/DOCUMENT_COMPLIANCE` | `ho_admin`, `hr_executive` | Document type, Verification status | CSV, XLS, PDF | `employee_documents` | **VERIFIED** |
| 16 | `ASSET_ALLOCATION` | Active Asset Allocation Ledger | Workforce Lifecycle & Compliance | `/admin/reports/ASSET_ALLOCATION` | `ho_admin`, `hr_executive` | Dept, Asset Category | CSV, XLS, PDF | `asset_allocations`, `assets` | **VERIFIED** |
| 17 | `ASSET_CLEARANCE` | Asset Return & Clearance Status | Workforce Lifecycle & Compliance | `/admin/reports/ASSET_CLEARANCE` | `ho_admin`, `hr_executive` | Clearance status, Condition | CSV, XLS, PDF | `asset_allocations` (`status = 'Returned'`) | **VERIFIED** |
| 18 | `FINE_DAMAGE_RECOVERY` | Fine & Damage Recovery Ledger | Workforce Lifecycle & Compliance | `/admin/reports/FINE_DAMAGE_RECOVERY` | `ho_admin`, `hr_executive` | Recovery status, Date range | CSV, XLS, PDF | `fine_recovery_records`, `damage_assessments` | **VERIFIED** |
| 19 | `SEPARATION_PIPELINE` | Resignation & Notice Pipeline | Workforce Lifecycle & Compliance | `/admin/reports/SEPARATION_PIPELINE` | `ho_admin`, `hr_executive` | Notice end date, Dept | CSV, XLS, PDF | `employees` resignations | **VERIFIED** |
| 20 | `LEAVE_BALANCE_LEDGER` | Leave Balance Ledger | Leave & Time Off | `/admin/reports/LEAVE_BALANCE_LEDGER` | `ho_admin`, `hr_executive`, `manager_mss`, `employee_ess` | Leave type, Dept, Employee | CSV, XLS, PDF | `leave_balances`, `leave_types` | **VERIFIED** |
| 21 | `LEAVE_UTILIZATION` | Leave Utilization Report | Leave & Time Off | `/admin/reports/LEAVE_UTILIZATION` | `ho_admin`, `hr_executive`, `manager_mss` | Date range, Leave type, Dept | CSV, XLS, PDF | `leave_applications` (`status = 'Approved'`) | **VERIFIED** |
| 22 | `LEAVE_APPLICATION_STATUS` | Leave Applications Pipeline | Leave & Time Off | `/admin/reports/LEAVE_APPLICATION_STATUS` | `ho_admin`, `hr_executive`, `manager_mss`, `employee_ess` | Status (Pending, Approved, Rejected) | CSV, XLS, PDF | `leave_applications` | **VERIFIED** |
| 23 | `LEAVE_ENCASHMENT` | Leave Encashment Register | Leave & Time Off | `/admin/reports/LEAVE_ENCASHMENT` | `ho_admin`, `hr_executive` | Financial year, Status | CSV, XLS, PDF | `leave_applications` encashment records | **VERIFIED** |
| 24 | `COMP_OFF_REGISTER` | Compensatory Off Register | Leave & Time Off | `/admin/reports/COMP_OFF_REGISTER` | `ho_admin`, `hr_executive`, `manager_mss` | Status (Earned, Availed, Expired) | CSV, XLS, PDF | `comp_off_records` | **VERIFIED** |
| 25 | `SANDWICH_LEAVE_IMPACT` | Sandwich Leave Deduction Audit | Leave & Time Off | `/admin/reports/SANDWICH_LEAVE_IMPACT` | `ho_admin`, `hr_executive` | Date range, Dept | CSV, XLS, PDF | `leave_applications` with sandwich impact | **VERIFIED** |
| 26 | `LEAVE_PERIOD_LOCK` | Leave Period Lock Status | Leave & Time Off | `/admin/reports/LEAVE_PERIOD_LOCK` | `ho_admin`, `hr_executive` | Year, Status (Locked, Unlocked) | CSV, XLS, PDF | `leave_period_locks` | **VERIFIED** |
| 27 | `DAILY_MUSTER_ROLL` | Form II Daily Muster Roll | Attendance & Operations | `/admin/reports/DAILY_MUSTER_ROLL` | `ho_admin`, `hr_executive` | Date, Shift, Dept, Location | CSV, XLS, PDF | `attendance_records`, `shifts` | **VERIFIED** |
| 28 | `MONTHLY_ATTENDANCE_SUMMARY` | Monthly Attendance Muster | Attendance & Operations | `/admin/reports/MONTHLY_ATTENDANCE_SUMMARY` | `ho_admin`, `hr_executive`, `manager_mss`, `employee_ess` | Month, Year, Dept | CSV, XLS, PDF | `attendance_records` aggregations | **VERIFIED** |
| 29 | `MISSING_PUNCH_EXCEPTIONS` | Missing Punch Exceptions | Attendance & Operations | `/admin/reports/MISSING_PUNCH_EXCEPTIONS` | `ho_admin`, `hr_executive`, `manager_mss` | Date range, Dept | CSV, XLS, PDF | `attendance_records` missing in/out | **VERIFIED** |
| 30 | `LATE_COMING_EARLY_GOING` | Late Arrival & Early Departure | Attendance & Operations | `/admin/reports/LATE_COMING_EARLY_GOING` | `ho_admin`, `hr_executive`, `manager_mss` | Date range, Threshold minutes | CSV, XLS, PDF | `attendance_records` late/early minutes | **VERIFIED** |
| 31 | `OVERTIME_REGISTER` | Overtime (OT) Register | Attendance & Operations | `/admin/reports/OVERTIME_REGISTER` | `ho_admin`, `hr_executive`, `manager_mss` | Date range, Approval status, OT type | CSV, XLS, PDF | `overtime_records` | **VERIFIED** |
| 32 | `SHIFT_ROSTER_SCHEDULE` | Shift Roster & Rota Schedule | Attendance & Operations | `/admin/reports/SHIFT_ROSTER_SCHEDULE` | `ho_admin`, `hr_executive`, `manager_mss` | Date range, Shift, Dept | CSV, XLS, PDF | `employee_shift_assignments`, `shifts` | **VERIFIED** |
| 33 | `REGULARIZATION_AUDIT` | Regularization Request Log | Attendance & Operations | `/admin/reports/REGULARIZATION_AUDIT` | `ho_admin`, `hr_executive`, `manager_mss` | Decision status, Date range | CSV, XLS, PDF | `attendance_regularizations` | **VERIFIED** |
| 34 | `PLANT_SHUTDOWN_LOG` | Plant Shutdown & Maintenance | Attendance & Operations | `/admin/reports/PLANT_SHUTDOWN_LOG` | `ho_admin`, `hr_executive` | Date range, Type, Location | CSV, XLS, PDF | `plant_shutdowns` | **VERIFIED** |
| 35 | `BIOMETRIC_DEVICE_STATUS` | Biometric Devices Health Audit | Attendance & Operations | `/admin/reports/BIOMETRIC_DEVICE_STATUS` | `ho_admin`, `hr_executive` | Device status, Location | CSV, XLS, PDF | `biometric_devices` | **VERIFIED** |
| 36 | `BIOMETRIC_PUNCH_RAW` | Raw Biometric Punch Stream | Attendance & Operations | `/admin/reports/BIOMETRIC_PUNCH_RAW` | `ho_admin`, `hr_executive` | Date range, Device ID, Status | CSV, XLS, PDF | `biometric_punch_logs` | **VERIFIED** |
| 37 | `MONTHLY_PAYROLL_REGISTER` | Monthly Payroll Master Register | Payroll & Compensation | `/admin/reports/MONTHLY_PAYROLL_REGISTER` | `ho_admin`, `hr_executive` | Payroll Run ID, Dept | CSV, XLS, PDF | `payroll_runs`, `payroll_run_employees` | **VERIFIED** |
| 38 | `EMPLOYEE_SALARY_STATEMENT` | Individual Salary Statements | Payroll & Compensation | `/admin/reports/EMPLOYEE_SALARY_STATEMENT` | `ho_admin`, `hr_executive` | Employee ID, Month, Year | CSV, XLS, PDF | `payroll_run_employees` | **VERIFIED** |
| 39 | `DEPARTMENT_WAGE_SUMMARY` | Department Wage Distribution | Payroll & Compensation | `/admin/reports/DEPARTMENT_WAGE_SUMMARY` | `ho_admin`, `hr_executive` | Payroll Run ID | CSV, XLS, PDF | `payroll_run_employees` by dept | **VERIFIED** |
| 40 | `GROSS_NET_VARIANCE` | Month-on-Month Variance | Payroll & Compensation | `/admin/reports/GROSS_NET_VARIANCE` | `ho_admin`, `hr_executive` | Current Run ID, Prior Run ID | CSV, XLS, PDF | Comparative payroll runs | **VERIFIED** |
| 41 | `PAYROLL_RECONCILIATION` | Payroll Run Reconciliation | Payroll & Compensation | `/admin/reports/PAYROLL_RECONCILIATION` | `ho_admin`, `hr_executive` | Status, Year | CSV, XLS, PDF | `payroll_runs` audit state | **VERIFIED** |
| 42 | `PAYSLIP_GENERATION_STATUS` | Payslip Distribution Audit | Payroll & Compensation | `/admin/reports/PAYSLIP_GENERATION_STATUS` | `ho_admin`, `hr_executive` | Payroll Run ID, View status | CSV, XLS, PDF | `payroll_run_employees` (`is_payslip_generated`) | **VERIFIED** |
| 43 | `LOAN_ADVANCE_LEDGER` | Loans & Advances Ledger | Payroll & Compensation | `/admin/reports/LOAN_ADVANCE_LEDGER` | `ho_admin`, `hr_executive` | Loan status, Employee ID | CSV, XLS, PDF | `employee_loans`, `employee_loan_installments` | **VERIFIED** |
| 44 | `FULL_FINAL_SETTLEMENT` | Full & Final (F&F) Register | Payroll & Compensation | `/admin/reports/FULL_FINAL_SETTLEMENT` | `ho_admin`, `hr_executive` | Settlement status, Date range | CSV, XLS, PDF | `fnf_settlements` | **VERIFIED** |
| 45 | `ARREARS_PAYMENT_LOG` | Salary Arrears Processing Log | Payroll & Compensation | `/admin/reports/ARREARS_PAYMENT_LOG` | `ho_admin`, `hr_executive` | Payroll Run ID | CSV, XLS, PDF | Arrears components in payroll | **VERIFIED** |
| 46 | `SALARY_HOLD_RELEASE` | Salary On-Hold & Release | Payroll & Compensation | `/admin/reports/SALARY_HOLD_RELEASE` | `ho_admin`, `hr_executive` | Payroll Run ID, Hold reason | CSV, XLS, PDF | `payroll_run_employees` (`is_hold`) | **VERIFIED** |
| 47 | `STATUTORY_PF_ECR` | PF Electronic Challan Return | Statutory Compliance | `/admin/reports/STATUTORY_PF_ECR` | `ho_admin`, `hr_executive` | Payroll Run ID | CSV, XLS, PDF | EPF/EPS/EDLI wage computations | **VERIFIED** |
| 48 | `STATUTORY_ESI_FORM5` | ESI Form 5 Monthly Return | Statutory Compliance | `/admin/reports/STATUTORY_ESI_FORM5` | `ho_admin`, `hr_executive` | Payroll Run ID | CSV, XLS, PDF | ESI wages & contribution computations | **VERIFIED** |
| 49 | `STATUTORY_PT_RETURN` | Professional Tax (PT) Return | Statutory Compliance | `/admin/reports/STATUTORY_PT_RETURN` | `ho_admin`, `hr_executive` | Payroll Run ID, State | CSV, XLS, PDF | PT slab deductions & Feb adjustment | **VERIFIED** |
| 50 | `STATUTORY_LWF_RETURN` | Labour Welfare Fund Statement | Statutory Compliance | `/admin/reports/STATUTORY_LWF_RETURN` | `ho_admin`, `hr_executive` | Half-year period, State | CSV, XLS, PDF | LWF statutory deductions | **VERIFIED** |
| 51 | `STATUTORY_TDS_WITHHOLDING` | Income Tax (TDS) Register | Statutory Compliance | `/admin/reports/STATUTORY_TDS_WITHHOLDING` | `ho_admin`, `hr_executive` | Financial Year, Tax Regime | CSV, XLS, PDF | TDS withholding records | **VERIFIED** |
| 52 | `STATUTORY_SUMMARY` | Statutory Compliance Summary | Statutory Compliance | `/admin/reports/STATUTORY_SUMMARY` | `ho_admin`, `hr_executive` | Payroll Run ID | CSV, XLS, PDF | Aggregated PF+ESI+PT+LWF+TDS | **VERIFIED** |
| 53 | `BANK_TRANSFER_ADVICE` | Bank Transfer Advice (NEFT/RTGS) | Disbursement & Banking | `/admin/reports/bank-advice` | `ho_admin`, `hr_executive` (Masked), Finance | Payroll Run ID, Unmasked Toggle | CSV, XLS, PDF | Net pay, IFSC, Bank Accounts (Masked) | **VERIFIED** |

