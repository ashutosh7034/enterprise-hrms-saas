# Enterprise HRMS — Quick Start & Run Guide

This guide provides step-by-step instructions to set up, start, and run the Enterprise HRMS application on your local machine.

---

## 1. System Requirements

- **PHP**: `8.3+` *(Installed at `C:\tools\php83\php.exe`)*
- **Database**: MySQL `8.0+` / MariaDB `10.4+` via XAMPP on port `3306`
- **Dependency Manager**: Composer `2.x+`
- **Framework**: Laravel 13 (`13.39.0`)

> [!IMPORTANT]
> **Why `php artisan serve` instead of XAMPP Apache?**  
> XAMPP Apache uses its bundled PHP 8.2 (`C:\xampp\php`), whereas Laravel 13 strictly requires **PHP 8.3+**.  
> Always use `php artisan serve` from your terminal so it uses your system's PHP 8.3.

---

## 2. Quick Run (If Already Configured)

If the database is already migrated and seeded:

### Step 1: Ensure MySQL is Running
Make sure the **MySQL** module is started in your XAMPP Control Panel (or running as a service on port `3306`).

### Step 2: Open Terminal in Project Folder
```powershell
# Navigate to your project (either in htdocs or your project directory)
cd C:\xampp\htdocs\HRMS_product
```

### Step 3: Start the Development Server
```powershell
php artisan serve
```

### Step 4: Open in Your Browser
Visit: **[http://localhost:8000](http://localhost:8000)** (or `http://127.0.0.1:8000`)

---

## 3. Fresh Installation / First-Time Setup

If you are setting up the project from scratch on a new machine:

### Step 1: Configure Environment (`.env`)
Ensure a `.env` file exists in the project root:
```powershell
cp .env.example .env
```
Ensure your `.env` contains the MySQL database settings:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=enterprise_hrms
DB_USERNAME=root
DB_PASSWORD=
```

### Step 2: Install Composer Dependencies
```powershell
composer install
```

### Step 3: Generate Application Key
```powershell
php artisan key:generate
```

### Step 4: Create MySQL Databases
Open MySQL (via phpMyAdmin or MySQL CLI) and execute:
```sql
CREATE DATABASE IF NOT EXISTS enterprise_hrms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS enterprise_hrms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 5: Run Migrations and Seed Master Data
```powershell
php artisan migrate:fresh --seed
```

---

## 4. Default Login Credentials

All seeded demo accounts use the standard password: **`password`**

| Persona / Role | Email | Portal Route | Key Permissions & Scope |
|:---|:---|:---|:---|
| **HO Admin** | `ho_admin@enterprise.com` | [`/dashboard/admin`](http://localhost:8000/dashboard/admin) | Full enterprise access, company switching (`VE` & `VRE`), Central Blacklist, Master Configs, 53 Reports |
| **HR Executive (VE)** | `hr@vishal.com` | [`/dashboard/hr`](http://localhost:8000/dashboard/hr) | Workforce onboarding, eKYC verification, shift rosters, leave approvals, payroll runs (Vishal Enterprise) |
| **HR Executive (VRE)** | `hr@vrishal.com` | [`/dashboard/hr`](http://localhost:8000/dashboard/hr) | HR operations isolated to Vrishal Eng. Pvt. Ltd. |
| **Manager MSS** | `manager@vishal.com` | [`/dashboard/manager`](http://localhost:8000/dashboard/manager) | Shift approvals, team attendance, regularizations *(Strict Page 94 segregation: zero salary access)* |
| **Employee ESS** | `employee@vishal.com` | [`/dashboard/employee`](http://localhost:8000/dashboard/employee) | Attendance punch status, leave balance ledger (CL/SL/EL), payslip PDF downloads, asset registry |

---

## 5. Main Functional Modules & Routes

- **Authentication / Login:** [`http://localhost:8000/login`](http://localhost:8000/login)
- **Central Blacklist Governance:** [`http://localhost:8000/blacklist`](http://localhost:8000/blacklist)
- **Enterprise Reporting Hub (53 Reports):** [`http://localhost:8000/admin/reports`](http://localhost:8000/admin/reports)
- **Letter & Document Print Engine:** [`http://localhost:8000/document-templates`](http://localhost:8000/document-templates)
- **Employee Directory:** [`http://localhost:8000/employees`](http://localhost:8000/employees)
- **Attendance & Muster:** [`http://localhost:8000/attendance`](http://localhost:8000/attendance)
- **Leave Management:** [`http://localhost:8000/leaves`](http://localhost:8000/leaves)
- **Payroll Processing:** [`http://localhost:8000/payroll`](http://localhost:8000/payroll)

---

## 6. Running Automated Tests

To run the complete automated test suite (strictly isolated to `enterprise_hrms_test`):

```powershell
php artisan test
```

Expected result: **209 passed (1,234 assertions)** with 0 failures.

---

## 7. Troubleshooting & FAQs

### Q1: Why do I see a Composer PHP version error when opening `http://localhost/HRMS_product/public`?
**Answer:** XAMPP Apache is configured with PHP 8.2, but Laravel 13 requires PHP 8.3. Run `php artisan serve` from your terminal instead, which runs your system's PHP 8.3 at `http://localhost:8000`.

### Q2: "SQLSTATE[HY000] [2002] Connection refused"
**Answer:** MySQL is not running on port 3306. Open the XAMPP Control Panel and start MySQL, or ensure the service `MySQL` is active.

### Q3: How do I run on a different port?
If port 8000 is occupied, specify another port:
```powershell
php artisan serve --port=8080
```
Then visit `http://localhost:8080`.

