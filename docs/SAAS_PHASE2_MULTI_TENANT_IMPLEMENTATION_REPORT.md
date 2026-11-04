# ENTERPRISE HRMS SaaS — PHASE 2 IMPLEMENTATION REPORT
## Multi-Tenant Architecture & Group Companies

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Date:** November 3, 2026  
**Implementation Phase:** Phase 2 (Completed)  
**Security Status:** Production Grade Isolation  
**Database Engines:** MySQL 8.0 (`enterprise_hrms` & `enterprise_hrms_test`)  
**PHP / Framework:** PHP 8.3.36 / Laravel 13  

---

## 1. EXECUTIVE SUMMARY

Under the ownership and operation of **Advait IT Solutions & Services**, Phase 2 of the Enterprise HRMS SaaS transformation has been successfully implemented. 

Phase 2 establishes the fundamental multi-tenant architectural layer:
1. **Multi-Tenancy Foundation:** Customer organizations are represented as tenants in the newly established `tenants` table.
2. **First Customer Tenant (Vishal Group):**
   - **Tenant ID:** `1`
   - **Tenant Code:** `TNT-VISHAL`
   - **Name:** `Vishal Group`
   - **Slug:** `vishal-group`
   - **Status:** `active`
3. **Group Company Structure (Parent-Child Hierarchy):**
   - **Vishal Enterprise** (Company ID: `1`, Code: `VE`) &rarr; Mapped to `tenant_id: 1`
   - **Vrishal Eng. Pvt. Ltd.** (Company ID: `2`, Code: `VRE`) &rarr; Mapped to `tenant_id: 1`
4. **Strict Isolation & Zero Data Leakage:**
   - Existing company-level scoped models (`BelongsToCompany`) remain intact.
   - Company switching logic in `CompanyContextService` has been hardened to restrict accessible companies strictly to the authenticated user's tenant (`tenant_id == $userTenantId`). A Head Office (HO) Admin from one customer tenant can never see or switch to legal companies belonging to another tenant.
5. **Tenant Access Enforcement Middleware:**
   - `EnsureTenantIsActive` intercepts all authenticated HRMS traffic. If a customer tenant is suspended or inactive, user sessions are terminated, tokens regenerated, and users redirected with an administrative notice.
6. **Platform Control Center Tenant Management:**
   - Secure Platform Super Admin CRUD interface under `/platform/tenants` with search, status filtering, metadata updates, status toggles, and dedicated `platform_audit_logs`.
   - **Zero Operational Exposure:** Platform Super Admins oversee tenancy structure and legal entities without any visibility into employee compensation, salary slips, or private HR personnel data.

---

## 2. DATABASE ARCHITECTURE & SCHEMA CHANGES

Three dedicated migrations were created and executed across both the development database (`enterprise_hrms`) and test database (`enterprise_hrms_test`):

### 2.1 Table: `tenants`
- **Migration:** `database/migrations/2026_11_03_000002_create_tenants_table.php`
- **Fields:**
  - `id` (bigint, unsigned, primary key, auto-increment)
  - `tenant_code` (varchar(30), unique, indexed) &mdash; e.g. `TNT-VISHAL`
  - `name` (varchar(150)) &mdash; e.g. `Vishal Group`
  - `slug` (varchar(100), unique, indexed) &mdash; e.g. `vishal-group`
  - `status` (enum: `active`, `suspended`, `inactive`, default: `active`, indexed)
  - `contact_name` (varchar(150), nullable)
  - `contact_email` (varchar(150), nullable, indexed)
  - `contact_phone` (varchar(50), nullable)
  - `address` (text, nullable)
  - `notes` (text, nullable)
  - `timestamps` (`created_at`, `updated_at`)

### 2.2 Table: `companies` Foreign Key Addition
- **Migration:** `database/migrations/2026_11_03_000003_add_tenant_id_to_companies_table.php`
- **Changes:**
  - Added `tenant_id` (bigint, unsigned, nullable, indexed) after `id`.
  - Added foreign key constraint: `FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT`.
  - Backwards-compatible rollback drops foreign key and column.

### 2.3 Table: `platform_audit_logs`
- **Migration:** `database/migrations/2026_11_03_000004_create_platform_audit_logs_table.php`
- **Fields:**
  - `id` (bigint, unsigned, primary key)
  - `platform_user_id` (bigint, unsigned, foreign key to `platform_users(id)`)
  - `tenant_id` (bigint, unsigned, nullable, foreign key to `tenants(id)`)
  - `action` (varchar(50), indexed) &mdash; e.g. `TENANT_CREATED`, `TENANT_UPDATED`, `TENANT_STATUS_CHANGED`
  - `metadata` (json, nullable) &mdash; structured event parameters
  - `ip_address` (varchar(45), nullable)
  - `created_at` (timestamp, indexed)

---

## 3. MODELS & SEEDING

### 3.1 Eloquent Models
1. **`App\Models\Tenant`:**
   - Fillable: `tenant_code`, `name`, `slug`, `status`, `contact_name`, `contact_email`, `contact_phone`, `address`, `notes`.
   - Relationships: `companies()` &rarr; `hasMany(Company::class)`.
   - Helpers: `isActive()`, `isSuspended()`.
2. **`App\Models\Company`:**
   - Added `tenant_id` to fillable attributes.
   - Relationship: `tenant()` &rarr; `belongsTo(Tenant::class)`.
3. **`App\Models\User`:**
   - Added `tenant()` helper returning the tenant instance associated via `$this->company`.
4. **`App\Models\PlatformAuditLog`:**
   - Model tracking Platform Super Admin operations with `platformUser()` and `tenant()` relationships.

### 3.2 TenantSeeder (`database/seeders/TenantSeeder.php`)
- Idempotently creates or updates the default tenant:
  - Code: `TNT-VISHAL`
  - Name: `Vishal Group`
  - Slug: `vishal-group`
  - Status: `active`
- Associates existing companies:
  - `Vishal Enterprise` (ID: 1, Code: `VE`) &rarr; `tenant_id: 1`
  - `Vrishal Eng. Pvt. Ltd.` (ID: 2, Code: `VRE`) &rarr; `tenant_id: 1`
- Executed and verified on both `enterprise_hrms` and `enterprise_hrms_test`.

---

## 4. TENANCY SECURITY & ISOLATION ENFORCEMENT

### 4.1 CompanyContextService Scoping
The existing `App\Services\CompanyContextService` was audited and hardened:
- **`getAccessibleCompanies()`**:
  Previously returned all active companies for HO Admins. It has been strictly updated:
  ```php
  $userTenantId = $user->company ? $user->company->tenant_id : null;
  return Company::where('status', 'Active')
      ->where('tenant_id', $userTenantId)
      ->orderBy('name')
      ->get();
  ```
  **Security Result:** An HO Admin of Vishal Group can switch between `Vishal Enterprise` and `Vrishal Eng. Pvt. Ltd.`, but will never see or access legal companies belonging to any other customer organization.
- **`setActiveCompanyId(int $companyId)`**:
  Now validates that the requested company exists, is active, and matches the user's `tenant_id`:
  ```php
  if ($user && $user->company && $company->tenant_id !== $user->company->tenant_id) {
      return false; // Blocks cross-tenant switching attempts
  }
  ```

### 4.2 EnsureTenantIsActive Middleware
Created `App\Http\Middleware\EnsureTenantIsActive` and registered as route middleware alias `'tenant.active'` in `bootstrap/app.php`. Attached to the web authentication pipeline:
```php
Route::middleware(['auth:web', 'tenant.active'])->group(function () {
    // All protected HRMS customer routes
});
```
- When a tenant is marked as `suspended` or `inactive`, any user belonging to that tenant attempting to access the application is immediately logged out, their session is invalidated, and they are redirected to `/login` with an informative error message.
- Platform Super Admins operate on the separate `platform` guard and are unaffected by customer tenant suspensions, allowing them to inspect, assist, or reactivate customer accounts.

---

## 5. PLATFORM CONTROL CENTER — TENANT MANAGEMENT

### 5.1 Controller (`App\Http\Controllers\Platform\TenantManagementController`)
Platform Super Admins (Advait IT Solutions) manage tenants through dedicated endpoints:
- `GET /platform/tenants` &mdash; Paginated listing, status filter, keyword search.
- `GET /platform/tenants/create` &mdash; Tenant onboarding form with JS slug/code generator.
- `POST /platform/tenants` &mdash; Validation and tenant persistence.
- `GET /platform/tenants/{tenant}` &mdash; Detailed overview showing legal companies, status controls, and tenant-scoped audit logs.
- `GET /platform/tenants/{tenant}/edit` &mdash; Tenant metadata editor.
- `PUT /platform/tenants/{tenant}` &mdash; Tenant update handler.
- `POST /platform/tenants/{tenant}/status` &mdash; Rapid status toggle (Active &harr; Suspended).

### 5.2 Blade Interface
- `resources/views/platform/tenants/index.blade.php`: High-density dashboard with metric cards, filtering, company counts, and status indicators.
- `resources/views/platform/tenants/create.blade.php`: Structured onboarding form with real-time code/slug normalization.
- `resources/views/platform/tenants/edit.blade.php`: Administrative edit form.
- `resources/views/platform/tenants/show.blade.php`: Customer tenant summary, associated legal entities table, and audit trail.
- `resources/views/layouts/platform.blade.php`: Added Customer Tenants navigation pill in header.
- `resources/views/platform/dashboard.blade.php`: Enhanced with tenant count KPIs and updated Phase 2 status badges.

### 5.3 Zero Operational Exposure Compliance
In accordance with SaaS data boundary requirements:
- Platform controllers strictly query entity metadata (`id`, `name`, `code`, `cin_number`, `email`, `phone`, `status`, `created_at`).
- Platform views and controllers contain zero queries or references to employee salaries, payroll registers, bank accounts, or sensitive HR records.

---

## 6. VERIFICATION & TEST SUITE METRICS

A dedicated test suite was created in `tests/Feature/TenantIsolationTest.php` covering all multi-tenancy requirements:

| Test Case | Description | Result |
| :--- | :--- | :---: |
| `test_tenant_seeder_maps_vishal_enterprise_and_vrishal_eng_to_vishal_group` | Verifies initial seeding of TNT-VISHAL and company mapping | **PASS** |
| `test_tenant_seeder_is_idempotent` | Re-running seeder does not create duplicates or corrupt mappings | **PASS** |
| `test_tenant_has_many_companies_relationship` | Eloquent `hasMany` relationship test | **PASS** |
| `test_company_belongs_to_tenant_relationship` | Eloquent `belongsTo` relationship test | **PASS** |
| `test_user_tenant_helper_returns_assigned_tenant` | User helper returns correct tenant via company | **PASS** |
| `test_platform_super_admin_can_view_tenants_list` | Platform Super Admin access to tenant index | **PASS** |
| `test_platform_super_admin_can_create_new_tenant` | Tenant creation and audit log generation | **PASS** |
| `test_tenant_creation_validation_rules` | Rejection of duplicate codes, slugs, and invalid formats | **PASS** |
| `test_platform_super_admin_can_view_tenant_details_without_employee_compensation_exposure` | Data privacy and compensation zero-leakage check | **PASS** |
| `test_platform_super_admin_can_update_tenant_metadata` | Tenant update and audit logging | **PASS** |
| `test_platform_super_admin_can_update_tenant_status` | Status modification and audit logging | **PASS** |
| `test_hrms_users_cannot_access_platform_tenants_endpoints` | HRMS user boundary protection | **PASS** |
| `test_unauthenticated_users_cannot_access_platform_tenants` | Guest redirection to platform login | **PASS** |
| `test_company_context_service_scopes_accessible_companies_to_user_tenant` | Accessible companies strictly filtered by tenant | **PASS** |
| `test_ho_admin_cannot_switch_to_company_in_different_tenant` | Cross-tenant company switching rejection | **PASS** |
| `test_ho_admin_can_switch_between_companies_within_same_tenant` | Intra-tenant company switching allowance | **PASS** |
| `test_suspended_tenant_user_is_blocked_by_middleware` | Tenant suspension middleware lockout and session termination | **PASS** |
| `test_active_tenant_user_can_access_application` | Active tenant access normal operation | **PASS** |
| `test_platform_super_admin_is_not_affected_by_tenant_suspension` | Super admin independence from customer tenant states | **PASS** |

**Tenant Isolation Test Suite Summary:**
- **Tests Executed:** 19
- **Passed:** 19 (100%)
- **Assertions:** 75
- **Failures / Errors:** 0

---

## 7. CONCLUSION & ROADMAP READINESS

Phase 2 is fully implemented, verified, and active in the codebase without altering any core HRMS business logic, employee data, or payroll calculation routines. 

The application is fully prepared for **Phase 3 (Licensing & Subscriptions Engine)**.

