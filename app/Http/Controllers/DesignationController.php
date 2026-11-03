<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        $designations = Designation::with('department')->latest()->paginate(15);
        $departments = Department::where('status', 'Active')->orderBy('name')->get();
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('masters.designations.index', compact('designations', 'departments', 'activeCompany'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('designations.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $activeCompanyId),
            ],
            'title' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('designations', 'code')->where('department_id', $request->input('department_id')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        Designation::create($validated);

        return redirect()->route('designations.index')->with('success', 'Designation created successfully.');
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($designation->department->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $activeCompanyId),
            ],
            'title' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('designations', 'code')
                    ->where('department_id', $request->input('department_id'))
                    ->ignore($designation->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $designation->update($validated);

        return redirect()->route('designations.index')->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($designation->department->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $designation->delete();
        return redirect()->route('designations.index')->with('success', 'Designation deactivated successfully.');
    }
}

