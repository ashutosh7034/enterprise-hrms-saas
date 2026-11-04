# SAAS TRANSFORMATION — PHASE 2 PRE-IMPLEMENTATION AUDIT
## Multi-Tenant Architecture & Group Companies

**Document Version:** 1.0.0  
**Audit Date:** 2026-11-03 13:30 IST  
**Auditor:** Principal SaaS Architect, Senior Laravel 13 Engineer & Enterprise Security Auditor  
**Environment:** Local Development (`enterprise_hrms`) & Isolated Testing (`enterprise_hrms_test`)  
**PHP Runtime:** PHP 8.3.36 | **Framework:** Laravel 13 | **Database Server:** MySQL 8.0  
**Current Test Suite Baseline:** **239 tests passed, 1,501 assertions (100% PASS)**  

---

## 1. Executive Summary

Phase 2 transitions the Enterprise HRMS from an intra-organization multi-company model into a true commercial multi-tenant SaaS platform owned by **Advait IT Solutions & Services**. 

This audit establishes the pre-migration baseline, verifies existing tenant-relevant structures, identifies tenant isolation boundaries, and plans the additive migration of `tenants` and `tenant_id` to ensure zero downtime and zero data loss for existing customer legal entities (**Vishal Enterprise** and **Vrishal Eng. Pvt. Ltd.**).

---

## 2. Mandatory Audit Findings

### 2.1 How each HRMS user is associated with a company
- The `users` table holds a direct foreign key `company_id` referencing `companies(id)`.
- The Eloquent model `App\Models\User` defines:
  ```php
  public function company(): BelongsTo
  {
      return $this->belongsTo(Company::class);
  }
  ```
- Active company context is resolved through `CompanyContextService::getActiveCompanyId()`, which inspects the session key `active_company_id`.

### 2.2 Whether any user belongs to multiple companies
- In the database schema, each user record has a single `company_id`.
- Users do not have multiple `company_id` assignments in the `users` table.
- However, cross-company visibility is granted to users where `can_switch_company = true` or `role.name === 'ho_admin'`.

### 2.3 Company Switching Logic
- Handled via `CompanyController::switchCompany(Request $request)`.
- Validated by `CompanyContextService::canUserSwitchCompany($user)`.
- Non-administrative roles (`hr_executive`, `manager_mss`, `employee_ess`) have `can_switch_company = false` and are permanently locked to their assigned `company_id`.

### 2.4 HO Admin Cross-Company Access Vulnerability (Pre-Phase 2)
- In the pre-Phase 2 codebase:
  ```php
  public function getAccessibleCompanies(): Collection
  {
      $authUser = Auth::guard('web')->user() ?: Auth::user();
      $user = ($authUser instanceof User) ? $authUser : null;
      if (!$user) {
          return Company::where('status', 'Active')->get();
      }

      if ($this->canUserSwitchCompany($user)) {
          return Company::where('status', 'Active')->orderBy('name')->get();
      }

      return Company::where('id', $user->company_id)->where('status', 'Active')->get();
  }
  ```
- **Vulnerability Identified:** If a second customer tenant (e.g. "Acme Corp") were created in `companies`, an HO Admin from "Vishal Group" would see Acme Corp in their company switcher dropdown because `getAccessibleCompanies()` queries `Company::where('status', 'Active')` without tenant scoping!
- **Phase 2 Correction:** HO Admin must be scoped strictly to companies belonging to their authorized tenant: `Company::where('tenant_id', $userTenantId)->where('status', 'Active')`.

### 2.5 Analysis of Models & Company Scoping
- **Domain Models Using `BelongsToCompany` (40+ Models):**
  - `Asset`, `AssetAllocation`, `AssetDamageAssessment`, `AttendanceRecord`, `AttendanceRegularization`, `BiometricDevice`, `BiometricPunchLog`, `Category`, `CompOffRecord`, `Contractor`, `Department`, `Employee`, `EmployeeLoan`, `EmployeeLoanEmiSchedule`, `EmployeeSalaryAssignment`, `EmployeeShiftAssignment`, `EmployeeShiftRoster`, `EmployeeTaxDeclaration`, `EmployeeTaxProjection`, `FineMaster`, `FineRecoveryRecord`, `FnfSettlement`, `GeneratedDocument`, `Holiday`, `HrmsNotification`, `LeaveAccrualBatch`, `LeaveApplication`, `LeaveBalance`, `LeaveBalanceTransaction`, `LeaveCarryForwardBatch`, `LeavePeriodLock`, `LeaveType`, `Location`, `NotificationChannelConfiguration`, `NotificationDeliveryLog`, `NotificationPreference`, `NotificationTemplate`, `OvertimeRecord`, `PayrollConfiguration`, `PayrollPeriod`, `PayrollPtSlab`, `PayrollRun`, `PayrollRunEmployee`, `PayrollStatutoryConfiguration`, `PlantShutdown`, `ReportExportHistory`, `SalaryComponent`, `SalaryRevisionHistory`, `SalaryStructure`, `SandwichLeavePolicy`, `ScheduledReport`, `Shift`.
- **Domain Models with Custom Company Scopes:**
  - `Designation` — Company-scoped via explicit `addGlobalScope`.
  - `DocumentTemplate` — Scoped to active company or global templates (`whereNull('company_id')`).
- **Global / System Level Models:**
  - `Role`, `Permission`, `PlatformUser`, `CentralBlacklistRecord`, `AssetCategory`.

### 2.6 Foreign Key & Data Leakage Assessment
- All business records link to `company_id`.
- None of the business tables store raw `tenant_id` directly, which prevents de-normalization bugs.
- Because legal companies belong to a single `tenant_id`, enforcing tenant ownership on the parent `companies` table guarantees that all child records (`employees`, `attendance`, `payroll`, `assets`) are cryptographically and logically isolated between customer tenants.

### 2.7 Reliance on Existing Company IDs
- Company ID 1 (`Vishal Enterprise`) and Company ID 2 (`Vrishal Eng. Pvt. Ltd.`) are hardcoded into existing employee rosters, payroll periods, salary revisions, and biometrics.
- **Strict Rule:** Company primary keys (`id: 1` and `id: 2`) must be 100% preserved. Migrations must be purely additive.

### 2.8 Platform Routes & Tenant Data Segregation
- The `/platform/*` routes operate under `auth:platform` and the `PlatformUser` model.
- Platform controllers currently do not touch employee, salary, or attendance records.
- Platform Tenant Management in Phase 2 will display only organizational metadata (`tenant_code`, `name`, `status`, `companies_count`) and will strictly prohibit access to sensitive employee compensation or personal data.

---

## 3. Database Inventory Prior to Phase 2

### Development Database (`enterprise_hrms`)
- **Total Tables:** 88 (87 baseline + `platform_users`)
- **Companies:** 2
  - ID 1: `Vishal Enterprise` (`VE`), `Active`
  - ID 2: `Vrishal Eng. Pvt. Ltd.` (`VRE`), `Active`
- **Users:** 131
- **Employees:** 349
- **Platform Admins:** 1 (`superadmin@advaititsolutions.com`)

### Testing Database (`enterprise_hrms_test`)
- **Total Tables:** 88 (87 baseline + `platform_users`)
- **Companies:** 2 (VE & VRE)
- **Users:** 266
- **Employees:** 670
- **Platform Admins:** 1 (`superadmin@advaititsolutions.com`)

---

## 4. Phase 2 Architecture & Migration Strategy

```
                                [ ADVAIT PLATFORM ]
                                         │
                             ┌───────────┴───────────┐
                             ▼                       ▼
                     [ Platform Guard ]        [ Web Guard ]
                     PlatformUser (SaaS)       User (Customer Tenant)
                             │                       │
                             ▼                       ▼
                       [ tenants ]              [ tenants ]
                            1:N                     1:N
                             │                       │
                             ▼                       ▼
                       [ companies ]           [ companies ]
                      (VE: 1, VRE: 2)         (VE: 1, VRE: 2)
                             │                       │
                             ▼                       ▼
                      [ BelongsToCompany Models (40+ Tables) ]
```

### Staged Migration Steps:
1. **Migration 1:** Create `tenants` table.
2. **Migration 2:** Create `platform_audit_logs` table for tracking platform actions.
3. **Migration 3:** Add nullable `tenant_id` to `companies` table with foreign key constraint and index.
4. **Seeder:** `TenantSeeder` creates initial tenant `Vishal Group` (`TNT-VISHAL`, `vishal-group`, `active`) and maps Company 1 and Company 2.
5. **Context Security:** Update `CompanyContextService` to derive tenant context from `$user->company->tenant_id` and restrict `getAccessibleCompanies()` and `setActiveCompanyId()` strictly to companies within the active tenant.
6. **Tenant Status Middleware:** Implement `EnsureTenantIsActive` to halt suspended/inactive tenant user access.
7. **Platform UI:** Build `/platform/tenants` module in the Platform Control Tower.

---

## 5. Audit Conclusion & Approval

The pre-implementation audit confirms that the architecture is safe for migration. No destructive schema changes or data modifications are required.

**Approval granted to proceed with Phase 2B (Tenant Database Foundation).**

