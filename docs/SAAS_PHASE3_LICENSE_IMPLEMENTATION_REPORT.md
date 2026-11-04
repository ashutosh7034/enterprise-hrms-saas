# ENTERPRISE HRMS SaaS — PHASE 3 IMPLEMENTATION REPORT
## Licensing & Subscriptions Engine

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Phase:** Phase 3 of 3 — Licensing & Subscriptions Engine  
**Framework:** Laravel 13  
**PHP Version:** 8.3.36  
**Database:** MySQL 8.0  
**Repository Branch:** main  
**Commit Baseline:** `71508ec70b90e1cd7ac44b3aa1d90380a85396c0`  
**Execution Timestamp:** 2026-11-03  
**Implementation Status:** **COMPLETED — APPROVED**

---

## 1. EXECUTIVE SUMMARY

Advait IT Solutions & Services has successfully architected, implemented, and verified **Phase 3: Licensing & Subscriptions Engine** for the Enterprise HRMS SaaS platform.

Phase 3 introduces an enterprise-grade commercial monetization and entitlement layer on top of the multi-tenant architecture delivered in Phase 2. The platform empowers Advait IT Solutions (the SaaS owner) to define commercial subscription plans, regulate resource consumption quotas (employee headcount limits and legal entity limits), control granular access to HRMS functional modules, enforce subscription lifecycles (activation, renewal, grace period, expiration, suspension), and maintain an immutable financial ledger of invoices and payment receipts without exposing sensitive operational employee data.

The complete automated test suite was executed:
- **Total Test Suites Executed:** 52
- **Total Tests Passed:** 284
- **Total Assertions Verified:** 1,736
- **Failures:** 0
- **Errors:** 0
- **Regression Impact:** 0% (Clean baseline preservation)

---

## 2. DATABASE ARCHITECTURE & MIGRATIONS

Five dedicated subscription and billing tables were introduced across both development (`enterprise_hrms`) and isolated testing (`enterprise_hrms_test`) databases with zero schema divergence:

### 2.1 Table: `subscription_plans`
Stores commercial SaaS tiers offered by Advait IT Solutions.
- `id` (bigint, unsigned, primary key)
- `plan_code` (varchar(30), unique, uppercase index) — e.g., `PLAN-STARTER`, `PLAN-PRO`, `PLAN-ENTERPRISE`
- `name` (varchar(100))
- `description` (text, nullable)
- `billing_interval` (enum: `'monthly'`, `'quarterly'`, `'yearly'`)
- `price` (decimal(12,2))
- `currency` (varchar(10), default `'INR'`)
- `employee_limit` (int, unsigned) — Headcount limit across tenant group companies
- `company_limit` (int, unsigned) — Legal entity limit
- `status` (enum: `'active'`, `'inactive'`)
- `created_at`, `updated_at`

### 2.2 Table: `subscription_features`
Normalized module feature entitlements attached to subscription plans.
- `id` (bigint, unsigned, primary key)
- `subscription_plan_id` (foreign key to `subscription_plans`, cascade on delete)
- `feature_code` (varchar(50)) — e.g., `employee_management`, `attendance_management`, `leave_management`, `payroll_management`, `asset_management`, `document_management`, `reports`, `recruitment`, `performance_management`, `employee_self_service`, `manager_self_service`
- `feature_name` (varchar(100))
- `is_enabled` (boolean, default true)
- Unique composite index: `['subscription_plan_id', 'feature_code']`

### 2.3 Table: `tenant_subscriptions`
Tracks commercial contracts and subscription lifecycles per customer tenant.
- `id` (bigint, unsigned, primary key)
- `tenant_id` (foreign key to `tenants`, cascade on delete)
- `subscription_plan_id` (foreign key to `subscription_plans`, restrict on delete)
- `subscription_code` (varchar(50), unique index) — e.g., `SUB-TNT-VISHAL-2026-XXXX`
- `status` (enum: `'pending'`, `'trial'`, `'active'`, `'grace_period'`, `'expired'`, `'suspended'`, `'cancelled'`)
- `starts_at` (timestamp, indexed)
- `ends_at` (timestamp, indexed)
- `grace_period_ends_at` (timestamp, nullable)
- `activated_at`, `suspended_at`, `cancelled_at` (timestamps, nullable)
- `renewal_type` (enum: `'manual'`, `'automatic'`)
- `created_by_platform_user_id` (foreign key to `platform_users`, nullable)
- `notes` (text, nullable), `metadata` (json, nullable)

### 2.4 Table: `subscription_invoices`
Commercial billing invoices generated upon plan provisioning and renewals.
- `id` (bigint, unsigned, primary key)
- `tenant_id` (foreign key to `tenants`, cascade on delete)
- `tenant_subscription_id` (foreign key to `tenant_subscriptions`, cascade on delete)
- `invoice_number` (varchar(50), unique index) — e.g., `INV-2026-00001`
- `amount` (decimal(12,2)), `currency` (varchar(10))
- `status` (enum: `'pending'`, `'paid'`, `'cancelled'`, `'void'`)
- `issued_at`, `due_at`, `paid_at` (timestamps)
- `notes` (text, nullable), `metadata` (json, nullable)

### 2.5 Table: `subscription_payments`
Financial settlement receipts linked to billing invoices.
- `id` (bigint, unsigned, primary key)
- `tenant_id`, `tenant_subscription_id`, `subscription_invoice_id` (foreign keys)
- `payment_reference` (varchar(100), unique index) — e.g., `PAY-XXXXXXXXXXXX`
- `payment_provider` (varchar(50)) — e.g., `manual_bank_transfer`, `cheque`, `upi`
- `amount` (decimal(12,2)), `currency` (varchar(10))
- `status` (enum: `'pending'`, `'completed'`, `'failed'`, `'refunded'`)
- `paid_at` (timestamp), `metadata` (json, nullable)

---

## 3. SEEDING & INITIAL PROVISIONING

### 3.1 `SubscriptionPlanSeeder`
Created three standard commercial tiers:
1. **Starter Tier (`PLAN-STARTER`)**:
   - Price: ₹4,999 / month (₹59,988 / year)
   - Quotas: 50 active employees, 1 legal entity
   - Modules: Workforce, Attendance, Leaves, ESS, MSS
2. **Professional Tier (`PLAN-PRO`)**:
   - Price: ₹12,999 / month (₹1,55,988 / year)
   - Quotas: 200 active employees, 3 legal entities
   - Modules: Workforce, Attendance, Leaves, Payroll & Statutory, Assets, Reports, ESS, MSS
3. **Enterprise Tier (`PLAN-ENTERPRISE`)**:
   - Price: ₹29,999 / month (₹3,59,988 / year)
   - Quotas: 1,000 active employees, 10 legal entities
   - Modules: All 11 modules enabled including Document Generation, Recruitment, and Performance.

### 3.2 `TenantSubscriptionSeeder`
- Provisioned the initial tenant **Vishal Group** (`TNT-VISHAL`) with an active 1-year Enterprise Tier subscription (`SUB-VISHAL-2026`).
- Generated initial invoice `INV-2026-00001` (₹3,59,988.00, Paid).
- Recorded payment receipt `PAY-VISHAL-INIT-2026` via Direct Bank Transfer.
- Seeded idempotently on both development and test databases.

---

## 4. CORE SERVICES & MIDDLEWARE PIPELINE

### 4.1 `SubscriptionService`
Encapsulates business operations with strict database transactions and platform audit trails:
- `assignPlan(Tenant $tenant, SubscriptionPlan $plan, array $options)`: Archives prior subscriptions, creates new subscription, generates invoice, records initial payment if settled, logs `TENANT_SUBSCRIPTION_ASSIGNED`.
- `activate(TenantSubscription $subscription)`: Transitions trial/pending to `active`, logs `TENANT_SUBSCRIPTION_ACTIVATED`.
- `renew(TenantSubscription $subscription, array $options)`: Extends validity by plan billing interval, resets grace period, generates renewal invoice, logs `TENANT_SUBSCRIPTION_RENEWED`.
- `suspend(TenantSubscription $subscription, ?string $reason)`: Transitions status to `suspended`, logs `TENANT_SUBSCRIPTION_SUSPENDED`.
- `reactivate(TenantSubscription $subscription)`: Evaluates end dates and reactivates to `active` or `grace_period`, logs `TENANT_SUBSCRIPTION_REACTIVATED`.
- `cancel(TenantSubscription $subscription, ?string $reason)`: Cancels contract, logs `TENANT_SUBSCRIPTION_CANCELLED`.
- `extendValidity(TenantSubscription $subscription, int $days)`: Adds specified days to `ends_at`, resets grace period, logs `TENANT_SUBSCRIPTION_EXTENDED`.

### 4.2 `LicenseEntitlementService`
Provides real-time quota calculations and feature entitlement queries:
- `getTenantSubscription(?Tenant $tenant)`: Resolves active or latest subscription for current tenant.
- `hasValidSubscription(?Tenant $tenant)`: Verifies if status is in `['active', 'trial', 'grace_period']`.
- `hasFeature(string $featureCode, ?Tenant $tenant)`: Verifies whether the module is enabled in the tenant's plan.
- `canAddEmployee(?Tenant $tenant, ?int $companyId)`: Computes active headcount across all companies belonging to the tenant and compares against `employee_limit`.
- `canAddCompany(?Tenant $tenant)`: Checks registered company count against `company_limit`.

### 4.3 Middleware Enforcement
- **`EnsureTenantSubscriptionIsValid`** (`tenant.subscription`):
  - Injected into the customer web guard pipeline: `Route::middleware(['auth:web', 'tenant.active', 'tenant.subscription'])`.
  - Blocks expired, suspended, or unassigned tenants, logs out the user, invalidates the session, and redirects to login with an informative error message.
  - Detects `grace_period` status: allows continued operational access while flashing a global `subscription_grace_warning` banner in the layout.
  - Automatically bypasses `/logout` to prevent redirect loops.
- **`EnsureModuleEntitlement`** (`entitled:{module_code}`):
  - Injected onto feature route groups (e.g. `assets`, `admin/documents`).
  - Queries `LicenseEntitlementService::hasFeature()`.
  - Rejects unauthorized module requests with HTTP 403 Forbidden ("Access Denied: The [Module] is not included in your organization's subscription plan").

---

## 5. RESOURCE QUOTA ENFORCEMENT

1. **Employee Headcount Quota**:
   - Injected into `EmployeeController::store()`.
   - Before executing onboarding transactions or blacklist checks, verifies `LicenseEntitlementService::canAddEmployee()`.
   - If headcount limit is reached, halts onboarding with a clean validation error: *"Tenant employee quota limit reached (X/Y). Please upgrade your subscription tier."*
2. **Legal Company Quota**:
   - Injected into `CompanyController::store()`.
   - Verifies `LicenseEntitlementService::canAddCompany()`.
   - Rejects company registration if company limit is reached.
   - Automatically scopes newly created companies to the user's active tenant (`$validated['tenant_id'] = $tenant->id`).

---

## 6. PLATFORM ADMINISTRATION & CUSTOMER UI

### 6.1 Platform Super Admin Control Center
- **Plans Management (`/platform/plans`)**: Listing with active subscriber counts, creation form with interactive feature checklist, and edit interface.
- **Subscriptions Management (`/platform/subscriptions`)**: Filterable table (by status, tenant, plan), subscription detail inspection, lifecycle modals (Extend validity, Renew, Suspend, Cancel).
- **Invoices & Billing (`/platform/invoices`)**: Financial revenue dashboard, invoice detail view, manual settlement recording modal.
- **Tenant Overview Enhancement (`/platform/tenants/{tenant}`)**: Dedicated Subscription License card with plan tier, quota progress, entitled modules, and direct lifecycle management triggers.
- **Platform Navigation & Dashboard**: Updated navigation bar with quick links to Plans, Subscriptions, and Invoices. Dashboard updated with Phase 3 KPIs.

### 6.2 Customer Tenant Subscription Profile (`/subscription`)
- Accessible to authenticated tenant administrators via the user profile dropdown.
- Displays current commercial plan, active status badge, validity expiration, and grace period countdown.
- Progress bars and percentages for active employee seats and registered company quotas.
- Interactive grid of all 11 HRMS modules showing licensed status or plan exclusion lock.
- Complete billing history table with invoice numbers, amounts, dates, and payment receipts.
- Direct contact link to Advait IT Solutions billing support for tier upgrades.

---

## 7. AUTOMATED VERIFICATION RESULTS

Two dedicated feature test suites were authored and executed:
1. `SubscriptionLifecycleTest.php` (13 tests, 53 assertions):
   - Plans CRUD & feature assignment.
   - Tenant plan assignment, invoice generation, payment receipt creation.
   - Active subscription access & grace period warning verification.
   - Expired & suspended subscription access denial.
   - Subscription extension & renewal.
   - Employee seat quota & legal company quota enforcement.
   - Customer `/subscription` profile rendering.
   - Platform endpoint guard isolation.
2. `ModuleEntitlementTest.php` (3 tests, 8 assertions):
   - Entitled module access (Asset Management, Document Management).
   - HTTP 403 Access Denied upon module feature disabling.
   - Dynamic reactivation of module access.

**Full Test Suite Execution:**
```
Tests:    284 passed (1736 assertions)
Duration: 256.58s
```

All 52 test suites across the application passed without a single failure or regression.

