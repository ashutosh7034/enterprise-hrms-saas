# Phase 6 Security & Tenancy Audit: Notification Engine
**Enterprise HRMS — Security Controls, Access Governance & Isolation Report**  
**Date:** November 2, 2026  
**Auditor:** Senior Application Security Architect & Compliance Engineer  
**Database:** MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target Production)  

---

## 1. Security & Compliance Scope

The Phase 6 security audit assessed the multi-channel notification engine across 8 threat vectors:
1. Multi-Company Tenancy & Isolation.
2. User-Specific Notification Authorization (Employee ESS privacy).
3. Role-Based Access Control (RBAC) on Administrative Hubs.
4. Data Minimization & Privacy Protection (Mobile number masking).
5. Safe Provider Execution & Zero Leakage (Mock safety toggles).
6. Idempotency & Replay Attack Prevention.
7. Template Sanitization & Injection Prevention.
8. Audit Logging & Administrative Accountability.

---

## 2. Security Controls & Audit Verification

### 2.1 Multi-Company Tenancy Isolation
- **Mechanism:** `BelongsToCompany` trait is applied to `NotificationChannelConfiguration`, `NotificationTemplate`, `NotificationPreference`, `HrmsNotification`, and `NotificationDeliveryLog`.
- **Global Scope Enforcement:** Route model binding automatically applies `where('company_id', $activeCompanyId)`. When a user attempts to access an entity belonging to another company, Eloquent throws a `404 Not Found` or `403 Forbidden`, preventing identifier enumeration across tenants.
- **Verification:** Tested in `NotificationSecurityAndIsolationTest::test_cross_company_notification_isolation`. A HR Executive in Company `VE` attempting to retry a delivery log in Company `VRE` was strictly blocked.

### 2.2 User-Specific Notification Privacy
- **Threat Vector:** Malicious employees inspecting network traffic or manipulating URLs to view another employee's payslip or disciplinary notifications.
- **Defense-in-Depth:**
  - Queries on `/notifications` and `/notifications/feed` filter strictly by `where('notifiable_id', auth()->id())->where('notifiable_type', User::class)`.
  - The `markAsRead` action validates that the targeted notification ID matches the authenticated user ID before executing updates.
- **Verification:** Verified in `NotificationSecurityAndIsolationTest::test_employee_can_only_access_own_notifications`. An ESS user is unable to view or alter any other employee's notifications.

### 2.3 Role-Based Access Control (RBAC)
- **Route Gate:** Administrative routes under `/admin/notifications/*` are protected with middleware `role:ho_admin,hr_executive`.
- **Enforcement Matrix:**
  - `employee_ess`: Blocked (HTTP 403) from dashboard, logs, templates, and channel settings.
  - `manager_mss`: Blocked (HTTP 403) from administrative notification controls.
  - `hr_executive`: Permitted (HTTP 200) within active company boundary.
  - `ho_admin`: Permitted (HTTP 200) globally across companies.
- **Verification:** Fully asserted in `NotificationSecurityAndIsolationTest::test_rbac_controls_on_admin_notification_endpoints`.

### 2.4 Mobile Number Masking & PII Protection
- **Threat Vector:** Unauthorized exposure of personal phone numbers in application logs or administration tables.
- **Defense:**
  - `MockSmsProvider` and `MockWhatsAppProvider` automatically mask phone numbers before writing to logs: e.g. `+91 98****3344`.
  - Delivery logs record masked addresses for SMS and WhatsApp entries.
- **Verification:** Asserted in `MultiChannelNotificationTest::test_sms_mock_dispatch_and_logging`.

### 2.5 Safe Provider Execution
- **Zero Real Transmission Guarantee:**
  - In `notification_channel_configurations`, `is_mock` defaults to `true`.
  - In `phpunit.xml`, `MAIL_MAILER` is set to `array` and `QUEUE_CONNECTION` is set to `sync`.
  - In `.env`, `MAIL_MAILER` defaults to `log`.
  - No external third-party HTTP requests are made during test execution or local development.

### 2.6 Idempotency & Duplicate Prevention
- **Threat Vector:** Automated event re-firing or network duplicate posts triggering repeated SMS or in-app alerts to employees.
- **Defense:** Unique constraint on `notifications.idempotency_key`. `NotificationService::notify` checks if the key exists before initiating dispatch, returning `'Idempotent Duplicate Ignored'` if already processed.
- **Verification:** Asserted in `MultiChannelNotificationTest::test_notification_idempotency_prevents_duplicate_dispatch`.

### 2.7 Mandatory Policy Enforcement
- **Threat Vector:** Employees attempting to opt out of critical statutory compliance notices (e.g., eKYC deadlines, PF filings, disciplinary notices).
- **Defense:** Templates flagged with `is_mandatory = true` (or categories `Compliance`/`Security`) bypass user preference suppression checks in `NotificationService`.
- **Verification:** Asserted in `MultiChannelNotificationTest::test_mandatory_notification_cannot_be_suppressed_by_preference`.

### 2.8 Audit Logging of Administrative Actions
- All manual retry attempts, template modifications, and channel setting changes are recorded immutably in `audit_logs` using `AuditLogService`.

---

## 3. Security Audit Findings & Final Status

| Audit Check | Status | Verification Detail |
|---|---|---|
| Cross-Company Query Scoping | **PASS** | Blocked via `BelongsToCompany` global scope. |
| Cross-User Information Leakage | **PASS** | Strictly scoped to `auth()->id()`. |
| RBAC Enforcement | **PASS** | Role middleware validated for all roles. |
| CSRF Token Protection | **PASS** | All POST, PUT, DELETE routes protected with `@csrf`. |
| PII / Phone Masking | **PASS** | Phone numbers masked in delivery logs. |
| Replay Attack / Duplication | **PASS** | Protected by unique `idempotency_key`. |
| External Carrier Isolation | **PASS** | Default Mock providers ensure safe simulation. |
| Audit Trail Immutability | **PASS** | Administrative changes logged via `AuditLogService`. |

**Overall Security Audit Status:** **APPROVED (Production-Ready Architecture)**

