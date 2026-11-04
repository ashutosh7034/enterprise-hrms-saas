<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        $locations = Location::with('company')->orderBy('id', 'asc')->paginate(100);
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('masters.locations.index', compact('locations', 'activeCompany'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('locations.index');
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
                Rule::unique('locations', 'code')->where('company_id', $activeCompanyId),
            ],
            'state' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_site' => ['nullable', 'boolean'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $validated['company_id'] = $activeCompanyId;
        $validated['is_site'] = $request->boolean('is_site');

        Location::create($validated);

        return redirect()->route('locations.index')->with('success', 'Location created successfully.');
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($location->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations', 'code')
                    ->where('company_id', $activeCompanyId)
                    ->ignore($location->id),
            ],
            'state' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_site' => ['nullable', 'boolean'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $validated['is_site'] = $request->boolean('is_site');

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($location->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Location deactivated successfully.');
    }
}

