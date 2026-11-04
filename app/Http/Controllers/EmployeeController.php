<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Services\AuditLogService;
use App\Services\BlacklistMatchingService;
use App\Services\CompanyContextService;
use App\Services\EmployeeOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeOnboardingService $onboardingService,
        protected BlacklistMatchingService $blacklistService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Employee::with(['location', 'department', 'designation', 'category', 'contractor'])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('gate_pass_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('ekyc_status')) {
            $query->where('ekyc_status', $request->input('ekyc_status'));
        }

        if ($request->filled('employment_channel')) {
            $query->where('employment_channel', $request->input('employment_channel'));
        }

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('employees.index', compact('employees', 'departments', 'locations'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $designations = Designation::where('status', 'Active')->get();
        $categories = Category::where('company_id', $companyId)->where('status', 'Active')->get();
        $contractors = Contractor::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('employees.create', compact('locations', 'departments', 'designations', 'categories', 'contractors'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'date_of_birth' => ['required', 'date', 'before:today -18 years'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'joining_date' => ['required', 'date'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['required', Rule::exists('designations', 'id')],
            'employee_category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'onboarding_channel' => ['required', 'in:Direct,Contractor,Referral,Agency'],
            'contractor_id' => [
                'nullable',
                'required_if:onboarding_channel,Contractor',
                Rule::exists('contractors', 'id')->where('company_id', $companyId),
            ],
            'gate_pass_number' => ['nullable', 'string', 'max:50'],
            'aadhaar_number' => ['nullable', 'string', 'regex:/^[0-9\s]{12,14}$/'],
            'pan_number' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
        ], [
            'date_of_birth.before' => 'Employee must be at least 18 years of age.',
            'contractor_id.required_if' => 'Please select the contractor vendor when onboarding channel is Contractor.',
            'pan_number.regex' => 'The PAN number must follow the standard 10-character Indian format (e.g. ABCDE1234F).',
        ]);

        // 0. Check Tenant Subscription Employee Quota
        $licenseEntitlement = app(\App\Services\LicenseEntitlementService::class);
        $quota = $licenseEntitlement->canAddEmployee(companyId: $companyId);
        if (!$quota['allowed']) {
            return back()->withInput()->withErrors([
                'first_name' => $quota['message'],
            ])->with('danger', $quota['message']);
        }

        // 1. Check Central Cross-Company Blacklist
        $blacklistCheck = $this->blacklistService->checkIdentity($validated);
        if ($blacklistCheck['is_blocked']) {
            $reason = $blacklistCheck['matches'][0]['record']->reason_category ?? 'Violations';
            return back()->withInput()->withErrors([
                'first_name' => "ONBOARDING BLOCKED: Candidate matches an active entry on the Central Blacklist ({$reason}).",
            ])->with('danger', "Immediate Security Halt: Candidate matches Central Blacklist. Onboarding cannot proceed.");
        }

        // 2. Gate Pass Uniqueness Check (within current company)
        if (!empty($validated['gate_pass_number'])) {
            $gatePassExists = Employee::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('gate_pass_number', trim($validated['gate_pass_number']))
                ->exists();
            if ($gatePassExists) {
                return back()->withInput()->withErrors([
                    'gate_pass_number' => "Gate Pass '{$validated['gate_pass_number']}' is already assigned to another active employee in this company.",
                ]);
            }
        }

        // 3. Duplicate check across current active employees
        $duplicate = $this->onboardingService->checkDuplicate($companyId, $validated);
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'phone' => "Duplicate employee record detected: Existing active employee {$duplicate->employee_code} ({$duplicate->full_name}) shares matching identity tokens.",
            ]);
        }

        // 4. Create Employee using Onboarding Service
        $employee = DB::transaction(function () use ($validated, $companyId, $blacklistCheck) {
            $emp = $this->onboardingService->onboardCandidate($companyId, $validated, Auth::user());

            // If there's a potential fuzzy match on the blacklist, record an IdentityMatchReview for HO Review
            if ($blacklistCheck['match_found'] && !$blacklistCheck['is_blocked']) {
                $match = $blacklistCheck['matches'][0];
                $this->blacklistService->logMatchReview(
                    $emp->id,
                    $match['record']->id,
                    $match['type'],
                    $match['score'],
                    ['reason' => $match['reason'], 'field' => $match['field']]
                );
            }

            return $emp;
        });

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "Employee {$employee->employee_code} ({$employee->full_name}) onboarded successfully.");
    }

    public function show(Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $employee->load([
            'company',
            'location',
            'department',
            'designation',
            'category',
            'contractor',
            'documents.versions',
            'ekycAttempts.verifier',
            'gatePassHistories.changedByUser',
            'previousEmployments',
            'assetAllocations.asset.category',
            'damageAssessments.recoveryRecords',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $designations = Designation::where('status', 'Active')->get();
        $categories = Category::where('company_id', $companyId)->where('status', 'Active')->get();
        $contractors = Contractor::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('employees.edit', compact('employee', 'locations', 'departments', 'designations', 'categories', 'contractors'));
    }

    public function update(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'date_of_birth' => ['required', 'date', 'before:today -18 years'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'joining_date' => ['required', 'date'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['required', Rule::exists('designations', 'id')],
            'employee_category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'employment_status' => ['required', 'in:Active,Probation,Notice Period,Resigned,Terminated,Suspended'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'date_of_joining' => $validated['joining_date'],
            'location_id' => $validated['location_id'],
            'department_id' => $validated['department_id'],
            'designation_id' => $validated['designation_id'],
            'category_id' => $validated['employee_category_id'],
            'status' => $validated['employment_status'] === 'Probation' ? 'Active' : $validated['employment_status'],
            'address' => $validated['address'],
        ];

        $oldValues = $employee->only(array_keys($updateData));
        $employee->update($updateData);

        $this->auditLogService->log(
            action: 'Employee Record Updated',
            entityType: 'Employee',
            entityId: $employee->id,
            oldValues: $oldValues,
            newValues: $updateData,
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "Employee {$employee->employee_code} updated successfully.");
    }

    public function updateGatePass(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'gate_pass_number' => ['nullable', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->onboardingService->updateGatePass(
                $employee,
                $validated['gate_pass_number'] ? trim($validated['gate_pass_number']) : null,
                $validated['reason']
            );

            return redirect()->route('employees.show', $employee->id)
                ->with('success', 'Gate Pass updated and logged in employee history.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['gate_pass_number' => $e->getMessage()]);
        }
    }
}
