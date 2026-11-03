# Enterprise HRMS — Phase 2 Database Changes & Schema Documentation
## Non-Destructive Additive Migrations (Migrations 11–23)

**Database Target**: MySQL 8.0.46 Community Server (Development) / MySQL 8.4 LTS (Production)  
**Strategy**: Non-destructive, additive migrations only. Zero modifications to existing tables or migrations.  
**Total Tables Added in Phase 2**: 12 tables + 1 column nullability refinement migration.  
**Status**: All migrations executed and verified.

---

## 1. Migration Index

| Migration File | Table Name | Purpose |
|---|---|---|
| `2026_11_02_000011_create_employees_table.php` | `employees` | Core workforce master records with company tenancy, masked Aadhaar, and eKYC tracking |
| `2026_11_02_000012_create_employee_gate_pass_histories_table.php` | `employee_gate_pass_histories` | Audit log of Gate Pass / RFID badge assignments and changes |
| `2026_11_02_000013_create_employee_documents_table.php` | `employee_documents` | Document metadata and verification statuses |
| `2026_11_02_000014_create_employee_document_versions_table.php` | `employee_document_versions` | Versioned file storage history for audit compliance |
| `2026_11_02_000015_create_employee_ekyc_attempts_table.php` | `employee_ekyc_attempts` | Complete attempt history for eKYC lifecycle, rate limiting, and failure tracking |
| `2026_11_02_000016_create_employee_previous_employments_table.php` | `employee_previous_employments` | Group and external employment history for re-hires and transfers |
| `2026_11_02_000017_create_central_blacklist_records_table.php` | `central_blacklist_records` | Group-wide central blacklist & whitelist registry (NO company tenancy global scope) |
| `2026_11_02_000018_create_identity_match_reviews_table.php` | `identity_match_reviews` | Resolution queue for fuzzy or partial blacklist identity matches |
| `2026_11_02_000019_create_asset_categories_table.php` | `asset_categories` | Asset classification, useful life in months, and default depreciation rates |
| `2026_11_02_000020_create_assets_table.php` | `assets` | Asset registry with company tenancy, purchase cost, salvage value, and serial tracking |
| `2026_11_02_000021_create_asset_allocations_table.php` | `asset_allocations` | Asset custody assignment, return inspection, and condition tracking |
| `2026_11_02_000022_create_fine_and_recovery_tables.php` | `fine_masters`, `asset_damage_assessments`, `fine_recovery_records` | Fine tariffs, damage assessments, waivers, and payroll-deduction-ready installment ledgers |
| `2026_11_02_000023_make_to_date_nullable_in_employee_previous_employments.php` | `employee_previous_employments` | Additive adjustment allowing null `to_date` for ongoing tenure or cross-company transfers |

---

## 2. Table-by-Table Schema Specifications

### 2.1 Table: `employees`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_code` (varchar 50, unique within company)
- `gate_pass_number` (varchar 50, nullable, indexed)
- `employment_channel` (enum: `'Direct'`, `'Contractor'`, `'Consultant'`, `'Apprentice'`)
- `contractor_id` (bigint, unsigned, nullable, FK -> `contractors.id`, nullOnDelete)
- `employee_type` (enum: `'Permanent'`, `'Probation'`, `'Contractual'`, `'Trainee'`)
- `location_id` (bigint, unsigned, FK -> `locations.id`)
- `department_id` (bigint, unsigned, FK -> `departments.id`)
- `category_id` (bigint, unsigned, FK -> `categories.id`)
- `designation_id` (bigint, unsigned, FK -> `designations.id`)
- `first_name` (varchar 100)
- `last_name` (varchar 100)
- `email` (varchar 191, nullable)
- `phone` (varchar 30, indexed)
- `gender` (enum: `'Male'`, `'Female'`, `'Other'`)
- `date_of_birth` (date)
- `date_of_joining` (date)
- `aadhaar_hash` (varchar 64, nullable, indexed) — *SHA-256 token; raw Aadhaar is NEVER stored*
- `aadhaar_masked` (varchar 20, nullable) — *Format: XXXX-XXXX-1234*
- `pan_number` (varchar 20, nullable, indexed)
- `address` (text, nullable)
- `status` (enum: `'Draft'`, `'Active'`, `'Suspended'`, `'Absconding'`, `'Terminated'`, `'Resigned'`, `'Blacklisted'`)
- `ekyc_status` (enum: `'Pending'`, `'Grace Period'`, `'Verified'`, `'Verified (HO Override)'`, `'Verification Failed'`)
- `ekyc_grace_ends_at` (timestamp, nullable)
- `created_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`, `deleted_at` (soft deletes)

### 2.2 Table: `employee_gate_pass_histories`
- `id` (bigint, unsigned, auto_increment, PK)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `previous_gate_pass` (varchar 50, nullable)
- `new_gate_pass` (varchar 50, nullable)
- `reason` (varchar 255, nullable)
- `changed_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`

### 2.3 Table: `employee_documents`
- `id` (bigint, unsigned, auto_increment, PK)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `document_type` (varchar 100) — *e.g., Aadhaar, PAN, Educational Certificate, Bank Passbook*
- `document_number` (varchar 100, nullable)
- `file_path` (varchar 255)
- `file_name` (varchar 255)
- `file_size_kb` (int, nullable)
- `mime_type` (varchar 100, nullable)
- `verification_status` (enum: `'Pending'`, `'Verified'`, `'Rejected'`)
- `verified_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `verified_at` (timestamp, nullable)
- `rejection_reason` (text, nullable)
- `timestamps`, `deleted_at` (soft deletes)

### 2.4 Table: `employee_document_versions`
- `id` (bigint, unsigned, auto_increment, PK)
- `employee_document_id` (bigint, unsigned, FK -> `employee_documents.id`, cascade)
- `version_number` (int, default 1)
- `file_path` (varchar 255)
- `file_name` (varchar 255)
- `file_size_kb` (int, nullable)
- `mime_type` (varchar 100, nullable)
- `uploaded_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`

### 2.5 Table: `employee_ekyc_attempts`
- `id` (bigint, unsigned, auto_increment, PK)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `adapter` (varchar 50) — *`simulated`, `manual`, or future government provider*
- `status` (enum: `'Success'`, `'Failed'`, `'Pending'`)
- `reference_id` (varchar 100, nullable)
- `error_code` (varchar 100, nullable)
- `error_message` (text, nullable)
- `attempt_number` (int, default 1)
- `raw_payload` (json, nullable)
- `attempted_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`

### 2.6 Table: `employee_previous_employments`
- `id` (bigint, unsigned, auto_increment, PK)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `company_name` (varchar 191)
- `designation` (varchar 191)
- `from_date` (date)
- `to_date` (date, nullable) — *Refined nullable via Migration 23*
- `last_drawn_salary` (decimal 12,2, nullable)
- `reason_for_leaving` (text, nullable)
- `exit_status` (enum: `'Normal Exit'`, `'Resigned'`, `'Terminated'`, `'Absconded'`, `'Disciplinary Exit'`)
- `is_internal_company` (boolean, default false)
- `previous_employee_code` (varchar 50, nullable)
- `timestamps`

### 2.7 Table: `central_blacklist_records`
- `id` (bigint, unsigned, auto_increment, PK)
- `source_company_id` (bigint, unsigned, nullable, FK -> `companies.id`, nullOnDelete)
- `first_name` (varchar 100)
- `last_name` (varchar 100)
- `date_of_birth` (date, nullable)
- `phone` (varchar 30, nullable, indexed)
- `aadhaar_hash` (varchar 64, nullable, indexed)
- `pan_number` (varchar 20, nullable, indexed)
- `reason_category` (enum: `'Theft / Fraud'`, `'Violence / Misconduct'`, `'Absconding'`, `'Gross Negligence'`, `'Regulatory Bar'`, `'Other'`)
- `reason_details` (text)
- `status` (enum: `'Proposed'`, `'Active'`, `'Whitelisted'`)
- `proposed_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `approved_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `approved_at` (timestamp, nullable)
- `whitelisted_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `whitelisted_at` (timestamp, nullable)
- `whitelist_reason` (text, nullable)
- `timestamps`, `deleted_at`

### 2.8 Table: `identity_match_reviews`
- `id` (bigint, unsigned, auto_increment, PK)
- `candidate_payload` (json)
- `blacklist_record_id` (bigint, unsigned, FK -> `central_blacklist_records.id`, cascade)
- `matched_field` (varchar 100)
- `confidence_score` (decimal 5,2) — *Percentage e.g. 85.00*
- `status` (enum: `'Pending Review'`, `'Confirmed Blacklist'`, `'False Positive Cleared'`)
- `reviewed_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `reviewed_at` (timestamp, nullable)
- `resolution_notes` (text, nullable)
- `timestamps`

### 2.9 Table: `asset_categories`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `name` (varchar 100)
- `code` (varchar 50)
- `depreciation_method` (enum: `'SLM'`, `'WDV'`)
- `depreciation_rate_percent` (decimal 5,2, default 0.00)
- `useful_life_months` (int, default 36)
- `is_serialized` (boolean, default true)
- `description` (text, nullable)
- `is_active` (boolean, default true)
- `timestamps`, `deleted_at`

### 2.10 Table: `assets`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `asset_category_id` (bigint, unsigned, FK -> `asset_categories.id`, cascade)
- `asset_tag` (varchar 100, unique within company)
- `serial_number` (varchar 100, nullable, indexed)
- `name` (varchar 191)
- `model_number` (varchar 100, nullable)
- `purchase_date` (date)
- `purchase_cost` (decimal 12,2)
- `salvage_value` (decimal 12,2, default 0.00)
- `current_condition` (enum: `'New'`, `'Good'`, `'Fair'`, `'Damaged'`, `'Scrapped'`)
- `status` (enum: `'In Stock'`, `'Allocated'`, `'In Repair'`, `'Damaged'`, `'Disposed'`)
- `warranty_expiry_date` (date, nullable)
- `location_id` (bigint, unsigned, nullable, FK -> `locations.id`, nullOnDelete)
- `timestamps`, `deleted_at`

### 2.11 Table: `asset_allocations`
- `id` (bigint, unsigned, auto_increment, PK)
- `asset_id` (bigint, unsigned, FK -> `assets.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `allocated_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `allocated_at` (timestamp)
- `handover_condition` (enum: `'New'`, `'Good'`, `'Fair'`, `'Damaged'`)
- `expected_return_date` (date, nullable)
- `returned_at` (timestamp, nullable)
- `returned_to` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `return_condition` (enum: `'New'`, `'Good'`, `'Fair'`, `'Damaged'`, `'Scrapped'`, nullable)
- `is_damaged_on_return` (boolean, default false)
- `return_notes` (text, nullable)
- `timestamps`

### 2.12 Tables: `fine_masters`, `asset_damage_assessments`, `fine_recovery_records`
- **`fine_masters`**:
  - `id`, `company_id` (FK), `violation_code` (unique), `title`, `description`, `max_fine_amount`, `default_installments`, `is_payroll_deductible`, `is_active`, `timestamps`, `deleted_at`.
- **`asset_damage_assessments`**:
  - `id`, `company_id` (FK), `asset_id` (FK), `employee_id` (FK), `assessed_by` (FK), `fine_master_id` (FK, nullable), `incident_date`, `damage_description`, `estimated_repair_cost`, `employee_liability_amount`, `waiver_amount`, `final_recovery_amount`, `status` (`'Draft'`, `'Pending Approval'`, `'Approved'`, `'Waived'`, `'Fully Settled'`), `approved_by` (FK), `approved_at`, `waiver_approved_by` (FK), `waiver_justification`, `installments_count`, `timestamps`, `deleted_at`.
- **`fine_recovery_records`**:
  - `id`, `damage_assessment_id` (FK), `installment_number`, `scheduled_amount`, `paid_amount`, `due_date`, `status` (`'Scheduled'`, `'Pending'`, `'Deducted in Payroll'`, `'Paid Directly'`, `'Waived'`), `paid_at`, `payment_method` (`'Payroll Deduction'`, `'Cash'`, `'Bank Transfer'`, `'Cheque'`, nullable), `payment_reference`, `payroll_batch_id` (nullable), `timestamps`.

---

## 3. MySQL 8.4 LTS Compatibility Verification

1. **Explicit Nullability**: All timestamp and datetime columns explicitly declare nullability (`nullable()` or default) adhering to strict SQL modes.
2. **Standard Collations**: All tables use `utf8mb4` with `utf8mb4_unicode_ci`.
3. **No Deprecated Constructs**: No MySQL 8.0-specific features that are obsolete or deprecated in 8.4 LTS were utilized.
4. **Referential Integrity**: All foreign keys utilize InnoDB cascading or nullification constraints consistently.

