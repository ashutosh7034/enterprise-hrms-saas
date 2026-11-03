# PHASE 7 DYNAMIC PLACEHOLDER RESOLUTION ENGINE SPECIFICATION

**Enterprise Human Resource Management System (HRMS)**  
**Target Environment:** PHP 8.3 | Laravel 13.39.0  
**Document Reference:** `DOCS-PH7-PLH-001`  
**Date:** November 2026  

---

## 1. Engine Objective & Architecture

The **Placeholder Resolution Engine** (`App\Services\Document\PlaceholderResolutionService`) provides safe, deterministic token substitution for all HR letters and print forms. It extracts live master data from the workforce profile, corporate entity master, active salary assignments, and document runtime metadata.

### 1.1 Core Principles
- **No `eval()` or Code Execution:** All replacements use string/regex dictionary lookups.
- **Safe HTML Escaping:** Dynamic strings are escaped using `e()` or `htmlspecialchars()` with `ENT_QUOTES` and UTF-8 encoding.
- **Role-Gated Salary Privileges:** Access to sensitive financial tokens (`{basic_salary}`, `{gross_salary}`, `{annual_ctc}`, `{net_salary}`) requires `ho_admin` or `hr_executive` role authorization. Non-privileged calls trigger `Illuminate\Auth\Access\AuthorizationException`.

---

## 2. Placeholder Registry Taxonomy

### 2.1 Company Metadata
- `{company_name}`: Official corporate entity name (e.g., *Vishal Enterprise* or *Vrishal Eng. Pvt. Ltd.*)
- `{company_code}`: Company code (`VE` or `VRE`)
- `{company_address}`: Registered legal office address
- `{company_phone}`, `{company_email}`, `{company_cin}`, `{company_gstin}`

### 2.2 Employee Profile
- `{employee_name}`: Employee full name (`first_name` + `last_name`)
- `{employee_code}`: Employee identification code (e.g., `VE-EMP-1001`)
- `{gate_pass_number}`: Physical plant access card token
- `{first_name}`, `{last_name}`, `{gender}`
- `{email}`, `{phone}`, `{address}`
- `{date_of_birth}`, `{date_of_joining}`, `{probation_end_date}`, `{confirmation_date}`
- `{department}`: Functional department title
- `{designation}`: Organizational designation
- `{location}`: Assigned plant or branch office
- `{category}`: Workforce category (Staff, Worker, Executive)
- `{reporting_manager_name}`, `{reporting_manager_designation}`
- `{pan_masked}`, `{aadhaar_masked}`, `{emergency_contact_name}`, `{emergency_contact_phone}`

### 2.3 Compensation & Financial (Restricted)
- `{basic_salary}`: Monthly basic pay formatted as INR (`₹ 25,000.00`)
- `{gross_salary}`: Total monthly gross compensation formatted as INR
- `{annual_ctc}`: Annualized Cost to Company (Gross * 12) formatted as INR
- `{net_salary}`: Estimated monthly take-home pay
- `{salary_effective_date}`: Effective date of active salary assignment

### 2.4 Document & Verification Metadata
- `{document_number}`: Sequential enterprise tracking code (e.g. `DOC-VE-2026-00001`)
- `{document_date}`: Generation date formatted as `d F Y`
- `{effective_date}`: Legal effective date of the letter action
- `{verification_code}`: Cryptographically unique token (e.g. `VE-DOC-8F9B2C1A`)
- `{verification_url}`: Full public verification portal URL
- `{signatory_name}`, `{signatory_designation}`
- `{current_year}`, `{current_date}`

---

## 3. Formatting Standards

- **Currency:** Prefixed with `₹ ` and formatted with comma thousand separators and two decimal digits (`₹ 50,000.00`).
- **Dates:** Long formal British/Indian format (`15 January 2026`) for maximum legibility in legal contracts.
- **Masking:** PAN masked as `ABXXXXX12C`; Aadhaar masked as `XXXXXXXX1234`.

