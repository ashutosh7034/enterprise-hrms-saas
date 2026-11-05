# KANVTECH HRMS — WORKFORCE MASTER DIRECTORY BUTTON AUDIT

**Module:** Workforce Master Directory  
**Route:** `GET /employees` (`employees.index`)  
**View:** `resources/views/employees/index.blade.php`  
**Audit Date:** November 5, 2026  
**Auditor:** Antigravity Engineering Pair  

---

## 1. Inventory of Existing Directory Actions

| # | Action Label | Element Type | Target Route | Controller & Method | Target Behavior & Functionality | Audit Findings / Assessment |
|---|---|---|---|---|---|---|
| **1** | **Export Master CSV** | Link Button (`<a>`) | `route('employees.export')` | `EmployeeLifecycleController@export` | Streams a filtered CSV file of all employees matching current search and filter parameters (Branch, Dept, Designation, Status, etc.). | Standalone button. Missing Excel (.xlsx) option. Belongs in a consolidated **Export** dropdown. |
| **2** | **Code Config** | Link Button (`<a>`) | `route('admin.employee-codes.index')` | `EmployeeCodeConfigurationController@index` | Opens the Code Generation Parameters form to configure prefix, sequence, reset rules, and live preview. | Belongs to the Employee Code domain. Separated from Code Master, causing user confusion. |
| **3** | **Code Master** | Link Button (`<a>`) | `route('admin.employee-codes.master')` | `EmployeeCodeConfigurationController@master` | Opens the table list of all assigned employee codes, generation methods (Auto/Manual), and CSV export. | Belongs to the Employee Code domain. Should be unified with Code Config into **Employee Code Settings**. |
| **4** | **Onboard Candidate** | Link Button (`<a>`) | `route('employees.create')` | `EmployeeController@create` | Opens the comprehensive 16-step Employee Onboarding Wizard. | Primary directory action. Should be clearly labeled **+ Add Employee** for standard enterprise terminology. |
| **5** | **Apply (Filter)** | Submit Button (`<button>`) | `route('employees.index')` | `EmployeeController@index` | Applies search query and dropdown filters (branch, department, designation, cadre, channel, status). | Working as expected inside the search card. Preserved intact. |
| **6** | **Clear Filters** | Link Button (`<a>`) | `route('employees.index')` | `EmployeeController@index` | Resets query parameters and refreshes the directory. | Working as expected. Preserved intact. |
| **7** | **View (Row)** | Link Button (`<a>`) | `route('employees.show', $emp->id)` | `EmployeeController@show` | Opens the detailed employee dossier and lifecycle hub. | Working as expected. Preserved intact. |
| **8** | **Lifecycle Actions (Row)** | Dropdown Button | Multiple lifecycle routes | `EmployeeLifecycleController` | Dropdown containing: Lifecycle Hub, Transfer Branch, Convert Intern, Manage Confirmation, Edit Dossier. | Working as expected. Preserved intact. |
| **9** | **Mass Upload / Bulk Import** | *Missing in UI* | *None* | *None* | Bulk import was previously requested but not yet implemented on the directory page. | **CRITICAL GAP:** Must be implemented as a dedicated, robust 6-step Bulk Import system supporting Excel/CSV templates, parsing, validation, duplicate checks, error reporting, and audit logs. |

---

## 2. Consolidation Mapping

### Header Actions: Before vs. After

```
BEFORE:
[Export Master CSV] [Code Config] [Code Master] [Onboard Candidate]

AFTER:
[+ Add Employee] [Bulk Import ▼] [Export ▼] [Employee Code Settings]
```

### 1. `+ Add Employee`
- **Label:** `+ Add Employee` (replaces `Onboard Candidate`)
- **Route:** `employees.create` (preserving full 16-section onboarding wizard)
- **Role:** Primary prominent button with `btn-primary`

### 2. `Bulk Import`
- **Label:** `Bulk Import`
- **Control:** Modal trigger and standalone page (`/employees/bulk-import`)
- **Includes:**
  - Download Excel Template (`.xlsx`)
  - Download CSV Template (`.csv`)
  - File Upload (drag-and-drop & file picker)
  - Data Parsing & Preview
  - Server-side Validation & Duplicate Detection
  - Confirmation & Execution
  - Detailed Import Summary & Downloadable Error Report

### 3. `Export`
- **Label:** `Export`
- **Control:** Dropdown menu:
  - `Export to CSV (.csv)` &rarr; `employees.export?format=csv`
  - `Export to Excel (.xlsx)` &rarr; `employees.export?format=xlsx`
- **Features:** Preserves active filter queries, tenant boundaries, and RBAC authorization.

### 4. `Employee Code Settings`
- **Label:** `Employee Code Settings` (consolidates `Code Config` and `Code Master`)
- **Route:** `admin.employee-codes.settings` (or `admin.employee-codes.index` unified)
- **Interface:** Tabbed unified management:
  - **Tab 1: Code Format & Sequence Configuration** (prefix, number length, reset rules, live preview badge, AJAX refresh)
  - **Tab 2: Code Master List** (filter, search, code generation audit, export CSV)
- **Backward Compatibility:** All existing routes (`admin.employee-codes.index`, `admin.employee-codes.master`, `admin.employee-codes.update`, `admin.employee-codes.export`) remain active and functional.

