# Enterprise HRMS — Phase 3 Unresolved Business Clarifications & Policy Assumptions

**Document Purpose**: Record configurable business rules, third-party biometric hardware dependencies, and statutory assumptions established during Phase 3 that require review or customization from executive stakeholders prior to production deployment.

---

## 1. Overtime (OT) Eligibility by Worker Category
- **Status in Phase 3**:
  - Implemented with configurable rounding increments (default: 15-minute floor blocks: e.g., 175 minutes rounds to 165 minutes).
  - All calculated OT records are routed to `overtime_records` with status `Pending` for managerial review and approval before export to Phase 5 Payroll.
- **Clarification Required from HR/Management**:
  - Are managerial and executive categories (`Executive`, `Manager`, `HOD`) strictly exempted from overtime pay, or do they receive compensatory off (Comp-Off) credit instead of monetary overtime?
  - Currently, overtime is calculated for all active employees who exceed their shift's full-day threshold, allowing managers to approve or reject based on category eligibility during review.

---

## 2. Shutdown Compensation Leave Adjustment Priority
- **Status in Phase 3**:
  - Plant shutdown master allows selecting:
    1. `Paid Shutdown` (Standard company-paid stoppage)
    2. `Adjust Against Leave` (Deducts leave balance)
    3. `Unpaid Shutdown` (Loss of Pay)
    4. `Comp-Off for Duty` (For maintenance crew working during shutdown)
- **Clarification Required from HR / Operations**:
  - When `Adjust Against Leave` is selected: Which leave bucket should be debited first during Phase 4 Leave Engine processing (e.g., Casual Leave first, or Paid Leave / Earned Leave first)?
  - What happens if an employee has zero leave balance remaining? Does the excess automatically convert to Paid Shutdown or Unpaid LOP?

---

## 3. Hardware Biometric Vendor SDK / Communication Protocol
- **Status in Phase 3**:
  - Implemented via a protocol-agnostic integration architecture (`BiometricIntegrationService`), featuring:
    1. Direct REST Webhook endpoint (`/api/biometric/webhook`) with secret token authentication.
    2. CLI Batch Ingest command (`php artisan biometric:ingest`).
    3. Device registry supporting `ZKTeco Push / ADMS`, `Direct IP Poll`, `REST Webhook`, `SOAP / XML`, and `Manual CSV Import`.
- **Clarification Required from Plant IT**:
  - Which physical terminal models (e.g. ZKTeco SilkBio-101TC, eSSL uFace, Realtime T502, Hikvision) will be deployed at the factories?
  - Will terminals communicate directly via cloud push HTTP webhooks, or will an on-premises middleware / polling agent bridge local LAN device events to the cloud HRMS?

---

## 4. Single Missing Punch Penalty Rules
- **Status in Phase 3**:
  - If an employee has only an IN punch or only an OUT punch, the attendance engine flags the record as `Missing Punch` with 0 actual worked minutes, and routes the exception to the Attendance Regularization and HO Reconciliation queues.
- **Clarification Required**:
  - If an employee fails to submit regularization within the payroll cutoff window (e.g., 3 days), should the system automatically award Half-Day credit, or mark the entire day as Absent / LOP?
  - Currently, unreconciled single punches remain in `Missing Punch` status for executive decision in Phase 5 Pre-Payroll validation.

