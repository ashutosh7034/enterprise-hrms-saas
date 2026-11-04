# ENTERPRISE HRMS SaaS — PHASE 2 FINAL UAT & SECURITY PRE-AUDIT
## Stage A Pre-Audit Inspection Report

**Project:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Audit Phase:** Phase 2 Final UAT & Application Security Audit  
**Target Environment:** Laravel 13 | PHP 8.3.36 | MySQL 8.0  
**Git Branch:** `main`  
**Git Commit SHA:** `71508ec70b90e1cd7ac44b3aa1d90380a85396c0`  
**Date:** November 3, 2026  
**Auditor:** Senior Laravel SaaS Architect, Application Security Engineer & QA Lead  

---

## 1. PRE-AUDIT INSPECTION OVERVIEW

This pre-audit document establishes the independent verification baseline of Phase 2 (Multi-Tenant Architecture & Group Companies) before conducting destructive, penetration, or regression test operations.

### Scope of Verification
1. Source code integrity and architecture boundaries.
2. Database schema, constraints, indexes, and migration consistency across both `enterprise_hrms` (dev) and `enterprise_hrms_test` (test).
3. Tenancy isolation, company context service, and global scopes.
4. Authentication guards (`web` vs `platform`) and session segregation.
5. Tenant suspension enforcement and route protection pipelines.
6. Platform Control Center (CRUD, zero operational exposure compliance, audit logging).
7. Potential security vulnerabilities, edge cases, and technical debt.

---

## 2. VERIFIED COMPONENTS & ARTIFACTS

| Component | File / Location | Inspection Status |
| :--- | :--- | :---: |
| **Platform User Model** | `app/Models/PlatformUser.php` | Verified (`platform` guard authenticatable, status helper) |
| **Customer Tenant Model** | `app/Models/Tenant.php` | Verified (`hasMany` companies, `isActive()`, `isSuspended()`) |
| **Legal Company Model** | `app/Models/Company.php` | Verified (`tenant_id` fillable, `belongsTo(Tenant::class)`) |
| **User Model** | `app/Models/User.php` | Verified (`tenant()` helper via `$user->company->tenant`) |
| **Platform Audit Log Model** | `app/Models/PlatformAuditLog.php` | Verified (relationships, immutable log storage) |
| **Company Context Service** | `app/Services/CompanyContextService.php` | Verified (tenant-scoped accessible companies and switcher) |
| **Global Company Scope** | `app/Models/Traits/BelongsToCompany.php` | Verified (global query filtering by active company ID) |
| **SetCompanyContext Middleware**| `app/Http/Middleware/SetCompanyContext.php` | Verified (view sharing for authenticated company context) |
| **Tenant Active Middleware** | `app/Http/Middleware/EnsureTenantIsActive.php` | Verified (locks out suspended/inactive tenant users) |
| **Platform Active Middleware** | `app/Http/Middleware/EnsurePlatformUserIsActive.php`| Verified (locks out inactive Platform Super Admins) |
| **Platform Guest Middleware** | `app/Http/Middleware/RedirectIfPlatformAuthenticated.php` | Verified (redirects authenticated platform admins to dashboard) |
| **Platform Tenant Controller** | `app/Http/Controllers/Platform/TenantManagementController.php` | Verified (CRUD, validation, status toggle, audit logs) |
| **Platform Tenant Views** | `resources/views/platform/tenants/*.blade.php` | Verified (index, create, edit, show, zero compensation leaks) |
| **Platform Layout & Dashboard**| `resources/views/layouts/platform.blade.php`, `dashboard.blade.php` | Verified (navigation pills, KPI cards, Phase 2 badge) |
| **Tenant Seeder** | `database/seeders/TenantSeeder.php` | Verified (idempotent, maps VE & VRE to `Vishal Group`) |
| **Platform User Seeder** | `database/seeders/PlatformUserSeeder.php` | Verified (uses `PLATFORM_SUPERADMIN_PASSWORD` env var) |

---

## 3. ROUTE AUDITING & PIPELINE COVERAGE

An audit of `routes/web.php` and `routes/api.php` reveals the following pipeline classifications:

### 3.1 Platform Super Admin Pipeline (`/platform/*`)
- **Prefix:** `/platform`
- **Guest Middleware:** `['platform.guest', 'throttle:60,1']` &rarr; `/platform/login`
- **Authenticated Middleware:** `['auth:platform', 'platform.active']`
- **Routes:** 11 routes (`platform.dashboard`, `platform.logout`, `platform.tenants.index`, `platform.tenants.create`, `platform.tenants.store`, `platform.tenants.show`, `platform.tenants.edit`, `platform.tenants.update`, `platform.tenants.status`).
- **Security Check:** Strictly blocked from regular HRMS users. Unauthenticated requests redirect to `platform.login`.

### 3.2 HRMS Customer Application Pipeline (Protected Routes)
- **Middleware:** `['auth:web', 'tenant.active']`
- **Scope:** All 80+ customer HRMS routes (Workforce, Attendance, Leaves, Payroll, Documents, ESS, MSS, Admin Reports, Master Data, Company Switcher).
- **Security Check:** If a customer tenant is suspended or inactive, `EnsureTenantIsActive` intercepts the request before any controller execution, destroys the session, regenerates the CSRF token, and redirects to `/login`.

### 3.3 Public & Guest Routes
- **Guest Web:** `/`, `/login`, `POST /login` (`middleware('guest')`).
- **Public Document Verification:** `/verify-document`, `/verify-document/{code}` (`middleware('throttle:60,1')`).
- **Biometric Webhook:** `POST /api/biometric/webhook` (`routes/api.php`, token/header authenticated).

---

## 4. DATABASE INTEGRITY INSPECTION

### 4.1 Development Database (`enterprise_hrms`)
- **Total Tables:** 90
- **Total Migrations:** 65 (All in status `Ran`)
- **Tenants Count:** 1
  - Tenant ID: `1`
  - Code: `TNT-VISHAL`
  - Name: `Vishal Group`
  - Slug: `vishal-group`
  - Status: `active`
- **Companies Count:** 2
  - Company ID: `1` (`VE` - Vishal Enterprise) &rarr; `tenant_id: 1`
  - Company ID: `2` (`VRE` - Vrishal Eng. Pvt. Ltd.) &rarr; `tenant_id: 1`
- **Unmapped Companies (`tenant_id IS NULL`):** 0

### 4.2 Test Database (`enterprise_hrms_test`)
- **Total Tables:** 90
- **Total Migrations:** 65 (All in status `Ran`)
- **Primary Tenant:** `TNT-VISHAL` (`Vishal Group`) mapping Company 1 (`VE`) and Company 2 (`VRE`).
- **Synthetic Test Tenants:** 7 isolated test tenants created during test execution (`TNT-UPDATETEST`, `TNT-STATUSTEST`, `TNT-ISOB`, `TNT-CROSSFAIL`, `TNT-SUSPENDED`, `TNT-SUSP-ADM`, `TNT-ACMETEST`).
- **Unmapped Companies in Test DB:** 0

---

## 5. SECURITY VULNERABILITIES & EDGE CASES IDENTIFIED

During the pre-audit code inspection, 3 potential security and isolation edge cases were identified for deeper penetration testing and remediation:

### 1. [VULN-PRE-01] Null Tenant Mapping Bypassing Company Switching Validation (Medium)
- **Location:** `app/Services/CompanyContextService.php` lines 86–91:
  ```php
  if ($user) {
      $userTenantId = $user->company?->tenant_id;
      if ($userTenantId && $company->tenant_id !== $userTenantId) {
          return false; // Cross-tenant switch blocked!
      }
  }
  ```
- **Vulnerability:** If a user belongs to an unmapped company (where `tenant_id` is `null`), `$userTenantId` is `null`. The condition `if ($userTenantId && ...)` evaluates to `false`. Consequently, an unmapped user could switch into any company, including a tenant-owned company.
- **Recommended Remediation:** Strengthen the check so that if either `$userTenantId` is null, or `$company->tenant_id !== $userTenantId`, the switch is strictly rejected:
  ```php
  if ($user) {
      $userTenantId = $user->company?->tenant_id;
      if (!$userTenantId || $company->tenant_id !== $userTenantId) {
          return false;
      }
  }
  ```

### 2. [VULN-PRE-02] Active Company Resolution Session Fallback with Null Tenant (Low)
- **Location:** `app/Services/CompanyContextService.php` lines 33–35:
  ```php
  $sessionQuery = Company::where('id', $sessionCompanyId)->where('status', 'Active');
  if ($userTenantId) {
      $sessionQuery->where('tenant_id', $userTenantId);
  }
  ```
- **Vulnerability:** If `$userTenantId` is null, `$sessionQuery` is not constrained by `tenant_id`, allowing a session with an arbitrary company ID to be accepted as the active company.
- **Recommended Remediation:** If `$userTenantId` is null, enforce `$sessionQuery->where('id', $user->company_id)` or reject session tampering.

### 3. [VULN-PRE-03] EnsureTenantIsActive Middleware Passthrough on Null Tenant (Medium)
- **Location:** `app/Http/Middleware/EnsureTenantIsActive.php` line 26:
  ```php
  $tenant = $user->tenant();
  if ($tenant && !$tenant->isActive()) { ... }
  ```
- **Vulnerability:** If an authenticated HRMS user has no tenant associated (e.g. `$user->company` is null or `tenant_id` is null), `$tenant` is `null`, and the middleware allows the user into the protected HRMS routes.
- **Recommended Remediation:** In an enterprise multi-tenant SaaS application, every HRMS user MUST belong to an active tenant. If `$tenant === null`, the user should be logged out and redirected to login with an error message: `"No active organization tenant found for your account."`

---

## 6. TECHNICAL DEBT & OBSERVATIONS

1. **Test Suite Duration:**
   - Full regression suite takes ~229 seconds across 50 test classes due to iterative payroll batch calculations.
2. **Frontend Viewport Double Scrollbar:**
   - Pre-existing cosmetic issue in sidebar footer and layout viewport identified in earlier UI sessions. Does not affect backend multi-tenancy or security boundaries.
3. **Biometric Webhook Scope:**
   - `/api/biometric/webhook` receives punches using device secret tokens without checking the tenant status of the device's company. (Hardware devices push buffered clock-ins; dropping punches during administrative suspension could cause permanent compliance data loss. Documented as architectural intent).

---

## 7. CONCLUSION & STAGE B-H READINESS

The application codebase is well-structured and follows strict Laravel conventions. The Stage A Pre-Audit is complete.

We will now proceed directly to:
- **Stage B:** Tenant Data Integrity Audit
- **Stage C:** Cross-Tenant Security Testing (including verification of VULN-PRE-01, 02, 03)
- **Stage D:** Tenant Suspension Testing
- **Stage E:** Platform Tenant Management UAT
- **Stage F:** Platform Audit Log Verification
- **Stage G:** Automated Testing
- **Stage H:** Regression Audit
- **Stage I & J:** Defect Classification & Minimal Remediation

