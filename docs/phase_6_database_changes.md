# Phase 6 Database Architecture Changes: Multi-Channel Notification Engine
**Enterprise HRMS — Database Schema & Data Dictionary**  
**Date:** November 2, 2026  
**Environment:** MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target Production)  
**Migrations Batch:** 10 (Migrations 47–51)  

---

## 1. Overview of Additive Migrations

Five additive, non-destructive migrations were executed against MySQL `enterprise_hrms` to establish the notification infrastructure:

| Migration File | Primary Table | Purpose |
|---|---|---|
| `2026_11_02_000047_create_notification_channel_configurations_table.php` | `notification_channel_configurations` | Per-company channel credentials, provider settings, enabled flags, and safety mock switches. |
| `2026_11_02_000048_create_notification_templates_table.php` | `notification_templates` | Multi-channel message templates with parameter substitution, categorisation, and mandatory flags. |
| `2026_11_02_000049_create_notification_preferences_table.php` | `notification_preferences` | User/employee channel preferences by category, guarding non-opt-outable mandatory alerts. |
| `2026_11_02_000050_create_extended_notifications_table.php` | `notifications` | Extended notification entity compatible with Laravel `DatabaseNotification` + multi-company tenancy. |
| `2026_11_02_000051_create_notification_delivery_logs_table.php` | `notification_delivery_logs` | Audit trail of every delivery attempt across email, SMS, WhatsApp, and in-app channels. |

---

## 2. Table Schemas & Data Dictionary

### 2.1 `notification_channel_configurations`
Stores channel settings per company (In-App, Email, SMS, WhatsApp).
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `companies.id` ON DELETE CASCADE)
- `channel` (ENUM: `'in_app'`, `'email'`, `'sms'`, `'whatsapp'`)
- `is_enabled` (BOOLEAN, Default: `true`)
- `is_mock` (BOOLEAN, Default: `true` — ensures test/development safety)
- `provider_name` (VARCHAR(50), Default: `'mock'`)
- `credentials` (TEXT, Nullable — encrypted credentials for SMS/WhatsApp API gateways)
- `settings` (JSON, Nullable — rate limits, sender IDs, daily limits)
- `created_by` (BIGINT UNSIGNED, Nullable, Foreign Key -> `users.id`)
- `created_at`, `updated_at` (TIMESTAMPS)
- **Constraints / Indexes:**
  - UNIQUE(`company_id`, `channel`)
  - INDEX(`channel`, `is_enabled`)

### 2.2 `notification_templates`
Stores message templates supporting dynamic curly placeholders (`{employee_name}`, `{company_name}`, `{action_url}`, etc.).
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `companies.id` ON DELETE CASCADE)
- `code` (VARCHAR(80) — e.g. `ONBOARDING_WELCOME`, `LEAVE_APPROVED`, `PAYSLIP_AVAILABLE`)
- `name` (VARCHAR(150))
- `category` (ENUM: `'Onboarding'`, `'Attendance'`, `'Shift'`, `'Leave'`, `'Payroll'`, `'Loans'`, `'Settlement'`, `'Compliance'`, `'Security'`)
- `event_type` (VARCHAR(100))
- `channel` (ENUM: `'in_app'`, `'email'`, `'sms'`, `'whatsapp'`)
- `subject` (VARCHAR(255), Nullable)
- `body_template` (TEXT)
- `action_url` (VARCHAR(255), Nullable)
- `is_mandatory` (BOOLEAN, Default: `false` — if true, ignores user opt-out)
- `is_active` (BOOLEAN, Default: `true`)
- `created_by` (BIGINT UNSIGNED, Nullable, Foreign Key -> `users.id`)
- `created_at`, `updated_at` (TIMESTAMPS)
- **Constraints / Indexes:**
  - UNIQUE(`company_id`, `code`, `channel`)
  - INDEX(`category`, `channel`, `is_active`)
  - INDEX(`event_type`, `is_active`)

### 2.3 `notification_preferences`
Tracks employee and user channel opt-ins/opt-outs per notification category.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Foreign Key -> `companies.id` ON DELETE CASCADE)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id` ON DELETE CASCADE)
- `employee_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `employees.id` ON DELETE SET NULL)
- `category` (ENUM: `'Onboarding'`, `'Attendance'`, `'Shift'`, `'Leave'`, `'Payroll'`, `'Loans'`, `'Settlement'`, `'Compliance'`, `'Security'`)
- `channel` (ENUM: `'in_app'`, `'email'`, `'sms'`, `'whatsapp'`)
- `is_enabled` (BOOLEAN, Default: `true`)
- `created_at`, `updated_at` (TIMESTAMPS)
- **Constraints / Indexes:**
  - UNIQUE(`user_id`, `category`, `channel`)
  - INDEX(`company_id`, `user_id`)

### 2.4 `notifications` (Extended Laravel Table)
Seamlessly extends Laravel's native `DatabaseNotification` schema while providing multi-company tenancy, employee links, channel tagging, idempotency, and retry controls.
- `id` (CHAR(36) UUID, Primary Key)
- `type` (VARCHAR(255) — Notification class, e.g. `App\Notifications\HrmsGeneralNotification`)
- `notifiable_type` (VARCHAR(255) — polymorphic target, `App\Models\User`)
- `notifiable_id` (BIGINT UNSIGNED)
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `companies.id` ON DELETE CASCADE)
- `employee_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `employees.id` ON DELETE SET NULL)
- `category` (ENUM: `'Onboarding'`, `'Attendance'`, `'Shift'`, `'Leave'`, `'Payroll'`, `'Loans'`, `'Settlement'`, `'Compliance'`, `'Security'`)
- `event_type` (VARCHAR(100), Nullable)
- `channel` (ENUM: `'in_app'`, `'email'`, `'sms'`, `'whatsapp'`, Default: `'in_app'`)
- `template_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `notification_templates.id` ON DELETE SET NULL)
- `data` (TEXT JSON — title, body, action_url, metadata, icon)
- `delivery_status` (ENUM: `'pending'`, `'dispatched'`, `'delivered'`, `'failed'`, `'skipped'`, Default: `'delivered'`)
- `read_at` (TIMESTAMP, Nullable)
- `scheduled_at` (TIMESTAMP, Nullable)
- `sent_at` (TIMESTAMP, Nullable)
- `retry_count` (INT UNSIGNED, Default: `0`)
- `failure_reason` (TEXT, Nullable)
- `idempotency_key` (VARCHAR(120), Nullable, UNIQUE — protects against double-dispatching)
- `created_at`, `updated_at` (TIMESTAMPS)
- **Constraints / Indexes:**
  - INDEX(`company_id`, `delivery_status`)
  - INDEX(`notifiable_type`, `notifiable_id`, `read_at`)
  - INDEX(`category`, `created_at`)
  - INDEX(`channel`, `delivery_status`)

### 2.5 `notification_delivery_logs`
Records every outbound delivery attempt across SMS, WhatsApp, Email, and In-App gateways.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `companies.id` ON DELETE CASCADE)
- `notification_id` (CHAR(36) UUID, Nullable, Foreign Key -> `notifications.id` ON DELETE CASCADE)
- `user_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `users.id` ON DELETE SET NULL)
- `employee_id` (BIGINT UNSIGNED, Nullable, Foreign Key -> `employees.id` ON DELETE SET NULL)
- `channel` (ENUM: `'in_app'`, `'email'`, `'sms'`, `'whatsapp'`)
- `recipient_address` (VARCHAR(180) — email or phone number)
- `status` (ENUM: `'queued'`, `'dispatched'`, `'delivered'`, `'failed'`, `'skipped'`)
- `provider_name` (VARCHAR(50), Default: `'mock'`)
- `provider_message_id` (VARCHAR(120), Nullable)
- `payload` (JSON, Nullable)
- `provider_response` (TEXT, Nullable)
- `failure_reason` (TEXT, Nullable)
- `attempt_number` (INT UNSIGNED, Default: `1`)
- `dispatched_at` (TIMESTAMP, Nullable)
- `delivered_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)
- **Constraints / Indexes:**
  - INDEX(`company_id`, `channel`, `status`)
  - INDEX(`user_id`, `created_at`)
  - INDEX(`employee_id`, `created_at`)

---

## 3. Seeded Data Summary

Default channel configurations and templates were seeded via `NotificationDefaultsSeeder`:
- **Channel Configurations:** 8 rows (4 per company for `VE` and `VRE`).
- **Notification Templates:** 40 rows spanning all 7 operational categories across in-app, email, SMS, and WhatsApp.
- **Safety Guarantee:** SMS and WhatsApp channels default to `is_mock = true` with `provider_name = 'mock'`, ensuring zero external communications occur during tests or development.

