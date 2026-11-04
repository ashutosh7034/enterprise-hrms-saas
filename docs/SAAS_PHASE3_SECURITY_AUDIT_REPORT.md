# ENTERPRISE HRMS SaaS — PHASE 3 SECURITY AUDIT REPORT
## Licensing & Subscriptions Engine Security Review

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Phase:** Phase 3 — Licensing & Subscriptions Engine  
**Framework:** Laravel 13  
**PHP Version:** 8.3.36  
**Database:** MySQL 8.0  
**Audit Date:** 2026-11-03  
**Security Lead:** Senior Application Security Engineer & SaaS Architect  
**Security Verdict:** **PASSED — ZERO CRITICAL / ZERO HIGH VULNERABILITIES**

---

## 1. AUDIT OBJECTIVE & SCOPE

The objective of this security audit is to independently verify that Phase 3 of the Enterprise HRMS SaaS transformation complies with strict enterprise security policies, zero-trust architecture principles, and multi-tenant data privacy standards.

The audit evaluates:
1. Platform Guard vs Web Guard isolation.
2. Zero Operational Exposure of employee compensation, bank accounts, and personal HR files to Platform Super Admins.
3. Cross-Tenant Data Isolation and prevention of Insecure Direct Object References (IDOR).
4. Server-Side Module Entitlement enforcement.
5. Resource Quota enforcement and bypass resistance.
6. Billing transaction integrity and non-repudiation.
7. Audit log immutability.

---

## 2. GUARD SEPARATION & AUTHENTICATION ENFORCEMENT

### 2.1 Multi-Guard Architecture Review
- **Platform Super Admin Guard (`platform`)**:
  - Provider: `PlatformUser` model (`platform_users` table).
  - Middleware: `auth:platform`, `platform.active`.
  - Routes: Scoped exclusively under `/platform/*`.
  - Rate Limiting: 60 requests per minute on login endpoint.
- **Customer Tenant HRMS Guard (`web`)**:
  - Provider: `User` model (`users` table).
  - Middleware: `auth:web`, `tenant.active`, `tenant.subscription`.
  - Routes: Standard HRMS operational application endpoints.

### 2.2 Cross-Guard Authentication Resistance
- Verified that Platform Super Admins cannot authenticate against the customer web login portal.
- Verified that Customer HRMS users (including HO Admins) cannot authenticate against the platform control tower.
- Verified that Customer HRMS session cookies do not grant access to `/platform/*` routes (redirects immediately to `platform.login`).
- Verified that Platform Super Admin session cookies do not automatically grant customer HRMS access.

---

## 3. ZERO OPERATIONAL EXPOSURE (PAGE 94 COMPLIANCE)

The core principle established in Phase 1 and 2 is strictly maintained in Phase 3:
**Platform Super Admins (Advait IT Solutions) manage commercial tenancy, plan tiers, and billing metadata, but are strictly prohibited from viewing employee payroll, bank accounts, or personal HR files.**

### 3.1 Code Inspection & Query Verification
- `TenantManagementController`: Loads only company metadata (`id`, `name`, `code`, `cin_number`, `email`, `phone`, `status`, `created_at`). No relationships to `Employee`, `PayrollRun`, `SalaryStructure`, or `Payslip` are exposed.
- `TenantSubscriptionController`: Loads only plan features, invoice summaries, and tenant contact details. It does not load employee records or individual salary data.
- `SubscriptionInvoiceController`: Contains only plan pricing, invoice numbers, amounts, and settlement receipts. Zero salary slip data is accessible.
- Automated tests verify that `platform.tenants.show` and `platform.subscriptions.show` assert:
  - `assertDontSee('basic_salary')`
  - `assertDontSee('salary_slips')`
  - `assertDontSee('cost_to_company')`
  - `assertSee('Zero Operational Exposure')`

---

## 4. SERVER-SIDE MODULE ENTITLEMENT ENFORCEMENT

### 4.1 Architecture & Flow
Module access control is enforced at the HTTP middleware boundary (`EnsureModuleEntitlement`), preventing client-side or URL-guessing bypasses.

```
Incoming Request
      │
      ▼
auth:web (Authentication Check)
      │
      ▼
tenant.active (Tenant Operational Status Check)
      │
      ▼
tenant.subscription (Subscription Validity & Grace Check)
      │
      ▼
entitled:{module_code} (Module Feature License Check)
      │
      ├──> Feature Enabled in Plan Tier? ──[YES]──> Next Middleware / Controller
      │
      └──> Feature Disabled in Plan Tier? ──[NO]───> Abort HTTP 403 Forbidden
```

### 4.2 Tampering & Bypass Testing
- Direct GET/POST requests to `/assets`, `/fine-masters`, and `/damage-assessments` by tenants without `asset_management` entitlement were tested:
  - **Result:** Server rejected request with HTTP 403 Forbidden.
- Direct GET/POST requests to `/admin/documents/templates` and `/admin/documents/generate` without `document_management` entitlement were tested:
  - **Result:** Server rejected request with HTTP 403 Forbidden.
- Modifying plan features dynamically updates access control within the same session without requiring database restarts.

---

## 5. RESOURCE QUOTA ENFORCEMENT & INTEGRITY

### 5.1 Employee Headcount Quota Defense
- Evaluated `LicenseEntitlementService::canAddEmployee()`.
- Calculation accurately aggregates active employees across **all group companies** mapped to the tenant:
  ```php
  $companyIds = $tenant->companies()->pluck('id');
  $currentCount = Employee::withoutGlobalScopes()
      ->whereIn('company_id', $companyIds)
      ->whereNull('deleted_at')
      ->where('status', 'Active')
      ->count();
  ```
- Uses `Employee::withoutGlobalScopes()` to ensure soft-deleted employees do not consume quota seats while active headcount across sister entities cannot be hidden by company context switching.
- Attempted onboarding past the quota limit:
  - **Result:** Transaction halted before candidate creation. Error returned: *"Tenant employee quota limit reached (X/Y). Please upgrade your subscription tier."*

### 5.2 Legal Company Quota Defense
- Evaluated `LicenseEntitlementService::canAddCompany()`.
- Head office administrator cannot register additional legal entities beyond `company_limit`.
- Attempted registering a 3rd entity when limit was set to 2:
  - **Result:** Request rejected with flash error. Entity record was not created in the database.

---

## 6. SUBSCRIPTION LIFECYCLE & GRACE PERIOD DEFENSE

| Subscription Status | Operational HRMS Access | Platform Behavior |
| :--- | :--- | :--- |
| `active` | Allowed | Seamless operation across all entitled modules. |
| `grace_period` | Allowed (Warned) | Seamless operation + Global warning banner indicating expiration and renewal deadline. |
| `expired` | Denied | User session invalidated, logged out, redirected to `/login` with explanatory message. |
| `suspended` | Denied | User session invalidated, logged out, redirected to `/login` with explanatory message. |
| `cancelled` | Denied | User session invalidated, logged out, redirected to `/login` with explanatory message. |
| `pending` | Denied | User session invalidated, logged out, redirected to `/login` with explanatory message. |

### 6.1 State Transition Verification
- Verified that suspending a subscription immediately terminates access for all tenant users on their next HTTP request.
- Verified that reactivating a subscription restores access immediately without data corruption.
- Verified that `/logout` is exempted from subscription blocks, preventing infinite redirection loops.

---

## 7. FINANCIAL TRANSACTION INTEGRITY & AUDIT LOGGING

### 7.1 Invoice & Payment Immutability
- Invoice amounts and payment references cannot be tampered with via user inputs.
- Payment references are cryptographically unique (`PAY-` prefix + 12-character random string or verified UTR).
- Invoices are settled within database transactions with rollback on failure.

### 7.2 Non-Repudiation Audit Logs (`platform_audit_logs`)
All Phase 3 subscription management actions executed by Platform Super Admins are recorded with:
- Platform User ID
- Tenant ID
- Action Enum (`SUBSCRIPTION_PLAN_CREATED`, `SUBSCRIPTION_PLAN_UPDATED`, `TENANT_SUBSCRIPTION_ASSIGNED`, `TENANT_SUBSCRIPTION_ACTIVATED`, `TENANT_SUBSCRIPTION_RENEWED`, `TENANT_SUBSCRIPTION_SUSPENDED`, `TENANT_SUBSCRIPTION_REACTIVATED`, `TENANT_SUBSCRIPTION_EXTENDED`, `SUBSCRIPTION_INVOICE_PAID`)
- Action Metadata JSON (Subscription code, plan code, dates, amounts)
- Operator IP Address
- Timestamp

---

## 8. VULNERABILITY MATRIX

| Vulnerability Category | Risk Level | Status | Mitigation / Control |
| :--- | :--- | :--- | :--- |
| Cross-Guard Privilege Escalation | Critical | Mitigated | Isolated guards, tables, and middleware in `config/auth.php`. |
| Cross-Tenant IDOR & Data Leakage | Critical | Mitigated | `BelongsToCompany` global scope + tenant company validation in `CompanyContextService`. |
| Employee Compensation Exposure | Critical | Mitigated | Platform models and controllers omit compensation relationships. |
| Module Entitlement Bypass | High | Mitigated | Server-side `EnsureModuleEntitlement` middleware on route groups. |
| Headcount Quota Tampering | Medium | Mitigated | Aggregation across group companies in `LicenseEntitlementService`. |
| Invoice Manipulation | Medium | Mitigated | Strict DB transactions, system-generated tokens, and audit logging. |

---

## 9. CONCLUSION & VERDICT

Phase 3 complies with all architectural security guidelines and enterprise data protection standards.

**Final Security Assessment:** **PASSED — PRODUCTION READY**

