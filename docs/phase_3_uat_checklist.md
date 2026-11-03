# Enterprise HRMS — Phase 3 User Acceptance Testing (UAT) Checklist

**Test Environment**: Local Development / Pre-Production  
**Base URL**: `http://127.0.0.1:8000`  
**Companies**: Vishal Enterprise (`VE`), Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Target Roles**: `ho_admin`, `hr_executive`, `manager_mss`, `employee_ess`

---

## 1. Role: HR Executive (`hr_executive`)

| Step # | Module / Action | Input / Action | Expected Result | Verified |
|---|---|---|---|---|
| **ATT-01** | Shift Schedule Creation | Navigate to `/shifts/create`. Enter Code `S-GEN2`, Start: `08:30`, End: `17:30`, Grace: `15m`, Break: `45m`. Select Sunday weekly off. Submit. | Shift is registered in Shift Master with timing indicator and thresholds. | [x] |
| **ATT-02** | Overnight Shift Schedule | Navigate to `/shifts/create`. Enter Start: `22:00`, End: `06:30`. Toggle "Cross-Day / Overnight Shift". Submit. | Shift displays with `(+1 day)` indicator and overnight calculations. | [x] |
| **ATT-03** | Date-Wise Shift Roster | Navigate to `/shifts-roster`. Open "Assign Roster" modal. Select employee, date range, and shift. Submit. | Roster matrix displays assigned shift badge across selected date columns. | [x] |
| **ATT-04** | Biometric Terminal Registry | Navigate to `/biometric-devices/create`. Enter identifier `PN-P1-GATE-02`, serial `ZK-10992`, select Site Location. Submit. | Terminal displays in device registry with healthy heartbeat indicator. | [x] |
| **ATT-05** | Raw Punch Monitor | Navigate to `/biometric-punch-logs`. Inspect incoming punch stream. Filter by direction (`IN`/`OUT`) or date. | Records render with original punch timestamps, SHA-256 fingerprints, and processing status. | [x] |
| **ATT-06** | Daily Attendance Muster | Navigate to `/attendance`. Pick a target date. Click "Recalculate Muster". | All active employees have deterministic daily attendance status, total work duration, and late/early flags computed. | [x] |
| **ATT-07** | Detailed Punch Audit | On an attendance record in `/attendance`, click "Detail". | Inspect visual punch timeline, break deductions, and shift comparison. | [x] |
| **ATT-08** | Overtime Approval | Navigate to `/overtime`. Locate a pending OT record. Click "Approve". Enter approved minutes (or accept 15-min block rounded minutes). Submit. | Record status updates to `Approved`. Daily muster reflects approved overtime minutes. | [x] |
| **ATT-09** | Request Plant Shutdown | Navigate to `/plant-shutdowns/create`. Select Plant Location, Planned Maintenance type, date range, and reason. Submit. | Shutdown is submitted in `Pending Approval` status. | [x] |
| **ATT-10** | Nominate Maintenance Crew | In `/plant-shutdowns/{id}`, click "Nominate Working Crew". Select working employee, date, and compensation type. | Crew member is added to the nominated maintenance crew table. | [x] |
| **ATT-11** | HR Regularization Approval | Navigate to `/regularization`. Locate pending request in `Pending HR` or `Pending Manager`. Click "HR Final Approve". | Request status updates to `Approved`. Daily muster updates immediately to `Regularized` (or `On Duty`) with recalculated work hours. | [x] |

---

## 2. Role: Head Office Administrator (`ho_admin`)

| Step # | Module / Action | Input / Action | Expected Result | Verified |
|---|---|---|---|---|
| **HO-01** | Approve Plant Shutdown | In `/plant-shutdowns/{id}` with `Pending` status, click "Approve Shutdown". | Shutdown transitions to `Approved`. Daily muster for all employees in scope automatically marks unpunched employees as `Site Shutdown`. | [x] |
| **HO-02** | Cancel Plant Shutdown | In `/plant-shutdowns/{id}`, click "Cancel Shutdown". Enter mandatory justification in modal. Confirm. | Shutdown transitions to `Cancelled`. System automatically triggers muster recalculation, reversing `Site Shutdown` back to normal muster. | [x] |
| **HO-03** | Compliance Radar Review | Navigate to `/attendance-reconciliation`. Pick target date. | 9-Metric KPI grid renders expected vs worked, missing punches, duplicate counts, and active audit discrepancy alerts. | [x] |
| **HO-04** | Reconcile Exception Record | On an unreconciled missing punch record in `/attendance-reconciliation`, click "Resolve". Enter audit remarks. Submit. | Record displays as `Reconciled` with audit remarks preserved. | [x] |

---

## 3. Negative Security & Boundary Verification

| Step # | Role Tested | Action | Expected Result | Verified |
|---|---|---|---|---|
| **SEC-01** | Unauthenticated | Send HTTP POST to `/api/biometric/webhook` without `X-Device-Token`. | **HTTP 401 Unauthorized**. Payload rejected. | [x] |
| **SEC-02** | Any Role | Submit duplicate biometric punch with identical fingerprint. | Duplicate flagged as `Ignored_Duplicate` without dropping transaction or throwing SQL error. | [x] |
| **SEC-03** | Employee / HR | Submit attendance regularization for a future date. | **HTTP 422 Validation Error**. Future date requests blocked. | [x] |
| **SEC-04** | Company 1 Admin | Attempt to reconcile attendance record belonging to Company 2. | **HTTP 404 Not Found**. Cross-company action blocked. | [x] |
| **SEC-05** | Any Role | Calculate daily attendance multiple times for the same date. | **Strictly Idempotent**. Database contains exactly 1 unique record per employee/date. | [x] |
| **SEC-06** | Any Role | Direct SQL update or delete on `audit_logs`. | **Database Trigger Halts Mutation**. Immutability guaranteed. | [x] |

