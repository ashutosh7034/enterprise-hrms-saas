<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankDetail;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeSkill;
use App\Models\EmployeeStatutoryDetail;
use App\Models\EmployeeVisaDetail;
use App\Models\Location;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use App\Services\EmployeeProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeSelfServiceProfileController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeProfileService $profileService,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display the authenticated employee's personal profile dashboard.
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee();

        if (!$employee) {
            return redirect()->route('dashboard.employee')
                ->with('warning', 'Your user account is not currently linked to an active employee dossier.');
        }

        $employee->load([
            'company',
            'location',
            'department',
            'designation',
            'category',
            'reportingManager',
            'addressRecord',
            'statutory',
            'bankDetail',
            'visaDetail',
            'familyMembers',
            'educations.verifier',
            'skills.assessor',
            'skills.histories',
            'documents.versions',
            'assetAllocations.asset.category',
            'profileHistories.changer',
        ]);

        $completeness = $this->profileService->calculateCompleteness($employee);

        return view('employees.ess.show', compact('employee', 'completeness'));
    }

    /**
     * Show the ESS self-service profile edit wizard.
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee();

        if (!$employee) {
            abort(404, 'Employee dossier not found.');
        }

        $employee->load([
            'addressRecord',
            'statutory',
            'bankDetail',
            'visaDetail',
            'familyMembers',
            'educations',
            'skills',
            'documents',
        ]);

        return view('employees.ess.edit', compact('employee'));
    }

    /**
     * Update permitted ESS fields.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee();

        if (!$employee) {
            abort(404, 'Employee dossier not found.');
        }

        if (!$request->filled('phone')) {
            $request->merge(['phone' => $employee->phone]);
        }

        $validated = $request->validate([
            // Basic & Personal
            'display_name' => ['nullable', 'string', 'max:150'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'identification_mark' => ['nullable', 'string', 'max:255'],
            'is_specially_abled' => ['nullable', 'boolean'],
            'disability_details' => ['nullable', 'string', 'max:255'],
            'personal_remarks' => ['nullable', 'string', 'max:500'],
            'marital_status' => ['nullable', 'in:Single,Married,Divorced,Widowed'],
            'marriage_anniversary_date' => ['nullable', 'date'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'spouse_name' => ['nullable', 'string', 'max:100'],
            'number_of_children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'children_in_hostel' => ['nullable', 'integer', 'min:0', 'max:20'],
            'religion' => ['nullable', 'string', 'max:50'],
            'caste' => ['nullable', 'string', 'max:50'],
            'sub_caste' => ['nullable', 'string', 'max:50'],
            'height_cm' => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:300'],

            // Contact
            'phone' => ['required', 'string', 'max:25'],
            'residential_phone' => ['nullable', 'string', 'max:25'],
            'personal_phone' => ['nullable', 'string', 'max:25'],
            'alternate_email' => ['nullable', 'email', 'max:191'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_address' => ['nullable', 'string'],

            // Address
            'current_flat_plot' => ['nullable', 'string', 'max:100'],
            'current_street' => ['nullable', 'string', 'max:150'],
            'current_area' => ['nullable', 'string', 'max:150'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'current_district' => ['nullable', 'string', 'max:100'],
            'current_state' => ['nullable', 'string', 'max:100'],
            'current_pincode' => ['nullable', 'string', 'max:20'],
            'current_country' => ['nullable', 'string', 'max:50'],
            'permanent_same_as_current' => ['nullable', 'boolean'],
            'permanent_flat_plot' => ['nullable', 'string', 'max:100'],
            'permanent_street' => ['nullable', 'string', 'max:150'],
            'permanent_area' => ['nullable', 'string', 'max:150'],
            'permanent_city' => ['nullable', 'string', 'max:100'],
            'permanent_district' => ['nullable', 'string', 'max:100'],
            'permanent_state' => ['nullable', 'string', 'max:100'],
            'permanent_pincode' => ['nullable', 'string', 'max:20'],
            'permanent_country' => ['nullable', 'string', 'max:50'],

            // Banking (Propose/Update)
            'bank_passbook_name' => ['nullable', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'bank_ifsc' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_type' => ['nullable', 'in:Savings,Salary,Current'],

            // Visa
            'visa_required' => ['nullable', 'boolean'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry_date' => ['nullable', 'date'],
            'visa_number' => ['nullable', 'string', 'max:50'],
            'visa_expiry_date' => ['nullable', 'date'],
        ]);

        $payload = [
            'display_name' => $validated['display_name'] ?? $employee->display_name,
            'blood_group' => $validated['blood_group'] ?? null,
            'identification_mark' => $validated['identification_mark'] ?? null,
            'is_specially_abled' => !empty($validated['is_specially_abled']),
            'disability_details' => $validated['disability_details'] ?? null,
            'personal_remarks' => $validated['personal_remarks'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'marriage_anniversary_date' => $validated['marriage_anniversary_date'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'spouse_name' => $validated['spouse_name'] ?? null,
            'number_of_children' => $validated['number_of_children'] ?? 0,
            'children_in_hostel' => $validated['children_in_hostel'] ?? 0,
            'religion' => $validated['religion'] ?? null,
            'caste' => $validated['caste'] ?? null,
            'sub_caste' => $validated['sub_caste'] ?? null,
            'height_cm' => $validated['height_cm'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'phone' => $validated['phone'],
            'residential_phone' => $validated['residential_phone'] ?? null,
            'personal_phone' => $validated['personal_phone'] ?? null,
            'alternate_email' => $validated['alternate_email'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $validated['emergency_contact_relation'] ?? null,
            'emergency_contact_address' => $validated['emergency_contact_address'] ?? null,

            'address' => [
                'current_flat_plot' => $validated['current_flat_plot'] ?? $request->input('curr_flat'),
                'current_street' => $validated['current_street'] ?? $request->input('curr_street'),
                'current_area' => $validated['current_area'] ?? $request->input('curr_area'),
                'current_city' => $validated['current_city'] ?? $request->input('curr_city'),
                'current_district' => $validated['current_district'] ?? $request->input('curr_dist'),
                'current_state' => $validated['current_state'] ?? $request->input('curr_state'),
                'current_pincode' => $validated['current_pincode'] ?? $request->input('curr_pin'),
                'current_country' => $validated['current_country'] ?? 'India',
                'permanent_same_as_current' => !empty($validated['permanent_same_as_current']),
                'permanent_flat_plot' => $validated['permanent_flat_plot'] ?? $request->input('perm_flat'),
                'permanent_street' => $validated['permanent_street'] ?? $request->input('perm_street'),
                'permanent_area' => $validated['permanent_area'] ?? $request->input('perm_area'),
                'permanent_city' => $validated['permanent_city'] ?? $request->input('perm_city'),
                'permanent_district' => $validated['permanent_district'] ?? $request->input('perm_dist'),
                'permanent_state' => $validated['permanent_state'] ?? $request->input('perm_state'),
                'permanent_pincode' => $validated['permanent_pincode'] ?? $request->input('perm_pin'),
                'permanent_country' => $validated['permanent_country'] ?? 'India',
            ],

            'bank' => [
                'bank_passbook_name' => $validated['bank_passbook_name'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'bank_ifsc' => !empty($validated['bank_ifsc']) ? strtoupper($validated['bank_ifsc']) : null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_type' => $validated['bank_account_type'] ?? 'Salary',
            ],

            'visa' => [
                'visa_required' => !empty($validated['visa_required']),
                'passport_number' => $validated['passport_number'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'visa_number' => $validated['visa_number'] ?? null,
                'visa_expiry_date' => $validated['visa_expiry_date'] ?? null,
            ],
        ];

        // Handle Profile Photo Upload
        if ($request->hasFile('profile_photo')) {
            $photo = $request->file('profile_photo');
            $path = $photo->store("profile_photos/{$employee->company_id}", 'public');
            $payload['profile_photo_path'] = $path;
        }

        $this->profileService->saveProfile($employee, $payload, $user, 'Self-Service Profile Update');

        if ($request->input('action') === 'submit_review') {
            $this->profileService->submitForReview($employee, $user);
            return redirect()->route('ess.profile.show')
                ->with('success', 'Your profile details have been saved and submitted to HR for formal verification.');
        }

        return redirect()->route('ess.profile.show')
            ->with('success', 'Profile draft updated successfully.');
    }

    /**
     * Submit profile for review via dedicated action.
     */
    public function submitForReview(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee();

        if (!$employee) {
            abort(404, 'Employee dossier not found.');
        }

        $this->profileService->submitForReview($employee, $user);

        return redirect()->route('ess.profile.show')
            ->with('success', 'Your profile has been submitted for HR verification.');
    }
}

