# Enterprise HRMS — Phase 3 Implementation Report
## Attendance Engine, Shift Management, Plant Shutdowns, Biometric Integration & Regularization

**System**: Enterprise Human Resource Management System (HRMS)  
**Entities**: Vishal Enterprise (`VE`) & Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Technology Stack**: Laravel 13 (`13.39.0`), PHP 8.3 (`8.3.36`), MySQL 8.0.46 (Target MySQL 8.4 LTS), Blade, Bootstrap 5, JavaScript  
**Execution Date**: November 2, 2026  
**Phase 3 Status**: **100% COMPLETE & VERIFIED** (71 Tests, 351 Assertions Passing)

---

## 1. Executive Summary

Phase 3 of the Enterprise HRMS has been successfully implemented, audited, and verified against all industrial plant requirements specified in the primary BRD. Phase 3 equips the enterprise with an industrial-grade, deterministic attendance calculation engine, hardware-agnostic biometric staging with duplicate punch preservation, flexible shift scheduling (including cross-midnight overnight shifts and grace windows), plant shutdown safeguards with supervisor crew nominations, multi-tier attendance regularization, and an executive Head Office compliance and reconciliation hub.

All 8 database migrations (Migrations 24–31) were executed additively, maintaining 100% backward compatibility with Phase 1 and Phase 2 data and strict forward compatibility with MySQL 8.4 LTS.

---

## 2. Core Modules Implemented

### 2.1 Shift Master & Employee Shift Roster
- **Shift Master**:
  - Supports Day, Evening, Rotational, and Overnight (cross-midnight, +1 day) working schedules.
  - Granular parameters: `start_time`, `end_time`, `break_duration_minutes`, `grace_period_minutes`, `half_day_threshold_minutes`, `full_day_threshold_minutes`, `weekly_off_days` (JSON array), and `effective_from`/`effective_to` dates.
  - Multi-level applicability scopes: Location/Site, Department, Category, or Company-Wide.
- **Roster & Shift Assignment**:
  - Date-wise calendar roster (`employee_shift_rosters`) with unique constraints `(company_id, employee_id, date)` preventing overlapping shifts.
  - Default recurring shift assignment (`employee_shift_assignments`) with automated historical superseding.
  - Three-tier effective shift resolution hierarchy:
    1. Date-specific Roster $\rightarrow$ 2. Active Recurring Assignment $\rightarrow$ 3. Company Default Active Shift.

### 2.2 Biometric Device Master & Raw Punch Staging
- **Protocol-Agnostic Device Registry**:
  - Hardware terminal registry (`biometric_devices`) tracking serial numbers, site mapping, IP/port, device types (Fingerprint, Facial, Multi-Modal), and protocols (REST Webhook, ZKTeco Push/ADMS, Direct IP Poll, SOAP).
  - Terminal health monitor tracking heartbeat timestamps, synchronization latency, and connection status (`Healthy`, `Warning`, `Offline`).
- **Raw Punch Staging (`biometric_punch_logs`)**:
  - Preserves original device punch timestamp (`punch_time`), distinct from contemporary ingestion time (`sync_time`). Delayed punches are correctly processed against their original timestamp.
  - Cryptographic duplicate detection: Computes a deterministic SHA-256 fingerprint (`company:user_id:punch_time:direction`). Duplicate punches are flagged as `is_duplicate = true` and `processing_status = 'Ignored_Duplicate'` without dropping data or halting batch transactions.
  - Error isolation & Reprocessing: Unmapped punches (unknown employee ID) are retained in `Error` status and can be reprocessed in batch once employee badges or gate passes are mapped.
  - Dual ingestion channels:
    1. Authenticated REST Webhook: `POST /api/biometric/webhook` with token authentication (`X-Device-Token`).
    2. CLI Command: `php artisan biometric:ingest {--company=} {--employee=} {--time=} {--simulate} {--file=}`.

### 2.3 Deterministic Attendance Calculation Engine
- **Service Architecture**: Implemented in [`AttendanceCalculationService`](file:///c:/Users/Ashutosh%20Pandey/Downloads/HRMS_product/app/Services/AttendanceCalculationService.php).
- **Guaranteed Idempotency**: Running calculation repeatedly for any date/employee combination yields identical muster without duplicate rows or drifted counters.
- **Punch Pairing & Metric Derivations**:
  - First punch = `first_in`, Last punch = `last_out`.
  - Computes `total_work_minutes`, deducts shift break duration to derive `actual_worked_minutes`.
  - Evaluates grace period: flags `late_arrival_minutes` if check-in exceeds `start_time + grace_period_minutes`.
  - Evaluates early check-out: flags `early_departure_minutes` if check-out precedes scheduled `end_time`.
- **Comprehensive Status Evaluation**:
  - `Present`: Worked minutes $\ge$ full day threshold.
  - `Half Day`: Worked minutes $\ge$ half day threshold.
  - `Absent`: Zero punches on working day.
  - `Missing Punch`: Single punch captured (only IN or only OUT), queued for regularization.
  - `Weekly Off`: Non-working day without punches.
  - `Site Shutdown`: Approved plant shutdown without punches (wages protected).
  - `Worked During Shutdown`: Valid punches during approved shutdown (eligible for shutdown OT).
  - `Regularized` / `On Duty`: Approved attendance regularization.

### 2.4 Overtime Calculation Engine
- Evaluates overtime on Normal, Holiday, Weekly Off, and Shutdown days.
- Normal day OT = `actual_worked_minutes - full_day_threshold`.
- Holiday / Weekly Off / Shutdown OT = 100% of `actual_worked_minutes`.
- **Configurable Block Rounding**: Applies floor block rounding (e.g., 15-minute increments: 175 minutes rounds to 165 minutes).
- **Approval Workflow**: Manager/HR approval interface with minute adjustment before Phase 5 Payroll handoff.

### 2.5 Plant & Site Shutdown Safeguards
- **Shutdown Master**:
  - Declares shutdowns for Planned Maintenance, Emergency, Power Failure, or Raw Material Shortage.
  - Mapped to Site (Location), optional Department, and optional Worker Category.
- **Safe Attendance Processing**:
  - Normal workforce without punches are safeguarded: marked as `Site Shutdown` (preventing false LOP/Absence).
  - Nominated maintenance crew who punch are marked as `Worked During Shutdown` and routed to shutdown compensation.
- **Shutdown Cancellation & Recalculation**:
  - Cancelling an approved shutdown requires mandatory justification and triggers an automatic muster recalculation reversing `Site Shutdown` flags back to standard muster.

### 2.6 Multi-Level Attendance Regularization
- **Regularization Types**: Missing IN Punch, Missing OUT Punch, Late Arrival, Early Departure, Business Travel / On Duty (OD), Biometric Device Failure, Attendance Correction.
- **Workflow**:
  - Employee/HR applies with mandatory justification. Future date applications are hard-blocked.
  - Manager Review: Pass / Reject / Send Back.
  - HR/HO Final Approval: Automatically updates `AttendanceRecord` muster, sets status to `Regularized` (or `On Duty`), recalculates work minutes, and writes immutable audit trail in `audit_logs`.

### 2.7 Head Office (HO) Team Attendance Compliance & Reconciliation
- **9-Metric Reconciliation Dashboard**:
  - Expected Workforce, Employees Worked, Absent, Missing Punches, Duplicate Punches Filtered, Device Sync Errors, Site Shutdowns, Worked During Shutdown, Pending Regularizations & Pending OT.
- **Audit Discrepancy Radar**:
  - Alerts on unmapped punches, single punch exceptions, and unexpected attendance.
- **Exception Resolution Queue**:
  - Allows HO Team users to reconcile exceptions with mandatory audit remarks.

---

## 3. Registered Routes & Endpoints

| Endpoint URI | HTTP Method | Action / Purpose | Middleware / RBAC |
|---|---|---|---|
| `api/biometric/webhook` | POST | Ingest raw punches from hardware terminals | Token Auth (`X-Device-Token`) |
| `shifts` | GET, POST | Shift Master listing & creation | `auth, active.company, check.role:ho_admin,hr_executive` |
| `shifts/{id}` | GET, PUT, DELETE | View, edit & deactivate shift | `auth, active.company, check.role:ho_admin,hr_executive` |
| `shifts-roster` | GET | Shift Roster Matrix calendar | `auth, active.company, check.role:ho_admin,hr_executive,manager_mss` |
| `shifts-roster/assign` | POST | Assign date-wise shift roster | `auth, active.company, check.role:ho_admin,hr_executive` |
| `shifts-roster/recurring` | POST | Assign recurring default shift | `auth, active.company, check.role:ho_admin,hr_executive` |
| `biometric-devices` | GET, POST | Terminal registry & registration | `auth, active.company, check.role:ho_admin,hr_executive` |
| `biometric-devices/{id}/edit` | GET, PUT | Update device configuration | `auth, active.company, check.role:ho_admin,hr_executive` |
| `biometric-punch-logs` | GET | Monitor raw punch staging | `auth, active.company, check.role:ho_admin,hr_executive` |
| `biometric-punch-logs/reprocess` | POST | Reprocess unmapped punches | `auth, active.company, check.role:ho_admin,hr_executive` |
| `attendance` | GET | Daily Attendance Muster | `auth, active.company, check.role:ho_admin,hr_executive,manager_mss` |
| `attendance/recalculate` | POST | Trigger muster recalculation | `auth, active.company, check.role:ho_admin,hr_executive` |
| `attendance/{id}` | GET | Detailed punch timeline & audit | `auth, active.company, check.role:ho_admin,hr_executive,manager_mss` |
| `overtime` | GET | Overtime approvals queue | `auth, active.company, check.role:ho_admin,hr_executive` |
| `overtime/{id}/approve` | POST | Approve overtime with minutes | `auth, active.company, check.role:ho_admin,hr_executive` |
| `overtime/{id}/reject` | POST | Reject overtime with reason | `auth, active.company, check.role:ho_admin,hr_executive` |
| `plant-shutdowns` | GET, POST | List & request plant shutdown | `auth, active.company, check.role:ho_admin,hr_executive` |
| `plant-shutdowns/{id}` | GET | View shutdown, crew & affected workforce | `auth, active.company, check.role:ho_admin,hr_executive` |
| `plant-shutdowns/{id}/approve` | POST | Approve shutdown & update muster | `auth, active.company, check.role:ho_admin` (HO Only) |
| `plant-shutdowns/{id}/cancel` | POST | Cancel shutdown with reason | `auth, active.company, check.role:ho_admin` (HO Only) |
| `plant-shutdowns/{id}/crew` | POST | Nominate working maintenance crew | `auth, active.company, check.role:ho_admin,hr_executive` |
| `regularization` | GET, POST | List & apply for regularization | `auth, active.company` (All Roles) |
| `regularization/{id}/manager` | POST | Manager review pass / reject / send back | `auth, active.company, check.role:ho_admin,hr_executive,manager_mss` |
| `regularization/{id}/hr` | POST | HR final approval & muster update | `auth, active.company, check.role:ho_admin,hr_executive` |
| `attendance-reconciliation` | GET | HO Compliance & reconciliation hub | `auth, active.company, check.role:ho_admin,hr_executive` |
| `attendance-reconciliation/{id}/reconcile` | POST | Clear exception with audit notes | `auth, active.company, check.role:ho_admin,hr_executive` |

---

## 4. Quality & Compliance Checklist

- [x] Multi-company data isolation enforced across shifts, devices, punches, attendance records, shutdowns, and regularizations.
- [x] All raw biometric punches preserved immutably with SHA-256 fingerprint duplicate protection.
- [x] Calculation engine is 100% deterministic and idempotent.
- [x] Plant shutdown safeguards protect workforce from wrongful absence during authorized outages.
- [x] 100% of Phase 1, 2, and 3 automated tests pass (71 tests, 351 assertions).

