<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankDetail;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeProfileHistory;
use App\Models\EmployeeSkill;
use App\Models\EmployeeSkillHistory;
use App\Models\EmployeeStatutoryDetail;
use App\Models\EmployeeVisaDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeProfileService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Calculate profile completeness percentage (0 - 100%).
     */
    public function calculateCompleteness(Employee $employee): int
    {
        $score = 0;

        // 1. Basic Info (15%)
        if (!empty($employee->first_name) && !empty($employee->last_name) && !empty($employee->gender) && !empty($employee->date_of_birth)) {
            $score += 15;
        }

        // 2. Employment (15%)
        if (!empty($employee->company_id) && !empty($employee->department_id) && !empty($employee->designation_id) && !empty($employee->date_of_joining)) {
            $score += 15;
        }

        // 3. Contact (10%)
        if (!empty($employee->phone)) {
            $score += 10;
        }

        // 4. Address (10%)
        $address = $employee->addressRecord;
        if ($address && (!empty($address->current_city) || !empty($address->current_state))) {
            $score += 10;
        } elseif (!empty($employee->address)) {
            $score += 5;
        }

        // 5. Statutory Details (10%)
        $statutory = $employee->statutory;
        if ($statutory && (!empty($statutory->uan_number) || !empty($statutory->pf_number) || !empty($employee->pan_number) || !empty($employee->aadhaar_hash))) {
            $score += 10;
        } elseif (!empty($employee->pan_number) || !empty($employee->aadhaar_hash)) {
            $score += 5;
        }

        // 6. Banking (10%)
        $bank = $employee->bankDetail;
        if ($bank && (!empty($bank->bank_account_number) && !empty($bank->bank_ifsc))) {
            $score += 10;
        }

        // 7. Family Details (5%)
        if ($employee->familyMembers()->count() > 0 || !empty($employee->emergency_contact_name)) {
            $score += 5;
        }

        // 8. Education (10%)
        if ($employee->educations()->count() > 0) {
            $score += 10;
        }

        // 9. Skills (10%)
        if ($employee->skills()->count() > 0) {
            $score += 10;
        }

        // 10. Documents (5%)
        if ($employee->documents()->count() > 0) {
            $score += 5;
        }

        return min(100, max(10, $score));
    }

    /**
     * Save or update an employee's comprehensive profile.
     */
    public function saveProfile(Employee $employee, array $data, ?User $actor = null, string $changeReason = 'Profile Update'): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor, $changeReason) {
            // Track changes on main Employee model
            $employeeFillables = $employee->getFillable();
            $employeeUpdates = array_intersect_key($data, array_flip($employeeFillables));

            // Auto compute service months and age
            if (!empty($employeeUpdates['date_of_joining'])) {
                // Preserved
            }

            $oldEmployeeValues = $employee->only(array_keys($employeeUpdates));
            $employee->update($employeeUpdates);

            // Log profile history for changed core fields
            foreach ($employeeUpdates as $key => $newVal) {
                $oldVal = $oldEmployeeValues[$key] ?? null;
                $strOld = is_array($oldVal) ? json_encode($oldVal) : (is_object($oldVal) ? (string)$oldVal : (string)($oldVal ?? ''));
                $strNew = is_array($newVal) ? json_encode($newVal) : (is_object($newVal) ? (string)$newVal : (string)($newVal ?? ''));
                if ($strOld !== $strNew && !in_array($key, ['updated_at', 'profile_completion_percentage'])) {
                    EmployeeProfileHistory::create([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'section' => $this->determineSection($key),
                        'field_name' => $key,
                        'old_value' => $strOld,
                        'new_value' => $strNew,
                        'changed_by' => $actor?->id,
                        'change_reason' => $changeReason,
                    ]);
                }
            }

            // 1. Address Record
            if (isset($data['address'])) {
                $addrData = is_array($data['address']) ? $data['address'] : [];
                $addr = $employee->addressRecord ?: new EmployeeAddress(['employee_id' => $employee->id]);
                $addr->fill($addrData);
                $addr->save();
            }

            // 2. Statutory Details
            if (isset($data['statutory'])) {
                $statData = is_array($data['statutory']) ? $data['statutory'] : [];
                $stat = $employee->statutory ?: new EmployeeStatutoryDetail(['employee_id' => $employee->id]);
                $stat->fill($statData);
                $stat->save();
            }

            // 3. Bank Details
            if (isset($data['bank'])) {
                $bankData = is_array($data['bank']) ? $data['bank'] : [];
                $bank = $employee->bankDetail ?: new EmployeeBankDetail(['employee_id' => $employee->id]);
                $bank->fill($bankData);
                $bank->save();
            }

            // 4. Visa Details
            if (isset($data['visa'])) {
                $visaData = is_array($data['visa']) ? $data['visa'] : [];
                $visa = $employee->visaDetail ?: new EmployeeVisaDetail(['employee_id' => $employee->id]);
                $visa->fill($visaData);
                $visa->save();
            }

            // Recalculate completeness
            $completeness = $this->calculateCompleteness($employee);
            $employee->update(['profile_completion_percentage' => $completeness]);

            return $employee->fresh([
                'addressRecord',
                'statutory',
                'bankDetail',
                'visaDetail',
                'familyMembers',
                'educations',
                'skills',
                'documents',
            ]);
        });
    }

    /**
     * Submit profile for HR review (ESS Workflow).
     */
    public function submitForReview(Employee $employee, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($employee, $actor) {
            $completeness = $this->calculateCompleteness($employee);

            $employee->update([
                'onboarding_status' => 'Submitted for Review',
                'submitted_at' => now(),
                'profile_completion_percentage' => $completeness,
            ]);

            EmployeeProfileHistory::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'section' => 'Workflow',
                'field_name' => 'onboarding_status',
                'old_value' => $employee->getOriginal('onboarding_status'),
                'new_value' => 'Submitted for Review',
                'changed_by' => $actor?->id,
                'change_reason' => 'Profile submitted for HR review via ESS',
            ]);

            return true;
        });
    }

    /**
     * Approve employee profile (HR / Admin).
     */
    public function approveProfile(Employee $employee, User $reviewer, ?string $remarks = null): bool
    {
        return DB::transaction(function () use ($employee, $reviewer, $remarks) {
            $employee->update([
                'onboarding_status' => 'Approved',
                'status' => 'Active',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'correction_notes' => null,
            ]);

            EmployeeProfileHistory::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'section' => 'Workflow',
                'field_name' => 'onboarding_status',
                'old_value' => 'Submitted for Review',
                'new_value' => 'Approved',
                'changed_by' => $reviewer->id,
                'change_reason' => $remarks ?: 'HR Profile Approval & Verification Completed',
            ]);

            return true;
        });
    }

    /**
     * Request corrections from employee (HR / Admin).
     */
    public function requestCorrections(Employee $employee, User $reviewer, string $correctionNotes): bool
    {
        return DB::transaction(function () use ($employee, $reviewer, $correctionNotes) {
            $employee->update([
                'onboarding_status' => 'Corrections Requested',
                'correction_notes' => $correctionNotes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            EmployeeProfileHistory::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'section' => 'Workflow',
                'field_name' => 'onboarding_status',
                'old_value' => $employee->getOriginal('onboarding_status'),
                'new_value' => 'Corrections Requested',
                'changed_by' => $reviewer->id,
                'change_reason' => "Correction requested: {$correctionNotes}",
            ]);

            return true;
        });
    }

    /**
     * Add or update an employee skill with immutable skill history tracking.
     */
    public function recordSkill(Employee $employee, array $skillData, ?User $actor = null, ?string $reason = null): EmployeeSkill
    {
        return DB::transaction(function () use ($employee, $skillData, $actor, $reason) {
            $skillId = $skillData['id'] ?? null;
            $skill = $skillId ? EmployeeSkill::where('employee_id', $employee->id)->find($skillId) : null;

            $oldLevel = $skill ? $skill->proficiency_level : null;
            $oldScore = $skill ? $skill->proficiency_score : null;

            if ($skill) {
                $skill->update($skillData);
            } else {
                $skillData['employee_id'] = $employee->id;
                $skill = EmployeeSkill::create($skillData);
            }

            // Record in immutable history ledger
            EmployeeSkillHistory::create([
                'employee_id' => $employee->id,
                'employee_skill_id' => $skill->id,
                'skill_name' => $skill->skill_name,
                'previous_level' => $oldLevel,
                'new_level' => $skill->proficiency_level,
                'previous_score' => $oldScore,
                'new_score' => $skill->proficiency_score,
                'change_reason' => $reason ?: ($oldLevel ? 'Proficiency Level Updated' : 'Initial Skill Assessed'),
                'assessment_notes' => $skillData['assessment_remarks'] ?? null,
                'changed_by' => $actor?->id,
            ]);

            return $skill;
        });
    }

    /**
     * Provision or send invitation for Employee Login Access.
     */
    public function provisionAccountAccess(Employee $employee, User $admin, ?string $password = null): User
    {
        $role = Role::where('name', 'employee_ess')->firstOrFail();

        $user = User::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('employee_code', $employee->employee_code)
            ->first();

        $initialPassword = $password ?: Str::random(12);

        if (!$user) {
            $user = User::create([
                'company_id' => $employee->company_id,
                'role_id' => $role->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'email' => $employee->email ?: "{$employee->employee_code}@enterprise.local",
                'phone' => $employee->phone,
                'password' => Hash::make($initialPassword),
                'is_active' => true,
                'can_switch_company' => false,
            ]);
        } else {
            $user->update([
                'is_active' => true,
                'role_id' => $role->id,
            ]);
            if ($password) {
                $user->update(['password' => Hash::make($password)]);
            }
        }

        EmployeeProfileHistory::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'section' => 'Account',
            'field_name' => 'account_access',
            'old_value' => 'Suspended/Inactive',
            'new_value' => 'Active',
            'changed_by' => $admin->id,
            'change_reason' => 'ESS Login Access provisioned by administrator',
        ]);

        return $user;
    }

    protected function determineSection(string $fieldName): string
    {
        if (in_array($fieldName, ['title', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'blood_group', 'nationality', 'place_of_birth', 'is_specially_abled', 'disability_details', 'personal_remarks', 'identification_mark', 'profile_photo_path'])) {
            return 'Basic';
        }
        if (in_array($fieldName, ['job_post_code', 'grade', 'division', 'unit', 'project', 'skill_level', 'location_id', 'department_id', 'category_id', 'designation_id', 'date_of_joining', 'probation_end_date', 'confirmation_date', 'resign_offer_date', 'last_working_date', 'fnf_retiring_date', 'last_increment_date', 'payment_mode', 'jv_code_cr', 'old_employee_code', 'is_account_frozen'])) {
            return 'Employment';
        }
        if (in_array($fieldName, ['phone', 'residential_phone', 'personal_phone', 'office_phone', 'extension_number', 'email', 'alternate_email', 'cc_email', 'microsoft_user', 'google_user'])) {
            return 'Contact';
        }
        if (in_array($fieldName, ['address', 'emergency_contact_address'])) {
            return 'Address';
        }
        if (in_array($fieldName, ['marital_status', 'marriage_anniversary_date', 'father_name', 'mother_name', 'spouse_name', 'number_of_children', 'children_in_hostel', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation'])) {
            return 'Family';
        }
        if (in_array($fieldName, ['religion', 'caste', 'sub_caste', 'height_cm', 'weight_kg'])) {
            return 'Personal';
        }
        if (in_array($fieldName, ['pan_number', 'aadhaar_hash', 'aadhaar_masked'])) {
            return 'Statutory';
        }
        return 'General';
    }
}

