# PHASE 7 DOCUMENT TEMPLATE CATALOG: 20 HR LETTERS & 20 HR FORMS

**Enterprise Human Resource Management System (HRMS)**  
**Entities Covered:** Vishal Enterprise (`VE`) & Vrishal Eng. Pvt. Ltd. (`VRE`)  
**Document Reference:** `DOCS-PH7-CAT-001`  
**Date:** November 2026  

---

## 1. Catalog Overview

The Phase 7 Letter & Form Print Engine provides 40 baseline enterprise templates partitioned into 20 formal HR Letters (Stage 7C) and 20 operational HR Forms (Stage 7D). Each template supports dynamic token substitution, immutable snapshotting, approval gates, and tamper-evident verification codes.

---

## 2. Standard 20 HR Letters Catalog (Stage 7C)

| # | Code | Document Title | Category | Requires Approval | Sensitive Salary | Core Placeholders |
|---|---|---|---|:---:|:---:|---|
| 1 | `LETTER_OFFER` | Offer of Employment Letter | Employee Lifecycle | Yes | Yes | `{employee_name}`, `{designation}`, `{department}`, `{gross_salary}`, `{annual_ctc}`, `{date_of_joining}` |
| 2 | `LETTER_APPOINTMENT` | Formal Appointment Letter | Employee Lifecycle | Yes | Yes | `{employee_name}`, `{employee_code}`, `{designation}`, `{probation_end_date}`, `{gross_salary}`, `{annual_ctc}` |
| 3 | `LETTER_CONFIRMATION` | Service Confirmation Letter | Employee Lifecycle | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{department}`, `{confirmation_date}` |
| 4 | `LETTER_PROBATION_EXT` | Probation Extension Notice | Employee Lifecycle | Yes | No | `{employee_name}`, `{employee_code}`, `{probation_end_date}`, `{effective_date}`, `{reporting_manager_name}` |
| 5 | `LETTER_PROMOTION` | Promotion and Grade Enhancement | Employee Lifecycle | Yes | Yes | `{employee_name}`, `{designation}`, `{department}`, `{gross_salary}`, `{annual_ctc}`, `{effective_date}` |
| 6 | `LETTER_TRANSFER` | Internal Transfer & Relocation Order | Employee Lifecycle | Yes | No | `{employee_name}`, `{designation}`, `{location}`, `{effective_date}`, `{reporting_manager_name}` |
| 7 | `LETTER_DESIGNATION_CHG`| Designation Change Letter | Employee Lifecycle | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{department}`, `{effective_date}` |
| 8 | `LETTER_SALARY_REV` | Compensation Revision Notice | Employee Lifecycle | Yes | Yes | `{employee_name}`, `{basic_salary}`, `{gross_salary}`, `{annual_ctc}`, `{effective_date}` |
| 9 | `LETTER_INCREMENT` | Annual Performance Increment Letter | Employee Lifecycle | Yes | Yes | `{employee_name}`, `{gross_salary}`, `{annual_ctc}`, `{effective_date}` |
| 10 | `LETTER_RELIEVING` | Official Relieving Letter | Employee Lifecycle | Yes | No | `{employee_name}`, `{employee_code}`, `{date_of_joining}`, `{effective_date}`, `{department}` |
| 11 | `LETTER_EXPERIENCE` | Experience & Service Certificate | Employee Lifecycle | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{date_of_joining}`, `{effective_date}` |
| 12 | `LETTER_EMPLOYMENT_VERIF`| Employment Verification Letter | Employee Request | No | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{department}`, `{location}`, `{date_of_joining}` |
| 13 | `LETTER_SALARY_CERT` | Official Salary Certificate | Employee Request | Yes | Yes | `{employee_name}`, `{basic_salary}`, `{gross_salary}`, `{net_salary}`, `{annual_ctc}` |
| 14 | `LETTER_BANK_OPENING` | Bank Account Opening Letter | Employee Request | No | No | `{employee_name}`, `{employee_code}`, `{address}`, `{date_of_joining}`, `{designation}` |
| 15 | `LETTER_ADDRESS_VERIF` | Address Verification Letter | Employee Request | No | No | `{employee_name}`, `{employee_code}`, `{address}`, `{designation}` |
| 16 | `LETTER_NOC` | No Objection Certificate (NOC) | Employee Request | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{company_name}` |
| 17 | `LETTER_LOAN_ELIGIBILITY`| Loan Eligibility & Repayment Cert | Employee Request | Yes | Yes | `{employee_name}`, `{gross_salary}`, `{annual_ctc}`, `{date_of_joining}` |
| 18 | `LETTER_WARNING` | Formal HR Warning Letter | HR Compliance | Yes | No | `{employee_name}`, `{employee_code}`, `{reporting_manager_name}`, `{department}` |
| 19 | `LETTER_SHOW_CAUSE` | Show Cause Notice | HR Compliance | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}`, `{company_name}` |
| 20 | `LETTER_DISCIPLINARY` | Disciplinary Action Letter | HR Compliance | Yes | No | `{employee_name}`, `{employee_code}`, `{effective_date}`, `{company_name}` |

---

## 3. Standard 20 HR Forms Catalog (Stage 7D)

| # | Code | Form Title | Category | Requires Approval | Sensitive Salary | Core Placeholders |
|---|---|---|---|:---:|:---:|---|
| 1 | `FORM_EMP_INFO` | Employee Information Sheet | Employee Forms | No | No | `{employee_name}`, `{employee_code}`, `{pan_masked}`, `{aadhaar_masked}`, `{address}`, `{phone}` |
| 2 | `FORM_JOINING` | Workforce Joining Report | Employee Forms | Yes | No | `{employee_name}`, `{employee_code}`, `{gate_pass_number}`, `{date_of_joining}`, `{reporting_manager_name}` |
| 3 | `FORM_DECLARATION` | Non-Disclosure & Integrity Declaration | Employee Forms | No | No | `{employee_name}`, `{employee_code}`, `{company_name}`, `{current_date}` |
| 4 | `FORM_EMERGENCY_CONTACT`| Emergency Contact & Medical Info | Employee Forms | No | No | `{employee_name}`, `{employee_code}`, `{emergency_contact_name}`, `{emergency_contact_phone}` |
| 5 | `FORM_NOMINEE_DECLARATION`| Nominee Declaration Form (PF/Gratuity)| Employee Forms | Yes | No | `{employee_name}`, `{employee_code}`, `{emergency_contact_name}`, `{current_date}` |
| 6 | `FORM_ASSET_HANDOVER` | Asset Handover & Undertaking Form | Employee Forms | Yes | No | `{employee_name}`, `{employee_code}`, `{gate_pass_number}`, `{current_date}` |
| 7 | `FORM_EXIT_CLEARANCE` | Exit Clearance (No-Dues) Form | Employee Forms | Yes | No | `{employee_name}`, `{employee_code}`, `{department}`, `{company_name}` |
| 8 | `FORM_LEAVE_APPLICATION` | Offline Leave Application Form | Leave & Attendance | Yes | No | `{employee_name}`, `{employee_code}`, `{department}`, `{location}` |
| 9 | `FORM_ATTENDANCE_REG` | Attendance Regularization Form | Leave & Attendance | Yes | No | `{employee_name}`, `{employee_code}`, `{company_name}` |
| 10 | `FORM_OT_APPROVAL` | Overtime Authorization Form | Leave & Attendance | Yes | No | `{employee_name}`, `{employee_code}`, `{location}`, `{company_name}` |
| 11 | `FORM_COMP_OFF` | Compensatory Off (Comp-Off) Grant | Leave & Attendance | Yes | No | `{employee_name}`, `{employee_code}`, `{company_name}` |
| 12 | `FORM_SALARY_REV_REQ` | Salary Revision Recommendation Form | Payroll & Finance | Yes | Yes | `{employee_name}`, `{employee_code}`, `{gross_salary}` |
| 13 | `FORM_LOAN_APP` | Company Loan Application Form | Payroll & Finance | Yes | Yes | `{employee_name}`, `{employee_code}`, `{gross_salary}` |
| 14 | `FORM_SALARY_ADVANCE` | Salary Advance Request Form | Payroll & Finance | Yes | Yes | `{employee_name}`, `{employee_code}` |
| 15 | `FORM_IT_DECLARATION` | Income Tax Investment Declaration | Payroll & Finance | No | Yes | `{employee_name}`, `{pan_masked}`, `{company_name}` |
| 16 | `FORM_FNF_SETTLEMENT` | F&F Settlement Computation Sheet | Payroll & Finance | Yes | Yes | `{employee_name}`, `{employee_code}`, `{basic_salary}`, `{gross_salary}`, `{effective_date}` |
| 17 | `FORM_TRANSFER_REQ` | Internal Transfer Request Form | Administrative | Yes | No | `{employee_name}`, `{employee_code}`, `{location}` |
| 18 | `FORM_DEPT_CHANGE` | Department Reassignment Form | Administrative | Yes | No | `{employee_name}`, `{employee_code}`, `{department}` |
| 19 | `FORM_DESIG_CHANGE` | Designation Reclassification Form | Administrative | Yes | No | `{employee_name}`, `{employee_code}`, `{designation}` |
| 20 | `FORM_RESIGNATION` | Formal Resignation Submission Form | Administrative | Yes | No | `{employee_name}`, `{employee_code}`, `{department}`, `{designation}`, `{current_date}` |

