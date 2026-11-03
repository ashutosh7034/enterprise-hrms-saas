# Enterprise HRMS — Phase 5 Statutory Compliance Assumptions
## Statutory Formulations, Slabs, Ceilings & Legal Parameters

**Document Version**: 1.0  
**Date**: November 2, 2026  
**Status**: Configurable System Rules (Pending Final Finance/Legal Sign-off)  
**Applicable Legal Entities**: Vishal Enterprise (`VE`), Vrishal Eng. Pvt. Ltd. (`VRE`)

---

## 1. Compliance Disclaimer

In accordance with statutory software guidelines, the HRMS implementation does **not** hardcode statutory rates or treat defaults as legally binding without corporate validation. All parameters below are modeled as **effective-dated database configurations**. When tax laws or statutory ceilings change, new effective-dated rows can be created without invalidating or altering past payroll runs.

---

## 2. Statutory Modules Detail

### 2.1 Employees' Provident Fund (EPF & MP Act, 1952)
- **Employee Contribution**: 12.00% of EPF Wages (Basic + DA).
- **Employer Contribution**: 12.00% total:
  - 8.33% to Employees' Pension Scheme (EPS) (capped at ₹1,250/mo on wage ceiling).
  - 3.67% to EPF.
  - Plus EPF Admin Charges (0.50%) and EDLI (0.50%).
- **Statutory Wage Ceiling**: ₹15,000 per month.
- **System Config Option**: `pf_restrict_to_ceiling` (Boolean).
  - When `true`: If EPF Wages exceed ₹15,000, employee PF is ₹1,800 (12% of ₹15,000).
  - When `false`: PF is calculated on actual EPF Wages without ceiling.
- **Effective Dating**: Supported in `payroll_statutory_configurations`.

### 2.2 Employees' State Insurance (ESI Act, 1948)
- **Employee Contribution**: 0.75% of Gross Wages.
- **Employer Contribution**: 3.25% of Gross Wages.
- **Statutory Wage Ceiling**: ₹21,000 per month Gross Wages (₹25,000 for employees with disability).
- **Qualification Rule**: If monthly gross wages exceed the ceiling, ESI deduction is `0.00` for that employee.
- **Exclusion**: Overtime earnings are evaluated per company policy rule `esi_include_overtime_in_ceiling`. Default is excluded from qualifying ceiling test.

### 2.3 Professional Tax (State Tax on Professions, Trades, Callings)
- **Jurisdiction**: State-based. The system links the employee's assigned work location (`locations.state`) to the relevant state slab.
- **Maharashtra Standard Slabs (Default)**:
  - Monthly Gross $\le$ ₹7,500: Nil.
  - Monthly Gross ₹7,501 to ₹10,000: ₹175 / month.
  - Monthly Gross > ₹10,000: ₹200 / month (except in February, where it is ₹300 / month to complete the ₹2,500 annual limit).
  - Female employees earning up to ₹25,000/month are exempt from PT in Maharashtra (Maharashtra State Tax Amendment).
- **Gujarat Standard Slabs (Default)**:
  - Monthly Gross $\le$ ₹12,000: Nil.
  - Monthly Gross > ₹12,000: ₹200 / month.
- **Configurability**: Persisted in `payroll_pt_slabs` table.

### 2.4 Labour Welfare Fund (LWF)
- **Jurisdiction**: State Welfare Board (e.g. Maharashtra Labour Welfare Fund).
- **Standard Maharashtra Rates**:
  - Deducted bi-annually in June and December.
  - Employee Contribution: ₹12 (or ₹25 for higher wage bands).
  - Employer Contribution: ₹36 (or ₹75 for higher wage bands).
- **Configurability**: Toggleable deduction frequency (`Monthly` vs `Bi-Annual June/Dec`) in `payroll_statutory_configurations`.

### 2.5 Income Tax / Tax Deducted at Source (TDS under Section 192)
- **Default Tax Regime**: New Tax Regime under Section 115BAC of the Income Tax Act, 1961 (FY 2024-25 / 2025-26):
  - Up to ₹3,00,000: Nil.
  - ₹3,00,001 to ₹7,00,000: 5%.
  - ₹7,00,001 to ₹10,00,000: 10%.
  - ₹10,00,001 to ₹12,00,000: 15%.
  - ₹12,00,001 to ₹15,00,000: 20%.
  - Above ₹15,00,000: 30%.
  - Standard Deduction: ₹75,000 (New Regime).
  - Rebate under Section 87A: Full tax rebate if total taxable income $\le$ ₹7,00,000.
  - Health & Education Cess: 4% on calculated tax.
- **Old Tax Regime (Optional via Employee Declaration)**:
  - Slabs: 0-2.5L (Nil), 2.5L-5L (5%), 5L-10L (20%), >10L (30%).
  - Standard Deduction: ₹50,000.
  - Chapter VI-A deductions supported: 80C (up to 1.5L), 80D (Health Insurance up to ₹25,000/₹50,000), 24(b) (Home Loan Interest up to ₹2,00,000).
- **Monthly TDS Formula**:
  $$\text{Monthly TDS} = \frac{\text{Projected Annual Tax} - \text{Cumulative TDS Deducted in YTD}}{\text{Remaining Months in Financial Year}}$$

---

## 3. Audit & Historical Integrity Rules

1. Every payroll run stores an immutable snapshot of all statutory rates and calculations in `payroll_run_employees` and `payroll_component_transactions`.
2. Any subsequent change to a statutory slab or rate is effective only from the specified `effective_from` date and does not alter closed or historical payroll periods.
3. Every change to a statutory configuration produces a change log in `audit_logs`.

