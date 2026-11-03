<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryComponent;
use App\Models\SalaryRevisionHistory;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalaryStructureService
{
    /**
     * Seed or ensure default standard salary components for a company.
     */
    public function ensureStandardComponents(Company $company): void
    {
        $standards = [
            // Earnings
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => true, 'sort_order' => 1],
            ['code' => 'HRA', 'name' => 'House Rent Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => false, 'sort_order' => 2],
            ['code' => 'DA', 'name' => 'Dearness Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => true, 'sort_order' => 3],
            ['code' => 'CONVEYANCE', 'name' => 'Conveyance Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => false, 'sort_order' => 4],
            ['code' => 'MEDICAL', 'name' => 'Medical Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => false, 'sort_order' => 5],
            ['code' => 'SPECIAL', 'name' => 'Special Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => false, 'sort_order' => 6],
            ['code' => 'PROD_ALLOWANCE', 'name' => 'Site / Production Allowance', 'component_type' => 'Earning', 'is_taxable' => true, 'is_statutory' => false, 'sort_order' => 7],
            
            // Employee Deductions
            ['code' => 'PF_EMP', 'name' => 'Provident Fund (Employee)', 'component_type' => 'Deduction', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 20],
            ['code' => 'ESI_EMP', 'name' => 'ESI (Employee)', 'component_type' => 'Deduction', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 21],
            ['code' => 'PT', 'name' => 'Professional Tax', 'component_type' => 'Deduction', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 22],
            ['code' => 'LWF', 'name' => 'Labour Welfare Fund', 'component_type' => 'Deduction', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 23],
            ['code' => 'TDS', 'name' => 'Income Tax (TDS)', 'component_type' => 'Deduction', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 24],

            // Employer Contributions
            ['code' => 'PF_EMPR', 'name' => 'Provident Fund (Employer)', 'component_type' => 'Employer Contribution', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 30],
            ['code' => 'ESI_EMPR', 'name' => 'ESI (Employer)', 'component_type' => 'Employer Contribution', 'is_taxable' => false, 'is_statutory' => true, 'sort_order' => 31],
        ];

        foreach ($standards as $item) {
            SalaryComponent::firstOrCreate(
                ['company_id' => $company->id, 'code' => $item['code']],
                array_merge($item, ['company_id' => $company->id, 'is_active' => true])
            );
        }
    }

    /**
     * Create a salary structure with itemized component rules.
     */
    public function createSalaryStructure(Company $company, array $data, array $components = []): SalaryStructure
    {
        return DB::transaction(function () use ($company, $data, $components) {
            $structure = SalaryStructure::create([
                'company_id' => $company->id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_target_ot' => !empty($data['is_target_ot']),
                'target_ot_amount' => $data['target_ot_amount'] ?? 0.00,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($components as $index => $comp) {
                SalaryStructureComponent::create([
                    'salary_structure_id' => $structure->id,
                    'salary_component_id' => $comp['salary_component_id'],
                    'calculation_type' => $comp['calculation_type'] ?? 'Fixed Amount',
                    'percentage' => $comp['percentage'] ?? null,
                    'percentage_of_component_id' => $comp['percentage_of_component_id'] ?? null,
                    'formula' => $comp['formula'] ?? null,
                    'default_amount' => $comp['default_amount'] ?? 0.00,
                    'is_mandatory' => $comp['is_mandatory'] ?? true,
                    'sort_order' => $comp['sort_order'] ?? ($index + 1),
                ]);
            }

            return $structure;
        });
    }

    /**
     * Safely evaluate a formula without arbitrary code execution.
     * Supported variables: component codes (e.g. BASIC, DA)
     * Supported operators: +, -, *, /
     */
    public function evaluateFormula(string $formula, array $values): float
    {
        // Sanitize: allow only alphanumeric component codes, digits, decimals, whitespace, and basic math operators
        $sanitized = trim($formula);
        if (!preg_match('/^[A-Za-z0-9_\.\s\+\-\*\/\(\)]+$/', $sanitized)) {
            throw new InvalidArgumentException("Unsafe salary formula expression detected: {$formula}");
        }

        // Tokenize and replace component codes with their numeric values
        $evaluatedExpr = preg_replace_callback('/[A-Za-z_][A-Za-z0-9_]*/', function ($matches) use ($values) {
            $token = strtoupper($matches[0]);
            return isset($values[$token]) ? (string) (float) $values[$token] : '0';
        }, $sanitized);

        // Calculate using safe deterministic arithmetic parser
        return $this->parseArithmetic($evaluatedExpr);
    }

    /**
     * Simple deterministic arithmetic evaluator for mathematical expressions.
     */
    protected function parseArithmetic(string $expr): float
    {
        // Remove spaces
        $expr = str_replace(' ', '', $expr);
        if ($expr === '') {
            return 0.0;
        }

        // Validate strictly numeric arithmetic expression
        if (!preg_match('/^[0-9\.\+\-\*\/\(\)]+$/', $expr)) {
            throw new InvalidArgumentException("Invalid arithmetic tokens in formula.");
        }

        // Safe evaluation through basic tokenization (handling + - * /)
        // Using PHP mathematical normalization without eval()
        try {
            $mathParser = new \App\Services\SalaryFormulaParser();
            return $mathParser->calculate($expr);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Assign or revise an employee's salary structure with revision tracking.
     */
    public function assignSalaryStructure(
        Employee $employee,
        SalaryStructure $structure,
        array $data,
        User $actor,
        bool $autoApprove = true
    ): EmployeeSalaryAssignment {
        return DB::transaction(function () use ($employee, $structure, $data, $actor, $autoApprove) {
            // Find existing active assignment to supersede if approving
            $previous = EmployeeSalaryAssignment::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->latest('id')
                ->first();

            $grossMonthly = (float) ($data['gross_monthly'] ?? 0.00);
            $ctcAnnual = (float) ($data['ctc_annual'] ?? ($grossMonthly * 12));

            $assignment = EmployeeSalaryAssignment::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'effective_from_date' => $data['effective_from_date'],
                'effective_to_date' => $data['effective_to_date'] ?? null,
                'ctc_annual' => $ctcAnnual,
                'gross_monthly' => $grossMonthly,
                'component_values_json' => $data['component_values'] ?? [],
                'is_target_ot' => $structure->is_target_ot,
                'target_ot_amount' => $structure->is_target_ot ? $structure->target_ot_amount : 0.00,
                'is_pf_eligible' => $data['is_pf_eligible'] ?? true,
                'is_esi_eligible' => $data['is_esi_eligible'] ?? true,
                'status' => $autoApprove ? 'Approved' : 'Draft',
                'approved_by' => $autoApprove ? $actor->id : null,
                'approved_at' => $autoApprove ? now() : null,
                'revision_notes' => $data['revision_notes'] ?? null,
            ]);

            if ($autoApprove && $previous) {
                // Supersede previous assignment
                $previous->update([
                    'status' => 'Superseded',
                    'effective_to_date' => \Carbon\Carbon::parse($data['effective_from_date'])->subDay()->toDateString(),
                ]);

                // Record revision history
                $incrementPct = $previous->ctc_annual > 0
                    ? round((($ctcAnnual - $previous->ctc_annual) / $previous->ctc_annual) * 100, 2)
                    : 0.00;

                SalaryRevisionHistory::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'previous_assignment_id' => $previous->id,
                    'new_assignment_id' => $assignment->id,
                    'effective_from_date' => $data['effective_from_date'],
                    'old_ctc_annual' => $previous->ctc_annual,
                    'new_ctc_annual' => $ctcAnnual,
                    'increment_percentage' => $incrementPct,
                    'changed_by' => $actor->id,
                    'reason' => $data['revision_notes'] ?? 'Salary Structure Revision',
                ]);
            }

            // Ensure employee has is_salary_activated set to true
            if ($autoApprove && !$employee->is_salary_activated) {
                $employee->update(['is_salary_activated' => true]);
            }

            return $assignment;
        });
    }
}

