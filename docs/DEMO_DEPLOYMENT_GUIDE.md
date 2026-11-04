# ENTERPRISE HRMS SaaS — DEMO DEPLOYMENT GUIDE
## Live Cloudflare Tunnel & Demonstration Environment

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Target Environment:** Local Demonstration via Cloudflare Tunnel  
**Framework:** Laravel 13.x | PHP 8.3 | MySQL 8.0  
**Current Live Demo URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com`  
**Date:** 2026-11-03  

---

## 1. DEMO OVERVIEW & ARCHITECTURE

This guide provides instructions to run, access, and manage the temporary public demonstration of the Enterprise HRMS SaaS platform using a secure Cloudflare Tunnel (`cloudflared`).

### How It Works

```
[ External Web Browser / Client ]
             │
             ▼ (HTTPS / TLS 1.3)
[ Cloudflare Global Edge Network ]
             │
             ▼ (Encrypted HTTP/2 Tunnel)
[ cloudflared daemon on local machine ]
             │
             ▼ (HTTP loopback)
[ Laravel Application Server (127.0.0.1:8000) ]
             │
             ▼
[ MySQL 8.0 Database (enterprise_hrms) ]
```

---

## 2. PRE-CONFIGURED DEMO CREDENTIALS

The demonstration database contains synthetic sample data representing **Vishal Group** and **Advait IT Solutions & Services**.

### A. SaaS Platform Super Admin (Advait IT Solutions)
- **Portal URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com/platform/login`
- **Email:** `superadmin@advaititsolutions.com`
- **Password:** `AdvaitPlatform@2026#Secure`
- **Capabilities:** Customer tenant management, subscription tier definition, quota limits, invoice management, audit logging. Zero exposure to employee payroll or personal files.

### B. Tenant Super Admin (Head Office Admin — Vishal Group)
- **Portal URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com/login`
- **Email:** `ho_admin@enterprise.com`
- **Password:** `password`
- **Capabilities:** Group company management, company switching (`VE` ↔ `VRE`), departmental masters, workforce oversight, self-service subscription & quota review (`/subscription`).

### C. HR Executive (Vishal Enterprise)
- **Portal URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com/login`
- **Email:** `hr@vishal.com`
- **Password:** `password`
- **Capabilities:** Candidate onboarding, employee document verification, shift scheduling, daily attendance muster, leave balance adjustments, payroll run processing.

### D. Line Manager (MSS — Manager Self Service)
- **Portal URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com/login`
- **Email:** `manager@vishal.com`
- **Password:** `password`
- **Capabilities:** Departmental team attendance, leave application reviews, shift approvals.

### E. Staff Employee (ESS — Employee Self Service)
- **Portal URL:** `https://infringement-affair-delivery-welcome.trycloudflare.com/login`
- **Email:** `employee@vishal.com`
- **Password:** `password`
- **Capabilities:** Personal dashboard, digital payslip downloads, leave balance review, attendance logs, profile details.

---

## 3. HOW TO START & STOP THE DEMO SERVICES

### Step 1: Start MySQL Database
Ensure MySQL 8.0 is running on localhost (Port 3306) and the `enterprise_hrms` database is available.

### Step 2: Start the Laravel Application
In PowerShell, run:
```powershell
cd "C:\Users\Ashutosh Pandey\Downloads\HRMS_product"
C:\tools\php83\php.exe artisan serve --host=127.0.0.1 --port=8000
```
*The application will listen on `http://127.0.0.1:8000`.*

### Step 3: Start the Cloudflare Tunnel
In a second PowerShell window, run:
```powershell
& "C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --protocol http2 --url http://127.0.0.1:8000
```
*The command will connect to Cloudflare Edge and output the public HTTPS URL (e.g., `https://xxxx.trycloudflare.com`).*

### Step 4: Stop the Demo Services
- To stop the Cloudflare Tunnel: Press `Ctrl + C` in the tunnel window.
- To stop the Laravel Application: Press `Ctrl + C` in the artisan serve window.
- The tunnel URL will immediately expire upon process termination.

---

## 4. DEMO ENVIRONMENT CONFIGURATION

For maximum security during the public demonstration, the following configuration was applied:

```ini
APP_NAME="Enterprise HRMS"
APP_ENV=local
APP_DEBUG=false
APP_URL=https://infringement-affair-delivery-welcome.trycloudflare.com
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

- **`APP_DEBUG=false`**: Prevents stack traces or environment variables from being displayed in browser on unhandled exceptions.
- **Trusted Proxies (`*`)**: Configured in `bootstrap/app.php` so Laravel automatically detects Cloudflare HTTPS termination, generating secure HTTPS URLs for assets and redirects.
- **Rate Limiting**: Platform login endpoint is throttled to 60 requests per minute to prevent brute-force attacks.

---

## 5. KEY DEMO WALKTHROUGH SCENARIOS

### Scenario 1: Platform Owner Overview (Advait IT Solutions)
1. Navigate to `/platform/login` and log in as `superadmin@advaititsolutions.com`.
2. Inspect the Control Tower dashboard KPIs: Total Tenants, Active Subscriptions, Commercial Plans, and Total Billed Revenue.
3. Open **Customer Tenants** (`/platform/tenants`) to see Vishal Group.
4. Open **Subscription Plans** (`/platform/plans`) to review Starter, Pro, and Enterprise tiers.
5. Open **Subscriptions** (`/platform/subscriptions`) to inspect active licenses, validity dates, and lifecycle action modals (Renew, Extend, Suspend).

### Scenario 2: Tenant Multi-Company Operations (Vishal Group)
1. In a separate browser/incognito window, navigate to `/login` and log in as `ho_admin@enterprise.com`.
2. Notice the **Company Switcher** pill in the top navigation bar. Switch between **Vishal Enterprise (VE)** and **Vrishal Eng. Pvt. Ltd. (VRE)**.
3. Notice that all displayed employees, locations, and departments dynamically filter to the selected company.
4. Click the user avatar > Select **Subscription & License** (`/subscription`).
5. Review the active Enterprise tier, quota utilization progress bars (Headcount seats and legal companies), and 11-module checklist.

### Scenario 3: Module Entitlement Shield
1. As Platform Super Admin, edit a plan or review feature entitlements.
2. If an unentitled module is accessed, the system cleanly displays an HTTP 403 Forbidden screen explaining that the module is not part of the active subscription plan.

---

## 6. TROUBLESHOOTING & FAQ

### Q1: The Cloudflare Tunnel fails to connect with "QUIC connection failed"?
- **Cause:** UDP traffic on port 7844 is blocked by local firewall or ISP.
- **Fix:** Always include `--protocol http2` when launching `cloudflared`.

### Q2: Assets or redirects point to `http://localhost`?
- **Cause:** Reverse proxy headers are not trusted.
- **Fix:** Ensure `$middleware->trustProxies(at: '*');` is present in `bootstrap/app.php` and `APP_URL` points to the tunnel URL.

### Q3: How do I change the demo tenant or reset sample passwords?
- All standard demo user passwords in the local database are set to `password` (and `AdvaitPlatform@2026#Secure` for Platform Super Admin).

