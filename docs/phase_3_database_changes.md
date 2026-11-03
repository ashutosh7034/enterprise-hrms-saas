# Enterprise HRMS — Phase 3 Database Changes & Schema Documentation
## Non-Destructive Additive Migrations (Migrations 24–31)

**Database Target**: MySQL 8.0.46 Community Server (Development) / MySQL 8.4 LTS (Production)  
**Strategy**: Non-destructive, additive migrations only. Zero modifications to existing tables or migrations.  
**Total Tables Added in Phase 3**: 8 tables (`shifts`, `employee_shift_assignments`, `employee_shift_rosters`, `biometric_devices`, `biometric_device_sync_logs`, `biometric_punch_logs`, `attendance_records`, `overtime_records`, `plant_shutdowns`, `shutdown_crew_nominations`, `attendance_regularizations`).  
**Status**: All migrations executed and verified.

---

## 1. Migration Index

| Migration File | Table Name | Purpose |
|---|---|---|
| `2026_11_02_000024_create_shifts_table.php` | `shifts` | Shift definitions with timing, grace period, thresholds & weekly off configuration |
| `2026_11_02_000025_create_employee_shift_rosters_and_assignments_tables.php` | `employee_shift_assignments`, `employee_shift_rosters` | Recurring default shift mapping & date-wise shift roster scheduling |
| `2026_11_02_000026_create_biometric_devices_and_sync_logs_tables.php` | `biometric_devices`, `biometric_device_sync_logs` | Hardware terminal registry, network protocols, health status & sync logs |
| `2026_11_02_000027_create_biometric_punch_logs_table.php` | `biometric_punch_logs` | Immutable raw punch staging with duplicate fingerprint detection |
| `2026_11_02_000028_create_attendance_records_table.php` | `attendance_records` | Aggregated daily attendance muster with paired punches, duration & status |
| `2026_11_02_000029_create_overtime_records_table.php` | `overtime_records` | Overtime calculations with 15-min block rounding & approval workflow |
| `2026_11_02_000030_create_plant_shutdowns_and_crew_tables.php` | `plant_shutdowns`, `shutdown_crew_nominations` | Industrial plant shutdown declarations, compensation policies & nominated maintenance crew |
| `2026_11_02_000031_create_attendance_regularizations_table.php` | `attendance_regularizations` | Multi-tier attendance regularization requests & audit-linked recalculation |

---

## 2. Table-by-Table Schema Specifications

### 2.1 Table: `shifts`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `location_id` (bigint, unsigned, nullable, FK -> `locations.id`, nullOnDelete)
- `department_id` (bigint, unsigned, nullable, FK -> `departments.id`, nullOnDelete)
- `category_id` (bigint, unsigned, nullable, FK -> `categories.id`, nullOnDelete)
- `code` (varchar 50)
- `name` (varchar 100)
- `start_time` (time)
- `end_time` (time)
- `break_duration_minutes` (unsigned smallint, default 45)
- `grace_period_minutes` (unsigned smallint, default 15)
- `half_day_threshold_minutes` (unsigned smallint, default 240)
- `full_day_threshold_minutes` (unsigned smallint, default 480)
- `is_overnight` (boolean, default false)
- `weekly_off_days` (json, nullable) — e.g., `["Sunday"]`
- `status` (enum: `'Active'`, `'Inactive'`, default `'Active'`)
- `effective_from` (date)
- `effective_to` (date, nullable)
- `timestamps`, `deleted_at` (soft deletes)
- **Unique**: `['company_id', 'code']`

### 2.2 Table: `employee_shift_assignments`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `shift_id` (bigint, unsigned, FK -> `shifts.id`, cascade)
- `from_date` (date)
- `to_date` (date, nullable)
- `status` (enum: `'Active'`, `'Superseded'`, default `'Active'`)
- `assigned_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`
- **Index**: `['company_id', 'employee_id', 'status']`

### 2.3 Table: `employee_shift_rosters`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `shift_id` (bigint, unsigned, nullable, FK -> `shifts.id`, nullOnDelete)
- `date` (date)
- `is_weekly_off` (boolean, default false)
- `is_holiday` (boolean, default false)
- `notes` (varchar 255, nullable)
- `assigned_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `timestamps`
- **Unique**: `['company_id', 'employee_id', 'date']` (named: `uniq_emp_shift_roster_date`)

### 2.4 Table: `biometric_devices`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `location_id` (bigint, unsigned, FK -> `locations.id`, cascade)
- `device_identifier` (varchar 100)
- `device_name` (varchar 150)
- `serial_number` (varchar 100, unique)
- `ip_address` (varchar 50, nullable)
- `port` (unsigned int, default 4370)
- `device_type` (enum: `'Biometric Fingerprint'`, `'Facial Recognition'`, `'Iris Scanner'`, `'RFID / Card Reader'`, `'Multi-Modal'`)
- `protocol` (enum: `'ZKTeco Push / ADMS'`, `'Direct IP Poll'`, `'REST Webhook'`, `'SOAP / XML'`, `'Manual CSV Import'`)
- `status` (enum: `'Active'`, `'Shutdown'`, `'Inactive'`, default `'Active'`)
- `health_status` (enum: `'Healthy'`, `'Warning'`, `'Offline'`, `'Sync Failure'`, default `'Healthy'`)
- `secret_token` (varchar 128, nullable)
- `last_sync_at` (timestamp, nullable)
- `last_heartbeat_at` (timestamp, nullable)
- `remarks` (text, nullable)
- `timestamps`, `deleted_at` (soft deletes)
- **Unique**: `['company_id', 'device_identifier']`

### 2.5 Table: `biometric_punch_logs`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `location_id` (bigint, unsigned, nullable, FK -> `locations.id`, nullOnDelete)
- `biometric_device_id` (bigint, unsigned, nullable, FK -> `biometric_devices.id`, nullOnDelete)
- `employee_id` (bigint, unsigned, nullable, FK -> `employees.id`, nullOnDelete)
- `biometric_user_id` (varchar 100, indexed)
- `punch_time` (datetime, indexed) — *Original device timestamp preserved*
- `sync_time` (datetime, indexed) — *Ingestion / receipt timestamp*
- `direction` (enum: `'IN'`, `'OUT'`, `'BREAK_IN'`, `'BREAK_OUT'`, `'UNKNOWN'`, default `'UNKNOWN'`)
- `punch_fingerprint` (varchar 64, indexed) — *SHA-256 hash of company:user_id:punch_time:direction*
- `raw_payload` (longtext, nullable)
- `source` (varchar 50, default `'Webhook'`)
- `processing_status` (enum: `'Raw'`, `'Processed'`, `'Ignored_Duplicate'`, `'Error'`, `'Reprocessed'`, default `'Raw'`)
- `is_duplicate` (boolean, default false)
- `error_message` (text, nullable)
- `processed_at` (timestamp, nullable)
- `timestamps`

### 2.6 Table: `attendance_records`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `attendance_date` (date)
- `shift_id` (bigint, unsigned, nullable, FK -> `shifts.id`, nullOnDelete)
- `first_in` (datetime, nullable)
- `last_out` (datetime, nullable)
- `total_punches` (unsigned smallint, default 0)
- `punch_records_json` (json, nullable)
- `total_work_minutes` (unsigned smallint, default 0)
- `break_minutes` (unsigned smallint, default 0)
- `actual_worked_minutes` (unsigned smallint, default 0)
- `late_arrival_minutes` (unsigned smallint, default 0)
- `early_departure_minutes` (unsigned smallint, default 0)
- `overtime_minutes` (unsigned smallint, default 0)
- `status` (enum: `'Present'`, `'Half Day'`, `'Absent'`, `'Weekly Off'`, `'Holiday'`, `'Missing Punch'`, `'Site Shutdown'`, `'Worked During Shutdown'`, `'On Duty'`, `'Regularized'`)
- `regularization_status` (enum: `'None'`, `'Pending'`, `'Approved'`, `'Rejected'`, default `'None'`)
- `regularization_id` (unsigned bigint, nullable)
- `is_overnight` (boolean, default false)
- `is_reconciled` (boolean, default false)
- `reconciliation_notes` (text, nullable)
- `is_locked` (boolean, default false)
- `calculated_at` (timestamp, nullable)
- `timestamps`
- **Unique**: `['company_id', 'employee_id', 'attendance_date']` (named: `uniq_emp_attendance_date`)

### 2.7 Table: `overtime_records`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `attendance_record_id` (bigint, unsigned, FK -> `attendance_records.id`, cascade)
- `ot_date` (date)
- `shift_id` (bigint, unsigned, nullable, FK -> `shifts.id`, nullOnDelete)
- `day_type` (enum: `'Normal'`, `'Holiday'`, `'Weekly Off'`, `'Shutdown'`, default `'Normal'`)
- `raw_calculated_minutes` (unsigned smallint, default 0)
- `rounded_ot_minutes` (unsigned smallint, default 0) — *15-min block floor*
- `requested_minutes` (unsigned smallint, default 0)
- `approved_minutes` (unsigned smallint, default 0)
- `status` (enum: `'Pending'`, `'Approved'`, `'Rejected'`, default `'Pending'`)
- `approved_by` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `approved_at` (timestamp, nullable)
- `rejection_reason` (text, nullable)
- `remarks` (text, nullable)
- `payroll_batch_id` (varchar 50, nullable) — *Ready for Phase 5 Payroll export*
- `timestamps`

### 2.8 Table: `plant_shutdowns` & `shutdown_crew_nominations`
- **`plant_shutdowns`**:
  - `id`, `company_id` (FK), `location_id` (FK), `department_id` (FK, nullable), `category_id` (FK, nullable), `shutdown_code` (unique), `title`, `shutdown_type` (`'Planned Maintenance'`, `'Emergency'`, `'Power Failure'`, `'Raw Material Shortage'`, `'Other'`), `start_date`, `end_date`, `start_time`, `end_time`, `reason`, `compensation_rule` (`'Paid Shutdown'`, `'Adjust Against Leave'`, `'Unpaid Shutdown'`, `'Comp-Off for Duty'`), `status` (`'Pending'`, `'Approved'`, `'Rejected'`, `'Cancelled'`, `'Completed'`), `requested_by` (FK), `approved_by` (FK), `approved_at`, `cancellation_reason`, `cancelled_by` (FK), `cancelled_at`, `timestamps`, `deleted_at`.
- **`shutdown_crew_nominations`**:
  - `id`, `plant_shutdown_id` (FK), `employee_id` (FK), `work_date`, `role_description`, `compensation_type` (`'Overtime Pay'`, `'Comp-Off Credit'`, `'Special Site Allowance'`), `is_approved`, `supervisor_id` (FK), `timestamps`.

### 2.9 Table: `attendance_regularizations`
- `id` (bigint, unsigned, auto_increment, PK)
- `company_id` (bigint, unsigned, FK -> `companies.id`, cascade)
- `employee_id` (bigint, unsigned, FK -> `employees.id`, cascade)
- `attendance_record_id` (bigint, unsigned, nullable, FK -> `attendance_records.id`, nullOnDelete)
- `attendance_date` (date)
- `regularization_type` (enum: `'Missing IN Punch'`, `'Missing OUT Punch'`, `'Late Arrival'`, `'Early Departure'`, `'Business Travel / On Duty'`, `'Biometric Device Failure'`, `'Attendance Correction'`)
- `requested_in_time` (datetime, nullable)
- `requested_out_time` (datetime, nullable)
- `reason` (text) — *Mandatory justification*
- `status` (enum: `'Pending Manager'`, `'Pending HR'`, `'Approved'`, `'Rejected'`, `'Sent Back'`, default `'Pending Manager'`)
- `applied_by` (bigint, unsigned, FK -> `users.id`, cascade)
- `manager_id` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `manager_action_at` (timestamp, nullable)
- `manager_remarks` (text, nullable)
- `hr_id` (bigint, unsigned, nullable, FK -> `users.id`, nullOnDelete)
- `hr_action_at` (timestamp, nullable)
- `hr_remarks` (text, nullable)
- `original_status` (varchar 50, nullable)
- `new_status` (varchar 50, nullable)
- `timestamps`
- **Short Indexes**: `idx_att_reg_status`, `idx_att_reg_emp_date` (compliant with MySQL 64-char identifier limit).

---

## 3. Forward Compatibility Verification (MySQL 8.4 LTS)
1. **Explicit Unsigned Types**: All minute and threshold counts explicitly use `unsignedSmallInteger` with absolute integer arithmetic, preventing negative value out-of-range violations.
2. **Deterministic Unique Indices**: Explicitly named short identifiers prevent identifier length overflow warnings.
3. **JSON Storage**: Shift weekly off arrays and punch timeline payloads use native JSON columns.

