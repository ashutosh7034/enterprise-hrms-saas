<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractorController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        $contractors = Contractor::latest()->paginate(15);
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('masters.contractors.index', compact('contractors', 'activeCompany'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('contractors.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'agency_name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('contractors', 'code')->where('company_id', $activeCompanyId),
            ],
            'contact_person' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'pan' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $validated['company_id'] = $activeCompanyId;

        Contractor::create($validated);

        return redirect()->route('contractors.index')->with('success', 'Contractor created successfully.');
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($contractor->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $validated = $request->validate([
            'agency_name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('contractors', 'code')
                    ->where('company_id', $activeCompanyId)
                    ->ignore($contractor->id),
            ],
            'contact_person' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'pan' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $contractor->update($validated);

        return redirect()->route('contractors.index')->with('success', 'Contractor updated successfully.');
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($contractor->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $contractor->delete();
        return redirect()->route('contractors.index')->with('success', 'Contractor deactivated successfully.');
    }
}

