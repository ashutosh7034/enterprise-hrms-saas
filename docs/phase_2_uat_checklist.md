# Enterprise HRMS — Phase 2 User Acceptance Testing (UAT) Checklist

**Test Environment**: Local Development / Pre-Production  
**Base URL**: `http://127.0.0.1:8000`  
**Companies**: Vishal Enterprise (`VE`), Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Target Roles**: `ho_admin`, `hr_executive`, `manager_mss`, `employee_ess`

---

## 1. Role: HR Executive (`hr_executive`)

| Step # | Module / Action | Input / Action | Expected Result | Verified |
|---|---|---|---|---|
| **HR-01** | Employee Onboarding | Navigate to `/employees/create`. Fill in candidate personal details, Aadhaar number, PAN number, and Gate Pass. Submit form. | Candidate is created. Auto-generated employee code assigned (`VE-EMP-xxxx`). Aadhaar displays as `XXXX-XXXX-xxxx`. Gate pass history logged. | [x] |
| **HR-02** | Duplicate Prevention | Attempt to onboard a candidate with an identical PAN or phone number as an existing active employee. | Submission fails with validation message alerting of existing employee. | [x] |
| **HR-03** | Gate Pass Management | Navigate to `/employees/{id}`. Open Gate Pass Update modal. Enter new gate pass and reason. Submit. | Gate pass is updated. Previous pass and reason recorded in Gate Pass History table. | [x] |
| **HR-04** | Document Upload & Versioning | On employee profile, upload Aadhaar scan (PDF). Upload a second revised file of the same document type. | First upload creates Version 1 (`Pending`). Second upload increments to Version 2. | [x] |
| **HR-05** | Document Verification | Click "Verify" or "Reject" on uploaded document. Provide rejection reason if rejected. | Status updates to `Verified` or `Rejected` with inspector name and timestamp. | [x] |
| **HR-06** | eKYC Trigger | On employee profile, click "Trigger eKYC Verification". Select adapter (Manual/Simulated). | Verification attempt is logged in eKYC history. Status updates accordingly. | [x] |
| **HR-07** | Propose Blacklist Entry | Navigate to `/blacklist/create`. Enter candidate details and reason category. Submit. | Record is created with status `Proposed`. Notification displays awaiting HO Admin approval. | [x] |
| **HR-08** | Asset Registration & Allocation | Navigate to `/assets/create`. Register a laptop. From `/assets/{id}`, allocate to an active employee. | Asset status transitions from `In Stock` to `Allocated`. Handover log recorded. | [x] |
| **HR-09** | Asset Return | From `/assets/{id}`, click "Process Return". Select condition as `Damaged` and note defects. | Asset status transitions to `Damaged`. Handover log records returned date and inspector. | [x] |
| **HR-10** | Damage Assessment | Navigate to `/fines/damage-assessments`. Create assessment for damaged asset, specify liability amount and 3 installments. | Assessment created in `Pending Approval` status. | [x] |
| **HR-11** | Approve Assessment | Click "Approve Assessment". | Assessment marked `Approved`. 3 monthly installment records generated automatically. | [x] |
| **HR-12** | Direct Payment Settlement | On an installment record, click "Record Payment". Enter receipt number and cash/bank method. | Installment status updates to `Paid Directly`. Assessment balance updates. | [x] |

---

## 2. Role: Head Office Administrator (`ho_admin`)

| Step # | Module / Action | Input / Action | Expected Result | Verified |
|---|---|---|---|---|
| **HO-01** | Company Context Switch | Click Company Switcher in top navigation. Switch between Vishal Enterprise and Vrishal Eng. Pvt. Ltd. | Active company updates immediately. Data lists (employees, assets, departments) filter strictly by selected company. | [x] |
| **HO-02** | Blacklist Approval | Navigate to `/blacklist`. Locate record in `Proposed` status. Click "Approve Blacklist". | Status updates to `Active`. Record is now actively enforced across all group companies. | [x] |
| **HO-03** | Blacklist Whitelisting | On an active blacklist record, click "Whitelist Candidate". Enter mandatory executive justification. | Status updates to `Whitelisted`. Candidate is now permitted for onboarding. Action logged to audit trail. | [x] |
| **HO-04** | eKYC Exception Override | Navigate to an employee whose eKYC has reached `Verification Failed` (3 attempts). Click "HO Override". Enter justification. | Employee eKYC status advances to `Verified (HO Override)`. Action logged with HO Admin identity. | [x] |
| **HO-05** | Fine Waiver Approval | Navigate to `/fines/damage-assessments/{id}`. Click "Apply Waiver". Enter waiver amount and executive reason. | Waiver applied. Scheduled recovery installments adjusted or marked `Waived`. Audit record logged. | [x] |

---

## 3. Negative Security & Boundary Tests

| Step # | Role Tested | Action | Expected Result | Verified |
|---|---|---|---|---|
| **SEC-01** | HR Executive | Attempt to access HO Override endpoint directly: `POST /employees/{id}/ekyc/override`. | **HTTP 403 Forbidden**. Action denied. | [x] |
| **SEC-02** | HR Executive | Attempt to whitelist a blacklisted candidate: `POST /blacklist/{id}/whitelist`. | **HTTP 403 Forbidden**. Action denied. | [x] |
| **SEC-03** | Manager MSS | Attempt to access `/employees/create` or `/assets/create`. | **HTTP 403 Forbidden**. Action denied. | [x] |
| **SEC-04** | Employee ESS | Attempt to access `/employees` or `/fines`. | **HTTP 403 Forbidden**. Redirected or forbidden. | [x] |
| **SEC-05** | Any Role | Attempt to allocate Company 1 asset to Company 2 employee. | **Validation Error / HTTP 422**. Cross-company asset allocation blocked. | [x] |
| **SEC-06** | Any Role | Attempt to update or delete record in `audit_logs` table directly via SQL or Eloquent. | **Blocked by Database Trigger & Model Event**. Error raised. | [x] |

