<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        $departments = Department::withCount('designations')->latest()->paginate(15);
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('masters.departments.index', compact('departments', 'activeCompany'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('departments.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where('company_id', $activeCompanyId),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $validated['company_id'] = $activeCompanyId;

        Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($department->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where('company_id', $activeCompanyId)
                    ->ignore($department->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($department->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Department deactivated successfully.');
    }
}

