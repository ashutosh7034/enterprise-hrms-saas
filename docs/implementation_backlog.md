# ENTERPRISE HRMS — IMPLEMENTATION BACKLOG & PHASED ROADMAP

## 1. Development Principles & Phasing Strategy

In accordance with enterprise engineering standards, implementation proceeds in strictly controlled, testable, and dependency-ordered phases. Each module builds upon verified foundations without skipping database constraints, authorization policies, or automated tests.

---

## 2. Phased Implementation Roadmap

```
+---------------------------------------------------------------------------------------+
| PHASE 1: CORE FOUNDATIONS & MULTI-COMPANY SETUP [COMPLETED & AUDITED]                 |
| - Laravel 13 Project Initialization & MySQL 8 Configuration                           |
| - Authentication, Roles (ESS, MSS, HR, HO Admin), Permissions & Base Layout           |
| - Multi-Company Master (Vishal Enterprise & Vrishal Eng.), Locations, Departments     |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 2: WORKFORCE ONBOARDING, eKYC, BLACKLIST & ASSETS [COMPLETED & VERIFIED]        |
| - Employee Master with Dedicated Gate Pass Number & Duplicate Checks                  |
| - Document Checklist Upload & Verification (Pending / Verified / Rejected)            |
| - eKYC Lifecycle: Failure Logging, 14-Day Grace Period, Exception Override Flow       |
| - Central Blacklist / Whitelist Engine with Multi-Level Approvals & Aadhaar Masking   |
| - Asset Master, Allocations, Fine Master, Damage Recovery & Depreciation Engine       |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 3: TIME & ATTENDANCE, SHIFTS, SHUTDOWNS & BIOMETRICS [COMPLETED & VERIFIED]     |
| - Shift Master (Grace Periods, Half-Day thresholds, Overnight shifts) & Roster        |
| - Biometric Device Master & Raw Punch Staging Ingestion                               |
| - Attendance Engine: Punch Pairing, Daily Muster Calculation, Overtime Evaluation     |
| - Plant / Site Shutdown Management (Maintenance, Power, Planned, Material Shortage)   |
| - Attendance Regularization Matrix (Business Travel/OD, Late, Missing Punch)          |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 4: LEAVE ENGINE, SANDWICH POLICIES & HOLIDAYS [COMPLETED & VERIFIED]            |
| - Leave Types, Monthly Accruals, Confirmation Allocation & Carry-Forward              |
| - Holiday Master with Site-Specific Calendars & Ground Staff Logic                    |
| - Sandwich Rule Engine (Holiday & Weekly-Off Inclusion / Exclusion by Policy)         |
| - Notice Period Leave Restrictions & Extensions                                       |
| - Comp-Off Accrual & Availing Engine (Tied to Verified Holiday/Off punches)           |
| - Leave Application & Multi-Level Approval Workflow                                   |
| - Leave Period Lock & HO Team Override Unlock                                         |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 5: PAYROLL ENGINE, STATUTORY, LOANS & FULL & FINAL [COMPLETED & AUDITED]        |
| - Salary Structure Master (Basic, HRA, Allowances, Target OT vs Actual OT)            |
| - Statutory Deduction Engines: PF (12%), ESI (0.75%), PT (State slabs), LWF, TDS      |
| - Pre-Payroll Validation Gate & Payroll Run Execution Engine                          |
| - Payslip Generation (Attendance summary, PDF download, Email dispatch)               |
| - Loan & Advance Master with Automatic Monthly EMI Deductions                         |
| - Full & Final Settlement (F&F) integrating Unpaid Wages, Encashment, Loans & Fines   |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 6: MULTI-CHANNEL NOTIFICATION ENGINE [COMPLETED & VERIFIED]                     |
| - Multi-Channel Notification Engine (In-App Bell, Email, SMS, WhatsApp)               |
| - System Triggers (Missing Punches, Leave Approvals, Shutdowns, eKYC Deadlines)       |
| - Notification Templates, Parameters, Granular Channel Preferences & Mandatory Policy |
| - Pluggable SMS & WhatsApp Providers (Safe Mock Simulation & Production Readiness)    |
| - Notification Control Tower: Telemetry, Delivery Logs Archive & Manual Retry Action  |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 7: LETTER & FORM PRINT ENGINE, TEMPLATES & ESS [COMPLETED & VERIFIED]           |
| - Letter & Form Template Master (40 Seeded Enterprise Templates across Categories)    |
| - Dynamic Placeholder Engine ({employee_name}, {gross_salary}, {uan}, etc.)           |
| - Dompdf A4 High-Fidelity Rendering with Corporate Letterhead & Signatures            |
| - Multi-Tier Approval Workflow (Draft -> Pending Review -> Approved -> Issued)        |
| - Tamper-Proof Public Verification Portal with SHA-256 Hashes & QR Codes              |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| PHASE 8: ENTERPRISE REPORTING, ROLE DASHBOARDS & FINAL AUDIT [COMPLETED & VERIFIED]   |
| - Centralized Reporting Engine: 53 Reports across 7 Domains (Workforce, PF, ESI, etc.)|
| - Memory-Safe Streaming Exports: CSV with UTF-8 BOM, Tabular Excel & Dompdf PDFs      |
| - Scheduled Report Delivery Subsystem & Artisan Command (reports:dispatch-scheduled)  |
| - Role-Based Dashboards: HO Admin, HR Executive, Manager MSS (Page 94) & Employee ESS |
| - End-to-End Automated Regression: 196 Tests Passing (1,038 Assertions, 0 Failures)   |
+---------------------------------------------------------------------------------------+
```

---

## 3. Granular Module Backlog & Verification Criteria

| Backlog Item ID | Module Name | Deliverables & Scope | Acceptance & Verification Criteria |
| :--- | :--- | :--- | :--- |
| **BL-01** | Core Foundation & Multi-Company | Laravel 13 scaffold, MySQL connection, Authentication, Role middleware, `companies`, `locations`, `departments`, `designations`, `categories` tables. | Both Vishal Enterprise and Vrishal Eng. seeded; switching active company filters all queries; authenticated roles restrict access. |
| **BL-02** | Workforce Master & Gate Pass | `employees` table, Onboarding wizard, Direct vs Contractor distinction, Gate Pass Number field with uniqueness check. | Duplicate detection catches identical Aadhaar/PAN/Phone; Gate Pass edits logged in `employee_gate_pass_histories`. |
| **BL-03** | Document Upload & eKYC | Document checklist upload, Verification UI, eKYC failure logging, 2-3 week resubmission grace period with countdown. | Non-blocking eKYC failure allows resubmission; manual HO override records verifier ID and distinct `Exceptionally Verified` status. |
| **BL-04** | Central Blacklist & Whitelist | Cross-company blacklist table, tokenized identity check, multi-level approval for blacklisting, candidate search check. | New registration blocks or holds candidate if found on blacklist; whitelisting requires justification and preserves history. |
| **BL-05** | Asset Fine Master & Recovery | Asset catalog, allocation forms, condition tracking, fine master, damage assessment & payroll deduction link. | Damaged asset fines calculate depreciated value and automatically post to payroll or F&F settlement. |
| **BL-06** | Shift Master & Biometric Device | Shift configuration (start/end, grace, half-day threshold, overnight flag), Biometric device CRUD, raw punch staging table. | Raw punches ingest into `biometric_punch_logs` and handle duplicate timestamps without dropping transactions. |
| **BL-07** | Attendance Engine & Shutdowns | Daily attendance calculation engine, punch pairing, overtime calculator, plant shutdown declarations and attendance impact. | Correctly classifies Present, Half Day, Absent, Overtime, and Shutdown; allows maintenance crew to log special work during shutdown. |
| **BL-08** | Regularization Scenarios | Request forms for Business Travel (OD), Late arrival, Early departure, Missing punch; Manager/HR approval workflow. | Approved regularization updates attendance muster status and restores calculated work minutes. |
| **BL-09** | Leave Engine & Sandwich Rules [COMPLETED] | Leave types, monthly accrual job, pro-rata confirmation credit, sandwich rule calculator (Holiday/Off inclusion). | Taking leave on Friday & Monday over weekend correctly applies sandwich deduction when enabled by policy. |
| **BL-10** | Comp-Off & Notice Period Leaves [COMPLETED] | Verified holiday/off work converts to Comp-Off balance; 60-day expiry enforcement; notice period leave restrictions and extensions. | Comp-Off cannot be claimed without approved holiday punch; unapproved notice period leave extends exit date or marks LOP. |
| **BL-11** | Leave Approvals & Period Locks [COMPLETED] | Multi-tier approval (Manager $\rightarrow$ HOD $\rightarrow$ HR), monthly leave period lock post-payroll closing, HO Team unlock. | Locked months prevent leave applications or edits; HO unlock requires mandatory justification logged to audit trail. |
| **BL-12** | Payroll Engine & Salary Slabs [COMPLETED] | Salary structure master, Target OT vs Actual OT calculation, statutory rules (PF, ESI, PT, LWF, TDS). | Net pay equals Gross less statutory, loans, and fines; Target OT applies configured flat amount when enabled. Verified with 34 tests. |
| **BL-13** | Payslip & Full & Final (F&F) [COMPLETED] | Pre-payroll validation gate, monthly payroll run, payslip generator with attendance details, F&F settlement calculator. | Payslip renders worked days, leaves, LOP, deductions, and PF UAN; F&F offsets unreturned assets and loan balances. Dompdf PDF output verified. |
| **BL-14** | Multi-Channel Notifications [COMPLETED] | Notification templates, in-app bell, trigger listeners (missing punch, leave approval, shutdown alert), delivery logs, preferences matrix, pluggable SMS & WhatsApp providers. | System triggers fire notifications without crashing business transactions; delivery status tracked in `notification_delivery_logs`. Verified with 17 automated tests. |
| **BL-15** | Letter & Form Print Engine | Letter & Form template master with 40 baseline enterprise templates (20 HR Letters + 20 HR Forms), dynamic placeholder engine, Dompdf A4 rendering, approval workflow, private storage, and tamper-proof public verification. | **COMPLETED & VERIFIED (Phase 7)**: 40 templates seeded, Dompdf PDF generation, immutable snapshotting, ESS portal, rate-limited public verification portal, 27 Phase 7 tests passing (178 full suite). |
| **BL-16** | Enterprise Reporting & Scheduled Subsystem [COMPLETED] | Centralized reporting catalog of 53 reports across 7 domains, dynamic filtering, CSV streaming with UTF-8 BOM, tab-delimited Excel, Dompdf landscape PDFs, bank account masking, recurring scheduled reports command (`reports:dispatch-scheduled`), and `report_export_histories` audit logs. | **COMPLETED & VERIFIED (Phase 8)**: 53 reports functional across Workforce, Lifecycle, Leaves, Attendance, Payroll, Statutory (PF/ESI/PT/LWF/TDS), and Bank Advice. Scheduled automated dispatches and compliance audit logging verified. 13 tests passing across reporting and scheduling suites. |
| **BL-17** | Role Dashboards, Page 94 Segregation & System Audit [COMPLETED] | Dedicated role dashboards for HO Admin, HR Executive, Manager MSS, and Employee ESS; real-time Chart.js aggregations; strict Page 94 segregation blocking Manager MSS from compensation data; complete UI/UX polish; full end-to-end regression audit across 196 tests. | **COMPLETED & VERIFIED (Phase 8)**: All 4 dashboards operational with real MySQL aggregations; Manager MSS restricted to operational presence (HTTP 403 on salary/admin routes); entire 196 application tests passing with 1,038 assertions and 0 failures. Production Certified. |
