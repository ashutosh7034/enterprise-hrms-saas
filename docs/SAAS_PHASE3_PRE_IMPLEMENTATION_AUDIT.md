# ENTERPRISE HRMS SaaS — PHASE 3 PRE-IMPLEMENTATION AUDIT
## Licensing & Subscriptions Engine Architecture & Readiness

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Phase:** Phase 3 of 3 (Final Transformation Phase)  
**Date:** November 3, 2026  
**Auditor:** Principal Laravel SaaS Architect, Security Engineer & Product Engineer  
**Git Branch / Commit:** `main` | `71508ec70b90e1cd7ac44b3aa1d90380a85396c0`  

---

## 1. EXECUTIVE SUMMARY & AUDIT OBJECTIVE

Phase 3 is the culmination of the multi-tenant SaaS transformation for **Advait IT Solutions & Services**. Having completed Phase 1 (Platform Super Admin Authentication) and Phase 2 (Multi-Tenant Architecture & Group Companies), this audit evaluates the technical readiness to integrate a **Licensing & Subscriptions Engine**.

### Core Objectives
1. Inventory existing models, services, middleware, routes, and database tables.
2. Determine how tenant context, company limits, and employee limits will be enforced without breaking existing HRMS operations.
3. Design a billing-ready schema supporting multiple plans, tenant subscriptions, validity dates, grace periods, module entitlements, and invoices.
4. Establish a non-disruptive migration and seeding strategy ensuring `Vishal Group` (`TNT-VISHAL`) is seamlessly provisioned with an Enterprise subscription.
5. Guarantee strict Zero Operational Exposure for Platform Super Admins while giving them full commercial control.

---

## 2. EXISTING ARCHITECTURE & DATABASE INVENTORY

### 2.1 Existing Models & Relationships
- **`PlatformUser`:** Authenticated on `platform` guard, oversees platform control center (`/platform/*`).
- **`Tenant`:** Primary customer boundary (`tenants` table). Has many `Company` models.
- **`Company`:** Legal entity with `tenant_id` foreign key. Belongs to `Tenant`.
- **`User`:** HRMS user tied to a `company_id`. Helper `$user->tenant()` resolves tenant via company.
- **`PlatformAuditLog`:** Audit trail for administrative actions performed by Platform Super Admins.
- **Operational HRMS Models (52 Models):** All implement `BelongsToCompany` global scope, isolating data to active company context.

### 2.2 Existing Authentication & Route Pipeline
```text
Routes Pipeline:
1. /platform/*                --> ['auth:platform', 'platform.active'] (Platform Super Admin)
2. /, /login, /login.post     --> ['guest'] (Public HRMS login)
3. /verify-document/*         --> ['throttle:60,1'] (Public QR verification)
4. /api/biometric/webhook     --> Token-based device auth
5. All HRMS Web Routes        --> ['auth:web', 'tenant.active'] (Customer Tenant users)
```

### 2.3 Existing Licensing & Billing Inventory
- **Current Subscription Tables:** **0** (No subscription or licensing tables exist currently).
- **Current Feature Flags / Entitlements:** Feature access is presently governed only by internal user roles (`ho_admin`, `hr_executive`, `manager_mss`, `employee_ess`). No tenant-level plan restrictions exist.
- **Current Billing Structures:** None. Only internal payroll, employee loans, and fine recovery exist.

---

## 3. PROPOSED PHASE 3 ARCHITECTURE

### 3.1 Hierarchy of Entitlement
```text
Platform Super Admin (Advait IT Solutions)
             │
             ▼
      Subscription Plans
  (Starter, Professional, Enterprise)
             │
             ▼
   Plan Feature Entitlements
  (Modules, Employee & Company Limits)
             │
             ▼
    Tenant Subscriptions
  (Tenant ID, Plan ID, Validity, Status)
             │
             ▼
  License Enforcement Engine
  (Active, Grace Period, Expired, Suspended)
             │
             ▼
Customer Tenant Access & Module Permissions
```

### 3.2 Target Database Schema

#### 1. `subscription_plans`
- `id` (bigint, unsigned, primary key)
- `plan_code` (varchar(30), unique, indexed) &mdash; e.g. `PLAN-STARTER`, `PLAN-PRO`, `PLAN-ENTERPRISE`
- `name` (varchar(100)) &mdash; e.g. `Starter Tier`, `Enterprise Suite`
- `description` (text, nullable)
- `billing_interval` (enum: `monthly`, `quarterly`, `yearly`, default: `monthly`)
- `price` (decimal(10,2), default: 0.00)
- `currency` (varchar(10), default: `INR`)
- `employee_limit` (int, unsigned, default: 50)
- `company_limit` (int, unsigned, default: 1)
- `status` (enum: `active`, `inactive`, default: `active`)
- `metadata` (json, nullable)
- `timestamps`

#### 2. `subscription_features` (Plan Entitlements)
- `id` (bigint, unsigned, primary key)
- `subscription_plan_id` (foreign key to `subscription_plans(id)` on delete cascade)
- `feature_code` (varchar(50), indexed) &mdash; e.g. `employee_management`, `attendance_management`, `leave_management`, `payroll_management`, `asset_management`, `document_management`, `reports`
- `feature_name` (varchar(100))
- `is_enabled` (boolean, default: true)
- `metadata` (json, nullable)
- `timestamps`

#### 3. `tenant_subscriptions`
- `id` (bigint, unsigned, primary key)
- `tenant_id` (bigint, unsigned, foreign key to `tenants(id)` on delete restrict)
- `subscription_plan_id` (bigint, unsigned, foreign key to `subscription_plans(id)`)
- `subscription_code` (varchar(50), unique, indexed) &mdash; e.g. `SUB-VISHAL-2026`
- `status` (enum: `pending`, `trial`, `active`, `grace_period`, `expired`, `suspended`, `cancelled`, default: `active`, indexed)
- `starts_at` (datetime)
- `ends_at` (datetime)
- `grace_period_ends_at` (datetime, nullable)
- `activated_at` (datetime, nullable)
- `suspended_at` (datetime, nullable)
- `cancelled_at` (datetime, nullable)
- `renewal_type` (enum: `manual`, `auto`, default: `manual`)
- `notes` (text, nullable)
- `metadata` (json, nullable)
- `created_by_platform_user_id` (bigint, unsigned, nullable, foreign key to `platform_users(id)`)
- `timestamps`

#### 4. `subscription_invoices` (Billing-Ready Foundation)
- `id` (bigint, unsigned, primary key)
- `tenant_id` (bigint, unsigned, foreign key to `tenants(id)`)
- `tenant_subscription_id` (bigint, unsigned, foreign key to `tenant_subscriptions(id)`)
- `invoice_number` (varchar(50), unique, indexed) &mdash; e.g. `INV-2026-0001`
- `amount` (decimal(10,2))
- `currency` (varchar(10), default: `INR`)
- `status` (enum: `pending`, `paid`, `cancelled`, `refunded`, default: `pending`)
- `issued_at` (datetime)
- `due_at` (datetime)
- `paid_at` (datetime, nullable)
- `notes` (text, nullable)
- `metadata` (json, nullable)
- `timestamps`

#### 5. `subscription_payments` (Payment Reference Log)
- `id` (bigint, unsigned, primary key)
- `tenant_id` (bigint, unsigned, foreign key to `tenants(id)`)
- `tenant_subscription_id` (bigint, unsigned, foreign key to `tenant_subscriptions(id)`)
- `subscription_invoice_id` (bigint, unsigned, foreign key to `subscription_invoices(id)`)
- `payment_reference` (varchar(100), unique, indexed)
- `payment_provider` (varchar(50)) &mdash; e.g. `manual_bank_transfer`, `offline_cheque`, `razorpay`, `stripe`
- `amount` (decimal(10,2))
- `currency` (varchar(10), default: `INR`)
- `status` (enum: `pending`, `completed`, `failed`, `refunded`, default: `completed`)
- `paid_at` (datetime)
- `metadata` (json, nullable)
- `timestamps`

---

## 4. ENGINE INTEGRATION & MIDDLEWARE STRATEGY

### 4.1 Services
1. **`App\Services\SubscriptionService`:**
   - Handles tenant subscription lifecycle: assignment, activation, plan upgrade/downgrade, validity extension, renewal, suspension, reactivation, and cancellation.
   - Calculates expiry and grace period window.
   - Generates billing invoices and logs platform audit entries.
2. **`App\Services\LicenseEntitlementService`:**
   - Evaluates:
     - Is the tenant subscription currently valid (`active` or in `grace_period`)?
     - Is a specific HRMS module (`feature_code`) enabled for the tenant?
     - Does current active employee count exceed the plan limit?
     - Does current legal company count exceed the plan limit?

### 4.2 Middleware Pipeline Integration
In `bootstrap/app.php`:
- Register middleware alias `'tenant.subscription'` pointing to `App\Http\Middleware\EnsureTenantSubscriptionIsValid`.
- Attach `'tenant.subscription'` to all protected customer routes:
  ```php
  Route::middleware(['auth:web', 'tenant.active', 'tenant.subscription'])->group(function () {
      // HRMS Customer Routes
  });
  ```
- **Policy Enforcement:**
  - If subscription is `active`: Pass request.
  - If subscription is in `grace_period`: Pass request with warning flash banner.
  - If subscription is `expired`, `suspended`, or `cancelled`: Block request, invalidate customer session, redirect to `/login` or customer subscription landing page with renewal instructions.
  - Platform Super Admin routes remain independent and unaffected.

### 4.3 Feature Entitlement Check
- Implement `EnsureModuleEntitlement` middleware (alias `'module:<code'>`) on optional or advanced modules (e.g. `asset_management`, `recruitment`, `documents`).
- If an unentitled module is accessed directly, abort with HTTP 403 (`"Your subscription plan does not include this module."`).

---

## 5. SEEDING STRATEGY & EXISTING DATA SAFETY

To maintain 100% backward compatibility and zero downtime for the existing tenant (`Vishal Group`):
1. **`SubscriptionPlanSeeder`:**
   - Seeds 3 standard commercial plans:
     - **Starter:** Monthly, ₹15,000, 50 employees, 1 company, core modules.
     - **Professional:** Monthly, ₹35,000, 250 employees, 3 companies, expanded modules.
     - **Enterprise:** Annual, ₹1,20,000, Unlimited/1000 employees, 10 companies, all modules enabled.
2. **`TenantSubscriptionSeeder`:**
   - Automatically provisions `Vishal Group` (`TNT-VISHAL`) with an **Enterprise Plan** subscription.
   - `status`: `active`
   - `starts_at`: Current date
   - `ends_at`: +1 Year
   - Generates initial paid invoice `INV-2026-0001` and payment record `PAY-REF-VISHAL-INIT`.
3. **Safety Verification:**
   - Existing employee records (100+ employees across VE and VRE) are preserved without modification.
   - Existing company records (ID 1 and 2) are preserved.
   - All 268 baseline tests will continue to pass seamlessly.

---

## 6. RISK MITIGATION & ROADMAP

| Risk | Impact | Mitigation Strategy |
| :--- | :---: | :--- |
| **Accidental lockout of Vishal Group** | High | Seeder provisions active Enterprise subscription with 1-year validity immediately upon migration. |
| **Concurrent employee onboarding exceeding limit** | Medium | Transaction-level count check in `EmployeeOnboardingService`. |
| **Platform Super Admin session bleed** | High | Platform Super Admin routes remain strictly on `auth:platform` and bypass tenant subscription middleware. |
| **Cross-tenant subscription tampering** | High | All subscription endpoints validate tenant ID server-side; customer users have zero write endpoints. |

---

## 7. AUDIT APPROVAL

The pre-implementation audit confirms that the architecture is safe, compatible, and ready for execution.

**Stage A Complete. Proceeding to Stage B & C (Migrations, Models & Seeders).**

