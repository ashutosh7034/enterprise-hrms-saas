<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Database\Seeder;

class Phase7DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        // Standard 20 HR Letters (Stage 7C)
        $letters = [
            [
                'code' => 'LETTER_OFFER',
                'name' => 'Offer of Employment Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Offer Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Formal offer of employment letter with compensation breakdown.',
                'content_html' => $this->getOfferLetterTemplate(),
            ],
            [
                'code' => 'LETTER_APPOINTMENT',
                'name' => 'Formal Appointment Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Appointment Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Comprehensive employment terms, probation rules, and compensation.',
                'content_html' => $this->getAppointmentLetterTemplate(),
            ],
            [
                'code' => 'LETTER_CONFIRMATION',
                'name' => 'Service Confirmation Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Confirmation Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Confirmation of regular permanent service following probation evaluation.',
                'content_html' => $this->getConfirmationLetterTemplate(),
            ],
            [
                'code' => 'LETTER_PROBATION_EXT',
                'name' => 'Probation Extension Notice',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Probation Extension Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Notice indicating extension of probation period with review objectives.',
                'content_html' => $this->getProbationExtensionTemplate(),
            ],
            [
                'code' => 'LETTER_PROMOTION',
                'name' => 'Promotion and Grade Enhancement Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Promotion Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Promotion notice detailing elevated role, responsibilities, and revised compensation.',
                'content_html' => $this->getPromotionLetterTemplate(),
            ],
            [
                'code' => 'LETTER_TRANSFER',
                'name' => 'Internal Transfer & Relocation Order',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Transfer Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Internal transfer letter between departments, plants, or branch locations.',
                'content_html' => $this->getTransferLetterTemplate(),
            ],
            [
                'code' => 'LETTER_DESIGNATION_CHG',
                'name' => 'Designation Change Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Designation Change Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Formal confirmation of corporate title or designation realignment.',
                'content_html' => $this->getDesignationChangeTemplate(),
            ],
            [
                'code' => 'LETTER_SALARY_REV',
                'name' => 'Compensation Revision Notice',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Salary Revision Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Notice outlining compensation restructuring and revised monthly package.',
                'content_html' => $this->getSalaryRevisionTemplate(),
            ],
            [
                'code' => 'LETTER_INCREMENT',
                'name' => 'Annual Performance Increment Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Increment Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Annual appraisal outcome and percentage increment notification.',
                'content_html' => $this->getIncrementLetterTemplate(),
            ],
            [
                'code' => 'LETTER_RELIEVING',
                'name' => 'Official Relieving Letter',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Relieving Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Formal release of employment post notice period completion and clearances.',
                'content_html' => $this->getRelievingLetterTemplate(),
            ],
            [
                'code' => 'LETTER_EXPERIENCE',
                'name' => 'Experience & Service Certificate',
                'category' => 'Employee Lifecycle',
                'document_type' => 'Experience Certificate',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Certificate certifying tenure, designations held, and conduct during tenure.',
                'content_html' => $this->getExperienceCertificateTemplate(),
            ],
            [
                'code' => 'LETTER_EMPLOYMENT_VERIF',
                'name' => 'Employment Verification Letter',
                'category' => 'Employee Request',
                'document_type' => 'Employment Verification',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'Verification letter for third-party background verification, loans, or authorities.',
                'content_html' => $this->getEmploymentVerificationTemplate(),
            ],
            [
                'code' => 'LETTER_SALARY_CERT',
                'name' => 'Official Salary Certificate',
                'category' => 'Employee Request',
                'document_type' => 'Salary Certificate',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Certified earnings statement for banking, mortgage, or official visa processing.',
                'content_html' => $this->getSalaryCertificateTemplate(),
            ],
            [
                'code' => 'LETTER_BANK_OPENING',
                'name' => 'Bank Account Confirmation Letter',
                'category' => 'Employee Request',
                'document_type' => 'Bank Account Confirmation',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'Introduction letter addressed to bank branch for corporate salary account opening.',
                'content_html' => $this->getBankAccountLetterTemplate(),
            ],
            [
                'code' => 'LETTER_ADDRESS_VERIF',
                'name' => 'Residential Address Verification Letter',
                'category' => 'Employee Request',
                'document_type' => 'Address Verification',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'HR certification verifying official residence address recorded in personnel file.',
                'content_html' => $this->getAddressVerificationTemplate(),
            ],
            [
                'code' => 'LETTER_NOC',
                'name' => 'No Objection Certificate (NOC)',
                'category' => 'Employee Request',
                'document_type' => 'No Objection Certificate',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'NOC for passport, international visa processing, higher education, or exams.',
                'content_html' => $this->getNocLetterTemplate(),
            ],
            [
                'code' => 'LETTER_LOAN_ELIGIBILITY',
                'name' => 'Loan Eligibility & Repayment Certification',
                'category' => 'Employee Request',
                'document_type' => 'Loan Eligibility Letter',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Certificate detailing employment stability and permissible salary deductions.',
                'content_html' => $this->getLoanEligibilityTemplate(),
            ],
            [
                'code' => 'LETTER_WARNING',
                'name' => 'Formal HR Warning Letter',
                'category' => 'HR Compliance',
                'document_type' => 'Warning Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Formal written warning addressing policy violations, absenteeism, or misconduct.',
                'content_html' => $this->getWarningLetterTemplate(),
            ],
            [
                'code' => 'LETTER_SHOW_CAUSE',
                'name' => 'Show Cause Notice',
                'category' => 'HR Compliance',
                'document_type' => 'Show Cause Notice',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Notice requiring employee to show cause in writing regarding specified infraction.',
                'content_html' => $this->getShowCauseNoticeTemplate(),
            ],
            [
                'code' => 'LETTER_DISCIPLINARY',
                'name' => 'Disciplinary Inquiry & Action Letter',
                'category' => 'HR Compliance',
                'document_type' => 'Disciplinary Action Letter',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Formal findings and sanctions resulting from domestic disciplinary inquiry.',
                'content_html' => $this->getDisciplinaryActionTemplate(),
            ],
        ];

        // Standard 20 HR Forms (Stage 7D)
        $forms = [
            [
                'code' => 'FORM_EMP_INFO',
                'name' => 'Employee Information Form',
                'category' => 'Employee Forms',
                'document_type' => 'Employee Info Form',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'Comprehensive personal, demographic, and background information form.',
                'content_html' => $this->getEmployeeInfoFormTemplate(),
            ],
            [
                'code' => 'FORM_JOINING',
                'name' => 'Workforce Joining Report',
                'category' => 'Employee Forms',
                'document_type' => 'Joining Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'First day joining confirmation and physical verification sign-off.',
                'content_html' => $this->getJoiningFormTemplate(),
            ],
            [
                'code' => 'FORM_DECLARATION',
                'name' => 'Code of Conduct & Compliance Declaration',
                'category' => 'Employee Forms',
                'document_type' => 'Declaration Form',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'Confidentiality, non-disclosure, and anti-bribery policy compliance declaration.',
                'content_html' => $this->getDeclarationFormTemplate(),
            ],
            [
                'code' => 'FORM_EMERGENCY_CONTACT',
                'name' => 'Emergency Contact & Medical Information Form',
                'category' => 'Employee Forms',
                'document_type' => 'Emergency Contact Form',
                'is_sensitive_salary' => false,
                'requires_approval' => false,
                'description' => 'Medical disclosure and primary emergency contact authorization form.',
                'content_html' => $this->getEmergencyContactFormTemplate(),
            ],
            [
                'code' => 'FORM_NOMINEE_DECLARATION',
                'name' => 'Statutory Nominee Declaration (PF, Gratuity & Insurance)',
                'category' => 'Employee Forms',
                'document_type' => 'Nominee Declaration Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Nomination proportions for statutory benefits, PF, and Group Term Life.',
                'content_html' => $this->getNomineeDeclarationTemplate(),
            ],
            [
                'code' => 'FORM_ASSET_HANDOVER',
                'name' => 'Asset Allocation & Undertaking Form',
                'category' => 'Employee Forms',
                'document_type' => 'Asset Handover Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Acknowledgment of company assets, serial numbers, and care agreement.',
                'content_html' => $this->getAssetHandoverTemplate(),
            ],
            [
                'code' => 'FORM_EXIT_CLEARANCE',
                'name' => 'Departmental Exit Clearance (No-Dues) Form',
                'category' => 'Employee Forms',
                'document_type' => 'Exit Clearance Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Departmental sign-offs (IT, Finance, Admin, HR) for full & final clearance.',
                'content_html' => $this->getExitClearanceTemplate(),
            ],
            [
                'code' => 'FORM_LEAVE_APPLICATION',
                'name' => 'Leave Application Form',
                'category' => 'Leave & Attendance Forms',
                'document_type' => 'Leave Application Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Formal paper print form for offline or exceptional leave requests.',
                'content_html' => $this->getLeaveApplicationTemplate(),
            ],
            [
                'code' => 'FORM_ATTENDANCE_REG',
                'name' => 'Attendance Regularization Request Form',
                'category' => 'Leave & Attendance Forms',
                'document_type' => 'Attendance Regularization Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Biometric punch regularization form with supervisor sign-off.',
                'content_html' => $this->getAttendanceRegTemplate(),
            ],
            [
                'code' => 'FORM_OT_APPROVAL',
                'name' => 'Overtime Authorization Form',
                'category' => 'Leave & Attendance Forms',
                'document_type' => 'Overtime Approval Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Pre-approved extra work and plant overtime authorization form.',
                'content_html' => $this->getOtApprovalTemplate(),
            ],
            [
                'code' => 'FORM_COMP_OFF',
                'name' => 'Compensatory Off (Comp-Off) Grant Form',
                'category' => 'Leave & Attendance Forms',
                'document_type' => 'Compensatory Off Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Grant of compensatory rest for weekend or holiday duty performed.',
                'content_html' => $this->getCompOffTemplate(),
            ],
            [
                'code' => 'FORM_SALARY_REV_REQ',
                'name' => 'Compensation Adjustment Recommendation Form',
                'category' => 'Payroll & Finance Forms',
                'document_type' => 'Salary Revision Request Form',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Managerial recommendation form for mid-year compensation correction.',
                'content_html' => $this->getSalaryRevReqTemplate(),
            ],
            [
                'code' => 'FORM_LOAN_APP',
                'name' => 'Employee Company Loan Application Form',
                'category' => 'Payroll & Finance Forms',
                'document_type' => 'Loan Application Form',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Application for company emergency loan with installment deduction schedule.',
                'content_html' => $this->getLoanAppTemplate(),
            ],
            [
                'code' => 'FORM_SALARY_ADVANCE',
                'name' => 'Salary Advance Request Form',
                'category' => 'Payroll & Finance Forms',
                'document_type' => 'Salary Advance Form',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Short-term advance against salary with immediate payroll recovery terms.',
                'content_html' => $this->getSalaryAdvanceTemplate(),
            ],
            [
                'code' => 'FORM_IT_DECLARATION',
                'name' => 'Annual Income Tax Investment Declaration Form',
                'category' => 'Payroll & Finance Forms',
                'document_type' => 'Income Tax Declaration Form',
                'is_sensitive_salary' => true,
                'requires_approval' => false,
                'description' => 'Section 80C, 80D, HRA, and National Pension Scheme proof submission form.',
                'content_html' => $this->getItDeclarationTemplate(),
            ],
            [
                'code' => 'FORM_FNF_SETTLEMENT',
                'name' => 'Full and Final Settlement Computation Sheet',
                'category' => 'Payroll & Finance Forms',
                'document_type' => 'FnF Settlement Form',
                'is_sensitive_salary' => true,
                'requires_approval' => true,
                'description' => 'Statement of final dues, encashment, gratuity, and deductions on exit.',
                'content_html' => $this->getFnfSettlementTemplate(),
            ],
            [
                'code' => 'FORM_TRANSFER_REQ',
                'name' => 'Employee Internal Transfer Request Form',
                'category' => 'Administrative Forms',
                'document_type' => 'Transfer Request Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Employee initiated request for inter-branch or plant reassignment.',
                'content_html' => $this->getTransferReqTemplate(),
            ],
            [
                'code' => 'FORM_DEPT_CHANGE',
                'name' => 'Department Reassignment Request Form',
                'category' => 'Administrative Forms',
                'document_type' => 'Dept Change Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Internal mobility request for transition between functional departments.',
                'content_html' => $this->getDeptChangeTemplate(),
            ],
            [
                'code' => 'FORM_DESIG_CHANGE',
                'name' => 'Designation & Grade Reclassification Form',
                'category' => 'Administrative Forms',
                'document_type' => 'Designation Change Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Corporate reclassification request for role title alignment.',
                'content_html' => $this->getDesigChangeTemplate(),
            ],
            [
                'code' => 'FORM_RESIGNATION',
                'name' => 'Formal Resignation Submission Form',
                'category' => 'Administrative Forms',
                'document_type' => 'Resignation Form',
                'is_sensitive_salary' => false,
                'requires_approval' => true,
                'description' => 'Employee resignation submission and notice period commitment form.',
                'content_html' => $this->getResignationFormTemplate(),
            ],
        ];

        $allDefinitions = array_merge($letters, $forms);

        foreach ($companies as $company) {
            foreach ($allDefinitions as $def) {
                $template = DocumentTemplate::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $def['code'] . '_' . $company->code,
                    ],
                    [
                        'name' => $def['name'],
                        'category' => $def['category'],
                        'document_type' => $def['document_type'],
                        'description' => $def['description'],
                        'content_html' => $def['content_html'],
                        'version' => 1,
                        'status' => 'Active',
                        'requires_approval' => $def['requires_approval'],
                        'is_sensitive_salary' => $def['is_sensitive_salary'],
                        'header_footer_enabled' => true,
                        'effective_from' => '2026-01-01',
                    ]
                );

                // Create initial immutable version record
                DocumentTemplateVersion::firstOrCreate(
                    [
                        'document_template_id' => $template->id,
                        'version_number' => 1,
                    ],
                    [
                        'content_html' => $def['content_html'],
                        'change_summary' => 'Initial approved enterprise baseline template',
                    ]
                );
            }
        }
    }

    // Template HTML Generators
    protected function getOfferLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin} | Contact: {company_phone}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong><br/>
        Address: {address}<br/>
        Contact: {phone} | Email: {email}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Offer of Employment for the position of {designation}</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>With reference to your application and subsequent personal interview sessions with our recruitment panel, the management of <strong>{company_name}</strong> is pleased to offer you the position of <strong>{designation}</strong> in our <strong>{department}</strong> department, based at our <strong>{location}</strong> office.</p>
        <p>Your employment will be governed by the following core terms:</p>
        <table class="table table-bordered table-sm my-3">
            <tr><th width="35%">Designation</th><td>{designation}</td></tr>
            <tr><th>Department</th><td>{department}</td></tr>
            <tr><th>Work Location</th><td>{location}</td></tr>
            <tr><th>Gross Monthly Compensation</th><td><strong>{gross_salary}</strong></td></tr>
            <tr><th>Total Annual CTC</th><td><strong>{annual_ctc}</strong></td></tr>
            <tr><th>Expected Joining Date</th><td>{date_of_joining}</td></tr>
            <tr><th>Probation Period</th><td>Six (6) calendar months</td></tr>
        </table>
        <p>A comprehensive Appointment Letter detailing all benefits, allowances, and statutory entitlements will be issued to you on your formal joining date upon verification of your original credential certificates and KYC documentation.</p>
        <p>Please sign and return the duplicate copy of this offer letter within 5 working days as a token of your formal acceptance.</p>
        <p class="mt-4">Yours sincerely,<br/>
        For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>This document is digitally registered. Verification Code: <strong>{verification_code}</strong> | Verify authenticity at: {verification_url}</p>
    </div>
</div>';
    }

    protected function getAppointmentLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin} | Contact: {company_phone}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> (Employee Code: {employee_code})<br/>
        Address: {address}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Appointment Letter — {designation}</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>Following your joining on <strong>{date_of_joining}</strong>, the management of <strong>{company_name}</strong> is pleased to confirm your appointment as <strong>{designation}</strong> in <strong>{department}</strong> at <strong>{location}</strong>.</p>
        <h5>1. Compensation Package</h5>
        <p>Your Gross Monthly Salary is agreed at <strong>{gross_salary}</strong> (Annualized CTC: <strong>{annual_ctc}</strong>), inclusive of statutory deductions (PF, ESI/LWF, and Professional Tax) as per prevailing legislation.</p>
        <h5>2. Probation and Confirmation</h5>
        <p>You shall be on probation until <strong>{probation_end_date}</strong>. Based on your performance appraisal and management review, your service will be confirmed in writing. Unless explicitly confirmed, your probationary status shall continue.</p>
        <h5>3. Reporting Hierarchy</h5>
        <p>You shall report to <strong>{reporting_manager_name}</strong> ({reporting_manager_designation}), or any other officer designated by the company.</p>
        <h5>4. Code of Conduct & Confidentiality</h5>
        <p>You shall adhere strictly to company standing orders, safety manuals, information security protocols, and business conduct codes.</p>
        <p class="mt-4">Welcome to the team!<br/>
        For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Online Verification: {verification_url}</p>
    </div>
</div>';
    }

    protected function getConfirmationLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> (Employee Code: {employee_code})<br/>
        Designation: {designation} | Department: {department}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Confirmation of Employment Services</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>Consequent upon the completion of your probationary period and subsequent satisfactory evaluation of your performance, we have great pleasure in confirming your services as <strong>{designation}</strong> in <strong>{company_name}</strong>, effective from <strong>{confirmation_date}</strong>.</p>
        <p>All other terms and conditions of your employment contract, as outlined in your original Appointment Letter, remain unaltered and in full force.</p>
        <p>We appreciate the dedication and diligence you have demonstrated during your probation and look forward to your sustained contribution towards our collective organizational milestones.</p>
        <p class="mt-4">Best wishes for a rewarding career with us.<br/>
        For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getProbationExtensionTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation} | Location: {location}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Extension of Probationary Period</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>This has reference to your appointment dated <strong>{date_of_joining}</strong> as {designation} and your initial probation scheduled to conclude on <strong>{probation_end_date}</strong>.</p>
        <p>Following a performance appraisal by your reporting manager, it has been noted that while you have made progress, certain key operational competencies require further demonstration and alignment with departmental benchmarks.</p>
        <p>Accordingly, the Management has decided to extend your probationary period by an additional period of three (3) months, effective from <strong>{effective_date}</strong>.</p>
        <p>During this extended term, you are encouraged to liaise closely with your manager, <strong>{reporting_manager_name}</strong>, to achieve the agreed milestones. Your performance will be reviewed again prior to the conclusion of this extension.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getPromotionLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Department: {department}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Promotion to {designation}</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>In recognition of your outstanding performance, leadership, and exemplary dedication towards the company goals, the Management is delighted to promote you to the position of <strong>{designation}</strong> in <strong>{department}</strong>, effective <strong>{effective_date}</strong>.</p>
        <p>Consequent to your elevation, your revised compensation structure will be as follows:</p>
        <table class="table table-bordered table-sm my-3">
            <tr><th width="40%">Revised Gross Monthly Salary</th><td><strong>{gross_salary}</strong></td></tr>
            <tr><th>Revised Annualized CTC</th><td><strong>{annual_ctc}</strong></td></tr>
            <tr><th>Effective From</th><td>{effective_date}</td></tr>
        </table>
        <p>We trust that in this expanded responsibility, you will continue to inspire your peers and lead your department to higher levels of operational excellence.</p>
        <p>Congratulations on your well-deserved achievement!</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getTransferLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Internal Reassignment & Transfer Order</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>Due to operational and business requirements, the Management has decided to transfer you from your present posting to the <strong>{location}</strong> branch/plant, effective <strong>{effective_date}</strong>.</p>
        <p>Upon reporting, you will take charge of duties under the supervision of <strong>{reporting_manager_name}</strong>. You are advised to ensure a complete and smooth handover of your current tasks, equipment, and customer portfolios before your relocation.</p>
        <p>Relocation allowances and travel reimbursements shall be disbursed in accordance with standard enterprise travel policies.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getDesignationChangeTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Reclassification of Official Designation</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>In accordance with our updated corporate organizational framework and job leveling structure, we are pleased to inform you that your designation has been reclassified to <strong>{designation}</strong> in <strong>{department}</strong>, effective <strong>{effective_date}</strong>.</p>
        <p>Please note that this designation revision is an organizational alignment and all other terms of your employment contract remain unchanged.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getSalaryRevisionTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Revision of Salary Structure</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>Following a strategic review of compensation parity and your valued role in <strong>{department}</strong>, the Management has revised your remuneration package, effective from <strong>{effective_date}</strong>.</p>
        <div class="salary-details my-3">
            <table class="table table-bordered table-sm">
                <tr><th width="40%">Monthly Basic Pay</th><td>{basic_salary}</td></tr>
                <tr><th>Revised Monthly Gross Salary</th><td><strong>{gross_salary}</strong></td></tr>
                <tr><th>Revised Annualized CTC</th><td><strong>{annual_ctc}</strong></td></tr>
            </table>
        </div>
        <p>All compensation details are strictly confidential. We look forward to your continued dedication.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getIncrementLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Annual Performance Increment Notice</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>We take this opportunity to thank you for your contributions towards the growth of <strong>{company_name}</strong> during the preceding appraisal cycle.</p>
        <p>In appreciation of your work, the Management is pleased to award you an annual increment, resulting in a revised Gross Monthly Compensation of <strong>{gross_salary}</strong> (Annual CTC: <strong>{annual_ctc}</strong>), effective <strong>{effective_date}</strong>.</p>
        <p>We count on your continuous enthusiasm and high performance in the upcoming year.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getRelievingLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To Whomsoever It May Concern</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Formal Relieving Letter — {employee_name} ({employee_code})</strong>
    </div>
    <div class="letter-body">
        <p>This is to certify that <strong>{employee_name}</strong> was employed with <strong>{company_name}</strong> from <strong>{date_of_joining}</strong> to <strong>{effective_date}</strong>.</p>
        <p>At the time of relieving, {first_name} was serving as <strong>{designation}</strong> in the <strong>{department}</strong> department at our <strong>{location}</strong> establishment.</p>
        <p>Consequent to the acceptance of resignation and successful completion of departmental exit handovers, company asset return, and no-dues clearance, {first_name} stands relieved of all professional duties with effect from the close of business hours on <strong>{effective_date}</strong>.</p>
        <p>We wish {first_name} every success in future personal and professional endeavors.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getExperienceCertificateTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Certificate of Service & Experience</strong>
    </div>
    <div class="letter-body">
        <p>This is to certify that <strong>{employee_name}</strong> (Employee Code: {employee_code}) was associated with <strong>{company_name}</strong> as a full-time employee from <strong>{date_of_joining}</strong> to <strong>{effective_date}</strong>.</p>
        <p>During the tenure of service, {first_name} held the position of <strong>{designation}</strong> in the <strong>{department}</strong> department.</p>
        <p>Throughout their association with the enterprise, we found them to be industrious, disciplined, and sincere in carrying out assigned responsibilities. Their conduct and character were good.</p>
        <p>We take this opportunity to wish them all success in their future career.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getEmploymentVerificationTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOM IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Proof of Active Employment Verification</strong>
    </div>
    <div class="letter-body">
        <p>This is to confirm that <strong>{employee_name}</strong> (Emp Code: <strong>{employee_code}</strong>) is currently an active full-time employee of <strong>{company_name}</strong>.</p>
        <p>The key details on corporate record are as follows:</p>
        <ul>
            <li><strong>Designation:</strong> {designation}</li>
            <li><strong>Department:</strong> {department}</li>
            <li><strong>Posting Location:</strong> {location}</li>
            <li><strong>Date of Joining:</strong> {date_of_joining}</li>
            <li><strong>Employment Category:</strong> {category}</li>
        </ul>
        <p>This verification letter is issued upon the request of the employee for official records.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getSalaryCertificateTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Certified Salary Certificate</strong>
    </div>
    <div class="letter-body">
        <p>This is to certify that <strong>{employee_name}</strong> ({employee_code}) is a permanent employee of <strong>{company_name}</strong>, presently serving as <strong>{designation}</strong> in <strong>{department}</strong> at <strong>{location}</strong>.</p>
        <p>The current monthly compensation breakdown of the employee is as certified below:</p>
        <table class="table table-bordered table-sm my-3">
            <tr><th width="40%">Monthly Basic Pay</th><td>{basic_salary}</td></tr>
            <tr><th>Total Monthly Gross Salary</th><td><strong>{gross_salary}</strong></td></tr>
            <tr><th>Approximate Monthly Net Take-Home</th><td><strong>{net_salary}</strong></td></tr>
            <tr><th>Annualized Cost to Company (CTC)</th><td><strong>{annual_ctc}</strong></td></tr>
        </table>
        <p>This certificate is issued at the specific request of the employee for submission to financial institutions / authorities without any liability on the part of the issuing company.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getBankAccountLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p>To,<br/><strong>The Branch Manager</strong><br/>Authorized Corporate Banking Partner</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Opening of Corporate Salary Account — {employee_name}</strong>
    </div>
    <div class="letter-body">
        <p>Dear Sir/Madam,</p>
        <p>We wish to introduce our employee <strong>{employee_name}</strong> (Employee Code: <strong>{employee_code}</strong>), who has joined <strong>{company_name}</strong> as <strong>{designation}</strong> on <strong>{date_of_joining}</strong>.</p>
        <p>We request you to kindly open a Corporate Zero-Balance Salary Account in the name of the employee to facilitate monthly payroll disbursement.</p>
        <p>The identity and current address ({address}) have been vetted against corporate records.</p>
        <p class="mt-4">Yours faithfully,<br/>For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getAddressVerificationTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Address Verification Certification</strong>
    </div>
    <div class="letter-body">
        <p>This is to certify that <strong>{employee_name}</strong> ({employee_code}), employed with <strong>{company_name}</strong> as <strong>{designation}</strong>, has registered the following residential address in official employment records:</p>
        <div class="p-3 bg-light border my-3">
            <strong>{address}</strong>
        </div>
        <p>This certificate is issued on the employee\'s request as proof of residence on record.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getNocLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: No Objection Certificate (NOC)</strong>
    </div>
    <div class="letter-body">
        <p>This is to certify that <strong>{company_name}</strong> has no objection to <strong>{employee_name}</strong> (Employee Code: {employee_code}), presently working as <strong>{designation}</strong>, applying for international passport/visa processing or relevant academic development.</p>
        <p>{first_name} is a confirmed employee of the company and is permitted to undertake the travel/program within approved leave entitlements.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getLoanEligibilityTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>TO WHOMSOEVER IT MAY CONCERN</strong></p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Loan Eligibility & Employment Stability Certificate</strong>
    </div>
    <div class="letter-body">
        <p>This is to confirm that <strong>{employee_name}</strong> ({employee_code}) has been employed on a confirmed basis with <strong>{company_name}</strong> since <strong>{date_of_joining}</strong> as {designation}.</p>
        <p>The employee draws an annual compensation of <strong>{annual_ctc}</strong> (Gross Monthly: <strong>{gross_salary}</strong>). The company confirms that standard payroll deductions for authorized financial obligations can be processed subject to statutory limits.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getWarningLetterTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>CONFIDENTIAL</strong><br/>
        To: <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation} | Department: {department}</p>
    </div>
    <div class="letter-subject mt-3 mb-3 text-danger">
        <strong>Subject: Official Written Warning Letter</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>It has been brought to the notice of Management that you have committed repeated breaches of company workplace guidelines / unexplained absenteeism contrary to established rules.</p>
        <p>Despite informal counsel by your supervisor <strong>{reporting_manager_name}</strong>, no acceptable improvement has been demonstrated.</p>
        <p>You are hereby issued this <strong>Formal Written Warning</strong>. You are advised to strictly rectify your conduct and demonstrate required compliance. Failure to do so may lead to strict disciplinary actions as per standing orders.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getShowCauseNoticeTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>CONFIDENTIAL & URGENT</strong><br/>
        To: <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation}</p>
    </div>
    <div class="letter-subject mt-3 mb-3 text-danger">
        <strong>Subject: Show Cause Notice for Unexcused Irregularity</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>You are hereby directed to show cause in writing within forty-eight (48) hours of receipt of this notice as to why disciplinary proceedings should not be initiated against you under corporate standing rules.</p>
        <p>Your written explanation must be submitted to the undersigned. If no reply is received within the stipulated deadline, it will be presumed that you have no defense to offer.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    protected function getDisciplinaryActionTemplate(): string
    {
        return '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>CONFIDENTIAL</strong><br/>
        To: <strong>{employee_name}</strong> ({employee_code})</p>
    </div>
    <div class="letter-subject mt-3 mb-3 text-danger">
        <strong>Subject: Final Determination on Disciplinary Proceedings</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>This has reference to the disciplinary inquiry held regarding misconduct on record. The inquiry findings have established the charges substantiated.</p>
        <p>Taking all circumstances into account, the Competent Authority has ordered appropriate disciplinary sanctions effective <strong>{effective_date}</strong>. This shall be placed in your personnel dossier.</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>';
    }

    // Forms
    protected function getEmployeeInfoFormTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>EMPLOYEE INFORMATION SHEET</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th width="30%">Full Name</th><td>{employee_name}</td><th width="20%">Employee Code</th><td>{employee_code}</td></tr>
        <tr><th>Department</th><td>{department}</td><th>Designation</th><td>{designation}</td></tr>
        <tr><th>Work Location</th><td>{location}</td><th>Category</th><td>{category}</td></tr>
        <tr><th>Date of Joining</th><td>{date_of_joining}</td><th>Date of Birth</th><td>{date_of_birth}</td></tr>
        <tr><th>Mobile Number</th><td>{phone}</td><th>Official Email</th><td>{email}</td></tr>
        <tr><th>Residential Address</th><td colspan="3">{address}</td></tr>
        <tr><th>Emergency Contact</th><td>{emergency_contact_name}</td><th>Contact Phone</th><td>{emergency_contact_phone}</td></tr>
        <tr><th>PAN Card</th><td>{pan_masked}</td><th>Aadhaar Number</th><td>{aadhaar_masked}</td></tr>
    </table>
    <div class="mt-4">
        <p>I hereby certify that all information submitted herein is true and correct.</p>
        <div class="row mt-5">
            <div class="col-6">Employee Signature: __________________</div>
            <div class="col-6 text-end">Date: {current_date}</div>
        </div>
    </div>
</div>';
    }

    protected function getJoiningFormTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>WORKFORCE JOINING REPORT</h4>
    </div>
    <p>This is to confirm that <strong>{employee_name}</strong> has physically reported for work on <strong>{date_of_joining}</strong> at <strong>{location}</strong>.</p>
    <table class="table table-bordered table-sm my-3">
        <tr><th>Emp Code</th><td>{employee_code}</td><th>Gate Pass No</th><td>{gate_pass_number}</td></tr>
        <tr><th>Department</th><td>{department}</td><th>Designation</th><td>{designation}</td></tr>
        <tr><th>Reporting Manager</th><td colspan="3">{reporting_manager_name} ({reporting_manager_designation})</td></tr>
    </table>
    <div class="row mt-5">
        <div class="col-6">Employee Signature: __________________</div>
        <div class="col-6 text-end">HR Sign-off: __________________</div>
    </div>
</div>';
    }

    protected function getDeclarationFormTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>NON-DISCLOSURE & INTEGRITY DECLARATION</h4>
    </div>
    <p>I, <strong>{employee_name}</strong> ({employee_code}), hereby declare that I shall maintain complete confidentiality of trade secrets, drawings, designs, client lists, and technical data belonging to <strong>{company_name}</strong>.</p>
    <p>I shall not misuse enterprise equipment or intellectual property and shall uphold the code of conduct during and after my service.</p>
    <div class="mt-5">
        <p>Signature: __________________________</p>
        <p>Date: {current_date}</p>
    </div>
</div>';
    }

    protected function getEmergencyContactFormTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>EMERGENCY CONTACT & MEDICAL DISCLOSURE FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee Name</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Emergency Contact Name</th><td>{emergency_contact_name}</td><th>Phone</th><td>{emergency_contact_phone}</td></tr>
        <tr><th>Blood Group</th><td>[ ________ ]</td><th>Known Allergies</th><td>[ None / Specify ]</td></tr>
    </table>
    <p class="mt-4 text-muted">Authorizing {company_name} medical first responders in case of plant / workplace emergency.</p>
</div>';
    }

    protected function getNomineeDeclarationTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>NOMINEE DECLARATION FORM (PF & GRATUITY)</h4>
    </div>
    <p>I, <strong>{employee_name}</strong> ({employee_code}), hereby nominate the following beneficiary to receive statutory dues in the event of my demise:</p>
    <table class="table table-bordered table-sm my-3">
        <tr><th>Nominee Full Name</th><th>Relationship</th><th>Age / DOB</th><th>Share Percentage</th></tr>
        <tr><td>{emergency_contact_name}</td><td>Primary Nominee</td><td>Adult</td><td>100%</td></tr>
    </table>
    <div class="mt-4">
        <p>Signature of Employee: _____________________ Date: {current_date}</p>
    </div>
</div>';
    }

    protected function getAssetHandoverTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>ASSET HANDOVER & UNDERTAKING FORM</h4>
    </div>
    <p>Acknowledgment of company issued equipment to <strong>{employee_name}</strong> ({employee_code}):</p>
    <table class="table table-bordered table-sm">
        <tr><th>Asset Tag</th><th>Description</th><th>Serial No</th><th>Condition</th></tr>
        <tr><td>AST-IT-001</td><td>Work Laptop / Desktop</td><td>SN-{employee_code}-01</td><td>Brand New / Functional</td></tr>
        <tr><td>AST-ACC-002</td><td>Security Gate Pass</td><td>{gate_pass_number}</td><td>Active</td></tr>
    </table>
    <p class="mt-3">I accept responsibility for safe custody of the above assets and agree to surrender them upon separation.</p>
    <p class="mt-4">Sign: _________________________ Date: {current_date}</p>
</div>';
    }

    protected function getExitClearanceTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>DEPARTMENTAL EXIT CLEARANCE (NO-DUES) FORM</h4>
    </div>
    <p>Employee: <strong>{employee_name}</strong> ({employee_code}) | Dept: {department}</p>
    <table class="table table-bordered table-sm">
        <tr><th>Department</th><th>Clearance Status</th><th>Authorized Signatory</th><th>Date</th></tr>
        <tr><td>Reporting Manager</td><td>Approved [ ]</td><td>____________________</td><td>___/___/2026</td></tr>
        <tr><td>IT Systems</td><td>Assets Returned [ ]</td><td>____________________</td><td>___/___/2026</td></tr>
        <tr><td>Administration / Stores</td><td>Tools & ID Surrendered [ ]</td><td>____________________</td><td>___/___/2026</td></tr>
        <tr><td>Finance / Accounts</td><td>No Dues [ ]</td><td>____________________</td><td>___/___/2026</td></tr>
        <tr><td>Human Resources</td><td>Exit Interview Complete [ ]</td><td>____________________</td><td>___/___/2026</td></tr>
    </table>
</div>';
    }

    protected function getLeaveApplicationTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>LEAVE APPLICATION FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee Name</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Department</th><td>{department}</td><th>Location</th><td>{location}</td></tr>
        <tr><th>Leave Type Requested</th><td colspan="3">[  ] Earned Leave (PL)   [  ] Casual Leave (CL)   [  ] Sick Leave (SL)</td></tr>
        <tr><th>Date Range</th><td colspan="3">From: ___/___/2026  To: ___/___/2026  (Total Days: ____)</td></tr>
        <tr><th>Reason for Leave</th><td colspan="3">_________________________________________________________</td></tr>
    </table>
    <div class="row mt-4">
        <div class="col-6">Applicant Signature</div>
        <div class="col-6 text-end">Manager Approval</div>
    </div>
</div>';
    }

    protected function getAttendanceRegTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>ATTENDANCE REGULARIZATION FORM</h4>
    </div>
    <p>Employee: <strong>{employee_name}</strong> ({employee_code})</p>
    <table class="table table-bordered table-sm">
        <tr><th>Date of Incident</th><th>Punch In</th><th>Punch Out</th><th>Reason (Machine Error / On Duty / Client Site)</th></tr>
        <tr><td>___/___/2026</td><td>__:__</td><td>__:__</td><td>___________________________________</td></tr>
    </table>
    <div class="row mt-4">
        <div class="col-6">Employee Signature</div>
        <div class="col-6 text-end">HOD Signature</div>
    </div>
</div>';
    }

    protected function getOtApprovalTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>OVERTIME AUTHORIZATION FORM</h4>
    </div>
    <p>Employee: <strong>{employee_name}</strong> ({employee_code}) | Location: {location}</p>
    <table class="table table-bordered table-sm">
        <tr><th>Date</th><th>Planned OT Hours</th><th>Nature of Urgent Plant Duty</th><th>Plant Head Sign</th></tr>
        <tr><td>___/___/2026</td><td>____ Hours</td><td>Production Overhaul</td><td>______________</td></tr>
    </table>
</div>';
    }

    protected function getCompOffTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>COMPENSATORY OFF (COMP-OFF) GRANT FORM</h4>
    </div>
    <p>Employee: <strong>{employee_name}</strong> ({employee_code})</p>
    <p>Worked on Weekly Off / Public Holiday: <strong>___/___/2026</strong></p>
    <p>Compensatory off granted to be availed within 60 days as per company leave regulations.</p>
</div>';
    }

    protected function getSalaryRevReqTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>COMPENSATION REVISION RECOMMENDATION FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee Name</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Present Gross Pay</th><td>{gross_salary}</td><th>Proposed Gross Pay</th><td>_________________</td></tr>
        <tr><th>Justification</th><td colspan="3">Performance / Promotion / Market Correction</td></tr>
    </table>
    <p class="mt-4">Authorized by HOD & HR Management.</p>
</div>';
    }

    protected function getLoanAppTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>COMPANY LOAN APPLICATION FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Applicant</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Current Monthly Gross</th><td>{gross_salary}</td><th>Requested Loan Amount</th><td>₹ _______________</td></tr>
        <tr><th>Repayment Tenor</th><td>______ Months</td><th>Purpose</th><td>Medical / Emergency / Housing</td></tr>
    </table>
    <p class="mt-3">I authorize monthly salary deduction until full recovery.</p>
</div>';
    }

    protected function getSalaryAdvanceTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>SALARY ADVANCE REQUEST FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Advance Amount</th><td>₹ ___________</td><th>Recovery Month</th><td>Next Payroll Cycle</td></tr>
    </table>
</div>';
    }

    protected function getItDeclarationTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>INCOME TAX INVESTMENT DECLARATION FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee</th><td>{employee_name}</td><th>PAN Number</th><td>{pan_masked}</td></tr>
        <tr><th>Section 80C (PPF/LIC/ELSS)</th><td colspan="3">₹ _________________ (Max 1,50,000)</td></tr>
        <tr><th>Section 80D (Mediclaim)</th><td colspan="3">₹ _________________</td></tr>
        <tr><th>Section 24 (Home Loan Interest)</th><td colspan="3">₹ _________________</td></tr>
    </table>
</div>';
    }

    protected function getFnfSettlementTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>FULL & FINAL SETTLEMENT SUMMARY SHEET</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee Name</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Joining Date</th><td>{date_of_joining}</td><th>Relieving Date</th><td>{effective_date}</td></tr>
        <tr><th>Last Monthly Gross</th><td>{gross_salary}</td><th>Basic Pay</th><td>{basic_salary}</td></tr>
    </table>
</div>';
    }

    protected function getTransferReqTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>EMPLOYEE INITIATED TRANSFER REQUEST FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee</th><td>{employee_name}</td><th>Current Branch</th><td>{location}</td></tr>
        <tr><th>Desired Branch</th><td>____________________</td><th>Reason</th><td>Personal / Family relocation</td></tr>
    </table>
</div>';
    }

    protected function getDeptChangeTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>DEPARTMENT REASSIGNMENT REQUEST FORM</h4>
    </div>
    <p>Employee: {employee_name} ({employee_code}) | Current Dept: {department}</p>
    <p>Target Department: _______________________</p>
</div>';
    }

    protected function getDesigChangeTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>DESIGNATION RECLASSIFICATION FORM</h4>
    </div>
    <p>Employee: {employee_name} ({employee_code}) | Current Designation: {designation}</p>
    <p>Requested Designation: _______________________</p>
</div>';
    }

    protected function getResignationFormTemplate(): string
    {
        return '<div class="form-container">
    <div class="text-center mb-4">
        <h3>{company_name}</h3>
        <h4>FORMAL RESIGNATION SUBMISSION FORM</h4>
    </div>
    <table class="table table-bordered table-sm">
        <tr><th>Employee</th><td>{employee_name}</td><th>Emp Code</th><td>{employee_code}</td></tr>
        <tr><th>Department</th><td>{department}</td><th>Designation</th><td>{designation}</td></tr>
        <tr><th>Submission Date</th><td>{current_date}</td><th>Proposed Last Working Day</th><td>___/___/2026</td></tr>
        <tr><th>Reason for Separation</th><td colspan="3">Career Growth / Relocation / Higher Studies / Other</td></tr>
    </table>
    <div class="mt-4">
        <p>I confirm my willingness to serve notice period obligations as per company appointment terms.</p>
        <p class="mt-4">Employee Signature: ______________________</p>
    </div>
</div>';
    }
}

