# KANVTECH HRMS — ONBOARDING DROPDOWN MASTER AUDIT REPORT

**Module:** Employee Master Onboarding (16-Step Wizard)  
**URL:** `/employees/create`  
**Audit Date:** November 5, 2026  
**Auditor:** Antigravity Engineering Agent  
**Environment:** Enterprise SaaS (Laravel 13, PHP 8.3, MariaDB/MySQL)  

---

## 1. Executive Summary

This audit catalogs every dropdown, select box, option group, and master-dependent control across all 16 sections of the Kanvtech Enterprise Workforce Onboarding wizard (`resources/views/employees/create.blade.php`).

Prior to this implementation:
- Core structural entities (Branch Location, Department, Designation, Cadre Category, Contractor, Reporting Manager) were powered by dedicated database tables (`locations`, `departments`, `designations`, `categories`, `contractors`, `employees`).
- Non-structural dropdowns (Title, Gender, Blood Group, Employee Grade, Employment Type, Initial Employment Status, Workforce Channel, Worker Skill Classification, Payment Mode, Marital Status, Emergency Contact Relation, Religion, Bank Account Type, Qualification Level, Competency Category, Skill Proficiency Tier, Document Types, Uniform Size, Parking Requirement) were hardcoded directly in Blade view templates.
- Administrators had no centralized interface to add, edit, or deactivate dropdown values without editing source code.

---

## 2. Complete Section-by-Section Dropdown Audit

| Section # | Section Name | Field Name | Control Type | Current Source | Target Master Category | Reusable Existing Model? |
|---|---|---|---|---|---|---|
| **1** | **Basic Information** | `title` | Select | Hardcoded (`Mr.`, `Ms.`, `Mrs.`, `Dr.`) | `title` | New Dynamic Master |
| **1** | **Basic Information** | `gender` | Select (Required) | Hardcoded (`Male`, `Female`, `Other`) | `gender` | New Dynamic Master |
| **1** | **Basic Information** | `blood_group` | Select | Hardcoded (`A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`) | `blood_group` | New Dynamic Master |
| **1** | **Basic Information** | `nationality` | Input (Default: Indian) | Hardcoded default text | `nationality` | New Dynamic Master |
| **1** | **Basic Information** | `specially_abled` | Toggle Switch | Boolean (`0`/`1`) | N/A (Boolean flag) | N/A |
| **1** | **Basic Information** | `disability_details` | Conditional Input | Free text | `disability_category` | New Dynamic Master |
| **2** | **Employment Information** | `grade` | Select (Required) | Hardcoded (`E1`, `E2`, `M1`, `M2`, `W1`, `W2`) | `employee_grade` | New Dynamic Master |
| **2** | **Employment Information** | `location_id` | Select (Required) | Database (`Location::all()`) | `locations` | **REUSE: `Location`** |
| **2** | **Employment Information** | `department_id` | Select (Required) | Database (`Department::all()`) | `departments` | **REUSE: `Department`** |
| **2** | **Employment Information** | `designation_id` | Select (Required) | Database (`Designation::all()`) | `designations` | **REUSE: `Designation`** |
| **2** | **Employment Information** | `employee_category_id` | Select (Required) | Database (`Category::all()`) | `categories` | **REUSE: `Category`** |
| **2** | **Employment Information** | `division` | Text | Free text (`Manufacturing Ops`) | `division` | New Dynamic Master |
| **2** | **Employment Information** | `unit` | Text | Free text (`Unit-1`) | `plant_unit` | New Dynamic Master |
| **2** | **Employment Information** | `project` | Text | Free text (`General Operations`) | `project` | New Dynamic Master |
| **2** | **Employment Information** | `onboarding_channel` | Select (Required) | Hardcoded (`Direct`, `Contractor`, `Referral`, `Agency`) | `onboarding_channel` | New Dynamic Master |
| **2** | **Employment Information** | `contractor_id` | Select (Conditional) | Database (`Contractor::all()`) | `contractors` | **REUSE: `Contractor`** |
| **2** | **Employment Information** | `skill_level` | Select (Required) | Hardcoded (`Skilled`, `Semi-Skilled`, `Unskilled`) | `skill_classification` | New Dynamic Master |
| **2** | **Employment Information** | `employment_type` | Select (Required) | Hardcoded (`Permanent`, `Intern`, `Contract`, `Probationary`, `Trainee`) | `employment_type` | New Dynamic Master |
| **2** | **Employment Information** | `initial_employment_status` | Select (Required) | Hardcoded (`Active`, `Probation`, `Intern`) | `employment_status` | New Dynamic Master |
| **2** | **Employment Information** | `reporting_manager_id` | Select | Database (`Employee::all()`) | `employees` | **REUSE: `Employee`** |
| **2** | **Employment Information** | `payment_mode` | Select | Hardcoded (`Bank Transfer`, `Cheque`, `Cash`) | `payment_mode` | New Dynamic Master |
| **3** | **Contact Information** | N/A | Text / Tel / Email | Form inputs | N/A | N/A |
| **4** | **Address Information** | `state`, `country` | Text / Autocomplete | Free text | `country`, `state` | New Dynamic Master |
| **5** | **Family Details** | `marital_status` | Select | Hardcoded (`Single`, `Married`, `Divorced`, `Widowed`) | `marital_status` | New Dynamic Master |
| **5** | **Family Details** | `emergency_contact_relation` | Select | Hardcoded (`Spouse`, `Father`, `Mother`, `Brother`, etc.) | `relationship_type` | New Dynamic Master |
| **6** | **Personal Details** | `religion` | Input | Free text | `religion` | New Dynamic Master |
| **6** | **Personal Details** | `caste` | Input | Free text (`General`, `OBC`, `SC`, `ST`) | `caste_category` | New Dynamic Master |
| **7** | **Statutory & Compliance** | `tax_regime` | Select / Radios | Hardcoded | `tax_regime` | New Dynamic Master |
| **8** | **Banking & Identity** | `bank_name` | Input / Datalist | Free text | `bank_name` | New Dynamic Master |
| **8** | **Banking & Identity** | `bank_account_type` | Select | Hardcoded (`Salary`, `Savings`, `Current`) | `bank_account_type` | New Dynamic Master |
| **9** | **Education & Qualifications** | `statutory_education_qualification` | Select | Hardcoded (`Bachelor's`, `Master's`, `Diploma`, `ITI`, `12th`, `10th`, `Doctorate`) | `qualification_level` | New Dynamic Master |
| **9** | **Education & Qualifications** | Degree / Qualification Name | Input | Free text | `degree_name` | New Dynamic Master |
| **9** | **Education & Qualifications** | Board / University | Input | Free text | `board_university` | New Dynamic Master |
| **10** | **Skills & Skill Matrix** | `competency_category` | Select | Hardcoded (`Technical`, `Operational`, `Safety`, `Quality Control`, `Leadership`) | `skill_category` | New Dynamic Master |
| **10** | **Skills & Skill Matrix** | `proficiency_tier` | Select | Hardcoded (`Beginner`, `Intermediate`, `Advanced`, `Expert`) | `skill_proficiency` | New Dynamic Master |
| **10** | **Skills & Skill Matrix** | Primary Technical Skill | Input | Free text | `skill_name` | New Dynamic Master |
| **11** | **Visa & Immigration** | `visa_type` | Input | Free text | `visa_type` | New Dynamic Master |
| **11** | **Visa & Immigration** | `country_of_employment` | Input | Free text (`India`) | `country` | New Dynamic Master |
| **12** | **Documents & Certificates** | `docStagingType` | Select (with optgroups) | Hardcoded optgroups (Identity, Educational, Employment, Banking, Health, Other) | `document_type` | New Dynamic Master |
| **13** | **Assets & IT Information** | Asset Allocation | Informational / Deferred | Handled post-onboarding via Assets module | `assets` | **REUSE: `Asset`** |
| **14** | **Login & Account Access** | `provision_account` | Toggle Switch | Boolean | N/A | N/A |
| **15** | **User Defined Fields** | `uniform_size` | Select | Hardcoded (`S`, `M`, `L`, `XL`, `XXL`, `3XL`) | `uniform_size` | New Dynamic Master |
| **15** | **User Defined Fields** | `parking_required` | Select | Hardcoded (`No`, `Two Wheeler`, `Four Wheeler`) | `parking_type` | New Dynamic Master |
| **16** | **Review & Submission** | Summary Manifest | Summary View | Form Aggregator | N/A | N/A |

---

## 3. Architecture Strategy: Centralized Dropdown Master System

### Existing Masters Reused Without Duplication
1. **Companies**: `App\Models\Company` (`companies` table)
2. **Branches / Locations**: `App\Models\Location` (`locations` table)
3. **Departments**: `App\Models\Department` (`departments` table)
4. **Designations**: `App\Models\Designation` (`designations` table)
5. **Cadre Categories**: `App\Models\Category` (`categories` table)
6. **Contractor Vendors**: `App\Models\Contractor` (`contractors` table)
7. **Employees / Reporting Managers**: `App\Models\Employee` (`employees` table)

### New Centralized Table: `onboarding_dropdown_masters`
To support all configurable dropdown options cleanly without creating dozens of tiny, single-purpose tables, we implement an enterprise lookup table:

```sql
CREATE TABLE `onboarding_dropdown_masters` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NULL,
    `company_id` BIGINT UNSIGNED NULL,
    `category` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `parent_id` BIGINT UNSIGNED NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_cat_active_order` (`category`, `is_active`, `display_order`),
    INDEX `idx_tenant_company` (`tenant_id`, `company_id`),
    CONSTRAINT `fk_dropdown_master_parent` FOREIGN KEY (`parent_id`) REFERENCES `onboarding_dropdown_masters`(`id`) ON DELETE SET NULL
);
```

### Master Categories Supported
- `title` (Personal Title)
- `gender` (Gender Identity)
- `blood_group` (Blood Group)
- `nationality` (Nationality)
- `marital_status` (Marital Status)
- `relationship_type` (Family & Emergency Relationship)
- `religion` (Religion)
- `caste_category` (Caste Classification)
- `disability_category` (Divyaang Impairment Category)
- `employee_grade` (Cadre Grade & Band)
- `employment_type` (Contractual Form)
- `employment_status` (Lifecycle State)
- `onboarding_channel` (Workforce Acquisition Channel)
- `skill_classification` (Worker Skill Level)
- `payment_mode` (Disbursement Mechanism)
- `bank_name` (Scheduled Commercial Bank)
- `bank_account_type` (Deposit Account Type)
- `qualification_level` (Academic Tier)
- `degree_name` (Degree / Diploma)
- `board_university` (Accredited Institution)
- `skill_category` (Competency Domain)
- `skill_name` (Operational Skill)
- `skill_proficiency` (Skill Tier)
- `visa_type` (Visa / Permit Class)
- `country` (Country of Origin / Issuance)
- `document_type` (Vault Document Specification)
- `uniform_size` (Apparel Size)
- `parking_type` (Vehicular Parking Allocation)

