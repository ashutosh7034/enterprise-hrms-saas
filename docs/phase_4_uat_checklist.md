# Enterprise HRMS — Phase 4 User Acceptance Testing (UAT) Checklist

**Test Environment**: Local Staging (`enterprise_hrms`)  
**Target Roles**: HO Admin (`ho_admin`), HR Executive (`hr_executive`), Manager MSS (`manager_mss`), Employee ESS (`employee_ess`)  
**Status**: UAT VERIFIED

---

## 1. Employee Self-Service (ESS) Scenarios

| Test ID | Role | Preconditions | Steps | Test Data | Expected Result | Actual Result | Status | Remarks |
|---|---|---|---|---|---|---|---|---|
| **UAT-ESS-01** | `employee_ess` | Logged in as ESS user with linked employee record | 1. Navigate to `/leaves/my-balances`<br>2. Inspect quota cards<br>3. Inspect transaction ledger | Logged in user: `emp@vishal.com` | Renders allocated leave quotas (CL, SL, PL) and recent ledger transactions | Quotas rendered cleanly with breakdown | **PASS** | Responsive card design confirmed |
| **UAT-ESS-02** | `employee_ess` | Has available CL quota | 1. Navigate to `/leaves/applications/create`<br>2. Select Casual Leave<br>3. Select Friday to Monday dates<br>4. Inspect live sandwich preview | Start: `2026-11-13` (Fri)<br>End: `2026-11-16` (Mon) | AJAX preview calculates 4 payable days including weekend, explaining sandwich policy rules | Preview table rendered with reasons | **PASS** | Intervening weekend highlighted |
| **UAT-ESS-03** | `employee_ess` | Application submitted | 1. Submit application<br>2. Navigate to application details<br>3. Click "Withdraw Application" | Reason: "Personal work" | Application status becomes 'Withdrawn' and reserved balance is released immediately | Reserved quota released back to available | **PASS** | Double deduction prevented |
| **UAT-ESS-04** | `employee_ess` | Verified attendance record exists on weekly off | 1. Navigate to `/leaves/compoff/create`<br>2. Select off-day attendance record<br>3. Enter duty remarks<br>4. Submit claim | Att Date: Sunday, 480 mins worked | Comp-Off request submitted with status 'Pending Approval' | Claim submitted to HR queue | **PASS** | Validates >= 240 mins threshold |

---

## 2. Manager Self-Service (MSS) Scenarios

| Test ID | Role | Preconditions | Steps | Test Data | Expected Result | Actual Result | Status | Remarks |
|---|---|---|---|---|---|---|---|---|
| **UAT-MSS-01** | `manager_mss` | Subordinate submitted leave request | 1. Log in as `manager@vishal.com`<br>2. Navigate to `/leaves/approvals`<br>3. Review team request | App: `LA-VE-2026-xxxxx` | Application renders with sandwich breakdown and employee details | Request displayed in approval hub | **PASS** | Direct report filtering verified |
| **UAT-MSS-02** | `manager_mss` | Reviewing request | 1. Click "Review & Decide"<br>2. Click "Manager Action"<br>3. Choose "Approve" with remarks<br>4. Submit decision | Remarks: "Approved for project schedule" | Status transitions to 'Pending HR', approval step recorded in audit trail | Routed to HR Executive queue | **PASS** | Approver identity stamped |
| **UAT-MSS-03** | `manager_mss` | Reviewing request | 1. Choose "Reject"<br>2. Enter remarks<br>3. Confirm | Remarks: "Critical client milestone delivery" | Status transitions to 'Rejected' and reserved balance is released | Reserved quota released | **PASS** | Audit trail logs decision |

---

## 3. HR Executive Scenarios

| Test ID | Role | Preconditions | Steps | Test Data | Expected Result | Actual Result | Status | Remarks |
|---|---|---|---|---|---|---|---|---|
| **UAT-HR-01** | `hr_executive` | Logged in as HR | 1. Navigate to `/leaves/types`<br>2. Click "New Leave Type"<br>3. Enter code, annual quota, accrual rate<br>4. Save | Code: `WL`, Name: "Wellness Leave", Quota: 6d | Leave type registered and scoped to company | Successfully created and listed | **PASS** | Duplicate code blocked |
| **UAT-HR-02** | `hr_executive` | Active company context | 1. Navigate to `/leaves/holidays/create`<br>2. Enter holiday name, date, type<br>3. Select site applicability<br>4. Save | Name: "Founders Day", Date: `2026-12-01` | Holiday added to calendar and integrated with attendance muster | Displayed on holiday calendar | **PASS** | Site scoping supported |
| **UAT-HR-03** | `hr_executive` | Uncredited calendar month | 1. Navigate to `/leaves/accrual`<br>2. Select Year: 2026, Month: 11<br>3. Click "Run Monthly Accrual" | Month: November 2026 | Eligible employees credited with monthly rate in immutable ledger; batch logged | Batch completed successfully | **PASS** | Idempotency confirmed |
| **UAT-HR-04** | `hr_executive` | Active employee with adjustment need | 1. Navigate to `/leaves/balances`<br>2. Click "Manual Adjustment"<br>3. Choose Credit, 2.0 days with reason | Reason: "Relocation comp adjustment" | Balance credited and transaction recorded in immutable audit ledger | Transaction visible in ledger | **PASS** | Full audit visibility |
| **UAT-HR-05** | `hr_executive` | Month-end payroll closure | 1. Navigate to `/leaves/period-locks`<br>2. Select 2026-11, click "Lock Period" | Month: November 2026 | Period locked; applications and balance adjustments in Nov 2026 blocked | Period locked successfully | **PASS** | HR lock enforced |

---

## 4. Head Office (HO) Admin Scenarios

| Test ID | Role | Preconditions | Steps | Test Data | Expected Result | Actual Result | Status | Remarks |
|---|---|---|---|---|---|---|---|---|
| **UAT-HO-01** | `ho_admin` | Logged in as HO Admin | 1. Switch company context between `VE` and `VRE`<br>2. Verify company-specific leave balances and applications | Switcher dropdown | Only data for the active company is accessible | Strict company tenancy isolation | **PASS** | Tenant boundary verified |
| **UAT-HO-02** | `ho_admin` | Leave period is locked | 1. Navigate to `/leaves/period-locks`<br>2. Click "HO Unlock"<br>3. Enter mandatory executive justification<br>4. Confirm | Justification: "Authorized audit correction for emergency medical leave" | Period unlocked; justification permanently archived in `leave_period_unlock_histories` | Period unlocked with full history | **PASS** | Non-empty reason required |
| **UAT-HO-03** | `ho_admin` | Year-end transition preview | 1. Navigate to `/leaves/accrual`<br>2. Select 2025 to 2026, Dry-Run checked<br>3. Process | Dry-Run: Yes | Generates carry-forward simulation without modifying live database balances | Simulation report displayed | **PASS** | Artisan command parity verified |

