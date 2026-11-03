# Enterprise HRMS — Phase 5 Database Changes & Migration Log

## 1. Overview

Phase 5 introduced 7 additive database migrations (Batches 40 to 46) establishing the foundation for salary structure management, payroll statutory compliance, batch execution runs, loan amortizations, and Full & Final settlements.

All migrations are forward-compatible with MySQL 8.4 LTS and adhere strictly to multi-company foreign key constraints with composite indexing.

---

## 2. Migration Manifest

| Migration File | Tables Created | Description |
|---|---|---|
| `2026_11_02_000040_create_salary_components_and_structures_tables.php` | `salary_components`, `salary_structures`, `salary_structure_components`, `employee_salary_assignments`, `salary_revision_histories` | Salary component catalog, formula-driven salary structures, employee salary assignments with revision history. |
| `2026_11_02_000041_create_payroll_configurations_and_statutory_tables.php` | `payroll_configurations`, `payroll_statutory_configurations`, `payroll_pt_slabs` | Corporate payroll rules, statutory ceilings, and state-level Professional Tax (PT) slab matrices. |
| `2026_11_02_000042_create_payroll_periods_and_runs_tables.php` | `payroll_periods`, `payroll_runs`, `payroll_approval_steps` | Monthly payroll periods, batch payroll control tower runs, and multi-tier approval audit trail. |
| `2026_11_02_000043_create_payroll_run_employees_and_payslips_tables.php` | `payroll_run_employees`, `payroll_component_transactions` | Employee payroll line items, payslip generation metadata, and itemized component audit transactions. |
| `2026_11_02_000044_create_employee_tax_declarations_and_projections_tables.php` | `employee_tax_declarations`, `employee_tax_projections` | Annual tax regime declarations (New vs Old) and rolling 12-month TDS income tax liability projections. |
| `2026_11_02_000045_create_employee_loans_and_emi_tables.php` | `employee_loans`, `employee_loan_emi_schedules` | Employee loans/advances applications and monthly EMI amortization repayment schedules. |
| `2026_11_02_000046_create_fnf_settlements_tables.php` | `fnf_settlements`, `fnf_settlement_components` | Full and Final exit settlements, notice shortfall calculations, gratuity, and loan/fine balance recovery. |

---

## 3. Detailed Schema Definitions

### 3.1 `salary_components`
- `id` (BIGINT, PK, Auto-increment)
- `company_id` (BIGINT, FK -> companies.id)
- `name` (VARCHAR 100)
- `code` (VARCHAR 50, e.g., BASIC, HRA, DA, SPECIAL, PF_EMP, PT)
- `type` (ENUM: `Earning`, `Deduction`)
- `is_taxable` (BOOLEAN, default: true)
- `is_statutory` (BOOLEAN, default: false)
- `is_active` (BOOLEAN, default: true)
- `sort_order` (INT, default: 0)
- Unique: `(company_id, code)`

### 3.2 `salary_structures`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `name` (VARCHAR 100)
- `code` (VARCHAR 50)
- `description` (TEXT, nullable)
- `currency` (VARCHAR 10, default: 'INR')
- `is_target_ot` (BOOLEAN, default: false)
- `target_ot_amount` (DECIMAL 10,2, default: 0.00)
- `is_active` (BOOLEAN, default: true)
- Unique: `(company_id, code)`

### 3.3 `salary_structure_components`
- `id` (BIGINT, PK)
- `salary_structure_id` (BIGINT, FK -> salary_structures.id)
- `salary_component_id` (BIGINT, FK -> salary_components.id)
- `calculation_type` (ENUM: `Fixed Amount`, `Percentage of Component`, `Formula`)
- `percentage` (DECIMAL 5,2, nullable)
- `percentage_of_component_id` (BIGINT, FK -> salary_components.id, nullable)
- `formula` (VARCHAR 255, nullable)
- `default_amount` (DECIMAL 10,2, default: 0.00)
- `is_mandatory` (BOOLEAN, default: true)
- `sort_order` (INT, default: 0)

### 3.4 `employee_salary_assignments`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `employee_id` (BIGINT, FK -> employees.id)
- `salary_structure_id` (BIGINT, FK -> salary_structures.id)
- `effective_from_date` (DATE)
- `effective_to_date` (DATE, nullable)
- `ctc_annual` (DECIMAL 12,2)
- `gross_monthly` (DECIMAL 10,2)
- `component_values_json` (JSON, nullable)
- `is_target_ot` (BOOLEAN, default: false)
- `target_ot_amount` (DECIMAL 10,2, default: 0.00)
- `is_pf_eligible` (BOOLEAN, default: true)
- `is_esi_eligible` (BOOLEAN, default: true)
- `status` (ENUM: `Draft`, `Approved`, `Superseded`)
- `approved_by` (BIGINT, FK -> users.id, nullable)
- `approved_at` (TIMESTAMP, nullable)
- `revision_notes` (TEXT, nullable)
- Index: `(company_id, employee_id, status)`

### 3.5 `salary_revision_histories`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `employee_id` (BIGINT, FK -> employees.id)
- `previous_assignment_id` (BIGINT, FK -> employee_salary_assignments.id, nullable)
- `new_assignment_id` (BIGINT, FK -> employee_salary_assignments.id)
- `effective_from_date` (DATE)
- `old_ctc_annual` (DECIMAL 12,2)
- `new_ctc_annual` (DECIMAL 12,2)
- `increment_percentage` (DECIMAL 5,2)
- `changed_by` (BIGINT, FK -> users.id)
- `reason` (TEXT, nullable)

### 3.6 `payroll_configurations`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `pay_cycle_type` (ENUM: `Monthly`, `Bi-Weekly`, `Weekly`, default: 'Monthly')
- `cutoff_day` (TINYINT, default: 25)
- `salary_calculation_basis` (ENUM: `Calendar Days`, `Fixed 30 Days`, `Standard 26 Days`, default: 'Calendar Days')
- `standard_work_days_per_month` (DECIMAL 4,2, default: 26.00)
- `ot_hourly_divisor` (DECIMAL 5,2, default: 208.00)
- `ot_multiplier` (DECIMAL 3,2, default: 2.00)
- `net_pay_rounding_rule` (ENUM: `Round to Nearest Rupee`, `Floor`, `Ceiling`, default: 'Round to Nearest Rupee')
- `is_active` (BOOLEAN, default: true)

### 3.7 `payroll_statutory_configurations`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `pf_employee_rate` (DECIMAL 5,2, default: 12.00)
- `pf_employer_rate` (DECIMAL 5,2, default: 12.00)
- `pf_wage_ceiling` (DECIMAL 10,2, default: 15000.00)
- `pf_restrict_to_ceiling` (BOOLEAN, default: true)
- `pf_eps_rate` (DECIMAL 5,2, default: 8.33)
- `pf_eps_ceiling` (DECIMAL 10,2, default: 1250.00)
- `pf_edli_rate` (DECIMAL 5,2, default: 0.50)
- `pf_admin_rate` (DECIMAL 5,2, default: 0.50)
- `esi_employee_rate` (DECIMAL 5,2, default: 0.75)
- `esi_employer_rate` (DECIMAL 5,2, default: 3.25)
- `esi_wage_ceiling` (DECIMAL 10,2, default: 21000.00)
- `esi_include_overtime_in_ceiling` (BOOLEAN, default: false)
- `lwf_employee_amount` (DECIMAL 8,2, default: 12.00)
- `lwf_employer_amount` (DECIMAL 8,2, default: 36.00)
- `lwf_deduction_frequency` (ENUM: `Monthly`, `Bi-Annual June/Dec`, `Annual`, default: 'Bi-Annual June/Dec')
- `standard_deduction_new_regime` (DECIMAL 10,2, default: 75000.00)
- `standard_deduction_old_regime` (DECIMAL 10,2, default: 50000.00)
- `section_87a_rebate_limit_new` (DECIMAL 10,2, default: 700000.00)
- `section_87a_rebate_limit_old` (DECIMAL 10,2, default: 500000.00)

### 3.8 `payroll_pt_slabs`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `state` (VARCHAR 100)
- `gender` (ENUM: `All`, `Male`, `Female`, default: 'All')
- `min_monthly_salary` (DECIMAL 10,2)
- `max_monthly_salary` (DECIMAL 10,2, nullable)
- `tax_amount` (DECIMAL 8,2)
- `special_month` (TINYINT, nullable)
- `special_tax_amount` (DECIMAL 8,2, nullable)

### 3.9 `payroll_runs`
- `id` (BIGINT, PK)
- `company_id` (BIGINT, FK -> companies.id)
- `payroll_period_id` (BIGINT, FK -> payroll_periods.id)
- `run_batch_number` (VARCHAR 50)
- `year` (SMALLINT)
- `month` (TINYINT)
- `total_eligible_employees` (INT)
- `total_processed_employees` (INT)
- `total_gross_earnings` (DECIMAL 14,2)
- `total_statutory_deductions` (DECIMAL 14,2)
- `total_other_deductions` (DECIMAL 14,2)
- `total_deductions` (DECIMAL 14,2)
- `total_net_payout` (DECIMAL 14,2)
- `total_employer_contributions` (DECIMAL 14,2)
- `status` (ENUM: `Draft`, `Pre-Validation Passed`, `Pending Approval`, `Approved`, `Locked`, `Rejected`, `Disbursed`)
- `processed_by` (BIGINT, FK -> users.id, nullable)
- `approved_by` (BIGINT, FK -> users.id, nullable)
- `approved_at` (TIMESTAMP, nullable)
- `rejection_remarks` (TEXT, nullable)
- `validation_errors_json` (JSON, nullable)
- Unique: `(company_id, run_batch_number)`
- Index: `(company_id, year, month, status)`

### 3.10 `payroll_run_employees`
- Itemized record per employee per payroll run capturing pro-rated basic, HRA, statutory contributions, loan EMI, asset fines, net payout, and payslip number.

### 3.11 `employee_loans` and `employee_loan_emi_schedules`
- Multi-tier loan application, straight-line/interest amortization schedule, status progression (`Applied`, `Manager Approved`, `HR Approved`, `Active`, `Closed`, `Rejected`).

### 3.12 `fnf_settlements` and `fnf_settlement_components`
- Comprehensive exit dues calculation with unpaid salary, leave encashment, gratuity, notice period shortfall recovery, loan clearance, and fine recovery.

