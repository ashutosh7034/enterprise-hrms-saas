# ENTERPRISE HRMS SaaS — DEMO SECURITY CHECKLIST
## Pre-Demo Threat Modeling & Security Posture Verification

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Demonstration Mode:** Cloudflare Tunnel Public HTTPS Access  
**Security Lead:** Senior Application Security Engineer  
**Audit Date:** 2026-11-03  
**Status:** **VERIFIED & SECURED**

---

## 1. CRITICAL SECURITY GATES

| Security Control | Requirement | Implementation Status | Verification Details |
| :--- | :--- | :---: | :--- |
| **Debug Mode** | `APP_DEBUG=false` | **ENFORCED** | Verified in `.env`. Prevents detailed stack traces, environment variables, or database queries from leaking on exceptions. |
| **Database Port Isolation** | MySQL port 3306 closed to public | **VERIFIED** | Cloudflare tunnel targets only `http://127.0.0.1:8000`. Port 3306 is bound to loopback/local network only. |
| **No Administrative Tools** | phpMyAdmin, Redis, SSH unexposed | **VERIFIED** | No secondary management tools or ports are proxied. |
| **HTTPS Enforcement** | Strict TLS encryption | **ENFORCED** | Cloudflare terminates public connections via TLS 1.3. Cookie `Secure` attribute is active. |
| **Secret & Key Protection** | No `.env` or Git exposure | **VERIFIED** | Web root points to `public/`. `.env` and `.git` reside outside public web root and cannot be retrieved via HTTP. |
| **Multi-Guard Separation** | Independent authentication tables | **VERIFIED** | `platform` guard uses `platform_users`; `web` guard uses `users`. Cross-guard credential re-use is blocked. |
| **Rate Limiting** | Prevent brute-force attacks | **ENFORCED** | Platform login endpoint is restricted to 60 requests/min via `throttle:60,1`. |
| **CSRF Protection** | Prevent Cross-Site Request Forgery | **ENFORCED** | Laravel CSRF token verification is active on all POST/PUT/DELETE endpoints. |
| **Session Security** | Protect session hijack | **ENFORCED** | Cookies configured with `HttpOnly=true`, `SameSite=lax`, and `Secure=true`. |
| **Data Privacy (Page 94)**| Zero operational data leakage | **VERIFIED** | Platform Super Admin cannot view employee compensation, bank details, or private HR files. |

---

## 2. TENANT ISOLATION & IDOR DEFENSE

### 2.1 Backend Query Isolation
- **`BelongsToCompany` Global Scope**: Automatically injects `WHERE company_id = ?` on every Eloquent query across Employee, Attendance, Leaves, Payroll, Assets, and Reports models.
- **Tenant Context Scoping**: `CompanyContextService::getActiveCompanyId()` restricts queries to companies belonging to the authenticated tenant.
- **Switching Protection**: `CompanyContextService::setActiveCompanyId()` explicitly verifies `$company->tenant_id === $userTenantId`. An HO Admin cannot switch to an entity outside their tenant boundary.

### 2.2 Direct Object Reference (IDOR) Hardening
- Attempting to access foreign tenant resources via URL parameter manipulation (e.g., `/employees/999` where ID 999 belongs to another tenant) returns HTTP 404 (via global scope) or HTTP 403 Forbidden.
- Invoices and subscriptions are scoped by `tenant_id` at the database query level.

---

## 3. SUBSCRIPTION & ENTITLEMENT GOVERNANCE

### 3.1 Server-Side Module Enforcement
- Protected functional modules are gated by `EnsureModuleEntitlement` middleware.
- Client-side navigation hiding alone is never relied upon. Direct HTTP requests to unlicensed module routes trigger an immediate server-side HTTP 403 Forbidden abort.

### 3.2 Headcount & Company Quota Guards
- Pre-creation quota checks run in `EmployeeController::store()` and `CompanyController::store()`.
- Onboarding cannot bypass limits through concurrent company submissions. Aggregate active headcount across sister entities is computed directly from the database.

---

## 4. PUBLIC TUNNEL EXPOSURE ASSESSMENT

### 4.1 Tunnel Scope
- **Exposed Host:** `infringement-affair-delivery-welcome.trycloudflare.com`
- **Internal Target:** `http://127.0.0.1:8000` (Laravel local application)
- **Tunnel Lifecycle:** Ephemeral Cloudflare Quick Tunnel. The tunnel process runs in the background and terminates immediately upon process exit. No persistent DNS records or public certificates remain.

### 4.2 Data Sanitization
- All sample records displayed during the demonstration are fictional/synthetic entities (**Vishal Group**, **Vishal Enterprise**, **Vrishal Eng. Pvt. Ltd.**).
- No actual employee personal identities, live bank account numbers, or real payroll disbursement data are present in the demo database.

---

## 5. SECURITY SIGN-OFF

The Enterprise HRMS demonstration environment meets all mandatory safety rules, data privacy standards, and reverse proxy security guidelines.

**SECURITY VERDICT: APPROVED FOR PUBLIC DEMONSTRATION**

