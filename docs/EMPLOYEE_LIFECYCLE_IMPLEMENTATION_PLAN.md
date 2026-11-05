# Kanvtech Enterprise HRMS — Employee Lifecycle & Admin-Controlled Employee Master
## Architecture & Technical Implementation Plan

**Module:** Employee Master / Employee Lifecycle Management  
**Workspace:** `C:\Users\Ashutosh Pandey\Downloads\HRMS_product`  
**Target Environment:** Local Development (`127.0.0.1:8000`, DB: `enterprise_hrms`)  
**Safety Protocol:** Strictly additive migrations, zero record deletion, preserving 349 active employee records.

---

## 1. System Architecture & Entity Design

### 1.1 Existing System Audit & Reusability
The application already provides:
- Multi-tenant architecture with `CompanyContextService` and `company_id` scoping.
- Existing master tables: `companies` (`code`, `name`), `locations` (`name`, `code`), `departments` (`name`, `code`), `designations` (`title`, `code`).
- Existing `employees` table holding core identity, personal, and basic employment fields.
- Existing RBAC model with roles (`ho_admin`, `hr_executive`, `manager_mss`, `employee_ess`).

### 1.2 Target Additive Entities
To deliver the requested lifecycle features cleanly without modifying legacy schemas destructively, the following additive migrations will be executed:

1. **`2026_11_05_000011_add_lifecycle_and_joining_attributes_to_employees_table.php`**:
   - `joining_location_id` (foreign key to `locations`) — preserves initial site allocation.
   - `joining_department_id` (foreign key to `departments`) — preserves initial department.
   - `joining_designation_id` (foreign key to `designations`) — preserves initial role.
   - `probation_start_date` (date, nullable).
   - `confirmation_due_date` (date, nullable).
   - `confirmation_status` (enum/string, default: `'Not Applicable'`).
   - `initial_employment_status` (string, nullable: `'Active'`, `'Probation'`, `'Intern'`).
   - `confirmation_remarks` (text, nullable).
   - `confirmation_letter_path` (string, nullable).
   - `code_generation_method` (string, default: `'Automatic'`).

2. **`2026_11_05_000012_create_employee_code_configurations_table.php`**:
   - Holds tenant/company-specific code generation templates:
     - `company_id`
     - `prefix` (default: `'EMP'`)
     - `company_prefix` (default: `'VE'`)
     - `branch_prefix` (nullable)
     - `department_prefix` (nullable)
     - `financial_year_prefix` (nullable)
     - `number_length` (default: 4)
     - `starting_sequence_number` (default: 1001)
     - `separator` (default: `'-'`)
     - `format_template` (default: `'{COMPANY}-{PREFIX}-{SEQUENCE}'`)
     - `reset_sequence_annually` (boolean, default: false)
     - `reset_sequence_per_branch` (boolean, default: false)
     - `reset_sequence_per_company` (boolean, default: true)
     - `allow_manual_employee_code` (boolean, default: false)
     - `uniqueness_scope` (string: `'company'`, `'tenant'`, `'global'`)
     - `is_active` (boolean, default: true)

3. **`2026_11_05_000013_create_employee_code_sequences_table.php`**:
   - Concurrency-safe atomic sequence tracking:
     - `company_id`
     - `sequence_key` (unique string index, e.g., `'VE_GLOBAL'` or `'VE_MUM_2026'`)
     - `last_number` (bigint unsigned)

4. **`2026_11_05_000014_create_employee_conversions_table.php`**:
   - Tracks Intern-to-Permanent and Employment Type conversions:
     - `employee_id`, `company_id`
     - `from_employee_type`, `to_employee_type`
     - `from_designation_id`, `to_designation_id`
     - `from_department_id`, `to_department_id`
     - `from_location_id`, `to_location_id`
     - `conversion_effective_date`
     - `new_salary_structure`
     - `confirmation_required` (boolean)
     - `status` (`'Draft'`, `'Pending Approval'`, `'Approved'`, `'Rejected'`, `'Completed'`, `'Cancelled'`)
     - `remarks`
     - `initiated_by`, `approved_by`, `approved_at`

5. **`2026_11_05_000015_create_employee_branch_transfers_table.php`**:
   - Tracks branch and department mobility:
     - `employee_id`, `company_id`
     - `from_location_id`, `to_location_id`
     - `from_department_id`, `to_department_id`
     - `from_designation_id`, `to_designation_id`
     - `from_reporting_manager_id`, `to_reporting_manager_id`
     - `transfer_type` (`'Permanent'`, `'Temporary'`)
     - `transfer_effective_date`
     - `transfer_end_date` (nullable)
     - `reason`, `remarks`
     - `status` (`'Draft'`, `'Pending Approval'`, `'Approved'`, `'Rejected'`, `'Completed'`, `'Cancelled'`)
     - `initiated_by`, `approved_by`, `approved_at`

6. **`2026_11_05_000016_create_employee_confirmation_records_table.php`**:
   - Tracks probation extensions, confirmations, and decisions:
     - `employee_id`, `company_id`
     - `action_type` (`'Confirmation'`, `'Probation Extension'`, `'Rejection'`)
     - `previous_probation_end_date`, `new_probation_end_date`
     - `confirmation_effective_date`
     - `confirmation_letter_path`, `remarks`
     - `status` (`'Confirmed'`, `'Probation Extended'`, `'Rejected'`)
     - `approved_by`, `approved_at`

7. **`2026_11_05_000017_create_employee_lifecycle_events_table.php`**:
   - Master immutable timeline ledger for audit compliance:
     - `employee_id`, `company_id`
     - `event_type` (`'Employee Created'`, `'Date of Joining Updated'`, `'Employment Type Changed'`, `'Intern Converted to Permanent'`, `'Employee Confirmed'`, `'Probation Extended'`, `'Branch Transferred'`, `'Department Transferred'`, `'Designation Changed'`, `'Reporting Manager Changed'`, `'Employee Code Changed'`, `'Employee Status Changed'`)
     - `previous_value`, `new_value` (JSON / string)
     - `effective_date`
     - `reason`, `remarks`
     - `requested_by`, `approved_by`, `approved_at`

---

## 2. Core Modules & Implementation Details

### 2.1 Date of Joining & Preservation of Joining vs Current Attributes
- During Onboarding / Creation:
  - `date_of_joining` is mandatory.
  - `joining_location_id = location_id`.
  - `joining_department_id = department_id`.
  - `joining_designation_id = designation_id`.
- During Branch/Department/Designation Transfers:
  - `date_of_joining` is immutable by default (editable only with explicit HR/Admin audit override).
  - `joining_location_id`, `joining_department_id`, `joining_designation_id` are permanently preserved.
  - `location_id`, `department_id`, `designation_id` update to the new branch when transfer is effective.

### 2.2 Concurrency-Safe Employee Code Generator
- Service: `App\Services\EmployeeCodeGeneratorService`
- Formula Parser: Replaces placeholders `{COMPANY}`, `{BRANCH}`, `{DEPT}`, `{FY}`, `{SEQUENCE}` using company/branch codes and zero-padded sequence numbers based on `number_length`.
- Race condition mitigation: Uses `DB::transaction()` with pessimistic locking `EmployeeCodeSequence::lockForUpdate()` on `sequence_key` so concurrent onboarding requests never collide or skip numbers.
- Live preview API: `POST /admin/employee-codes/preview` returns the formatted string for interactive admin testing.
- Manual edit override: When `allow_manual_employee_code` is enabled, authorized admins can manually input a code, automatically checked against tenant/company uniqueness rules.

### 2.3 Intern-to-Permanent Conversion Engine
- Controller: `EmployeeLifecycleController@convertEmploymentType`
- Business Rules:
  - Retains existing `employee_code` and database `id`.
  - Supports immediate or future-dated conversion. If immediate (effective today or past), updates `employee_type = 'Permanent'`, new designation/branch/department, and logs to `EmployeeLifecycleEvent`.
  - Preserves historic intern tenure and attendance/payroll linkages.
  - If confirmation is required, updates `confirmation_status = 'On Probation'`.

### 2.4 Employee Confirmation Management
- Controller: `EmployeeLifecycleController@confirmEmployee` & `extendProbation`
- Business Rules:
  - Confirmation status: `Not Applicable`, `On Probation`, `Confirmation Due`, `Confirmed`, `Probation Extended`, `Rejected / Employment Discontinued`.
  - Probation extension requires revision of `probation_end_date` and mandatory justification remarks.
  - Confirmation allows uploading formal confirmation letter directly into the private encrypted vault.

### 2.5 Branch & Department Transfer Management
- Controller: `EmployeeLifecycleController@branchTransfer`
- Business Rules:
  - Supports `Permanent` or `Temporary` transfers.
  - Immediate execution or scheduled future-effective date.
  - Updates current `location_id`, `department_id`, `designation_id`, `reporting_manager_id`.
  - Retains `joining_location_id` completely untouched.

### 2.6 Redesigned Employee Master Directory
- Controller: `EmployeeController@index`
- Columns:
  1. Employee Code & ID
  2. Employee Name & Avatar
  3. Company & Joining Branch vs Current Branch
  4. Department & Designation
  5. Employment Type (Pill: Permanent, Intern, Contract, etc.)
  6. Date of Joining
  7. Confirmation Status (Badge)
  8. Reporting Manager
  9. Employment Status (Active, Probation, etc.)
  10. Action Menu (View Dossier, Edit, Lifecycle Hub, Transfer, Convert, Confirm)
- Advanced Filters: Branch, Department, Designation, Employment Type, Confirmation Status, Date of Joining range, Search by Name/Code/Phone.

### 2.7 Employee Code Master & Audit Hub
- Route: `/admin/employee-codes`
- Lists all assigned employee codes, generation methods (`Automatic` vs `Manual`), creation timestamps, company, and filters.
- Supports CSV/Excel export.

---

## 3. Testing & Verification Strategy

1. **Unit & Feature Tests (`tests/Feature/EmployeeLifecycleManagementTest.php`):**
   - Concurrency-safe code sequence generation.
   - Code preview with format templates.
   - Preserving Date of Joining and Joining Branch during transfers.
   - Intern-to-permanent conversion without ID changes.
   - Confirmation and probation extension workflows.
   - Branch transfer with permanent vs temporary modes.
   - Immutable lifecycle history ledger.
   - Role-based authorization and IDOR protection.
2. **Automated Headless Browser UAT (Chromium via CDP):**
   - Render Employee Directory with new columns and filters.
   - Navigate to Employee Profile -> Employee Lifecycle tab.
   - Execute Branch Transfer form modal.
   - Execute Intern-to-Permanent Conversion modal.
   - Execute Confirmation & Probation Extension modal.
   - Configure custom Employee Code format and test preview.
   - Capture UAT screenshots to `docs/screenshots/uat/`.

---

## 4. Rollback Strategy
All migrations are strictly additive with corresponding `down()` methods dropping foreign keys and tables cleanly without affecting existing employee records.

