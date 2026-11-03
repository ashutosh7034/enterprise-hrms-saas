# PHASE 7 ARCHITECTURE AUDIT: LETTER & FORM PRINT ENGINE, DOCUMENT TEMPLATES, PDF GENERATION & ESS

**Enterprise Human Resource Management System (HRMS)**  
**Target Environment:** PHP 8.3 | Laravel 13.39.0 | MySQL 8.0.46 (Dev) / MySQL 8.4 LTS (Target)  
**Safety Gate Status:** CLEARED (`enterprise_hrms_test` verified and isolated from `enterprise_hrms`)  
**Document Reference:** `DOCS-PH7-ARCH-001`  
**Date:** November 2026  

---

## 1. Executive Summary & Objectives

Phase 7 of the Enterprise HRMS implements a complete, industrial-grade **Letter & Form Print Engine**, providing automated document composition, dynamic placeholder resolution, regulatory and organizational template management, immutable snapshotting, PDF document generation, multi-stage approval workflows, Employee Self-Service (ESS) document distribution, and secure public verification.

The engine must support the multi-entity corporate structure:
- **Vishal Enterprise (`VE`)**
- **Vrishal Eng. Pvt. Ltd. (`VRE`)**

It guarantees strict multi-company isolation, strict RBAC controls, immutable audit trails, private disk storage for generated PDFs, and a secure rate-limited verification mechanism that prevents PII leaks or enumeration attacks.

---

## 2. Existing System Inspection & Inter-Module Alignment

An in-depth code-level audit was conducted across existing modules (Phases 1–6):

### 2.1 Multi-Company Isolation & Tenancy (`BelongsToCompany`)
- All document templates and generated documents inherit tenant boundaries through `App\Models\Traits\BelongsToCompany`.
- Global scope ensures tenant-scoped queries by default based on `CompanyContextService::getActiveCompanyId()`.
- System-wide default templates (shared across entities) will have `company_id = NULL` and can be customized per entity (`company_id = 1` for VE, `company_id = 2` for VRE).

### 2.2 Role-Based Access Control (RBAC)
- **`ho_admin` & `hr_executive`:** Full lifecycle control over Document Templates, Versioning, Generation, and Approval workflows.
- **`manager_mss`:** Generation requests and approval delegation for subordinates in their department/reporting line.
- **`employee_ess`:** Read-only access strictly to documents generated and issued for their own employee profile (`/documents/my-documents`).
- **Salary/Compensation Security Gate:** Sensitive salary placeholders (e.g., `{basic_salary}`, `{gross_salary}`, `{annual_ctc}`) must be restricted so that ESS users cannot trigger unauthorized compensation letters or expose unassigned/confidential salary grades.

### 2.3 Data Foundation Dependencies
- **Workforce Profile (`App\Models\Employee`):** Supplies personal details, identification (masked PAN/Aadhaar), joining/confirmation dates, designation, department, location, contractor links, reporting hierarchy.
- **Compensation & Payroll (`App\Models\EmployeeSalaryAssignment`, `PayrollRunEmployee`):** Supplies active gross salary, CTC, basic salary, allowance breakdown.
- **Leave & Attendance (`LeaveBalance`, `AttendanceRecord`):** Supplies balances for leave encashment and form pre-fills.
- **Exit & Settlement (`FnfSettlement`):** Supplies relieving dates, settlement reference numbers, clearance flags.
- **Notification Engine (Phase 6):** Alerts employees and managers via In-App, Email, SMS, or WhatsApp when documents are submitted, approved, issued, or rejected.

---

## 3. PDF Generation Infrastructure

### 3.1 PDF Engine: `Dompdf\Dompdf`
- The system reuses the already integrated and proven `Dompdf\Dompdf` package (utilized in Phase 5 for payslips and F&F settlement statements).
- Standard paper sizing: **A4 (`210mm x 297mm`)**, portrait mode default, with customizable margins (15mm – 20mm).
- Typography: Standard Helvetica / DejaVu Sans fonts with UTF-8 support for currency symbols (e.g., `₹`, INR).
- Page Numbering & Footers: Native Dompdf inline PHP/CSS canvas page numbering ("Page X of Y") and corporate confidentiality notices.
- Header/Letterhead: Dynamic rendering of corporate logo, CIN, GSTIN, registered office address, and contact numbers.

### 3.2 Secure Document Storage
- Generated PDF binaries are **NEVER** stored in the public web root (`public/`).
- Storage path: `storage/app/private/documents/{company_code}/{year}/{document_number}.pdf`.
- Access is strictly mediated via authenticated Laravel controller responses (`Storage::download()` or `response()->file()`) enforcing RBAC and company isolation checks.

---

## 4. Dynamic Placeholder Engine Architecture

### 4.1 Token Format and Grammar
Placeholders follow the deterministic syntax `{token_name}`.

### 4.2 Token Registry Taxonomy
1. **Company Placeholders:**
   - `{company_name}`: Legal company name (e.g., Vishal Enterprise)
   - `{company_code}`: Company code (VE / VRE)
   - `{company_address}`: Registered legal address
   - `{company_phone}`, `{company_email}`, `{company_cin}`, `{company_gstin}`
2. **Employee Placeholders:**
   - `{employee_name}`: Full name (`first_name` + `last_name`)
   - `{employee_code}`: E.g., `VE-0001`
   - `{gate_pass_number}`: E.g., `GP-0001`
   - `{first_name}`, `{last_name}`, `{gender}`
   - `{email}`, `{phone}`, `{address}`
   - `{date_of_birth}`, `{date_of_joining}`, `{confirmation_date}`, `{probation_end_date}`
   - `{department}`, `{designation}`, `{location}`, `{category}`
   - `{reporting_manager_name}`, `{reporting_manager_designation}`
3. **Compensation & Payroll Placeholders (Security-gated):**
   - `{basic_salary}`: Formatted currency (e.g., `₹ 25,000.00`)
   - `{gross_salary}`: Formatted monthly gross (e.g., `₹ 50,000.00`)
   - `{annual_ctc}`: Formatted annual CTC (e.g., `₹ 6,00,000.00`)
   - `{salary_effective_date}`: Effective date of compensation structure
4. **Document Metadata Placeholders:**
   - `{document_number}`: Unique identifier (e.g., `DOC-VE-2026-0001`)
   - `{document_date}`: Date of issue/generation
   - `{effective_date}`: Date letter becomes effective
   - `{verification_code}`: Unique 12-character verification token
   - `{verification_url}`: Full URL to public verification portal
   - `{signatory_name}`, `{signatory_designation}`

### 4.3 Security & Execution Model
- **No `eval()` or Arbitrary PHP Code:** The placeholder resolution engine uses strict regex mapping (`preg_replace_callback`) and whitelist dictionary lookups.
- **Safe HTML Escaping:** All dynamic values are escaped using `e()` or `htmlspecialchars()` before substitution, protecting against stored XSS.
- **Role Permission Checking:** If a non-authorized role attempts to generate a document containing sensitive salary placeholders, an `AuthorizationException` is triggered.

---

## 5. Document Lifecycle & Approval State Machine

The document generation process follows a strict state transition model:

```
[Draft] ──> [Pending Approval] ──> [Approved] ──> [Generated] ──> [Issued]
   │                 │                                  │
   └──> [Cancelled]  └──> [Rejected]                    └──> [Archived]
```

1. **Draft:** HR or Manager drafts letter content or selects an approved template.
2. **Pending Approval:** Submitted for review if the template has `requires_approval = true`.
3. **Approved:** Approved by designated authority (HO Admin / HR Head).
4. **Generated:** The placeholder engine resolves all tokens, generates an immutable HTML snapshot (`content_snapshot_html`), persists metadata (`metadata_snapshot_json`), and builds the PDF binary.
5. **Issued:** Document is marked published; notification dispatched to employee; visible in ESS "My Documents".
6. **Archived / Cancelled:** Revoked or marked historical with reason logged in audit trail.

---

## 6. Document Verification System Architecture

### 6.1 Tamper-Evident Verification Code
- Each generated document is assigned a cryptographically random, uppercase alphanumeric verification code (e.g., `VRE-DOC-8F9B2C1A`).
- The verification code and QR/URL are printed in the document footer.

### 6.2 Public Verification Endpoint: `/verify-document/{code}`
- Publicly accessible without login.
- **Rate-Limited:** Enforces 10 requests per minute per IP to prevent brute-force enumeration.
- **Zero PII Leakage:** Displays strictly:
  - Document Status (Valid / Revoked / Expired)
  - Document Title & Type (e.g., "Relieving & Experience Certificate")
  - Issuing Entity (e.g., "Vishal Enterprise")
  - Issue Date
  - Masked Recipient Name (e.g., "A*** S***")
  - Verification Timestamp
  - **NEVER** displays: salary, full residential address, phone number, PAN, Aadhaar, bank details, or internal remarks.
- Every lookup is logged in `document_verification_logs` with IP, user agent, timestamp, and query result.

---

## 7. Database Architecture: Batch 11 Schema Blueprint

### 7.1 `document_templates`
- `id` (bigint unsigned, PK)
- `company_id` (bigint unsigned, nullable, FK -> companies)
- `category` (enum: Employee Lifecycle, Employee Request, HR Compliance, Employee Forms, Leave & Attendance Forms, Payroll & Finance Forms, Administrative Forms)
- `document_type` (varchar 100, e.g., 'Offer Letter', 'Joining Form')
- `code` (varchar 50, unique per company)
- `name` (varchar 255)
- `description` (text, nullable)
- `content_html` (longtext)
- `version` (int, default 1)
- `status` (enum: Draft, Pending Approval, Approved, Active, Inactive, Archived)
- `effective_from` (date, nullable)
- `effective_to` (date, nullable)
- `requires_approval` (boolean, default false)
- `is_sensitive_salary` (boolean, default false)
- `header_footer_enabled` (boolean, default true)
- `created_by` (bigint unsigned, FK -> users)
- `approved_by` (bigint unsigned, nullable, FK -> users)
- `approved_at` (datetime, nullable)
- `timestamps`, `deleted_at`

### 7.2 `document_template_versions`
- `id` (bigint unsigned, PK)
- `document_template_id` (bigint unsigned, FK -> document_templates)
- `version_number` (int)
- `content_html` (longtext)
- `change_summary` (varchar 500, nullable)
- `created_by` (bigint unsigned, FK -> users)
- `approved_by` (bigint unsigned, nullable, FK -> users)
- `timestamps`

### 7.3 `generated_documents`
- `id` (bigint unsigned, PK)
- `company_id` (bigint unsigned, FK -> companies)
- `employee_id` (bigint unsigned, FK -> employees)
- `template_id` (bigint unsigned, nullable, FK -> document_templates)
- `template_version_id` (bigint unsigned, nullable, FK -> document_template_versions)
- `document_number` (varchar 100, unique)
- `verification_code` (varchar 64, unique, indexed)
- `category` (varchar 100)
- `document_type` (varchar 100)
- `title` (varchar 255)
- `content_snapshot_html` (longtext)
- `metadata_snapshot_json` (json, nullable)
- `pdf_file_path` (varchar 500, nullable)
- `file_size_kb` (int, nullable)
- `status` (enum: Draft, Pending Approval, Approved, Generated, Issued, Rejected, Cancelled, Archived)
- `effective_date` (date, nullable)
- `requested_by` (bigint unsigned, nullable, FK -> users)
- `generated_by` (bigint unsigned, nullable, FK -> users)
- `approved_by` (bigint unsigned, nullable, FK -> users)
- `approved_at` (datetime, nullable)
- `issued_at` (datetime, nullable)
- `rejection_reason` (text, nullable)
- `timestamps`, `deleted_at`

### 7.4 `document_verification_logs`
- `id` (bigint unsigned, PK)
- `generated_document_id` (bigint unsigned, nullable, FK -> generated_documents)
- `verification_code_queried` (varchar 64)
- `ip_address` (varchar 45)
- `user_agent` (text, nullable)
- `result_status` (enum: Valid, Invalid, Expired, Revoked)
- `created_at` (timestamp)

---

## 8. Implementation Stages Roadmap

1. **Stage 7A:** Architecture Audit *(Completed - this document)*.
2. **Stage 7B, 7C, 7D:** Batch 11 Database Migrations & Comprehensive Seeders for 20 HR Letters + 20 HR Forms.
3. **Stage 7E:** Dynamic Placeholder Engine (`PlaceholderResolutionService`).
4. **Stage 7F & 7G:** Document Generation & Dompdf PDF Rendering Engine (`DocumentGenerationService`).
5. **Stage 7H & 7I:** Approval Workflow & Document History Repository.
6. **Stage 7J & 7K:** ESS "My Documents" Portal & Public Tamper-Proof Document Verification Portal.
7. **Stage 7L:** Multi-Company Isolation & Private Storage Enforcement.
8. **Stage 7M:** UI/UX Interfaces adhering strictly to the Slate & Teal Design System.
9. **Stage 7N:** Unit, Feature, and Multi-Company Regression Test Suite execution on `enterprise_hrms_test`.
10. **Stage 7O & 7P:** Comprehensive Phase 7 Documentation and Completion Report Artifact.

