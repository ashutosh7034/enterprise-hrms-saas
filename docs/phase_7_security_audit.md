# PHASE 7 SECURITY AUDIT & MULTI-COMPANY ISOLATION REPORT

**Enterprise Human Resource Management System (HRMS)**  
**Target Database:** MySQL 8.0.46 (Dev) | MySQL 8.4 LTS (Target) | Isolated Test DB: `enterprise_hrms_test`  
**Document Reference:** `DOCS-PH7-SEC-001`  
**Date:** November 2026  

---

## 1. Multi-Company Tenancy & Isolation Audit

### 1.1 Tenant Model Boundary (`BelongsToCompany`)
- All `GeneratedDocument` records strictly inherit `BelongsToCompany`.
- Queries are automatically bounded by `company_id = activeCompanyId` via global scope.
- In `DocumentGenerationService::authorizeAccess(...)`, explicit cross-company checks enforce `abort(403)` or throw `AuthorizationException` if the document's company does not match the active session context.
- Verified in `Tests\Feature\DocumentGenerationAndPdfTest::test_cross_company_document_download_is_forbidden`.

### 1.2 Document Template Scoping
- Templates are partitioned per entity (`company_id = 1` for Vishal Enterprise, `company_id = 2` for Vrishal Eng. Pvt. Ltd.).
- Global templates (`company_id IS NULL`) are safely shared across legal entities without cross-tenant modification leaks.

---

## 2. Public Verification Portal Security & Anti-Enumeration

### 2.1 Zero PII Leakage Guarantee
The public verification endpoint (`/verify-document/{code}`) exposes only non-sensitive verification metadata:
- **Masked Name:** Recipient name is always masked (`A*** S***`).
- **No Contact Info:** Mobile phone numbers and residential addresses are withheld.
- **No Financial Data:** Monthly gross salary, basic pay, and CTC figures are completely stripped from public verification output.
- **No Government IDs:** Masked PAN and Aadhaar are never displayed.

### 2.2 Rate Limiting
- Public verification requests are capped at 60 requests per minute per IP via Laravel's `throttle:60,1` middleware to prevent brute-force token enumeration.

### 2.3 Verification Audit Trail
Every verification query (whether valid or invalid) creates an immutable record in `document_verification_logs` capturing:
- Queried verification code
- Client IP address and User Agent
- Result status (`Valid`, `Invalid`, `Revoked`, `Expired`)
- Timestamp

---

## 3. Role-Based Access Control (RBAC) Matrix

| Route / Capability | HO Admin | HR Executive | Manager MSS | Employee ESS | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| Template Master (`/admin/documents/templates`) | Allowed | Allowed | Denied (403) | Denied (403) | Redirect Login |
| Document Hub (`/admin/documents`) | Allowed | Allowed | Denied (403) | Denied (403) | Redirect Login |
| Generate Document (`POST /admin/documents/generate`) | Allowed | Allowed | Denied (403) | Denied (403) | Redirect Login |
| Approval Queue (`/admin/documents/approvals`) | Allowed | Allowed | Denied (403) | Denied (403) | Redirect Login |
| Sensitive Salary Tokens in Generation | Allowed | Allowed | Denied (403) | Denied (403) | Denied (403) |
| ESS My Documents (`/documents/my-documents`) | Allowed | Allowed | Allowed | Allowed (Own) | Redirect Login |
| Public Verification (`/verify-document/{code}`) | Allowed | Allowed | Allowed | Allowed | Allowed |

