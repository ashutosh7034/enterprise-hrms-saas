# Enterprise HRMS — Phase 5 Unresolved Business Clarifications
## Payroll Policies, Calculation Basis, Statutory Slabs & Approval Hierarchies

**Document Version**: 1.0  
**Date**: November 2, 2026  
**Audience**: Executive Management, Finance Directorate, Human Resources & Payroll Steering Committee  
**Purpose**: Document all payroll business rules, statutory interpretations, and operational configurations that require executive confirmation.

---

## 1. Executive Summary

In adherence to enterprise software engineering standards, hardcoded assumptions have been prohibited. Where the source Business Requirements Document (`hr_applications discussion points_1.1.pdf`) is silent or permits multiple standard interpretations, the system implements **configurable policy masters** with conservative industry-standard defaults.

The policies below are isolated per company (`Vishal Enterprise` vs `Vrishal Eng. Pvt. Ltd.`) to allow independent operational rules.

---

## 2. Policy Clarification Matrix

| Policy ID | Policy Area | BRD Reference | Configurable Setting | Recommended Standard Default | Management Decision Needed |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **POL-PAY-01** | **Attendance Calculation Basis** | P. 43 | `attendance_calculation_basis` | **Actual Calendar Days** (e.g. 30 in April, 31 in May, 28/29 in Feb). | Confirm whether monthly per-day rate uses actual calendar days or a fixed 26-day / 30-day industrial divisor. |
| **POL-PAY-02** | **Payroll Cutoff Day** | P. 43 | `payroll_cutoff_day` | **25th of the Month** (Punches from 26th prev month to 25th current month, or 1st to end-of-month). | Confirm company cutoff calendar (1st–31st vs 26th–25th). |
| **POL-PAY-03** | **Unpaid Absence / LOP Divisor** | P. 43, 44 | `lop_deduction_basis` | **Gross / Total Calendar Days** per LOP day. | Confirm if LOP deductions deduct only Basic + DA or proportional Gross Salary. |
| **POL-PAY-04** | **Overtime Rate Multiplier** | P. 43 | `ot_calculation_rate` | **Double Hourly Rate (2.0x)** for Factory Workers under Factories Act; **1.0x** for General Staff. | Confirm specific OT hourly calculation formula: `((Basic + DA) / 26 / 8) * 2.0` vs `(Gross / 30 / 8) * 1.5`. |
| **POL-PAY-05** | **Target OT vs Actual OT Priority** | P. 43 | `target_ot_mode` | **Structure Flag**: If employee's salary structure has `is_target_ot = true`, credit fixed amount; otherwise compute biometrically. | Confirm whether Target OT employees are strictly barred from additional biometric OT pay. |
| **POL-PAY-06** | **Provident Fund (PF) Wage Ceiling** | P. 44 | `pf_wage_ceiling` | **₹15,000 / month**. If Basic > ₹15,000, cap PF deduction at ₹1,800 unless employee has opted for higher PF on actual Basic. | Confirm whether higher voluntary PF contribution on full Basic is enabled by default. |
| **POL-PAY-07** | **ESI Wage Ceiling & Inclusion** | P. 44 | `esi_wage_ceiling` | **₹21,000 / month** gross wage limit. Employee contribution 0.75%, Employer 3.25%. | Confirm whether overtime earnings count towards the ₹21,000 threshold for ESI qualification in a given month. |
| **POL-PAY-08** | **Professional Tax (PT) Slabs** | P. 44 | `professional_tax_state` | **Maharashtra Slabs**: Nil (< ₹7,500 for men, < ₹25,000 for women), ₹175 (₹7,501–₹10,000), ₹200 (> ₹10,000; ₹300 in February). | Confirm operating states for all locations (Maharashtra vs Gujarat vs others) to ensure localized tax slab binding. |
| **POL-PAY-09** | **Labour Welfare Fund (LWF)** | P. 44 | `lwf_deduction_frequency` | **Bi-Annual (June & December)**: Employee ₹12, Employer ₹36 (Maharashtra slab). | Confirm whether LWF is deducted monthly or bi-annually. |
| **POL-PAY-10** | **Income Tax (TDS) Default Regime** | P. 44 | `default_tax_regime` | **New Tax Regime (Section 115BAC)** as default under Indian Finance Act, unless employee submits explicit Old Regime declaration. | Confirm approval of New Tax Regime as default fallback. |
| **POL-PAY-11** | **Payroll Net Pay Rounding** | P. 43 | `net_pay_rounding_rule` | **Round to Nearest Rupee (`Round Half Up`)**. | Confirm whether net payable is rounded to ₹1 or ₹10. |
| **POL-PAY-12** | **Loan Maximum Tenure & Cap** | P. 49 | `max_loan_tenure_months` | **24 Months** maximum; Maximum loan cap = 3x Monthly Gross Salary. | Confirm maximum loan eligibility formula and interest policy (interest-free advance vs company interest rate). |
| **POL-PAY-13** | **F&F Leave Encashment Basis** | P. 50 | `encashment_component_basis` | **Basic Salary Only** / 30 * Unused PL Balance (subject to maximum carry-forward cap). | Confirm whether leave encashment includes Dearness Allowance (DA). |
| **POL-PAY-14** | **Notice Period Shortfall Recovery** | P. 50 | `notice_shortfall_calculation` | **Gross Salary / 30 * Shortfall Days**. | Confirm whether notice shortfall is recovered at Gross or Basic rate. |
| **POL-PAY-15** | **Gratuity Calculation Formula** | P. 50 | `gratuity_calculation_formula` | **(15 * Last Drawn Basic * Completed Years) / 26** for service $\ge$ 5 years (Payment of Gratuity Act, 1972). | Confirm whether gratuity payout requires strict 5-year completion or allows pro-rata for contractual terms. |

---

## 3. Recommended Architectural Defaults in Codebase

Until formal sign-off from corporate finance:
1. All parameters above are persisted in `payroll_configurations` and `payroll_statutory_configurations` tables with effective dates.
2. The system UI exposes a dedicated **Payroll Configuration & Statutory Settings** screen accessible by `ho_admin` to modify these parameters at runtime without code deployment.
3. Every executed payroll run snapshots the exact configuration applied in `payroll_runs` and `payroll_run_employees` to maintain audit reproducibility.

