<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'application_number',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'is_half_day',
        'half_day_type',
        'requested_days',
        'calculated_days',
        'approved_days',
        'sandwich_breakdown_json',
        'reason',
        'supporting_document_path',
        'status',
        'current_stage',
        'is_notice_period_override',
        'notice_period_justification',
        'manager_id',
        'manager_action_at',
        'manager_remarks',
        'hod_id',
        'hod_action_at',
        'hod_remarks',
        'hr_id',
        'hr_action_at',
        'hr_remarks',
        'rejection_reason',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_half_day' => 'boolean',
            'requested_days' => 'decimal:2',
            'calculated_days' => 'decimal:2',
            'approved_days' => 'decimal:2',
            'sandwich_breakdown_json' => 'array',
            'is_notice_period_override' => 'boolean',
            'manager_action_at' => 'datetime',
            'hod_action_at' => 'datetime',
            'hr_action_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(LeaveApprovalStep::class)->orderBy('created_at');
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['Pending Manager', 'Pending HOD', 'Pending HR']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}

