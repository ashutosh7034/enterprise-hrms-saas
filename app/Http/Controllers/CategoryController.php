<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(): View
    {
        $categories = Category::latest()->paginate(15);
        $activeCompany = $this->companyContext->getActiveCompany();

        return view('masters.categories.index', compact('categories', 'activeCompany'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('categories.index');
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
                Rule::unique('categories', 'code')->where('company_id', $activeCompanyId),
            ],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $validated['company_id'] = $activeCompanyId;

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Employee category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($category->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')
                    ->where('company_id', $activeCompanyId)
                    ->ignore($category->id),
            ],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Employee category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $activeCompanyId = $this->companyContext->getActiveCompanyId();

        if ($category->company_id !== $activeCompanyId) {
            abort(403, 'Unauthorized cross-company operation.');
        }

        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Employee category deactivated successfully.');
    }
}

