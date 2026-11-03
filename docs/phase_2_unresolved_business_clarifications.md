# Enterprise HRMS — Phase 2 Unresolved Business Clarifications & Integration Assumptions

**Document Purpose**: Record and trace business rules, third-party dependencies, and statutory assumptions established during Phase 2 that require confirmation or configuration from executive stakeholders before production deployment.

---

## 1. External eKYC Service Provider Integration
- **Status in Phase 2**: Implemented using a clean, pluggable adapter pattern (`EkycAdapterInterface`) featuring:
  1. `SimulatedEkycAdapter`: Built for automated testing and sandbox environments.
  2. `ManualVerificationAdapter`: Built for HR document verification against physical or scanned IDs.
- **Clarification Required for Production**:
  - Which third-party eKYC aggregator or government-approved API gateway (e.g., Karza, Signzy, NSDL, Protean, Digilocker) will the client contract with?
  - Once credentials (Client ID, Secret, Public Key) are provided, a new `GovernmentEkycAdapter` implementing `EkycAdapterInterface` will be connected without modifying existing controllers or business logic.

---

## 2. Statutory Fine Recovery Caps (Payment of Wages Act, 1936)
- **Status in Phase 2**:
  - `fine_masters` and `asset_damage_assessments` allow configuring employee liability and installment counts.
  - An installment ledger records scheduled recovery records with `is_payroll_deductible = true`.
- **Statutory Constraint to Re-confirm with Legal/HR**:
  - Under Section 7 & 8 of the Indian Payment of Wages Act, total deductions (including fines and damage recovery) in any single wage period cannot exceed **50%** of wages (or 75% in cases involving cooperative societies).
  - During Phase 5 (Payroll Engine), the deduction processor will validate monthly installment deductions against this statutory threshold. If an installment exceeds the allowable monthly wage deduction limit, the system will automatically defer the excess balance to subsequent wage cycles.

---

## 3. Contractor Workforce Gate Pass Lifecycles
- **Status in Phase 2**:
  - Employees onboarded under the `Contractor` channel can be linked to `contractors` (agencies).
  - Gate pass numbers are enforced unique per company.
- **Clarification Required**:
  - When a third-party contractor is de-listed or terminated, should all associated contractual personnel gate passes be automatically expired/deactivated in batch, or should individual checkout workflows be required?
  - Currently, contractor status transitions require explicit HR review.

---

## 4. Blacklist Fuzzy Matching Confidence Thresholds
- **Status in Phase 2**:
  - Exact token match (PAN, Aadhaar hash, Phone) blocks candidate immediately.
  - Levenshtein phonetic/distance matching on Name + DOB queues an `IdentityMatchReview` if similarity exceeds 80%.
- **Clarification Required**:
  - Does HR management require adjustable per-company confidence thresholds, or is an 80% default acceptable across all business units?

