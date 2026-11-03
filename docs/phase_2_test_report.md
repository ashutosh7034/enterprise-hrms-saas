# Enterprise HRMS — Phase 2 Automated Test Report

**Execution Engine**: PHPUnit 11 / Laravel Test Runner  
**Total Tests**: 48  
**Total Assertions**: 241  
**Total Failures**: 0  
**Pass Rate**: **100%**  
**Execution Time**: 3.08 seconds  
**Test Date**: November 2, 2026

---

## 1. Test Suite Summary

| Test Suite File | Module Tested | Tests | Assertions | Status |
|---|---|---|---|---|
| `Tests\Feature\AssetManagementTest` | Asset Registration, Company Scoping, Allocation, Return Inspection & Depreciation Calculations | 5 | 18 | **PASSED** |
| `Tests\Feature\CentralBlacklistTest` | Blacklist Proposal, HO Approval, Cross-Company Blocking, Whitelisting & Identity Review Resolution | 4 | 19 | **PASSED** |
| `Tests\Feature\DocumentVerificationTest` | Employee Document Upload, Multi-Version Increment, HR Verification & Rejection | 3 | 14 | **PASSED** |
| `Tests\Feature\EkycLifecycleTest` | eKYC Success Flow, 14-Day Grace Period, 3-Attempt Lockout & HO Exception Override vs HR 403 Forbidden | 5 | 20 | **PASSED** |
| `Tests\Feature\EmployeeOnboardingTest` | Candidate Onboarding, Code Gen, Duplicate Detection, Central Blacklist Halting & Gate Pass History | 4 | 19 | **PASSED** |
| `Tests\Feature\FineAndRecoveryTest` | Fine Master Policy, Damage Assessment Approval, Installment Generation, Direct Settlement & HO Waiver | 4 | 24 | **PASSED** |
| `Tests\Feature\AuditLogImmutabilityTest` | Master Data Event Capture, Application Immutability & DB Immutability Triggers | 3 | 12 | **PASSED** |
| `Tests\Feature\CompanySwitchingTest` | Active Company Switching, Session Scoping & Unauthorized Switch Rejection | 2 | 10 | **PASSED** |
| `Tests\Feature\MasterDataCrudTest` | Locations, Categories, Notice Periods, Contractor Agency Management | 3 | 18 | **PASSED** |
| `Tests\Feature\MultiCompanyIsolationTest` | Company Scoped Queries & View Isolation | 2 | 12 | **PASSED** |
| `Tests\Feature\RbacAuthorizationTest` | ESS/MSS/HR/HO Route Access Restrictions | 4 | 22 | **PASSED** |
| `Tests\Feature\SecurityAuditRegressionTest` | Cross-Company ID Manipulation, DB Triggers & Prod Credential Masking | 5 | 29 | **PASSED** |
| `Tests\Feature\UiScreensVerificationTest` | Master & Dashboard Blade Screen Rendering & Auth Flow | 2 | 18 | **PASSED** |
| `Tests\Feature\ExampleTest` | Application Baseline Health | 1 | 2 | **PASSED** |
| `Tests\Unit\ExampleTest` | Unit Baseline Health | 1 | 4 | **PASSED** |
| **TOTAL** | | **48** | **241** | **100% PASSED** |

---

## 2. Detailed Breakdown of Phase 2 Feature Tests

### 2.1 `Tests\Feature\AssetManagementTest`
1. `test_can_register_new_asset_with_company_scope`:
   - Verifies an asset is registered with proper serial, asset tag, and purchase cost within the active company scope.
2. `test_can_allocate_asset_to_employee`:
   - Verifies asset custody assignment to an employee, updating asset status to `Allocated` and creating an allocation log.
3. `test_cannot_allocate_cross_company_asset`:
   - Verifies an attempt to allocate an asset from Vishal Enterprise to an employee at Vrishal Eng. Pvt. Ltd. is rejected with validation or 404/403.
4. `test_can_return_asset_with_condition_inspection`:
   - Verifies return custody workflow, recording return condition (`Damaged`), return date, and updating asset condition.
5. `test_depreciation_calculation_service`:
   - Verifies SLM and WDV calculation accuracy against mathematical models for given cost, useful life, and salvage values.

### 2.2 `Tests\Feature\CentralBlacklistTest`
1. `test_can_propose_blacklist_entry_and_ho_admin_approves`:
   - HR proposes candidate for blacklisting; HO Admin approves and transitions status to `Active`.
2. `test_blacklist_is_enforced_cross_company`:
   - Candidate blacklisted under Company 1 is actively blocked from onboarding under Company 2.
3. `test_whitelisting_clears_block_with_mandatory_justification`:
   - HO Admin whitelists a blacklisted record with reason; candidate is subsequent cleared for onboarding.
4. `test_identity_match_review_workflow`:
   - Ambiguous or partial candidate match generates an `IdentityMatchReview`; HR evaluates and clears as `False Positive Cleared`.

### 2.3 `Tests\Feature\DocumentVerificationTest`
1. `test_can_upload_document_and_version_is_created`:
   - Initial document upload creates parent record and version 1.
2. `test_subsequent_upload_increments_version_number`:
   - Uploading a replacement file increments to version 2 and updates current file pointer.
3. `test_hr_can_verify_or_reject_document_with_reason`:
   - HR updates document status to `Verified` or `Rejected` with recorded feedback.

### 2.4 `Tests\Feature\EkycLifecycleTest`
1. `test_successful_ekyc_attempt_marks_employee_verified`:
   - Simulated/manual eKYC verification advances employee status from `Pending` to `Verified`.
2. `test_failed_attempt_activates_grace_period_with_14_day_deadline`:
   - First failure transitions employee to `Grace Period` with 14-day expiry.
3. `test_three_failed_attempts_locks_into_verification_failed`:
   - 3 consecutive failures transitions employee status to `Verification Failed` and locks further automated attempts.
4. `test_ho_admin_can_approve_exception_override`:
   - HO Admin executes manual override with justification; status transitions to `Verified (HO Override)`.
5. `test_hr_executive_cannot_approve_exception_override`:
   - HR Executive attempting override is blocked with HTTP 403 Forbidden.

### 2.5 `Tests\Feature\EmployeeOnboardingTest`
1. `test_can_onboard_new_candidate_with_auto_generated_code`:
   - Creates new employee, confirms code sequence `VE-EMP-xxxx`, verifies Aadhaar masking `XXXX-XXXX-1234` and SHA-256 tokenization.
2. `test_duplicate_candidate_in_same_company_is_rejected`:
   - Candidate with duplicate phone or PAN is rejected with session errors.
3. `test_candidate_flagged_on_central_blacklist_is_halted_immediately`:
   - Candidate matching active blacklist record is immediately halted.
4. `test_gate_pass_update_validates_uniqueness_and_logs_history`:
   - Gate pass update checks company uniqueness and creates an immutable log in `employee_gate_pass_histories`.

### 2.6 `Tests\Feature\FineAndRecoveryTest`
1. `test_can_create_fine_master_policy`:
   - Creates fine policy with max amount and default installment count.
2. `test_can_log_damage_assessment_and_approve_generates_installments`:
   - Logs damage assessment, approves, and automatically creates scheduled recovery records.
3. `test_direct_payment_recording_settles_installment`:
   - Records direct cash payment settling an installment.
4. `test_ho_admin_can_apply_waiver`:
   - HO Admin applies full or partial waiver with required justification.

---

## 3. Regression Health

Phase 1 features (Multi-company switching, master data CRUD, RBAC boundaries, database-level audit immutability triggers, UI rendering) continue to pass 100% without regression.

