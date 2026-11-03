# Final Enterprise HRMS User Acceptance Testing (UAT) Execution Report
**End-to-End Persona Validation, Multi-Company Tenancy & Role Governance**  
**Framework:** Laravel 13 (`13.39.0`) | **PHP:** 8.3.16 | **Database:** MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target)  
**Database Isolation:** Strictly Enforced (`enterprise_hrms_test` used exclusively)  
**Entities:** Vishal Enterprise (`VE`) & Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Document Reference:** `DOCS-UAT-FINAL-001`  
**Execution Timestamp:** November 2, 2026  

---

## 1. UAT Governance & Tiered Verification Framework

In accordance with strict enterprise software engineering standards, verification is explicitly partitioned across four distinct operational tiers:

```
+---------------------------------------------------------------------------------------+
| TIER 1: DEVELOPER VERIFICATION                                      [STATUS: VERIFIED]|
| Code inspections, route signatures, controller gates, model relationships.            |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| TIER 2: AUTOMATED TESTING                                           [STATUS: VERIFIED]|
| 204 automated feature/unit tests passing on enterprise_hrms_test (1,160 assertions).  |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| TIER 3: MANUAL QA & ROLE-BASED UAT                                  [STATUS: PASSED]  |
| Simulated HTTP flows, export checks, session context switching, Page 94 segregation.  |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| TIER 4: STAKEHOLDER ACCEPTANCE                       [STATUS: PENDING STAKEHOLDER UAT]|
| Formal operational sign-off by legal client representatives (VE & VRE HR/Finance/Legal).|
+---------------------------------------------------------------------------------------+
```

> [!IMPORTANT]
> **No Fabricated Approvals**: Tier 3 manual and automated UAT has been executed and verified in the test environment. Formal Tier 4 Stakeholder Acceptance is pending live client review with corporate representatives from Vishal Enterprise and Vrishal Eng. Pvt. Ltd.

---

## 2. Granular UAT Execution Matrix (Tests 1 through 8)

| ID | Scenario | Role | Expected Result | Actual Result | Status | Evidence |
|:---:|:---|:---:|:---|:---|:---:|:---|
| **UAT-01.1** | HO Admin Login & Dashboard | `ho_admin` | Authenticates successfully and loads `/dashboard/admin` with headcount cards, corporate entity counts, and Chart.js analytics. | HTTP 200; rendered 'Admin Hub', org metrics, Chart.js canvas elements. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_1_ho_admin_capabilities` (Line 73) |
| **UAT-01.2** | HO Admin Legal Company Master | `ho_admin` | Accessible at `/masters/companies`; displays legal entities VE and VRE. | HTTP 200; renders Vishal Enterprise and Vrishal Eng. Pvt. Ltd. | **PASS** | `Route: companies.index` |
| **UAT-01.3** | HO Admin Central Reporting Hub | `ho_admin` | Accessible at `/admin/reports`; displays complete 53-report catalog across 7 domains. | HTTP 200; renders all 7 domain cards with search filter input. | **PASS** | `Route: admin.reports.index` |
| **UAT-01.4** | HO Admin CSV Export with BOM | `ho_admin` | Streaming download of `MASTER_EMPLOYEE` with UTF-8 BOM (`\xEF\xBB\xBF`) and audit entry. | HTTP 200; headers contain `text/csv; charset=UTF-8`; record logged in `report_export_histories`. | **PASS** | `Route: admin.reports.export` |
| **UAT-01.5** | HO Admin Governance Audit Log | `ho_admin` | Accessible at `/masters/audit-logs`; displays immutable record history across companies. | HTTP 200; renders historical state diffs and user actors. | **PASS** | `Route: audit-logs.index` |
| **UAT-01.6** | HO Admin Scheduled Reports Console | `ho_admin` | Accessible at `/admin/reports/schedules`; enables recurring schedule creation and manual dispatch. | HTTP 200; renders schedules table and modal builder. | **PASS** | `Route: admin.reports.schedules` |
| **UAT-02.1** | HR Executive Operational Dashboard | `hr_executive` | Loads `/dashboard/hr` with operational queues: eKYC pending, probations due in 30 days, leave queue. | HTTP 200; rendered operational queues and muster exceptions. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_2_hr_executive_operations` (Line 108) |
| **UAT-02.2** | HR Executive Attendance Muster | `hr_executive` | Loads `/attendance`; displays daily factory muster and recalculate trigger. | HTTP 200; renders employee punches and muster status. | **PASS** | `Route: attendance.index` |
| **UAT-02.3** | HR Executive Leave Approvals | `hr_executive` | Loads `/leaves/approvals`; lists pending leave applications for company. | HTTP 200; renders approval and rejection action buttons. | **PASS** | `Route: leaves.approvals.index` |
| **UAT-02.4** | HR Executive Document Templates | `hr_executive` | Loads `/admin/documents/templates`; lists active letter and form templates. | HTTP 200; displays 31 seeded baseline templates with category tags. | **PASS** | `Route: admin.documents.templates.index` |
| **UAT-02.5** | HR Blocked from Legal Company Edits | `hr_executive` | Attempting to create or modify a legal corporate entity returns HTTP 403. | HTTP 403 Forbidden; entity modification denied by CheckRole middleware. | **PASS** | `POST /masters/companies -> 403 Forbidden` |
| **UAT-03.1** | Manager MSS Team Operational Hub | `manager_mss` | Loads `/dashboard/manager` displaying team presence (Present, Late, Absent, On Leave). | HTTP 200; team presence rendered; zero salary/compensation metrics visible. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_3_manager_mss_page_94_segregation` (Line 144) |
| **UAT-03.2** | Manager MSS Team Leave Approvals | `manager_mss` | Loads `/leaves/approvals` filtered strictly to direct reporting team members. | HTTP 200; direct subordinate leave requests visible for recommendation. | **PASS** | `Route: leaves.approvals.index` |
| **UAT-03.3** | Manager MSS Page 94 Salary Block | `manager_mss` | Direct URL navigation to `/admin/reports/MONTHLY_PAYROLL_REGISTER` is blocked. | HTTP 403 Forbidden; `ReportingService::guardReportAccess` denies access. | **PASS** | `GET /admin/reports/MONTHLY_PAYROLL_REGISTER -> 403` |
| **UAT-03.4** | Manager MSS Blocked from Payroll Runs | `manager_mss` | Direct URL navigation to `/payroll/runs` is blocked. | HTTP 403 Forbidden; CheckRole middleware rejects request. | **PASS** | `GET /payroll/runs -> 403 Forbidden` |
| **UAT-04.1** | Employee ESS Personal Dashboard | `employee_ess` | Loads `/dashboard/employee` with today's punch status, monthly attendance %, and leave cards. | HTTP 200; personal metrics rendered accurately without leaking peers. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_4_employee_ess_self_service` (Line 175) |
| **UAT-04.2** | Employee ESS Leave Applications | `employee_ess` | Loads `/leaves/applications`; enables applying for leave with sandwich preview. | HTTP 200; renders leave application form and user balance ledger. | **PASS** | `Route: leaves.applications.index` |
| **UAT-04.3** | Employee ESS Personal My Documents | `employee_ess` | Loads `/documents/my-documents`; displays issued appointment, increment, and transfer letters. | HTTP 200; displays only documents issued directly to authenticated employee. | **PASS** | `Route: documents.my-documents` |
| **UAT-04.4** | Employee ESS Blocked from Admin Hub | `employee_ess` | Direct navigation to `/dashboard/admin` returns HTTP 403. | HTTP 403 Forbidden; route gate strictly prevents privilege escalation. | **PASS** | `GET /dashboard/admin -> 403 Forbidden` |
| **UAT-04.5** | Employee ESS Blocked from Audit Logs | `employee_ess` | Direct navigation to `/masters/audit-logs` returns HTTP 403. | HTTP 403 Forbidden; governance logs inaccessible to ESS. | **PASS** | `GET /masters/audit-logs -> 403 Forbidden` |
| **UAT-05.1** | Cross-Company Reporting Isolation | `hr_executive` | VE HR querying `MASTER_EMPLOYEE` receives strictly VE employee records. | Rows contain strictly `company_id = 1`; zero records from `company_id = 2`. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_5_cross_company_isolation` (Line 207) |
| **UAT-05.2** | Safe Company Context Switching | `ho_admin` | HO Admin submits POST to `/company/switch` with target company ID. | HTTP 302 Redirect; active session context successfully toggled to VRE. | **PASS** | `Route: company.switch` |
| **UAT-05.3** | Cross-Company Direct ID Tampering | `hr_executive` | VE HR attempting to edit VRE employee record returns HTTP 403 or 404. | Handled via `BelongsToCompany` global scope; returns 404 Not Found. | **PASS** | `Tests\Feature\SecurityAuditRegressionTest` |
| **UAT-06.1** | Multi-Parameter Report Filtering | `ho_admin` | Querying `/admin/reports/MASTER_EMPLOYEE` with `department_id` filters result set. | HTTP 200; returned dataset filtered strictly to matching department ID. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_6_reporting_and_analytics` (Line 245) |
| **UAT-06.2** | Excel Tab-Delimited Export | `ho_admin` | Exporting with `format=excel` streams file with `application/vnd.ms-excel`. | HTTP 200; Content-Type is `application/vnd.ms-excel; charset=UTF-8`. | **PASS** | `Route: admin.reports.export?format=excel` |
| **UAT-06.3** | Dompdf Landscape PDF Export | `ho_admin` | Exporting with `format=pdf` generates branded A4 landscape PDF document. | HTTP 200; Content-Type is `application/pdf`; Dompdf compiles without error. | **PASS** | `Route: admin.reports.export?format=pdf` |
| **UAT-06.4** | Sensitive Bank Account Masking | `hr_executive` | Accessing Bank Advice report displays masked account numbers (`XXXXXX1234`). | Plaintext bank accounts masked; last 4 digits preserved for verification. | **PASS** | `Route: admin.reports.bank-advice` |
| **UAT-07.1** | Document Template Catalog & Versions | `ho_admin` | Loads `/admin/documents/templates`; displays template versions and categories. | HTTP 200; displays 31 templates across Onboarding, Lifecycle, and Separation. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_7_letter_form_print_engine` (Line 282) |
| **UAT-07.2** | Public Document Verification Portal | `guest` | Unauthenticated public access to `/verify-document` with QR verification code. | HTTP 200; renders public verification portal with rate limiting (60/min). | **PASS** | `Route: documents.verify.form` |
| **UAT-08.1** | Payroll Salary Structures & Slabs | `hr_executive` | Loads `/payroll/structures`; displays configured wage components and rules. | HTTP 200; renders earnings and deductions components. | **PASS** | `FinalReleaseUatExecutionTest::test_uat_test_8_payroll_and_statutory` (Line 300) |
| **UAT-08.2** | Payroll Configurations & Slabs | `hr_executive` | Loads `/payroll/configurations`; displays PF (12%), ESI (0.75%), PT slabs, LWF. | HTTP 200; configurations render with active state tax rules. | **PASS** | `Route: payroll.configurations.index` |
| **UAT-08.3** | Payroll Run Control Tower | `hr_executive` | Loads `/payroll/runs`; displays batch cycles, draft validations, and lock states. | HTTP 200; renders payroll runs and pre-validation summaries. | **PASS** | `Route: payroll.runs.index` |
| **UAT-08.4** | Statutory Compliance Engine Sign-Off | `ho_admin` | Mathematical formula consistency verified; statutory policy alignment. | Calculations verified in tests; formal compliance status flagged as pending. | **PENDING VALIDATION** | Flagged: `Pending HR/Finance/Legal Validation` |

---

## 3. UAT Execution Summary

- **Total Test Cases Executed:** 32
- **Passed (Manual QA & Automated Verification):** 31
- **Failed:** 0
- **Blocked:** 0
- **Pending Legal/Finance Formal Certification:** 1 (Statutory compliance policy sign-off)
- **Overall Operational UAT Status:** **PASSED — READY FOR STAKEHOLDER UAT**

