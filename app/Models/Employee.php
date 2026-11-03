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
        'gate_pass_number',
        'employment_channel',
        'contractor_id',
        'employee_type',
        'location_id',
        'department_id',
        'category_id',
        'designation_id',
        'reporting_manager_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'probation_end_date',
        'confirmation_date',
        'notice_period_start_date',
        'notice_period_end_date',
        'aadhaar_hash',
        'aadhaar_masked',
        'pan_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'status',
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
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'notice_period_start_date' => 'date',
            'notice_period_end_date' => 'date',
            'ekyc_resubmission_deadline' => 'date',
            'is_salary_activated' => 'boolean',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
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
