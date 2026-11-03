<?php

namespace App\Services\Document;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\GeneratedDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class PlaceholderResolutionService
{
    /**
     * Sensitive salary placeholders that require elevated role privileges.
     */
    protected array $sensitiveSalaryPlaceholders = [
        '{basic_salary}',
        '{gross_salary}',
        '{net_salary}',
        '{annual_ctc}',
        '{salary_effective_date}',
    ];

    /**
     * Get the master registry of all available placeholders and their human descriptions.
     */
    public function getPlaceholderRegistry(): array
    {
        return [
            'Company' => [
                '{company_name}' => 'Legal Company Name',
                '{company_code}' => 'Short Company Code (VE/VRE)',
                '{company_address}' => 'Registered Office Address',
                '{company_phone}' => 'Corporate Telephone Number',
                '{company_email}' => 'Corporate Email Address',
                '{company_cin}' => 'Corporate Identification Number (CIN)',
                '{company_gstin}' => 'Goods and Services Tax ID (GSTIN)',
            ],
            'Employee Profile' => [
                '{employee_name}' => 'Full Employee Name',
                '{employee_code}' => 'Official Employee Code',
                '{gate_pass_number}' => 'Gate Pass Number',
                '{first_name}' => 'Employee First Name',
                '{last_name}' => 'Employee Last Name',
                '{gender}' => 'Gender',
                '{email}' => 'Work/Personal Email',
                '{phone}' => 'Contact Mobile Number',
                '{address}' => 'Residential Address',
                '{date_of_birth}' => 'Date of Birth (d F Y)',
                '{date_of_joining}' => 'Date of Joining (d F Y)',
                '{probation_end_date}' => 'Probation End Date',
                '{confirmation_date}' => 'Confirmation Date',
                '{department}' => 'Department Name',
                '{designation}' => 'Designation Title',
                '{location}' => 'Branch / Work Location',
                '{category}' => 'Employee Category',
                '{reporting_manager_name}' => 'Reporting Manager Full Name',
                '{reporting_manager_designation}' => 'Reporting Manager Designation',
                '{pan_masked}' => 'Masked PAN Number',
                '{aadhaar_masked}' => 'Masked Aadhaar Number',
                '{emergency_contact_name}' => 'Emergency Contact Person',
                '{emergency_contact_phone}' => 'Emergency Contact Phone',
            ],
            'Compensation & Payroll (Privileged)' => [
                '{basic_salary}' => 'Monthly Basic Pay (INR Currency Formatted)',
                '{gross_salary}' => 'Monthly Gross Pay (INR Currency Formatted)',
                '{annual_ctc}' => 'Total Annual Cost to Company (INR Currency Formatted)',
                '{net_salary}' => 'Calculated Monthly Net Salary (INR Currency Formatted)',
                '{salary_effective_date}' => 'Salary Revision / Effective Date',
            ],
            'Document & Verification' => [
                '{document_number}' => 'Unique Tracking Document Number',
                '{document_date}' => 'Date Document Generated / Issued',
                '{effective_date}' => 'Effective Date of Document Action',
                '{verification_code}' => 'Tamper-Evident Verification Code',
                '{verification_url}' => 'Full URL for Public Verification',
                '{signatory_name}' => 'Authorized Signatory Name',
                '{signatory_designation}' => 'Authorized Signatory Designation',
                '{current_year}' => 'Four-digit Year (e.g. 2026)',
                '{current_date}' => 'Current Date (d F Y)',
            ],
        ];
    }

    /**
     * Resolve all placeholders in a template HTML string for a specific context.
     *
     * @param string $templateHtml
     * @param Company $company
     * @param Employee $employee
     * @param User|null $actor
     * @param array $extraData
     * @param GeneratedDocument|null $doc
     * @return string
     * @throws AuthorizationException
     */
    public function resolve(
        string $templateHtml,
        Company $company,
        Employee $employee,
        ?User $actor = null,
        array $extraData = [],
        ?GeneratedDocument $doc = null
    ): string {
        // 1. Check salary privilege if content contains salary placeholders
        $this->guardSalaryPlaceholders($templateHtml, $actor);

        // 2. Build dictionary of all values
        $dictionary = $this->buildDictionary($company, $employee, $actor, $extraData, $doc);

        // 3. Deterministic replacement
        return str_replace(array_keys($dictionary), array_values($dictionary), $templateHtml);
    }

    /**
     * Ensure acting user has privilege if sensitive salary placeholders are present.
     */
    public function guardSalaryPlaceholders(string $templateHtml, ?User $actor): void
    {
        if (!$actor) {
            return;
        }

        $roleName = $actor->role?->name ?? '';
        $isPrivileged = in_array($roleName, ['ho_admin', 'hr_executive']);

        if (!$isPrivileged) {
            foreach ($this->sensitiveSalaryPlaceholders as $placeholder) {
                if (stripos($templateHtml, $placeholder) !== false) {
                    throw new AuthorizationException(
                        "You do not possess the required HR/Admin privilege to resolve sensitive compensation placeholder: {$placeholder}"
                    );
                }
            }
        }
    }

    /**
     * Build the lookup key-value map for placeholder substitution.
     */
    public function buildDictionary(
        Company $company,
        Employee $employee,
        ?User $actor = null,
        array $extraData = [],
        ?GeneratedDocument $doc = null
    ): array {
        // Eager load necessary relationships
        $employee->loadMissing(['department', 'designation', 'location', 'category', 'reportingManager']);

        $reportingManager = $employee->reportingManager;
        $activeSalary = $this->resolveActiveSalary($employee);

        $now = Carbon::now();
        $docDate = isset($extraData['document_date']) ? Carbon::parse($extraData['document_date']) : $now;
        $effectiveDate = isset($extraData['effective_date']) ? Carbon::parse($extraData['effective_date']) : $docDate;

        $docNumber = $doc?->document_number ?? ($extraData['document_number'] ?? 'DOC-PREVIEW-0000');
        $verificationCode = $doc?->verification_code ?? ($extraData['verification_code'] ?? 'PREVIEW-CODE-000');
        $verificationUrl = url("/verify-document/{$verificationCode}");

        $signatoryName = $extraData['signatory_name'] ?? 'Authorized Signatory';
        $signatoryDesignation = $extraData['signatory_designation'] ?? 'Head - Human Resources';

        $dict = [
            // Company
            '{company_name}' => e($company->name ?? 'Vishal Enterprise'),
            '{company_code}' => e($company->code ?? 'VE'),
            '{company_address}' => e($company->address ?? 'Corporate Office, Industrial Area, Gujarat, India'),
            '{company_phone}' => e($company->phone ?? '+91 265 2000000'),
            '{company_email}' => e($company->email ?? 'hr@vishalenterprise.com'),
            '{company_cin}' => e($company->cin ?? 'U29100GJ2010PTC061234'),
            '{company_gstin}' => e($company->gstin ?? '24AABCV1234F1Z5'),

            // Employee Profile
            '{employee_name}' => e($employee->full_name),
            '{employee_code}' => e($employee->employee_code),
            '{gate_pass_number}' => e($employee->gate_pass_number ?? 'N/A'),
            '{first_name}' => e($employee->first_name),
            '{last_name}' => e($employee->last_name),
            '{gender}' => e($employee->gender ?? 'N/A'),
            '{email}' => e($employee->email ?? 'N/A'),
            '{phone}' => e($employee->phone ?? 'N/A'),
            '{address}' => e($employee->address ?? 'N/A'),
            '{date_of_birth}' => $employee->date_of_birth ? $employee->date_of_birth->format('d F Y') : 'N/A',
            '{date_of_joining}' => $employee->date_of_joining ? $employee->date_of_joining->format('d F Y') : 'N/A',
            '{probation_end_date}' => $employee->probation_end_date ? $employee->probation_end_date->format('d F Y') : 'N/A',
            '{confirmation_date}' => $employee->confirmation_date ? $employee->confirmation_date->format('d F Y') : 'N/A',
            '{department}' => e($employee->department?->name ?? 'General Administration'),
            '{designation}' => e($employee->designation?->name ?? 'Staff Associate'),
            '{location}' => e($employee->location?->name ?? 'Head Office'),
            '{category}' => e($employee->category?->name ?? 'Staff'),
            '{reporting_manager_name}' => e($reportingManager ? $reportingManager->full_name : 'Managing Director'),
            '{reporting_manager_designation}' => e($reportingManager?->designation?->name ?? 'Executive Management'),
            '{pan_masked}' => e($employee->masked_pan ?? 'N/A'),
            '{aadhaar_masked}' => e($employee->aadhaar_masked ?? 'N/A'),
            '{emergency_contact_name}' => e($employee->emergency_contact_name ?? 'N/A'),
            '{emergency_contact_phone}' => e($employee->emergency_contact_phone ?? 'N/A'),

            // Compensation
            '{basic_salary}' => $this->formatCurrency($activeSalary['basic']),
            '{gross_salary}' => $this->formatCurrency($activeSalary['gross']),
            '{annual_ctc}' => $this->formatCurrency($activeSalary['annual_ctc']),
            '{net_salary}' => $this->formatCurrency($activeSalary['gross'] * 0.90), // Approximate net estimation
            '{salary_effective_date}' => $activeSalary['effective_date'] ? Carbon::parse($activeSalary['effective_date'])->format('d F Y') : 'N/A',

            // Document & Verification
            '{document_number}' => e($docNumber),
            '{document_date}' => $docDate->format('d F Y'),
            '{effective_date}' => $effectiveDate->format('d F Y'),
            '{verification_code}' => e($verificationCode),
            '{verification_url}' => e($verificationUrl),
            '{signatory_name}' => e($signatoryName),
            '{signatory_designation}' => e($signatoryDesignation),
            '{current_year}' => $now->format('Y'),
            '{current_date}' => $now->format('d F Y'),
        ];

        // Process any custom extra runtime placeholders (e.g. {transfer_branch}, {new_designation}, {increment_amount}, etc.)
        foreach ($extraData as $key => $val) {
            if (is_scalar($val)) {
                $dict['{' . trim($key, '{}') . '}'] = e((string) $val);
            }
        }

        return $dict;
    }

    /**
     * Resolve active salary assignment details.
     */
    protected function resolveActiveSalary(Employee $employee): array
    {
        $assignment = EmployeeSalaryAssignment::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->latest('effective_from_date')
            ->first();

        if ($assignment) {
            $gross = (float) $assignment->gross_monthly;
            $components = $assignment->component_values_json ?? [];
            $basic = (float) ($components['BASIC'] ?? ($gross * 0.50));
            $annual = $gross * 12.0;

            return [
                'basic' => $basic,
                'gross' => $gross,
                'annual_ctc' => $annual,
                'effective_date' => $assignment->effective_from_date,
            ];
        }

        return [
            'basic' => 0.00,
            'gross' => 0.00,
            'annual_ctc' => 0.00,
            'effective_date' => null,
        ];
    }

    /**
     * Format a number as Indian Rupee currency.
     */
    protected function formatCurrency(float $amount): string
    {
        return '₹ ' . number_format($amount, 2, '.', ',');
    }
}

