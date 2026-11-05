# Kanvtech HRMS — Centralized Master Value Scope Implementation Report

## Executive Summary
This document provides a comprehensive report of the implementation of the **Dynamic Scope Dropdown System** in the **Add & Edit Master Value Modals** under **Admin → Dropdown Masters** (`/admin/masters`).

The solution introduces 4 distinct governance scopes, full tenant and company isolation, branch-level granularity, dynamic UI field cascade, client-side company-branch filtering, and duplicate prevention within identical scopes.

---

## 1. Scope Dropdown Options & Architectural Mapping

| Scope Name | Form Value | Database Mapping | Behavior & Visibility |
| :--- | :--- | :--- | :--- |
| **Global Enterprise Standard** | `global` | `company_id = NULL`, `location_id = NULL` | Available enterprise-wide across all active and future tenant entities. Read-only for company-level administrators; editable only by HO Administrators & Super Admins. |
| **Active Company Only** | `active_company` | `company_id = $activeCompany->id`, `location_id = NULL` | Available exclusively to the currently active entity in context (`company_id`). |
| **Selected Company** | `selected_company` | `company_id = $inputCompanyId`, `location_id = NULL` | Scoped to a specific selected company within the enterprise cluster. |
| **Selected Branch** | `selected_branch` | `company_id = $inputCompanyId`, `location_id = $inputLocationId` | Granular facility-level scope. Available only to employees and onboarding dossiers attached to the specific physical branch/location. |

---

## 2. Dynamic UI & Cascading Behavior

1. **Reactive Field Visibility**:
   - When **Global Enterprise Standard** or **Active Company Only** is selected:
     - Target Company and Target Branch groups are completely hidden.
     - Form inputs are stripped of the HTML5 `required` attribute.
     - Dynamic contextual help messages appear below the scope selector in enterprise teal or green.
   - When **Selected Company** is chosen:
     - The **Target Company** select field appears with `required` validation.
     - Target Branch remains hidden.
   - When **Selected Branch** is chosen:
     - Both **Target Company** and **Target Branch / Location** select fields become visible and mandatory.
2. **Dynamic Client-Side Branch Filtering**:
   - On changing Target Company, the Branch dropdown automatically re-evaluates `company_id` without full page reload.
   - Prevents selecting branches that belong to a different company.
3. **Table View Scope Badges**:
   - Global Standard: `<span class="badge bg-secondary-subtle">...</span>` with `bi-globe2` icon.
   - Company Scope: `<span class="badge bg-primary-subtle">...</span>` with `bi-building` icon and company code (`Company: VE`).
   - Branch Scope: `<span class="badge bg-info-subtle">...</span>` with `bi-geo-alt` icon and branch name (`Branch: Ground Plant 1`).
4. **Edit Modal Hydration**:
   - Clicking `.btn-edit-master` extracts `data-scope`, `data-company-id`, and `data-location-id`, auto-selects the corresponding scope option, filters branch options, and displays the appropriate inputs and guidance text.

---

## 3. Backend Governance & Security Enforcement

1. **Database Schema**:
   - Migration `2026_11_05_000022_add_location_id_to_onboarding_dropdown_masters_table.php` added nullable foreign key `location_id` referencing `locations(id)` with cascading nullification on delete.
2. **Server-Side Validation**:
   - `scope`: `in:global,active_company,selected_company,selected_branch,company`.
   - `company_id`: `required_if:scope,selected_company,selected_branch|nullable|exists:companies,id`.
   - `location_id`: `required_if:scope,selected_branch|nullable|exists:locations,id`.
   - Branch-to-Company relationship verified server-side (`Location::where('id', $locId)->where('company_id', $compId)->exists()`).
3. **Duplicate Prevention**:
   - Master value names are verified for uniqueness within the exact same category and scope combination:
     - Global: `whereNull('company_id')->whereNull('location_id')`.
     - Company: `where('company_id', $id)->whereNull('location_id')`.
     - Branch: `where('company_id', $compId)->where('location_id', $locId)`.
   - Same master value names are permitted across different companies/branches, preserving autonomy.
4. **Tenant & Company Isolation Query**:
   - `OnboardingDropdownMaster::scopeForScope($companyId, $tenantId, $locationId)`:
     - Global records: `whereNull('company_id')`.
     - Scoped records: matching active company ID and location ID.
     - Cross-company records: completely excluded.
5. **Full Audit Logging**:
   - Both `create()` and `update()` in `OnboardingMasterService` capture `company_id` and `location_id` in audit ledgers.

---

## 4. Verification & Regression Test Results

All 42 feature tests passed with 230 assertions:

```text
PASS  Tests\Feature\MasterValueScopeDropdownTest (10 tests, 38 assertions)
  ✓ masters page displays all scope options and badges
  ✓ can create global enterprise standard master value
  ✓ can create active company scoped master value
  ✓ can create selected company scoped master value
  ✓ can create selected branch scoped master value
  ✓ rejects branch from mismatched company
  ✓ duplicate master value is prevented within same scope
  ✓ same name is allowed in different company scopes
  ✓ can update master value scope
  ✓ tenant and company isolation in options query

PASS  Tests\Feature\OnboardingDropdownMasterTest (7 tests, 18 assertions)
PASS  Tests\Feature\MasterCategoryDropdownOverflowFixTest (4 tests, 36 assertions)
PASS  Tests\Feature\OnboardingAutoSaveAndResumeTest (8 tests, 51 assertions)
PASS  Tests\Feature\WorkforceDirectoryActionConsolidationTest (7 tests, 24 assertions)
PASS  Tests\Feature\CollapsibleSidebarNavigationTest (6 tests, 63 assertions)

Total: 42 passed, 230 assertions (0 regressions)
```

---

## 5. Visual Proof of Work (Artifacts)

1. `39_master_table_scope_badges.png`: Table view with Scope column showing badges.
2. `40_add_master_modal_scope_default.png`: Add Master Value modal showing Scope dropdown default.
3. `41_add_master_modal_selected_branch_dynamic_fields.png`: Add Master Value modal with "Selected Branch" chosen and dynamic company and branch dropdowns.
4. `42_edit_master_modal_scope_populated.png`: Edit Master Value modal showing pre-populated scope.

---

## Final Verdict
**STATUS: READY FOR UAT**

