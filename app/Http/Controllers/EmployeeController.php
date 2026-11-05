<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeSkill;
use App\Models\Location;
use App\Models\OnboardingDraft;
use App\Services\AuditLogService;
use App\Services\BlacklistMatchingService;
use App\Services\CompanyContextService;
use App\Services\EmployeeOnboardingService;
use App\Services\EmployeeProfileService;
use App\Services\OnboardingDraftService;
use App\Services\OnboardingMasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeOnboardingService $onboardingService,
        protected EmployeeProfileService $profileService,
        protected BlacklistMatchingService $blacklistService,
        protected AuditLogService $auditLogService,
        protected OnboardingMasterService $masterService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Employee::with([
            'location',
            'joiningLocation',
            'department',
            'designation',
            'category',
            'contractor',
            'reportingManager',
            'company',
            'userAccount',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhere('gate_pass_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->input('designation_id'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employee_type', $request->input('employment_type'));
        }

        if ($request->filled('confirmation_status')) {
            $query->where('confirmation_status', $request->input('confirmation_status'));
        }

        if ($request->filled('reporting_manager_id')) {
            $query->where('reporting_manager_id', $request->input('reporting_manager_id'));
        }

        if ($request->filled('doj_from')) {
            $query->whereDate('date_of_joining', '>=', $request->input('doj_from'));
        }

        if ($request->filled('doj_to')) {
            $query->whereDate('date_of_joining', '<=', $request->input('doj_to'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->input('onboarding_status'));
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
        $designations = Designation::where('status', 'Active')->get();
        $managers = Employee::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('employees.index', compact('employees', 'departments', 'locations', 'designations', 'managers'));
    }

    public function create(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $designations = Designation::where('status', 'Active')->get();
        $categories = Category::where('company_id', $companyId)->where('status', 'Active')->get();
        $contractors = Contractor::where('company_id', $companyId)->where('status', 'Active')->get();
        $managers = Employee::where('company_id', $companyId)->where('status', 'Active')->get();
        $masters = $this->masterService->getActiveMastersGrouped($companyId, $tenantId);

        // Resume draft if draft_id is supplied
        $draft = null;
        if ($request->filled('draft_id')) {
            $draft = OnboardingDraft::where('company_id', $companyId)
                ->where('draft_id', $request->query('draft_id'))
                ->first();
        }

        $activeDraftsCount = OnboardingDraft::where('company_id', $companyId)
            ->where('status', 'draft')
            ->count();

        return view('employees.create', compact(
            'locations',
            'departments',
            'designations',
            'categories',
            'contractors',
            'managers',
            'masters',
            'draft',
            'activeDraftsCount'
        ));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();

        // 1. If action is draft fallback save, persist as draft without blocking required field validations
        if ($request->input('action') === 'draft') {
            $draft = app(OnboardingDraftService::class)->saveDraft(
                payload: $request->all(),
                draftId: $request->input('draft_id'),
                companyId: $companyId,
                tenantId: $tenantId,
                user: Auth::user(),
                expectedVersion: $request->filled('draft_version') ? (int)$request->input('draft_version') : null
            );

            return redirect()->route('employees.create', ['draft_id' => $draft->draft_id])
                ->with('success', "Onboarding draft {$draft->draft_id} saved successfully.");
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today -18 years'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:191'],
            'joining_date' => ['required', 'date'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['required', Rule::exists('designations', 'id')],
            'employee_category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'onboarding_channel' => ['required', 'string', 'max:50'],
            'contractor_id' => [
                'nullable',
                Rule::requiredIf(fn() => $request->input('onboarding_channel') === 'Contractor'),
                Rule::exists('contractors', 'id')->where('company_id', $companyId),
            ],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'initial_employment_status' => ['nullable', 'string', 'max:50'],
            'probation_start_date' => ['nullable', 'date'],
            'probation_end_date' => ['nullable', 'date'],
            'confirmation_due_date' => ['nullable', 'date'],
            'reporting_manager_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'manual_employee_code' => ['nullable', 'boolean'],
            'custom_employee_code' => ['nullable', 'string', 'max:50'],
            'gate_pass_number' => ['nullable', 'string', 'max:50'],
            'aadhaar_number' => ['nullable', 'string', 'regex:/^[0-9\s]{12,14}$/'],
            'pan_number' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'documents' => ['nullable', 'array'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.*.document_type' => ['nullable', 'string', 'max:150'],
            'documents.*.category' => ['nullable', 'string', 'max:100'],
            'documents.*.expiry_date' => ['nullable', 'date'],
            'documents.*.title' => ['nullable', 'string', 'max:255'],
            'documents.*.remarks' => ['nullable', 'string', 'max:500'],
        ];

        $validated = $request->validate($rules, [
            'first_name.required' => 'The first name is required for final onboarding submission.',
            'last_name.required' => 'The last name is required for final onboarding submission.',
            'date_of_birth.before' => 'Employee must be at least 18 years of age.',
            'contractor_id.required' => 'Please select the contractor vendor when onboarding channel is Contractor.',
            'pan_number.regex' => 'The PAN number must follow the standard 10-character Indian format (e.g. ABCDE1234F).',
            'documents.*.file.max' => 'Document attachments cannot exceed 5MB.',
            'documents.*.file.mimes' => 'Document attachments must be a PDF, JPG, JPEG, or PNG file.',
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

        // 4. Create Employee and Extended Profile in a transaction
        $employee = DB::transaction(function () use ($validated, $companyId, $blacklistCheck, $request) {
            $emp = $this->onboardingService->onboardCandidate($companyId, $validated, Auth::user());

            // Handle Photo upload
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store("profile_photos/{$companyId}", 'public');
                $emp->update(['profile_photo_path' => $path]);
            }

            // If submitted from an existing draft, link draft and adopt draft attachments
            if ($request->filled('draft_id')) {
                $draft = OnboardingDraft::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('draft_id', $request->input('draft_id'))
                    ->first();

                if ($draft) {
                    if (empty($emp->profile_photo_path) && !empty($draft->uploaded_files['profile_photo']['path'])) {
                        $emp->update(['profile_photo_path' => $draft->uploaded_files['profile_photo']['path']]);
                    }

                    if (!empty($draft->uploaded_files['documents']) && is_array($draft->uploaded_files['documents'])) {
                        foreach ($draft->uploaded_files['documents'] as $dDoc) {
                            $docType = $dDoc['type'] ?? 'General';
                            $category = $dDoc['category'] ?? 'Identity';
                            $filePath = $dDoc['path'] ?? null;
                            if ($filePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                                $empDoc = EmployeeDocument::create([
                                    'employee_id' => $emp->id,
                                    'document_type' => $docType,
                                    'document_category' => $category,
                                    'file_path' => $filePath,
                                    'original_name' => $dDoc['fileName'] ?? basename($filePath),
                                    'mime_type' => ($dDoc['isPdf'] ?? false) ? 'application/pdf' : 'image/jpeg',
                                    'file_size_kb' => max(1, (int) ceil(($dDoc['fileSize'] ?? 1024) / 1024)),
                                    'is_mandatory' => in_array($docType, ['Aadhaar Card', 'PAN Card']),
                                    'verification_status' => 'Pending',
                                    'expiry_date' => $dDoc['expiry'] ?? null,
                                    'remarks' => $dDoc['title'] ?? null,
                                    'version' => 1,
                                    'uploaded_by' => Auth::id(),
                                ]);

                                EmployeeDocumentVersion::create([
                                    'employee_document_id' => $empDoc->id,
                                    'version' => 1,
                                    'file_path' => $filePath,
                                    'original_name' => $dDoc['fileName'] ?? basename($filePath),
                                    'mime_type' => ($dDoc['isPdf'] ?? false) ? 'application/pdf' : 'image/jpeg',
                                    'file_size_kb' => max(1, (int) ceil(($dDoc['fileSize'] ?? 1024) / 1024)),
                                    'uploaded_by' => Auth::id(),
                                ]);
                            }
                        }
                    }

                    $draft->update([
                        'status' => 'submitted',
                        'employee_id' => $emp->id,
                        'submitted_at' => now(),
                    ]);
                }
            }

            // Build Profile Data from 16-section request
            $profileData = [
                'title' => $request->input('title'),
                'ticket_number' => $request->input('ticket_number'),
                'display_name' => $request->input('display_name'),
                'aka_name' => $request->input('aka_name'),
                'nationality' => $request->input('nationality', 'Indian'),
                'place_of_birth' => $request->input('place_of_birth'),
                'blood_group' => $request->input('blood_group'),
                'identification_mark' => $request->input('identification_mark'),
                'is_specially_abled' => $request->boolean('is_specially_abled'),
                'disability_details' => $request->input('disability_details'),
                'personal_remarks' => $request->input('personal_remarks'),

                'job_post_code' => $request->input('job_post_code'),
                'grade' => $request->input('grade'),
                'division' => $request->input('division'),
                'unit' => $request->input('unit'),
                'project' => $request->input('project'),
                'skill_level' => $request->input('skill_level', 'Skilled'),
                'reporting_manager_id' => $request->input('reporting_manager_id'),
                'payment_mode' => $request->input('payment_mode', 'Bank Transfer'),
                'jv_code_cr' => $request->input('jv_code_cr'),
                'old_employee_code' => $request->input('old_employee_code'),

                'residential_phone' => $request->input('residential_phone'),
                'personal_phone' => $request->input('personal_phone'),
                'office_phone' => $request->input('office_phone'),
                'extension_number' => $request->input('extension_number'),
                'alternate_email' => $request->input('alternate_email'),
                'cc_email' => $request->input('cc_email'),
                'microsoft_user' => $request->input('microsoft_user'),
                'google_user' => $request->input('google_user'),

                'marital_status' => $request->input('marital_status'),
                'marriage_anniversary_date' => $request->input('marriage_anniversary_date'),
                'father_name' => $request->input('father_name'),
                'mother_name' => $request->input('mother_name'),
                'spouse_name' => $request->input('spouse_name'),
                'number_of_children' => $request->input('number_of_children', 0),
                'children_in_hostel' => $request->input('children_in_hostel', 0),
                'emergency_contact_name' => $request->input('emergency_contact_name'),
                'emergency_contact_phone' => $request->input('emergency_contact_phone'),
                'emergency_contact_relation' => $request->input('emergency_contact_relation'),
                'emergency_contact_address' => $request->input('emergency_contact_address'),

                'religion' => $request->input('religion'),
                'caste' => $request->input('caste'),
                'sub_caste' => $request->input('sub_caste'),
                'height_cm' => $request->input('height_cm'),
                'weight_kg' => $request->input('weight_kg'),
                'health_checkup_date' => $request->input('health_checkup_date'),
                'custom_fields' => $request->input('custom_fields'),

                'address' => [
                    'current_flat_plot' => $request->input('current_flat_plot') ?? $request->input('curr_flat'),
                    'current_street' => $request->input('current_street') ?? $request->input('curr_street'),
                    'current_area' => $request->input('current_area') ?? $request->input('curr_area'),
                    'current_city' => $request->input('current_city') ?? $request->input('curr_city'),
                    'current_district' => $request->input('current_district') ?? $request->input('curr_dist'),
                    'current_state' => $request->input('current_state') ?? $request->input('curr_state'),
                    'current_pincode' => $request->input('current_pincode') ?? $request->input('curr_pin'),
                    'current_country' => $request->input('current_country', 'India'),
                    'permanent_same_as_current' => $request->boolean('permanent_same_as_current'),
                    'permanent_flat_plot' => $request->input('permanent_flat_plot') ?? $request->input('perm_flat'),
                    'permanent_street' => $request->input('permanent_street') ?? $request->input('perm_street'),
                    'permanent_area' => $request->input('permanent_area') ?? $request->input('perm_area'),
                    'permanent_city' => $request->input('permanent_city') ?? $request->input('perm_city'),
                    'permanent_district' => $request->input('permanent_district') ?? $request->input('perm_dist'),
                    'permanent_state' => $request->input('permanent_state') ?? $request->input('perm_state'),
                    'permanent_pincode' => $request->input('permanent_pincode') ?? $request->input('perm_pin'),
                    'permanent_country' => $request->input('permanent_country', 'India'),
                ],

                'statutory' => [
                    'uan_number' => $request->input('uan_number'),
                    'gratuity_code' => $request->input('gratuity_code'),
                    'pran' => $request->input('pran'),
                    'has_pt' => $request->boolean('has_pt', true),
                    'pt_number' => $request->input('pt_number'),
                    'has_pf' => $request->boolean('has_pf', true),
                    'pf_join_date' => $request->input('pf_join_date'),
                    'pf_number' => $request->input('pf_number'),
                    'fpf_number' => $request->input('fpf_number'),
                    'pf_new_version' => $request->input('pf_new_version'),
                    'vpf_percentage' => $request->input('vpf_percentage'),
                    'pf_remarks' => $request->input('pf_remarks'),
                    'pf_nationality' => $request->input('pf_nationality'),
                    'pf_country' => $request->input('pf_country'),
                    'is_international_worker' => $request->boolean('is_international_worker'),
                    'pf_previous_member_id' => $request->input('pf_previous_member_id'),
                    'npr_number' => $request->input('npr_number'),
                    'election_card_number' => $request->input('election_card_number'),
                    'ration_card_number' => $request->input('ration_card_number'),
                    'statutory_education_qualification' => $request->input('statutory_education_qualification'),
                    'has_esic' => $request->boolean('has_esic', true),
                    'esic_number' => $request->input('esic_number'),
                    'esic_old_number' => $request->input('esic_old_number'),
                    'esic_imp_code' => $request->input('esic_imp_code'),
                    'esic_imp_name' => $request->input('esic_imp_name'),
                ],

                'bank' => [
                    'bank_passbook_name' => $request->input('bank_passbook_name'),
                    'pan_card_name' => $request->input('pan_card_name'),
                    'aadhaar_card_name' => $request->input('aadhaar_card_name'),
                    'bank_name' => $request->input('bank_name'),
                    'bank_branch' => $request->input('bank_branch'),
                    'bank_ifsc' => $request->input('bank_ifsc') ? strtoupper($request->input('bank_ifsc')) : null,
                    'bank_account_number' => $request->input('bank_account_number'),
                    'bank_account_type' => $request->input('bank_account_type', 'Salary'),
                    'payment_mode' => $request->input('payment_mode', 'Bank Transfer'),
                ],

                'visa' => [
                    'visa_required' => $request->boolean('visa_required'),
                    'visa_type' => $request->input('visa_type'),
                    'visa_number' => $request->input('visa_number'),
                    'passport_number' => $request->input('passport_number'),
                    'passport_issue_date' => $request->input('passport_issue_date'),
                    'passport_expiry_date' => $request->input('passport_expiry_date'),
                    'passport_issuing_country' => $request->input('passport_issuing_country'),
                    'visa_issue_date' => $request->input('visa_issue_date'),
                    'visa_expiry_date' => $request->input('visa_expiry_date'),
                    'visa_issuing_country' => $request->input('visa_issuing_country'),
                    'immigration_status' => $request->input('immigration_status'),
                    'work_permit_number' => $request->input('work_permit_number'),
                    'work_permit_expiry_date' => $request->input('work_permit_expiry_date'),
                    'country_of_employment' => $request->input('country_of_employment'),
                    'visa_remarks' => $request->input('visa_remarks'),
                ],
            ];

            $this->profileService->saveProfile($emp, $profileData, Auth::user(), 'Initial Profile Registration');

            // 5. Handle Document Attachments (Step 12)
            if ($request->has('documents') && is_array($request->input('documents'))) {
                foreach ($request->input('documents') as $idx => $docData) {
                    if ($request->hasFile("documents.{$idx}.file")) {
                        $file = $request->file("documents.{$idx}.file");
                        if ($file && $file->isValid()) {
                            $origName = $file->getClientOriginalName();
                            $fileSize = $file->getSize();
                            $mimeType = $file->getMimeType();
                            $docType = !empty($docData['document_type']) ? trim($docData['document_type']) : 'Other Document';
                            $category = !empty($docData['category']) ? trim($docData['category']) : $this->determineDocumentCategory($docType);
                            $expiryDate = !empty($docData['expiry_date']) ? $docData['expiry_date'] : null;
                            $remarks = !empty($docData['remarks']) ? $docData['remarks'] : (!empty($docData['title']) ? $docData['title'] : null);

                            $storedPath = $file->store("private/employee_documents/{$companyId}/{$emp->id}", 'local');
                            $fileSizeKb = max(1, (int) ceil($fileSize / 1024));

                            $empDoc = EmployeeDocument::create([
                                'employee_id' => $emp->id,
                                'document_type' => $docType,
                                'document_category' => $category,
                                'file_path' => $storedPath,
                                'original_name' => $origName,
                                'mime_type' => $mimeType,
                                'file_size_kb' => $fileSizeKb,
                                'is_mandatory' => in_array($docType, ['Aadhaar Card', 'PAN Card']),
                                'verification_status' => 'Pending',
                                'expiry_date' => $expiryDate,
                                'remarks' => $remarks,
                                'version' => 1,
                                'uploaded_by' => Auth::id(),
                            ]);

                            EmployeeDocumentVersion::create([
                                'employee_document_id' => $empDoc->id,
                                'version' => 1,
                                'file_path' => $storedPath,
                                'original_name' => $origName,
                                'mime_type' => $mimeType,
                                'file_size_kb' => $fileSizeKb,
                                'uploaded_by' => Auth::id(),
                            ]);

                            $this->auditLogService->log(
                                action: 'Employee Document Attached on Onboarding',
                                entityType: 'EmployeeDocument',
                                entityId: $empDoc->id,
                                oldValues: [],
                                newValues: [
                                    'employee_code' => $emp->employee_code,
                                    'document_type' => $docType,
                                    'category' => $category,
                                    'original_name' => $origName,
                                ],
                                companyId: $companyId,
                                actor: Auth::user()
                            );
                        }
                    }
                }

                // Recalculate profile completeness with attached documents
                $this->profileService->calculateCompleteness($emp);
            }

            // If fuzzy match on blacklist, log IdentityMatchReview
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

        $msg = "Employee {$employee->employee_code} ({$employee->full_name}) onboarded successfully.";

        return redirect()->route('employees.show', $employee->id)->with('success', $msg);
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
            'reportingManager',
            'addressRecord',
            'statutory',
            'bankDetail',
            'visaDetail',
            'familyMembers',
            'educations.verifier',
            'skills.assessor',
            'skills.histories.changer',
            'documents.versions',
            'ekycAttempts.verifier',
            'gatePassHistories.changedByUser',
            'previousEmployments',
            'assetAllocations.asset.category',
            'damageAssessments.recoveryRecords',
            'joiningLocation',
            'joiningDepartment',
            'joiningDesignation',
            'conversions.fromDesignation',
            'conversions.toDesignation',
            'conversions.fromDepartment',
            'conversions.toDepartment',
            'conversions.fromLocation',
            'conversions.toLocation',
            'conversions.initiator',
            'branchTransfers.fromLocation',
            'branchTransfers.toLocation',
            'branchTransfers.fromDepartment',
            'branchTransfers.toDepartment',
            'branchTransfers.fromDesignation',
            'branchTransfers.toDesignation',
            'branchTransfers.fromReportingManager',
            'branchTransfers.toReportingManager',
            'branchTransfers.initiator',
            'confirmationRecords.approver',
            'lifecycleEvents.requester',
            'lifecycleEvents.approver',
            'profileHistories.changer',
            'userAccount',
        ]);

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $designations = Designation::where('status', 'Active')->get();
        $managers = Employee::where('company_id', $companyId)->where('status', 'Active')->where('id', '!=', $employee->id)->get();

        $completeness = $this->profileService->calculateCompleteness($employee);

        return view('employees.show', compact('employee', 'completeness', 'locations', 'departments', 'designations', 'managers'));
    }

    public function edit(Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $tenantId = $this->companyContext->getActiveTenantId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $employee->load([
            'addressRecord',
            'statutory',
            'bankDetail',
            'visaDetail',
            'familyMembers',
            'educations',
            'skills',
            'userAccount',
        ]);

        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->get();
        $designations = Designation::where('status', 'Active')->get();
        $categories = Category::where('company_id', $companyId)->where('status', 'Active')->get();
        $contractors = Contractor::where('company_id', $companyId)->where('status', 'Active')->get();
        $managers = Employee::where('company_id', $companyId)->where('id', '!=', $employee->id)->where('status', 'Active')->get();
        $masters = $this->masterService->getActiveMastersGrouped($companyId, $tenantId);

        return view('employees.edit', compact(
            'employee',
            'locations',
            'departments',
            'designations',
            'categories',
            'contractors',
            'managers',
            'masters'
        ));
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
            'gender' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today -18 years'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:191'],
            'joining_date' => ['required', 'date'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['required', Rule::exists('designations', 'id')],
            'employee_category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'employment_status' => ['required', 'in:Draft,Pending Verification,Active,Inactive,Resigned,Terminated,Transferred,Probation'],
            'change_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'date_of_joining' => $validated['joining_date'],
            'location_id' => $validated['location_id'],
            'department_id' => $validated['department_id'],
            'designation_id' => $validated['designation_id'],
            'category_id' => $validated['employee_category_id'],
            'status' => $validated['employment_status'] === 'Probation' ? 'Active' : $validated['employment_status'],

            'title' => $request->input('title'),
            'ticket_number' => $request->input('ticket_number'),
            'display_name' => $request->input('display_name'),
            'aka_name' => $request->input('aka_name'),
            'nationality' => $request->input('nationality', 'Indian'),
            'place_of_birth' => $request->input('place_of_birth'),
            'blood_group' => $request->input('blood_group'),
            'identification_mark' => $request->input('identification_mark'),
            'is_specially_abled' => $request->boolean('is_specially_abled'),
            'disability_details' => $request->input('disability_details'),
            'personal_remarks' => $request->input('personal_remarks'),

            'job_post_code' => $request->input('job_post_code'),
            'grade' => $request->input('grade'),
            'division' => $request->input('division'),
            'unit' => $request->input('unit'),
            'project' => $request->input('project'),
            'skill_level' => $request->input('skill_level', 'Skilled'),
            'reporting_manager_id' => $request->input('reporting_manager_id'),
            'payment_mode' => $request->input('payment_mode', 'Bank Transfer'),
            'jv_code_cr' => $request->input('jv_code_cr'),
            'old_employee_code' => $request->input('old_employee_code'),
            'is_account_frozen' => $request->boolean('is_account_frozen'),

            'residential_phone' => $request->input('residential_phone'),
            'personal_phone' => $request->input('personal_phone'),
            'office_phone' => $request->input('office_phone'),
            'extension_number' => $request->input('extension_number'),
            'alternate_email' => $request->input('alternate_email'),
            'cc_email' => $request->input('cc_email'),
            'microsoft_user' => $request->input('microsoft_user'),
            'google_user' => $request->input('google_user'),

            'marital_status' => $request->input('marital_status'),
            'marriage_anniversary_date' => $request->input('marriage_anniversary_date'),
            'father_name' => $request->input('father_name'),
            'mother_name' => $request->input('mother_name'),
            'spouse_name' => $request->input('spouse_name'),
            'number_of_children' => $request->input('number_of_children', 0),
            'children_in_hostel' => $request->input('children_in_hostel', 0),
            'emergency_contact_name' => $request->input('emergency_contact_name'),
            'emergency_contact_phone' => $request->input('emergency_contact_phone'),
            'emergency_contact_relation' => $request->input('emergency_contact_relation'),
            'emergency_contact_address' => $request->input('emergency_contact_address'),

            'religion' => $request->input('religion'),
            'caste' => $request->input('caste'),
            'sub_caste' => $request->input('sub_caste'),
            'height_cm' => $request->input('height_cm'),
            'weight_kg' => $request->input('weight_kg'),
            'health_checkup_date' => $request->input('health_checkup_date'),
            'custom_fields' => $request->input('custom_fields'),

            'address' => [
                'current_flat_plot' => $request->input('current_flat_plot') ?? $request->input('curr_flat'),
                'current_street' => $request->input('current_street') ?? $request->input('curr_street'),
                'current_area' => $request->input('current_area') ?? $request->input('curr_area'),
                'current_city' => $request->input('current_city') ?? $request->input('curr_city'),
                'current_district' => $request->input('current_district') ?? $request->input('curr_dist'),
                'current_state' => $request->input('current_state') ?? $request->input('curr_state'),
                'current_pincode' => $request->input('current_pincode') ?? $request->input('curr_pin'),
                'current_country' => $request->input('current_country', 'India'),
                'permanent_same_as_current' => $request->boolean('permanent_same_as_current'),
                'permanent_flat_plot' => $request->input('permanent_flat_plot') ?? $request->input('perm_flat'),
                'permanent_street' => $request->input('permanent_street') ?? $request->input('perm_street'),
                'permanent_area' => $request->input('permanent_area') ?? $request->input('perm_area'),
                'permanent_city' => $request->input('permanent_city') ?? $request->input('perm_city'),
                'permanent_district' => $request->input('permanent_district') ?? $request->input('perm_dist'),
                'permanent_state' => $request->input('permanent_state') ?? $request->input('perm_state'),
                'permanent_pincode' => $request->input('permanent_pincode') ?? $request->input('perm_pin'),
                'permanent_country' => $request->input('permanent_country', 'India'),
            ],

            'statutory' => [
                'uan_number' => $request->input('uan_number'),
                'gratuity_code' => $request->input('gratuity_code'),
                'pran' => $request->input('pran'),
                'has_pt' => $request->boolean('has_pt', true),
                'pt_number' => $request->input('pt_number'),
                'has_pf' => $request->boolean('has_pf', true),
                'pf_join_date' => $request->input('pf_join_date'),
                'pf_number' => $request->input('pf_number'),
                'fpf_number' => $request->input('fpf_number'),
                'pf_new_version' => $request->input('pf_new_version'),
                'vpf_percentage' => $request->input('vpf_percentage'),
                'pf_remarks' => $request->input('pf_remarks'),
                'pf_nationality' => $request->input('pf_nationality'),
                'pf_country' => $request->input('pf_country'),
                'is_international_worker' => $request->boolean('is_international_worker'),
                'pf_previous_member_id' => $request->input('pf_previous_member_id'),
                'npr_number' => $request->input('npr_number'),
                'election_card_number' => $request->input('election_card_number'),
                'ration_card_number' => $request->input('ration_card_number'),
                'statutory_education_qualification' => $request->input('statutory_education_qualification'),
                'has_esic' => $request->boolean('has_esic', true),
                'esic_number' => $request->input('esic_number'),
                'esic_old_number' => $request->input('esic_old_number'),
                'esic_imp_code' => $request->input('esic_imp_code'),
                'esic_imp_name' => $request->input('esic_imp_name'),
            ],

            'bank' => [
                'bank_passbook_name' => $request->input('bank_passbook_name'),
                'pan_card_name' => $request->input('pan_card_name'),
                'aadhaar_card_name' => $request->input('aadhaar_card_name'),
                'bank_name' => $request->input('bank_name'),
                'bank_branch' => $request->input('bank_branch'),
                'bank_ifsc' => $request->input('bank_ifsc') ? strtoupper($request->input('bank_ifsc')) : null,
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_account_type' => $request->input('bank_account_type', 'Salary'),
                'payment_mode' => $request->input('payment_mode', 'Bank Transfer'),
            ],

            'visa' => [
                'visa_required' => $request->boolean('visa_required'),
                'visa_type' => $request->input('visa_type'),
                'visa_number' => $request->input('visa_number'),
                'passport_number' => $request->input('passport_number'),
                'passport_issue_date' => $request->input('passport_issue_date'),
                'passport_expiry_date' => $request->input('passport_expiry_date'),
                'passport_issuing_country' => $request->input('passport_issuing_country'),
                'visa_issue_date' => $request->input('visa_issue_date'),
                'visa_expiry_date' => $request->input('visa_expiry_date'),
                'visa_issuing_country' => $request->input('visa_issuing_country'),
                'immigration_status' => $request->input('immigration_status'),
                'work_permit_number' => $request->input('work_permit_number'),
                'work_permit_expiry_date' => $request->input('work_permit_expiry_date'),
                'country_of_employment' => $request->input('country_of_employment'),
                'visa_remarks' => $request->input('visa_remarks'),
            ],
        ];

        // Handle Photo upload
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store("profile_photos/{$companyId}", 'public');
            $payload['profile_photo_path'] = $path;
        }

        $reason = $validated['change_reason'] ?? 'HR Administrator Dossier Update';
        $this->profileService->saveProfile($employee, $payload, Auth::user(), $reason);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "Employee {$employee->employee_code} updated successfully.");
    }

    /**
     * Approve employee profile (HR / Admin).
     */
    public function approve(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $remarks = $request->input('remarks', 'Profile approved and verified by HR.');
        $this->profileService->approveProfile($employee, Auth::user(), $remarks);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "Employee {$employee->employee_code} profile has been approved.");
    }

    /**
     * Request corrections from employee (HR / Admin).
     */
    public function requestCorrection(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'correction_notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->profileService->requestCorrections($employee, Auth::user(), $validated['correction_notes']);

        return redirect()->route('employees.show', $employee->id)
            ->with('warning', "Correction request sent to {$employee->full_name}.");
    }

    /**
     * Provision ESS Login Access.
     */
    public function provisionAccess(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $password = $request->input('password');
        $user = $this->profileService->provisionAccountAccess($employee, Auth::user(), $password);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "ESS Login Account for {$user->name} ({$user->email}) is active.");
    }

    /**
     * Add Repeatable Family Member.
     */
    public function addFamilyMember(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        if (!$request->has('name') && $request->filled('full_name')) {
            $request->merge(['name' => $request->input('full_name')]);
        }
        if (!$request->has('contact_number') && $request->filled('phone')) {
            $request->merge(['contact_number' => $request->input('phone')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'relationship' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'contact_number' => ['nullable', 'string', 'max:25'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'is_dependent' => ['nullable', 'boolean'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['is_dependent'] = !empty($validated['is_dependent']);
        $validated['is_emergency_contact'] = !empty($validated['is_emergency_contact']);

        EmployeeFamilyMember::create($validated);
        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Family member added successfully.');
    }

    /**
     * Delete Family Member.
     */
    public function deleteFamilyMember(Employee $employee, EmployeeFamilyMember $familyMember)
    {
        if ($familyMember->employee_id !== $employee->id) {
            abort(403);
        }

        $familyMember->delete();
        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Family member removed.');
    }

    /**
     * Add Repeatable Education Record.
     */
    public function addEducation(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        if (!$request->has('qualification_level') && $request->filled('degree_level')) {
            $request->merge(['qualification_level' => $request->input('degree_level')]);
        }
        if (!$request->has('institution_name') && $request->filled('institute_name')) {
            $request->merge(['institution_name' => $request->input('institute_name')]);
        }
        if (!$request->has('passing_year') && $request->filled('year_of_passing')) {
            $request->merge(['passing_year' => $request->input('year_of_passing')]);
        }
        if ($request->has('percentage_or_cgpa')) {
            $request->merge(['percentage_or_cgpa' => (string) $request->input('percentage_or_cgpa')]);
        }

        $validated = $request->validate([
            'qualification_level' => ['required', 'string', 'max:50'],
            'qualification_name' => ['required', 'string', 'max:150'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'institution_name' => ['required', 'string', 'max:200'],
            'board_university' => ['nullable', 'string', 'max:200'],
            'passing_year' => ['nullable', 'integer', 'min:1950', 'max:2030'],
            'percentage_or_cgpa' => ['nullable', 'max:20'],
            'grade' => ['nullable', 'string', 'max:20'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $certPath = null;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store("private/education_certs/{$companyId}/{$employee->id}", 'local');
        }

        EmployeeEducation::create([
            'employee_id' => $employee->id,
            'qualification_level' => $validated['qualification_level'],
            'qualification_name' => $validated['qualification_name'],
            'specialization' => $validated['specialization'] ?? null,
            'institution_name' => $validated['institution_name'],
            'board_university' => $validated['board_university'] ?? null,
            'passing_year' => $validated['passing_year'] ?? null,
            'percentage_or_cgpa' => $validated['percentage_or_cgpa'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'certificate_file_path' => $certPath,
            'is_verified' => true,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Education record added successfully.');
    }

    /**
     * Delete Education Record.
     */
    public function deleteEducation(Employee $employee, EmployeeEducation $education)
    {
        if ($education->employee_id !== $employee->id) {
            abort(403);
        }

        $education->delete();
        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Education record removed.');
    }

    /**
     * Add or Update Employee Skill.
     */
    public function addSkill(Request $request, Employee $employee)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($employee->company_id !== $companyId && !Auth::user()->isHoAdmin()) {
            abort(403, 'Unauthorized company boundary access.');
        }

        if (!$request->has('has_certification') && $request->has('is_certified')) {
            $request->merge(['has_certification' => $request->boolean('is_certified')]);
        }
        if (!$request->has('assessment_remarks') && $request->filled('remarks')) {
            $request->merge(['assessment_remarks' => $request->input('remarks')]);
        }

        $validated = $request->validate([
            'skill_id' => ['nullable', 'exists:employee_skills,id'],
            'skill_name' => ['required', 'string', 'max:150'],
            'skill_category' => ['required', 'string', 'max:100'],
            'proficiency_level' => ['required', 'in:Beginner,Intermediate,Advanced,Expert'],
            'proficiency_score' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'years_of_experience' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'has_certification' => ['nullable', 'boolean'],
            'is_certified' => ['nullable', 'boolean'],
            'certification_name' => ['nullable', 'string', 'max:150'],
            'certification_number' => ['nullable', 'string', 'max:100'],
            'assessment_remarks' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $skillData = [
            'id' => $validated['skill_id'] ?? null,
            'skill_name' => $validated['skill_name'],
            'skill_category' => $validated['skill_category'],
            'proficiency_level' => $validated['proficiency_level'],
            'proficiency_score' => $validated['proficiency_score'] ?? null,
            'years_of_experience' => $validated['years_of_experience'] ?? 0.0,
            'has_certification' => !empty($validated['has_certification']) || !empty($validated['is_certified']),
            'certification_name' => $validated['certification_name'] ?? null,
            'certification_number' => $validated['certification_number'] ?? null,
            'assessment_remarks' => $validated['assessment_remarks'] ?? ($validated['remarks'] ?? null),
            'assessment_date' => now()->toDateString(),
            'assessed_by' => Auth::id(),
            'verification_status' => 'Verified',
        ];

        $this->profileService->recordSkill($employee, $skillData, Auth::user(), $validated['reason'] ?? 'Skill evaluation');
        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Skill matrix record saved with audit history.');
    }

    /**
     * Delete Skill.
     */
    public function deleteSkill(Employee $employee, EmployeeSkill $skill)
    {
        if ($skill->employee_id !== $employee->id) {
            abort(403);
        }

        $skill->delete();
        $this->profileService->calculateCompleteness($employee);

        return back()->with('success', 'Skill removed from matrix.');
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

    protected function determineDocumentCategory(string $type): string
    {
        $lower = strtolower($type);
        if (str_contains($lower, 'degree') || str_contains($lower, 'qualification') || str_contains($lower, 'marksheet') || str_contains($lower, 'education') || str_contains($lower, 'certificate') || str_contains($lower, 'school')) {
            return 'Educational';
        }
        if (str_contains($lower, 'passport') || str_contains($lower, 'visa') || str_contains($lower, 'permit') || str_contains($lower, 'immigration')) {
            return 'Visa & Immigration';
        }
        if (str_contains($lower, 'health') || str_contains($lower, 'medical') || str_contains($lower, 'fitness')) {
            return 'Health';
        }
        if (str_contains($lower, 'agreement') || str_contains($lower, 'contract') || str_contains($lower, 'offer') || str_contains($lower, 'relieving') || str_contains($lower, 'experience') || str_contains($lower, 'resume') || str_contains($lower, 'cv') || str_contains($lower, 'appointment')) {
            return 'Employment';
        }
        if (str_contains($lower, 'bank') || str_contains($lower, 'passbook') || str_contains($lower, 'cheque')) {
            return 'Banking';
        }
        if (str_contains($lower, 'aadhaar') || str_contains($lower, 'pan') || str_contains($lower, 'voter') || str_contains($lower, 'driving') || str_contains($lower, 'address') || str_contains($lower, 'photo') || str_contains($lower, 'identity')) {
            return 'Identity';
        }
        return 'Other';
    }
}
