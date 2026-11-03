# PHASE 8: DATABASE CHANGES SPECIFICATION (BATCH 12)

**Enterprise Human Resource Management System (HRMS)**  
**Environment:** MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target)  
**Database Isolation:** Dev (`enterprise_hrms`) & Test (`enterprise_hrms_test`)  
**Document Reference:** `DOCS-PH8-DB-001`  
**Date:** November 2026  

---

## 1. Batch 12 Migrations Overview

Batch 12 introduces 3 dedicated tables providing immutable export logging, automated reporting schedules, and execution audit trails. All migrations have been executed on both `enterprise_hrms` and `enterprise_hrms_test`.

| Migration File | Target Table | Primary Purpose |
|---|---|---|
| `2026_11_02_000056_create_report_export_histories_table.php` | `report_export_histories` | Immutable audit trail for all dataset downloads (CSV, Excel, PDF) |
| `2026_11_02_000057_create_scheduled_reports_table.php` | `scheduled_reports` | Recurrent scheduled reporting definitions and delivery configurations |
| `2026_11_02_000058_create_scheduled_report_logs_table.php` | `scheduled_report_logs` | Execution and delivery tracking logs for background scheduler tasks |

---

## 2. Table Specifications

### 2.1 Table: `report_export_histories`

```sql
CREATE TABLE `report_export_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `report_type` varchar(100) NOT NULL,
  `file_format` varchar(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `row_count` int unsigned NOT NULL DEFAULT '0',
  `file_size_kb` int unsigned NOT NULL DEFAULT '0',
  `filters_applied_json` json DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Completed',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_export_histories_report_type_index` (`report_type`),
  KEY `report_export_histories_status_index` (`status`),
  KEY `report_export_histories_company_id_report_type_index` (`company_id`,`report_type`),
  KEY `report_export_histories_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `report_export_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_export_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Table: `scheduled_reports`

```sql
CREATE TABLE `scheduled_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `frequency` enum('Daily','Weekly','Monthly') NOT NULL DEFAULT 'Monthly',
  `export_format` enum('csv','xlsx','pdf') NOT NULL DEFAULT 'csv',
  `recipients_json` json NOT NULL,
  `filters_json` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_reports_report_type_index` (`report_type`),
  KEY `scheduled_reports_is_active_index` (`is_active`),
  KEY `scheduled_reports_next_run_at_index` (`next_run_at`),
  KEY `scheduled_reports_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `scheduled_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 Table: `scheduled_report_logs`

```sql
CREATE TABLE `scheduled_report_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scheduled_report_id` bigint unsigned NOT NULL,
  `status` enum('Success','Failed','Skipped') NOT NULL DEFAULT 'Success',
  `recipients_count` int unsigned NOT NULL DEFAULT '0',
  `error_message` text,
  `file_path` varchar(500) DEFAULT NULL,
  `dispatched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_report_logs_status_index` (`status`),
  KEY `scheduled_report_logs_scheduled_report_id_foreign` (`scheduled_report_id`),
  CONSTRAINT `scheduled_report_logs_scheduled_report_id_foreign` FOREIGN KEY (`scheduled_report_id`) REFERENCES `scheduled_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Eloquent Model Relational Enhancements

In addition to new tables, the Eloquent domain layer was reinforced to provide explicit bidirectional relations:

1. **`App\Models\Department`:** Added `employees(): HasMany` pointing to `Employee`.
2. **`App\Models\Designation`:** Added `employees(): HasMany` pointing to `Employee`.
3. **`App\Models\Category`:** Added `employees(): HasMany` pointing to `Employee`.
4. **`App\Models\Location`:** Added `employees(): HasMany` pointing to `Employee`.
5. **`App\Models\User`:** Added `hasAnyRole(string|array $roles): bool` supporting polymorphic role lookups.

