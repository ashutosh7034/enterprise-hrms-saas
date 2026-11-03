# Enterprise HRMS — Phase 4 Database Changes & Schema Documentation
## Non-Destructive Additive Migrations (Migrations 32–39)

**Database Target**: MySQL 8.0.46 Community Server (Development) / MySQL 8.4 LTS (Production)  
**Strategy**: Non-destructive, additive migrations only. Zero modifications to existing tables or migrations.  
**Total Tables Added in Phase 4**: 10 tables (`leave_types`, `leave_balances`, `leave_balance_transactions`, `leave_accrual_batches`, `leave_carry_forward_batches`, `holidays`, `sandwich_leave_policies`, `comp_off_records`, `leave_applications`, `leave_approval_steps`, `leave_period_locks`, `leave_period_unlock_histories`) and 2 added columns to `employees`.  
**Status**: All migrations executed and verified.

---

## 1. Migration Index

| Migration File | Tables / Target | Purpose |
|---|---|---|
| `2026_11_02_000032_create_leave_types_table.php` | `leave_types` | Leave type master with quotas, accrual schedule, notice constraints & policy rules |
| `2026_11_02_000033_create_leave_balances_and_transactions_tables.php` | `leave_balances`, `leave_balance_transactions` | Employee balance balances & immutable transaction ledger tracking credits/debits |
| `2026_11_02_000034_create_leave_accrual_and_carry_forward_batches_tables.php` | `leave_accrual_batches`, `leave_carry_forward_batches` | Batch logs for idempotent monthly accruals & annual carry-forward/expiry transitions |
| `2026_11_02_000035_create_holidays_and_sandwich_policies_tables.php` | `holidays`, `sandwich_leave_policies` | Site-specific holiday calendars & prioritized sandwich leave policy rules |
| `2026_11_02_000036_create_comp_off_records_table.php` | `comp_off_records` | Comp-Off lifecycle tracking, attendance link & 60-day expiry enforcement |
| `2026_11_02_000037_create_leave_applications_and_approval_steps_tables.php` | `leave_applications`, `leave_approval_steps` | Multi-tier leave applications, sandwich calculations & approval audit trail |
| `2026_11_02_000038_create_leave_period_locks_and_unlocks_tables.php` | `leave_period_locks`, `leave_period_unlock_histories` | Monthly leave period closure & HO Admin unlock with mandatory justification |
| `2026_11_02_000039_add_notice_period_dates_to_employees_table.php` | `employees` | Added `notice_period_start_date` and `notice_period_end_date` |

---

## 2. Table-by-Table Schema Specifications

### 2.1 Table: `leave_types`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `code` (varchar 50)
- `name` (varchar 100)
- `description` (text, nullable)
- `is_paid` (boolean, default true)
- `annual_entitlement` (decimal 5,2, default 0.00)
- `monthly_accrual_rate` (decimal 5,2, default 0.00)
- `accrual_frequency` (enum: `'None'`, `'Monthly'`, `'Quarterly'`, `'Yearly'`, default `'Monthly'`)
- `accrual_timing` (enum: `'Start of Month'`, `'End of Month'`, default `'Start of Month'`)
- `is_accrual_enabled` (boolean, default false)
- `requires_confirmation` (boolean, default false)
- `min_service_days` (unsigned smallint, default 0)
- `max_consecutive_days` (unsigned smallint, default 0)
- `min_notice_days` (unsigned smallint, default 0)
- `max_backdated_days` (unsigned smallint, default 0)
- `is_carry_forward_enabled` (boolean, default false)
- `carry_forward_limit` (decimal 5,2, default 0.00)
- `expiry_months` (unsigned smallint, default 0)
- `allow_negative_balance` (boolean, default false)
- `max_negative_balance` (decimal 5,2, default 0.00)
- `is_sandwich_enabled` (boolean, default true)
- `is_compoff` (boolean, default false)
- `is_applicable_in_notice_period` (boolean, default false)
- `max_notice_period_days` (decimal 5,2, default 0.00)
- `applicable_categories`, `applicable_locations`, `applicable_departments`, `applicable_employment_types` (json, nullable)
- `status` (enum: `'Active'`, `'Inactive'`, default `'Active'`)
- `effective_from` (date)
- `effective_to` (date, nullable)
- `timestamps`, `deleted_at` (soft deletes)
- **Unique**: `['company_id', 'code']` (short index name `idx_leave_type_comp_code`)

### 2.2 Table: `leave_balances`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `leave_type_id` (bigint, unsigned, FK -> `leave_types.id`, cascade)
- `year` (unsigned smallint)
- `opening_balance`, `accrued_balance`, `consumed_balance`, `reserved_balance`, `carry_forward_balance`, `expired_balance`, `adjustment_credit`, `adjustment_debit`, `closing_balance`, `available_balance` (decimal 6,2, default 0.00)
- `timestamps`
- **Unique**: `['company_id', 'employee_id', 'leave_type_id', 'year']` (`idx_leave_bal_unique`)

### 2.3 Table: `leave_balance_transactions` (Immutable Ledger)
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `leave_type_id` (bigint, unsigned, FK -> `leave_types.id`, cascade)
- `transaction_type` (enum: `'Opening Balance'`, `'Monthly Accrual'`, `'Confirmation Allocation'`, `'Leave Application'`, `'Leave Cancellation'`, `'Carry Forward'`, `'Expiry'`, `'Comp-Off Credit'`, `'Manual Adjustment'`, `'Reversal'`)
- `transaction_date` (date)
- `credit_days` (decimal 6,2, default 0.00)
- `debit_days` (decimal 6,2, default 0.00)
- `balance_before` (decimal 6,2)
- `balance_after` (decimal 6,2)
- `reference_type` (varchar 100, nullable)
- `reference_id` (unsigned bigint, nullable)
- `transaction_uuid` (varchar 64, unique `idx_leave_tx_uuid`)
- `remarks` (text, nullable)
- `created_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `created_at` (timestamp, useCurrent)

### 2.4 Table: `holidays`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `name` (varchar 100)
- `code` (varchar 50, nullable)
- `date` (date)
- `holiday_type` (enum: `'National Holiday'`, `'Regional Holiday'`, `'Festival Holiday'`, `'Company Holiday'`, `'Optional Holiday'`, `'Site-Specific Holiday'`, `'Shutdown Holiday'`)
- `location_id`, `department_id`, `category_id` (nullable FKs for site/scope targeting)
- `is_ground_staff_applicable` (boolean, default true)
- `is_paid` (boolean, default true)
- `year` (unsigned smallint)
- `description` (text, nullable)
- `status` (enum: `'Active'`, `'Inactive'`, default `'Active'`)
- `timestamps`

### 2.5 Table: `sandwich_leave_policies`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `leave_type_id` (bigint, unsigned, nullable, FK -> `leave_types.id`, nullOnDelete)
- `policy_name` (varchar 100)
- `is_enabled` (boolean, default true)
- `include_intervening_weekly_offs` (boolean, default true)
- `include_intervening_holidays` (boolean, default true)
- `location_id`, `department_id`, `category_id` (nullable scope overrides)
- `effective_from` (date), `effective_to` (date, nullable)
- `priority` (unsigned smallint, default 1)
- `timestamps`

### 2.6 Table: `comp_off_records`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `attendance_record_id` (bigint, unsigned, FK -> `attendance_records.id`, cascade)
- `qualifying_work_date` (date)
- `source_type` (enum: `'Weekly Off Work'`, `'Holiday Work'`, `'Plant Shutdown Overtime'`, `'Emergency Duty'`)
- `eligible_work_minutes` (unsigned smallint, default 0)
- `credited_days` (decimal 4,2, default 1.00)
- `availed_days` (decimal 4,2, default 0.00)
- `expiry_date` (date)
- `status` (enum: `'Pending Approval'`, `'Approved'`, `'Partially Availed'`, `'Fully Availed'`, `'Expired'`, `'Rejected'`, `'Cancelled'`)
- `approved_by` (FK -> `users.id`, nullable), `approval_remarks` (text, nullable), `approved_at` (timestamp, nullable)
- `timestamps`
- **Unique**: `['company_id', 'employee_id', 'attendance_record_id']` (`idx_compoff_unique_att`)

### 2.7 Table: `leave_applications`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `application_number` (varchar 50, unique with `company_id`)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `leave_type_id` (bigint, unsigned, FK -> `leave_types.id`, cascade)
- `start_date`, `end_date` (date)
- `is_half_day` (boolean, default false), `half_day_type` (enum: `'First Half'`, `'Second Half'`, nullable)
- `requested_days` (decimal 4,2)
- `calculated_days` (decimal 4,2, sandwich-inclusive)
- `approved_days` (decimal 4,2, nullable)
- `sandwich_breakdown_json` (json, nullable)
- `reason` (text)
- `supporting_document_path` (varchar 255, nullable)
- `status` (enum: `'Pending Manager'`, `'Pending HOD'`, `'Pending HR'`, `'Approved'`, `'Rejected'`, `'Cancelled'`, `'Withdrawn'`)
- `current_stage` (enum: `'Manager'`, `'HOD'`, `'HR'`, `'Completed'`)
- `is_notice_period_override` (boolean, default false)
- `notice_period_justification` (text, nullable)
- `manager_id`, `manager_action_at`, `manager_remarks`
- `hod_id`, `hod_action_at`, `hod_remarks`
- `hr_id`, `hr_action_at`, `hr_remarks`
- `rejection_reason`, `cancellation_reason`
- `timestamps`, `deleted_at`

### 2.8 Table: `leave_approval_steps`
- `id` (bigint, unsigned, auto_increment, PK)
- `leave_application_id` (bigint, unsigned, FK -> `leave_applications.id`, cascade)
- `stage` (enum: `'Manager'`, `'HOD'`, `'HR'`)
- `action` (enum: `'Submitted'`, `'Approved'`, `'Rejected'`, `'Sent Back'`, `'Cancelled'`, `'Withdrawn'`, `'Overridden'`)
- `approver_id` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `remarks` (text, nullable)
- `created_at` (timestamp, useCurrent)

### 2.9 Table: `leave_period_locks` & `leave_period_unlock_histories`
- `leave_period_locks`:
  - `id`, `company_id`, `year` (smallint), `month` (tinyint), `is_locked` (boolean, default true)
  - `locked_by` (FK -> `users.id`), `locked_at` (timestamp), `remarks` (text, nullable)
  - **Unique**: `['company_id', 'year', 'month']` (`idx_leave_lock_period`)
- `leave_period_unlock_histories`:
  - `id`, `leave_period_lock_id` (FK -> `leave_period_locks.id`, cascade)
  - `unlocked_by` (FK -> `users.id`), `justification` (text, mandatory HO reason), `unlocked_at` (timestamp)
  - `re_locked_at` (timestamp, nullable), `re_locked_by` (FK -> `users.id`, nullable)

