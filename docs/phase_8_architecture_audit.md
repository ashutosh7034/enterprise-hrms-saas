# PHASE 8 ARCHITECTURE AUDIT: ENTERPRISE REPORTING, ROLE-BASED DASHBOARDS, FINAL POLISH & AUTOMATED AUDIT

**Enterprise Human Resource Management System (HRMS)**  
**Target Environment:** PHP 8.3 | Laravel 13.39.0 | MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target)  
**Safety Gate Status:** CLEARED (`enterprise_hrms_test` isolated from `enterprise_hrms`)  
**Document Reference:** `DOCS-PH8-ARCH-001`  
**Date:** November 2026  

---

## 1. Executive Summary & Objective

Phase 8 is the culminating phase of the Enterprise HRMS application. It unifies all operational and regulatory data across Phases 1 through 7 into a comprehensive **Enterprise Reporting & Analytics Engine**, delivers dedicated **Role-Based Dashboards (HO Admin, HR Executive, Manager MSS, Employee ESS)** adhering strictly to Page 94 segregation rules, executes a comprehensive **UI/UX Polish**, and performs an exhaustive **End-to-End Automated System Audit**.

The target deployment operates across the two corporate entities:
- **Vishal Enterprise (`VE`)**
- **Vrishal Eng. Pvt. Ltd. (`VRE`)**

It guarantees strict multi-company isolation, strict RBAC controls, immutable audit trails, private disk storage for report exports, optimized MySQL aggregations, and zero PII/compensation data leakage.

---

## 2. Phase Numbering & Implementation Sequence Reconciliation

A critical architectural reconciliation has been conducted regarding phase sequencing between the initial planning documentation and the actual verified implementation trajectory:

### 2.1 Sequence Audit
- In the initial preliminary backlog drafts, "Enterprise Reporting & Analytics" was originally designated as Phase 7, and "Role-Based Dashboards & Audit" was designated as Phase 8.
- During actual execution, the business prioritized the **Letter & Form Print Engine** (Document Templates, Dynamic Placeholders, Dompdf A4 rendering, Approval Workflows, and Public Document Verification Portal), which was formally implemented, tested (27 tests), and audited as **Phase 7**.
- Consequently, Phase 8 is formally expanded to encompass the complete suite of:
  1. **Enterprise Reporting & Analytics Engine** (HR Reports, Lifecycle, Leaves, Attendance, Payroll, Statutory, Bank Advice, Multi-Format Exports, Scheduled Dispatch).
  2. **Role-Based Dashboards & Data Visualizations** (HO Admin, HR Executive, Manager MSS, Employee ESS).
  3. **UI/UX Final Polish & Accessibility Audit** across all 14+ modules.
  4. **Performance, Query & Index Optimization Audit**.
  5. **Comprehensive Security, RBAC & Multi-Company Audit**.
  6. **End-to-End Automated Regression & Verification Report**.

### 2.2 Canonical Phasing Map
- **Phase 1:** Multi-Company Foundation, Org Masters & RBAC *(Completed & Verified)*
- **Phase 2:** Workforce Onboarding, eKYC, Central Blacklist & Asset Management *(Completed & Verified)*
- **Phase 3:** Attendance Engine, Shift Rosters, Plant Shutdowns & Biometric Integration *(Completed & Verified)*
- **Phase 4:** Leave Engine, Sandwich Policies, Comp-Off & Holiday Calendars *(Completed & Verified)*
- **Phase 5:** Payroll Engine, Statutory Tax Calculations, Loans & F&F Settlements *(Completed & Verified)*
- **Phase 6:** Multi-Channel Notification Engine (In-App, Email, SMS, WhatsApp) *(Completed & Verified)*
- **Phase 7:** Letter & Form Print Engine | Document Templates | PDF Generation | ESS *(Completed & Verified)*
- **Phase 8:** Enterprise Reporting & Analytics | Role-Based Dashboards | Final System Polish | Automated Audit *(Current Phase)*

---

## 3. Existing System Inspection & Reporting Data Foundations

An inspection was conducted across all existing database tables, models, and services in the application:

### 3.1 Workforce & Organization Data
- **Models:** `Employee`, `Company`, `Location`, `Department`, `Designation`, `Category`, `Contractor`.
- **Key Attributes:** `date_of_joining`, `confirmation_date`, `probation_end_date`, `status`, `employment_channel`, `contractor_id`, `gender`.
- **Target Reports:** Employee Master, Active/Inactive, Joiners, Confirmations Due, Probation Due, Department/Designation/Location Breakdown, Contractor vs Direct Employee, Headcount & Attrition.

### 3.2 Workforce Lifecycle, eKYC, Blacklist & Assets
- **Models:** `EmployeeDocument`, `EmployeeEkycAttempt`, `CentralBlacklistRecord`, `IdentityMatchReview`, `Asset`, `AssetAllocation`, `FineRecoveryRecord`, `DamageAssessment`.
- **Target Reports:** Onboarding Status, eKYC Pending / Grace Active, Document Verification Status, Asset Allocation Ledger, Asset Clearance Ledger, Resignation / Separation Pipeline.

### 3.3 Leave & Holiday System
- **Models:** `LeaveApplication`, `LeaveBalance`, `LeaveType`, `LeavePeriodLock`, `CompOffRecord`, `Holiday`, `SandwichLeavePolicy`.
- **Target Reports:** Leave Balance Ledger, Leave Utilization Summary, Leave Application Pipeline, Leave Accrual History, Leave Encashment Summary, Comp-Off Granted/Availed/Expired, Leave Period Lock Status.

### 3.4 Time, Attendance & Plant Operations
- **Models:** `AttendanceRecord`, `AttendancePunch`, `AttendanceRegularizationRequest`, `BiometricDevice`, `PlantShutdown`, `Shift`, `EmployeeShiftAssignment`, `OvertimeRecord`.
- **Target Reports:** Factory Form II Daily Muster Roll, Monthly Attendance Muster, Missing Punch Exception Report, Late Arrival / Early Departure Report, Overtime Register (Normal vs Holiday OT), Shift Roster Attendance, Regularization History, Plant Shutdown Operations, Biometric Synchronization Log.

### 3.5 Payroll, Statutory Compliance & Settlement
- **Models:** `PayrollRun`, `PayrollRunEmployee`, `PayrollComponentTransaction`, `EmployeeSalaryAssignment`, `SalaryStructure`, `EmployeeLoan`, `EmployeeLoanInstallment`, `FnfSettlement`.
- **Target Reports:** Monthly Payroll Register, Employee Salary Statement, Department Wage Summary, Gross vs Net Pay Variance, Run Reconciliation Status, Payslip Generation Status, Loan & EMI Recovery Ledger, Salary Advance Summary, Full & Final Settlement Register.
- **Statutory Compliance Reports:**
  - **PF (EPF/EPS/EDLI):** Form 12A / Electronic Challan Return (ECR) data with capped/uncapped wages.
  - **ESI:** Form 5 Monthly Contribution statement (gross wages, employee 0.75%, employer 3.25%).
  - **Professional Tax (PT):** Maharashtra monthly slab returns and February adjustments.
  - **Labour Welfare Fund (LWF):** Bi-annual deduction tracking.
  - **TDS:** Income tax withholding register (Old vs New tax regimes).

---

## 4. Centralized Enterprise Reporting Architecture

### 4.1 `App\Services\Reporting\ReportingService`
The reporting subsystem will be organized under a high-performance, reusable service layer:
- **Tenant Isolation:** Enforces `CompanyContextService::getActiveCompanyId()` on every query.
- **RBAC & Data Filtering:**
  - HO Admin & HR Executive: Full company-wide reporting with sensitive financial columns visible.
  - Manager MSS: Scope restricted to direct reporting lines (`reporting_manager_id`) or assigned department. Sensitive salary columns are strictly withheld or hidden unless explicitly authorized.
  - Employee ESS: Restricted strictly to own employee records (`employee_id = auth_employee_id`).
- **Query Optimization:**
  - Optimized MySQL `SELECT` projections selecting only required columns.
  - Eager loading (`with([...])`) on all relational foreign keys (`department`, `designation`, `location`, `category`) to eliminate N+1 overhead.
  - Chunked processing (`chunk(500)`) for streaming large CSV and Excel-compatible exports, avoiding PHP memory exhaustion (`memory_limit`).
  - Native database aggregations (`SUM`, `COUNT`, `AVG`, `GROUP BY`) executed at the database level rather than inside PHP memory.

### 4.2 Multi-Format Export Engine
- **CSV:** Streamed via `Symfony\Component\HttpFoundation\StreamedResponse` with UTF-8 BOM for Microsoft Excel compatibility.
- **Excel:** Clean tab-delimited or XML/HTML spreadsheet format.
- **PDF:** Formatted A4 portrait/landscape outputs rendered via `Dompdf\Dompdf` with clean page-break styling, company headers, and confidentiality notices.
- **Export History Logging:** Captures user ID, report type, format, row count, file size, applied filters, and timestamp in `report_export_histories`.

### 4.3 Sensitive Banking Data Protection
- Bank account numbers in general reports must be masked: `XXXXXX4321`.
- Full unmasked bank account numbers, IFSC codes, and net transfer amounts are strictly restricted to authorized Finance and HO Admin roles for bank transfer advice generation.

---

## 5. Scheduled Report Delivery Architecture

### 5.1 Automation Infrastructure
- **Model:** `ScheduledReport` and `ScheduledReportLog`.
- **Frequencies:** Daily, Weekly, Monthly.
- **Artisan Command:** `php artisan reports:dispatch-scheduled`.
- **Delivery Mechanisms:** Integrates with the Phase 6 Multi-Channel Notification Engine (Email attachments or secure notification links). In testing and local development, email delivery uses `log` or mock drivers for zero external leakage.
- **Access Verification:** Before executing a scheduled job, the scheduler verifies that all target recipients possess active accounts and legitimate RBAC permissions for the requested report category.

---

## 6. Role-Based Dashboard Architecture (Page 94 Segregation)

The system provides 4 distinct, non-overlapping dashboard interfaces:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           DASHBOARD PORTALS                             │
├────────────────────┬────────────────────┬───────────────┬───────────────┤
│    HO Admin Hub    │  HR Executive Hub  │  Manager MSS  │ Employee ESS  │
├────────────────────┼────────────────────┼───────────────┼───────────────┤
│ • Group Headcount  │ • Company Staff    │ • My Team     │ • My Profile  │
│ • Vishal vs Vrishal│ • Onboarding Queue │ • Attendance  │ • Today Punch │
│ • Entity Turnover  │ • eKYC Approvals   │ • Leave Appro │ • Leave Bal   │
│ • Payroll Overview │ • Regularizations  │ • Regulariz   │ • My Payslips │
│ • Cross-Entity Log │ • Probation Due    │ • OT Approvals│ • My Documents│
│ • Notification Stat│ • Compliance Mon   │ • (No Salary) │ • My Requests │
└────────────────────┴────────────────────┴───────────────┴───────────────┘
```

### 6.1 Real Database Data Visualizations
All charts are rendered using **Chart.js** embedded in standard Blade components:
- **Headcount by Department:** Horizontal/Vertical Bar Chart.
- **Monthly Joining vs Attrition Trends:** Multi-line Chart.
- **Daily Attendance Composition:** Donut/Pie Chart (Present, Absent, On Leave, Late, Half Day).
- **Leave Utilization by Type:** Stacked Bar Chart (CL, PL, SL).
- **Monthly Gross Payroll Cost Trend:** Area/Line Chart.
- Zero fabricated metrics; all datasets are dynamically computed from active database records.

---

## 7. Database Changes: Batch 12 Schema Blueprint

### 7.1 `report_export_histories`
- `id` (bigint unsigned, PK)
- `company_id` (bigint unsigned, FK -> companies)
- `user_id` (bigint unsigned, FK -> users)
- `report_type` (varchar 100, index)
- `file_format` (varchar 20)
- `file_path` (varchar 500, nullable)
- `row_count` (int unsigned, default 0)
- `file_size_kb` (int unsigned, default 0)
- `filters_applied_json` (json, nullable)
- `status` (varchar 50, default 'Completed')
- `ip_address` (varchar 45, nullable)
- `created_at` (timestamp)

### 7.2 `scheduled_reports`
- `id` (bigint unsigned, PK)
- `company_id` (bigint unsigned, FK -> companies)
- `name` (varchar 255)
- `report_type` (varchar 100)
- `frequency` (enum: 'Daily', 'Weekly', 'Monthly')
- `export_format` (enum: 'csv', 'excel', 'pdf')
- `recipients_json` (json)
- `filters_json` (json, nullable)
- `is_active` (boolean, default true)
- `last_run_at` (datetime, nullable)
- `next_run_at` (datetime, nullable)
- `created_by` (bigint unsigned, FK -> users)
- `timestamps`, `deleted_at`

### 7.3 `scheduled_report_logs`
- `id` (bigint unsigned, PK)
- `scheduled_report_id` (bigint unsigned, FK -> scheduled_reports, cascade)
- `status` (enum: 'Success', 'Failed', 'Skipped')
- `recipients_count` (int unsigned, default 0)
- `error_message` (text, nullable)
- `file_path` (varchar 500, nullable)
- `dispatched_at` (timestamp)

---

## 8. Implementation Roadmap across Stages 8A through 8O

1. **Stage 8A:** Centralized Enterprise Reporting Architecture & Service (`ReportingService`).
2. **Stage 8B:** HR Reports implementation across all 6 domain categories (Employee, Lifecycle, Leave, Attendance, Payroll, Statutory).
3. **Stage 8C & 8D:** Bank Advice, Multi-Format Export Engine & Standardized Multi-Parameter Filters.
4. **Stage 8E:** Scheduled Report Delivery Subsystem & Artisan Command (`reports:dispatch-scheduled`).
5. **Stage 8F & 8G:** Role-Based Dashboards (HO Admin, HR Executive, Manager MSS, Employee ESS) & Chart.js Visualizations.
6. **Stage 8H & 8I:** UI/UX Final Polish, Design System Consistency & Accessibility Audit.
7. **Stage 8J & 8K:** Performance Optimization (Queries, Eager Loading, Indexes) & Security / RBAC Matrix.
8. **Stage 8L:** End-to-End Automated Regression Testing on MySQL `enterprise_hrms_test`.
9. **Stage 8M & 8N:** Role-Based UAT Scenarios & Phase 8 Comprehensive Documentation Suite.
10. **Stage 8O:** Final Project Completion & Verification Report (`enterprise_hrms_final_completion_and_verification_report.md`).

