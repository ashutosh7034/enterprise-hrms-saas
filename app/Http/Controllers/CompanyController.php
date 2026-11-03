<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        abort_if(!Auth::user()->isHoAdmin(), 403, 'Unauthorized. Only HO Team Admins can view legal entities.');

        $companies = Company::withCount(['locations', 'departments', 'categories', 'contractors'])->get();
        return view('masters.companies.index', compact('companies'));
    }

    public function switchCompany(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        $companyId = (int) $request->input('company_id');
        $user = Auth::user();

        if (!$this->companyContext->canUserSwitchCompany($user) && $user->company_id !== $companyId) {
            abort(403, 'You do not have permission to switch to this company.');
        }

        $success = $this->companyContext->setActiveCompanyId($companyId);
        if (!$success) {
            return back()->with('error', 'Unable to switch company. Company may be inactive.');
        }

        $company = Company::find($companyId);
        return back()->with('success', "Active company switched to {$company->name}.");
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized. Only HO Team Admins can register legal entities.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:50', 'unique:companies,code'],
            'cin_number' => ['nullable', 'string', 'max:50'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        Company::create($validated);

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        if (!Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized. Only HO Team Admins can modify legal entities.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:50', 'unique:companies,code,' . $company->id],
            'cin_number' => ['nullable', 'string', 'max:50'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }
}

