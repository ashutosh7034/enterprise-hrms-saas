<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryComponent;
use App\Models\SalaryRevisionHistory;
use App\Models\SalaryStructure;
use App\Services\CompanyContextService;
use App\Services\SalaryStructureService;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function __construct(
        protected SalaryStructureService $structureService,
        protected CompanyContextService $contextService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $company = Company::findOrFail($companyId);
        $this->structureService->ensureStandardComponents($company);

        $structures = SalaryStructure::where('company_id', $companyId)
            ->with(['components.component'])
            ->paginate(15);

        $assignments = EmployeeSalaryAssignment::where('company_id', $companyId)
            ->with(['employee.department', 'employee.designation', 'salaryStructure'])
            ->latest('effective_from_date')
            ->paginate(20, ['*'], 'assignments_page');

        return view('payroll.structures.index', compact('structures', 'assignments', 'company'));
    }

    public function create()
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $company = Company::findOrFail($companyId);
        $this->structureService->ensureStandardComponents($company);

        $components = SalaryComponent::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('payroll.structures.create', compact('company', 'components'));
    }

    public function store(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $company = Company::findOrFail($companyId);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_target_ot' => ['nullable', 'boolean'],
            'target_ot_amount' => ['nullable', 'numeric', 'min:0'],
            'components' => ['nullable', 'array'],
        ]);

        // Check duplicate code
        if (SalaryStructure::where('company_id', $companyId)->where('code', $validated['code'])->exists()) {
            return back()->withErrors(['code' => 'Salary structure code already exists in this company.'])->withInput();
        }

        $componentsData = [];
        if (!empty($validated['components'])) {
            foreach ($validated['components'] as $comp) {
                if (!empty($comp['salary_component_id'])) {
                    $componentsData[] = [
                        'salary_component_id' => $comp['salary_component_id'],
                        'calculation_type' => $comp['calculation_type'] ?? 'Fixed Amount',
                        'default_amount' => $comp['default_amount'] ?? 0.00,
                        'percentage' => $comp['percentage'] ?? null,
                        'formula' => $comp['formula'] ?? null,
                    ];
                }
            }
        }

        $this->structureService->createSalaryStructure($company, $validated, $componentsData);

        return redirect()->route('payroll.structures.index')->with('success', 'Salary Structure created successfully.');
    }

    public function assignForm(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Active', 'Resigned'])
            ->orderBy('first_name')
            ->get();

        $structures = SalaryStructure::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        return view('payroll.structures.assign', compact('employees', 'structures'));
    }

    public function storeAssignment(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'salary_structure_id' => ['required', 'exists:salary_structures,id'],
            'effective_from_date' => ['required', 'date'],
            'gross_monthly' => ['required', 'numeric', 'min:0'],
            'ctc_annual' => ['nullable', 'numeric', 'min:0'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'da' => ['nullable', 'numeric', 'min:0'],
            'special_allowance' => ['nullable', 'numeric', 'min:0'],
            'is_pf_eligible' => ['nullable', 'boolean'],
            'is_esi_eligible' => ['nullable', 'boolean'],
            'revision_notes' => ['nullable', 'string'],
        ]);

        $employee = Employee::withoutGlobalScopes()->where('company_id', $companyId)->findOrFail($validated['employee_id']);
        $structure = SalaryStructure::where('company_id', $companyId)->findOrFail($validated['salary_structure_id']);

        $componentValues = [
            'BASIC' => (float) $validated['basic_salary'],
            'HRA' => (float) ($validated['hra'] ?? 0.00),
            'DA' => (float) ($validated['da'] ?? 0.00),
            'SPECIAL' => (float) ($validated['special_allowance'] ?? 0.00),
        ];

        $assignmentData = [
            'effective_from_date' => $validated['effective_from_date'],
            'gross_monthly' => $validated['gross_monthly'],
            'ctc_annual' => $validated['ctc_annual'] ?? ($validated['gross_monthly'] * 12.0),
            'component_values' => $componentValues,
            'is_pf_eligible' => !empty($validated['is_pf_eligible']),
            'is_esi_eligible' => !empty($validated['is_esi_eligible']),
            'revision_notes' => $validated['revision_notes'] ?? null,
        ];

        $this->structureService->assignSalaryStructure(
            $employee,
            $structure,
            $assignmentData,
            $request->user(),
            autoApprove: true
        );

        return redirect()->route('payroll.structures.index')->with('success', "Salary Structure assigned successfully to {$employee->full_name}.");
    }

    public function revisions(Employee $employee)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $revisions = SalaryRevisionHistory::where('employee_id', $employee->id)
            ->with(['previousAssignment', 'newAssignment.salaryStructure', 'changer'])
            ->latest('effective_from_date')
            ->get();

        return view('payroll.structures.revisions', compact('employee', 'revisions'));
    }
}

