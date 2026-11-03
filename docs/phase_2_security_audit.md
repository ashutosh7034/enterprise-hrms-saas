# Enterprise HRMS — Phase 2 Security & Compliance Audit
## Regulatory Privacy, RBAC Boundaries, Cross-Company Isolation & Audit Immutability

**Auditor Role**: Senior Security Engineer & Compliance Lead  
**Audit Target**: Enterprise HRMS Phase 2 Application Code, Database, and Services  
**Compliance Frameworks**: UIDAI Aadhaar Regulatory Guidelines, Indian IT Act, OWASP Top 10, Multi-Tenant Security Standards  
**Status**: **PASSED — ALL GATES VERIFIED**

---

## 1. UIDAI Regulatory Aadhaar Compliance & Data Protection

### 1.1 Plaintext Aadhaar Elimination
- **Requirement**: Plaintext 12-digit Aadhaar numbers must NEVER be stored in databases, written to disk, exposed in JSON payloads, or written to server logs.
- **Implementation**:
  - `EmployeeOnboardingService::hashAadhaar()` transforms the input into an immutable SHA-256 token (`aadhaar_hash`), used strictly for duplicate detection and blacklist lookup.
  - `EmployeeOnboardingService::maskAadhaar()` generates a privacy-preserving display string: `XXXX-XXXX-1234` (`aadhaar_masked`), displaying only the final 4 digits.
- **Verification**:
  - Automated test `EmployeeOnboardingTest::test_can_onboard_new_candidate_with_auto_generated_code` asserts:
    1. Raw 12-digit string is absent from `$employee->toArray()` and its JSON serialization.
    2. `$employee->aadhaar_masked` strictly matches `XXXX-XXXX-1234`.
    3. `$employee->aadhaar_hash` strictly equals `hash('sha256', $rawAadhaar)`.
    4. Database table `employees` contains no `aadhaar_number` column.

---

## 2. Multi-Company Isolation vs Cross-Company Central Blacklist

### 2.1 Multi-Tenant Isolation
- The `BelongsToCompany` trait applies an Eloquent Global Scope filtering all queries by `active_company_id`.
- Models verified under strict company isolation:
  - `Employee`, `Asset`, `AssetCategory`, `FineMaster`, `AssetDamageAssessment`.
- Cross-company ID manipulation attacks (`SecurityAuditRegressionTest::test_cross_company_manipulated_id_attacks_are_blocked`) return HTTP 404/403 when an authenticated user in Vishal Enterprise attempts to mutate or view records belonging to Vrishal Eng. Pvt. Ltd.

### 2.2 Cross-Company Central Blacklist Scoping
- **Architecture**: `CentralBlacklistRecord` intentionally does **NOT** apply `BelongsToCompany`.
- **Security Justification**: If an employee is terminated for theft or gross misconduct at Vishal Enterprise and blacklisted, the group-wide security posture requires that Vrishal Eng. Pvt. Ltd. immediately halts any onboarding attempt for the same individual.
- **Verification**: `CentralBlacklistTest::test_blacklist_is_enforced_cross_company` verifies that a candidate blacklisted under Company 1 (`VE`) is blocked when submitted for onboarding under Company 2 (`VRE`).

---

## 3. RBAC Matrix & Privilege Escalation Defenses

| Protected Action | HO Admin (`ho_admin`) | HR Executive (`hr_executive`) | Manager (`manager_mss`) | Employee (`employee_ess`) | Enforced By |
|---|---|---|---|---|---|
| View Employees | Allowed | Allowed | Direct Reports Only | Self Only | `EmployeePolicy`, `CheckRole` |
| Onboard Candidate | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| Update Gate Pass | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| eKYC Trigger | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| **eKYC Exception Override** | **Allowed** | **Denied (403)** | Denied (403) | Denied (403) | `CheckRole:ho_admin` |
| Propose Blacklist Entry | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| **Approve Blacklist Entry** | **Allowed** | **Denied (403)** | Denied (403) | Denied (403) | `CheckRole:ho_admin` |
| **Whitelist Individual** | **Allowed** | **Denied (403)** | Denied (403) | Denied (403) | `CheckRole:ho_admin` |
| Resolve Identity Review | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| Create / Allocate Asset | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| Approve Damage Assessment | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |
| **Apply Fine Waiver** | **Allowed** | **Denied (403)** | Denied (403) | Denied (403) | `CheckRole:ho_admin` |
| Direct Fine Payment Record | Allowed | Allowed | Denied (403) | Denied (403) | `CheckRole:ho_admin,hr_executive` |

- **Verification**: `EkycLifecycleTest::test_hr_executive_cannot_approve_exception_override` explicitly tests that HR Executives receive HTTP 403 when attempting an override.

---

## 4. Audit Trail Integrity & Immutability

1. **Comprehensive Event Auditing**:
   - Every candidate onboarding, gate pass update, eKYC failure, HO override, blacklist proposition/approval/whitelisting, asset allocation/return, and fine assessment/waiver triggers an immutable `AuditLog` entry.
2. **Database-Level Immutability**:
   - Triggers `trg_audit_logs_prevent_update` and `trg_audit_logs_prevent_delete` on MySQL prevent in-place modification or deletion of existing audit records.
3. **Application-Level Immutability**:
   - Eloquent events on `AuditLog` model throw exceptions if `update()` or `delete()` is called programmatically.
   - Tested and verified in `AuditLogImmutabilityTest`.

---

## 5. Input Validation & Web Security

- **Strict Validation**: All controller endpoints enforce strict FormRequest / inline validation with regex patterns for PAN (`^[A-Z]{5}[0-9]{4}[A-Z]$`), Aadhaar (12 numeric digits), phone (numeric sanitization), and positive numeric amounts for financial fields.
- **CSRF Token Enforcement**: All state-modifying requests (`POST`, `PUT`, `PATCH`, `DELETE`) require valid Laravel CSRF tokens.
- **SQL Injection Prevention**: 100% of queries use Eloquent ORM or parameterized PDO bindings; zero raw string interpolation in queries.

