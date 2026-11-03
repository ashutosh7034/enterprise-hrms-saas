# Phase 8 User Acceptance Testing (UAT) Checklist
**Enterprise Human Resource Management System (HRMS)**  
**Enterprise Reporting, Role Dashboards & Final System Polish**  
**Date:** November 2, 2026  
**Document Reference:** `DOCS-PH8-UAT-001`  

---

## 1. UAT Scope & Test Personas

This User Acceptance Testing (UAT) protocol validates end-to-end functionality across five distinct enterprise personas operating across **Vishal Enterprise (`VE`)** and **Vrishal Eng. Pvt. Ltd. (`VRE`)**:

1. **HO Admin (`ho_admin`)**: Global organizational executive with complete visibility and unmasked financial authorization.
2. **HR Executive (`hr_executive`)**: Plant-level human resources officer managing operational onboarding, leaves, and muster rolls.
3. **Manager MSS (`manager_mss`)**: Department line manager supervising ground staff with strict Page 94 salary segregation.
4. **Employee ESS (`employee_ess`)**: Individual workforce member checking personal attendance, leave balances, and payslips.
5. **Finance Officer**: Corporate payroll and treasury delegate reconciling disbursements and bank transfer advices.

---

## 2. Test Scenarios by Persona

### Persona 1: HO Admin (`ho_admin`)
| Step # | Action / Test Case | Expected Result | Acceptance Criteria | Status |
|:---:|:---|:---|:---|:---:|
| 1.1 | Login as HO Admin and navigate to `/dashboard/admin`. | Executive dashboard renders total active workforce, monthly gross payroll, entity counts, Chart.js department donut, and 6-month joiner trend. | Aggregations reflect real MySQL data without errors. | **PASSED** |
| 1.2 | Navigate to `/admin/reports` (Central Reporting Hub). | Catalog renders 53 reports categorized under 7 domain headers with real-time search input. | All domain cards render with descriptions and action buttons. | **PASSED** |
| 1.3 | View "Employee Master Directory" (`/admin/reports/view/employee_master`). | Report table renders with dynamic filter drawer (Department, Designation, Status, Search). | Changing department filter updates row count and table dynamically. | **PASSED** |
| 1.4 | Click "Export CSV" on Employee Master report. | File downloads as `.csv`. Opening in Microsoft Excel preserves UTF-8 characters without corruption. | UTF-8 BOM present; memory stays bounded. | **PASSED** |
| 1.5 | Click "Export PDF" on Employee Master report. | File downloads as A4 Landscape PDF with corporate headers, pagination, and confidentiality footer. | Layout renders crisply without truncated columns. | **PASSED** |
| 1.6 | Switch active company from `VE` to `VRE` using topbar switcher. | Dashboard and report views instantly refresh to show strictly `VRE` workforce and financial data. | Zero cross-company record leakage. | **PASSED** |

---

### Persona 2: HR Executive (`hr_executive`)
| Step # | Action / Test Case | Expected Result | Acceptance Criteria | Status |
|:---:|:---|:---|:---|:---:|
| 2.1 | Login as HR Executive and navigate to `/dashboard/hr`. | HR dashboard renders operational queues: Pending eKYC, Probations ending in 30 days, Pending Leave Approvals, and Attendance Exceptions. | Queue cards link directly to respective resolution screens. | **PASSED** |
| 2.2 | Access "Monthly Attendance Muster Roll" (`/admin/reports/view/monthly_attendance_muster`). | Report renders month-to-date attendance records with present/late/absent counts. | Summary KPI cards calculate total worked hours and late counts. | **PASSED** |
| 2.3 | Navigate to `/admin/reports/schedules` and create a scheduled report. | Modal opens. Enter Report Type, Frequency (`Weekly`), Time (`08:00`), and recipient emails. | Schedule appears in active schedules table with `next_run_at` calculated. | **PASSED** |
| 2.4 | Click "Run Now" on an active scheduled report. | Background command executes instantly. Status badge confirms successful generation. | Log appears in `scheduled_report_logs`. | **PASSED** |
| 2.5 | Navigate to `/admin/reports/history`. | Compliance audit table renders recent export actions, IP addresses, format, and row counts. | Actions are non-editable and non-deletable. | **PASSED** |

---

### Persona 3: Manager MSS (`manager_mss` — Page 94 Compliance)
| Step # | Action / Test Case | Expected Result | Acceptance Criteria | Status |
|:---:|:---|:---|:---|:---:|
| 3.1 | Login as Manager MSS and navigate to `/dashboard/manager`. | Team presence hub displays: Team Total, Today's Present, Late Arrivals, Absent, and On Leave. | ZERO salary, payroll, or compensation fields are rendered. | **PASSED** |
| 3.2 | Verify Team Approval queues on Manager Dashboard. | Pending team leave requests and team regularizations render with quick review actions. | Only direct reports appear in the queues. | **PASSED** |
| 3.3 | Attempt to directly access `/admin/reports` via browser address bar. | System returns HTTP 403 Forbidden. | Non-administrative role strictly blocked by route middleware. | **PASSED** |
| 3.4 | Attempt to directly access `/admin/reports/view/payroll_master_register`. | System returns HTTP 403 Forbidden. | Manager cannot view compensation registers under any circumstances. | **PASSED** |

---

### Persona 4: Employee ESS (`employee_ess`)
| Step # | Action / Test Case | Expected Result | Acceptance Criteria | Status |
|:---:|:---|:---|:---|:---:|
| 4.1 | Login as Employee ESS and navigate to `/dashboard/employee`. | Personal portal displays: Today's punch status (Clock In / Clock Out times) and current month's attendance %. | Accurately calculates attendance percentage based on worked days. | **PASSED** |
| 4.2 | Inspect Personal Leave Balances card. | Real-time balances for Casual Leave (CL), Sick Leave (SL), and Earned Leave (EL) render. | Balances match approved leave deductions. | **PASSED** |
| 4.3 | Click "Download Latest Payslip" button. | Directs to `/payroll/payslips/{id}` for the most recent finalized payroll cycle. | Displays formatted payslip without accessing other employees' slips. | **PASSED** |
| 4.4 | Inspect "Allocated Company Assets" section. | Shows company laptops, ID badges, or safety tools currently allocated to the employee. | Displays serial number and allocation date. | **PASSED** |

---

### Persona 5: Finance & Compliance Officer
| Step # | Action / Test Case | Expected Result | Acceptance Criteria | Status |
|:---:|:---|:---|:---|:---:|
| 5.1 | Navigate to Bank Advice Report (`/admin/reports/bank-advice`). | NEFT/RTGS salary transfer register renders with total disbursement sum and employee count. | Bank account numbers appear masked by default (`XXXXXX1234`). | **PASSED** |
| 5.2 | Check "Show Unmasked Account Numbers" checkbox and click Filter. | As an authorized finance/admin role, account numbers appear in plaintext for bank file upload. | Unmasked view permitted only for authorized roles. | **PASSED** |
| 5.3 | Navigate to EPF Form 12A Statement (`/admin/reports/view/epf_form_12a`). | Computes employee 12%, employer EPS (8.33%), and EPF (3.67%) on capped ₹15,000 wages. | Calculations conform to EPFO statutory specifications. | **PASSED** |
| 5.4 | Navigate to ESI Form 5 Statement (`/admin/reports/view/esi_form_5`). | Computes employee 0.75% and employer 3.25% on gross wages for eligible employees. | Total contribution balances conform to ESIC guidelines. | **PASSED** |

---

## 3. UAT Execution Summary & Sign-Off

All 23 test scenarios across the 5 personas executed successfully. The reporting system, role-based dashboards, and data privacy features adhere strictly to enterprise architectural guidelines.

- **Total Test Cases:** 23
- **Passed:** 23
- **Failed:** 0
- **UAT Sign-Off Status:** FULLY ACCEPTED & VERIFIED

