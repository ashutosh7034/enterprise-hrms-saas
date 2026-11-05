<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_code',
        'profile_photo_path',
        'ticket_number',
        'display_name',
        'aka_name',
        'gate_pass_number',
        'employment_channel',
        'contractor_id',
        'employee_type',
        'location_id',
        'department_id',
        'category_id',
        'designation_id',
        'job_post_code',
        'grade',
        'division',
        'unit',
        'project',
        'skill_level',
        'reporting_manager_id',
        'first_name',
        'title',
        'middle_name',
        'last_name',
        'email',
        'alternate_email',
        'cc_email',
        'microsoft_user',
        'google_user',
        'phone',
        'residential_phone',
        'personal_phone',
        'office_phone',
        'extension_number',
        'gender',
        'date_of_birth',
        'nationality',
        'place_of_birth',
        'blood_group',
        'identification_mark',
        'is_specially_abled',
        'disability_details',
        'personal_remarks',
        'date_of_joining',
        'probation_start_date',
        'probation_end_date',
        'confirmation_due_date',
        'confirmation_date',
        'confirmation_status',
        'confirmation_remarks',
        'confirmation_letter_path',
        'initial_employment_status',
        'code_generation_method',
        'joining_location_id',
        'joining_department_id',
        'joining_designation_id',
        'resign_offer_date',
        'last_working_date',
        'fnf_retiring_date',
        'last_increment_date',
        'group_join_date',
        'process_start_date',
        'payment_mode',
        'employment_remarks',
        'jv_code_cr',
        'old_employee_code',
        'is_account_frozen',
        'notice_period_start_date',
        'notice_period_end_date',
        'aadhaar_hash',
        'aadhaar_masked',
        'pan_number',
        'address',
        'marital_status',
        'marriage_anniversary_date',
        'father_name',
        'mother_name',
        'spouse_name',
        'number_of_children',
        'children_in_hostel',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'emergency_contact_address',
        'religion',
        'caste',
        'sub_caste',
        'height_cm',
        'weight_kg',
        'health_checkup_date',
        'custom_fields',
        'status',
        'onboarding_status',
        'profile_completion_percentage',
        'correction_notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'ekyc_status',
        'ekyc_resubmission_deadline',
        'is_salary_activated',
        'rehire_of_employee_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_joining' => 'date',
            'probation_start_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_due_date' => 'date',
            'confirmation_date' => 'date',
            'resign_offer_date' => 'date',
            'last_working_date' => 'date',
            'fnf_retiring_date' => 'date',
            'last_increment_date' => 'date',
            'group_join_date' => 'date',
            'process_start_date' => 'date',
            'marriage_anniversary_date' => 'date',
            'notice_period_start_date' => 'date',
            'notice_period_end_date' => 'date',
            'health_checkup_date' => 'date',
            'ekyc_resubmission_deadline' => 'date',
            'is_salary_activated' => 'boolean',
            'is_specially_abled' => 'boolean',
            'is_account_frozen' => 'boolean',
            'custom_fields' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'profile_completion_percentage' => 'integer',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getEffectiveDisplayNameAttribute(): string
    {
        return !empty($this->display_name) ? $this->display_name : $this->full_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getServiceMonthsAttribute(): int
    {
        if (!$this->date_of_joining) {
            return 0;
        }
        $endDate = $this->last_working_date ?? now();
        return (int) $this->date_of_joining->diffInMonths($endDate);
    }

    public function getInitialsAttribute(): string
    {
        $first = substr($this->first_name ?? 'E', 0, 1);
        $last = substr($this->last_name ?? 'M', 0, 1);
        return strtoupper($first . $last);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? asset('storage/' . $this->profile_photo_path) : null;
    }

    public function getEmploymentStatusAttribute(): string
    {
        return $this->status ?? 'Active';
    }

    public function getEkycGracePeriodEndsAtAttribute()
    {
        return $this->ekyc_resubmission_deadline;
    }

    public function setEkycGracePeriodEndsAtAttribute($val): void
    {
        $this->attributes['ekyc_resubmission_deadline'] = $val;
    }

    public function setEkycStatusAttribute($val): void
    {
        if ($val === 'Grace Period Active') {
            $val = 'Resubmission Required';
        }
        $this->attributes['ekyc_status'] = $val;
    }

    public function getEkycExceptionRemarksAttribute(): ?string
    {
        return $this->ekycAttempts()
            ->where('result', 'Exception Approved')
            ->latest('id')
            ->value('remarks');
    }

    public function getEkycAttemptCountAttribute(): int
    {
        return $this->ekycAttempts()->count();
    }

    public function setEkycAttemptCountAttribute($val): void
    {
        // No-op or computed
    }

    public function getMaskedPanAttribute(): string
    {
        if (empty($this->pan_number)) {
            return 'N/A';
        }
        return substr($this->pan_number, 0, 2) . 'XXXXX' . substr($this->pan_number, -2);
    }

    public function getEmploymentTypeAttribute(): ?string
    {
        return $this->employee_type ?? 'Permanent';
    }

    public function setEmploymentTypeAttribute($value): void
    {
        $this->attributes['employee_type'] = $value;
    }

    public function joiningLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'joining_location_id');
    }

    public function joiningDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'joining_department_id');
    }

    public function joiningDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'joining_designation_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(EmployeeConversion::class)->orderByDesc('id');
    }

    public function branchTransfers(): HasMany
    {
        return $this->hasMany(EmployeeBranchTransfer::class)->orderByDesc('id');
    }

    public function confirmationRecords(): HasMany
    {
        return $this->hasMany(EmployeeConfirmationRecord::class)->orderByDesc('id');
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(EmployeeLifecycleEvent::class)->orderByDesc('id');
    }

    // Relationships
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function addressRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeAddress::class);
    }

    public function statutory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeStatutoryDetail::class);
    }

    public function bankDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeBankDetail::class);
    }

    public function visaDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeVisaDetail::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function skillHistories(): HasMany
    {
        return $this->hasMany(EmployeeSkillHistory::class)->latest('id');
    }

    public function profileHistories(): HasMany
    {
        return $this->hasMany(EmployeeProfileHistory::class)->latest('id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function userAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'employee_code', 'employee_code');
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function gatePassHistories(): HasMany
    {
        return $this->hasMany(EmployeeGatePassHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function ekycAttempts(): HasMany
    {
        return $this->hasMany(EmployeeEkycAttempt::class);
    }

    public function previousEmployments(): HasMany
    {
        return $this->hasMany(EmployeePreviousEmployment::class);
    }

    public function assetAllocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function activeAssetAllocations(): HasMany
    {
        return $this->assetAllocations()->where('status', 'Active');
    }

    public function damageAssessments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(AssetDamageAssessment::class, AssetAllocation::class, 'employee_id', 'asset_allocation_id');
    }

    public function fineRecoveryRecords(): HasMany
    {
        return $this->hasMany(FineRecoveryRecord::class);
    }

    public function rehireParent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rehire_of_employee_id');
    }

    public function shiftRosters(): HasMany
    {
        return $this->hasMany(EmployeeShiftRoster::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function activeShiftAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeShiftAssignment::class)->where('status', 'Active')->latestOfMany();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function punchLogs(): HasMany
    {
        return $this->hasMany(BiometricPunchLog::class);
    }

    public function regularizations(): HasMany
    {
        return $this->hasMany(AttendanceRegularization::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveBalanceTransactions(): HasMany
    {
        return $this->hasMany(LeaveBalanceTransaction::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function compOffRecords(): HasMany
    {
        return $this->hasMany(CompOffRecord::class);
    }

    public function salaryAssignments(): HasMany
    {
        return $this->hasMany(EmployeeSalaryAssignment::class);
    }

    public function activeSalaryAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeSalaryAssignment::class)
            ->where('status', 'Approved')
            ->latestOfMany();
    }

    public function payrollRunEmployees(): HasMany
    {
        return $this->hasMany(PayrollRunEmployee::class);
    }

    public function taxDeclarations(): HasMany
    {
        return $this->hasMany(EmployeeTaxDeclaration::class);
    }

    public function taxProjections(): HasMany
    {
        return $this->hasMany(EmployeeTaxProjection::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function fnfSettlement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FnfSettlement::class);
    }

    public function getReviewNotesAttribute(): ?string
    {
        return $this->correction_notes;
    }

    public function getSubmittedForReviewAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->submitted_at;
    }

    public function isInNoticePeriod(): bool
    {
        if ($this->status === 'Resigned') {
            return true;
        }

        if ($this->notice_period_start_date) {
            $today = now()->toDateString();
            $startDate = $this->notice_period_start_date->toDateString();
            $endDate = $this->notice_period_end_date?->toDateString() ?? '2099-12-31';

            return $today >= $startDate && $today <= $endDate;
        }

        return false;
    }
}
