<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_paid',
        'annual_entitlement',
        'monthly_accrual_rate',
        'accrual_frequency',
        'accrual_timing',
        'is_accrual_enabled',
        'requires_confirmation',
        'min_service_days',
        'max_consecutive_days',
        'min_notice_days',
        'max_backdated_days',
        'is_carry_forward_enabled',
        'carry_forward_limit',
        'expiry_months',
        'allow_negative_balance',
        'max_negative_balance',
        'is_sandwich_enabled',
        'is_compoff',
        'is_applicable_in_notice_period',
        'max_notice_period_days',
        'applicable_categories',
        'applicable_locations',
        'applicable_departments',
        'applicable_employment_types',
        'status',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'annual_entitlement' => 'decimal:2',
            'monthly_accrual_rate' => 'decimal:2',
            'is_accrual_enabled' => 'boolean',
            'requires_confirmation' => 'boolean',
            'min_service_days' => 'integer',
            'max_consecutive_days' => 'integer',
            'min_notice_days' => 'integer',
            'max_backdated_days' => 'integer',
            'is_carry_forward_enabled' => 'boolean',
            'carry_forward_limit' => 'decimal:2',
            'expiry_months' => 'integer',
            'allow_negative_balance' => 'boolean',
            'max_negative_balance' => 'decimal:2',
            'is_sandwich_enabled' => 'boolean',
            'is_compoff' => 'boolean',
            'is_applicable_in_notice_period' => 'boolean',
            'max_notice_period_days' => 'decimal:2',
            'applicable_categories' => 'array',
            'applicable_locations' => 'array',
            'applicable_departments' => 'array',
            'applicable_employment_types' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function sandwichPolicies(): HasMany
    {
        return $this->hasMany(SandwichLeavePolicy::class);
    }

    public function isApplicableToEmployee(Employee $employee): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        if ($this->effective_from && now()->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && now()->gt($this->effective_to)) {
            return false;
        }

        if ($this->requires_confirmation && empty($employee->confirmation_date)) {
            return false;
        }

        if (!empty($this->applicable_categories) && !in_array($employee->category_id, $this->applicable_categories)) {
            return false;
        }

        if (!empty($this->applicable_locations) && !in_array($employee->location_id, $this->applicable_locations)) {
            return false;
        }

        if (!empty($this->applicable_departments) && !in_array($employee->department_id, $this->applicable_departments)) {
            return false;
        }

        if (!empty($this->applicable_employment_types) && !in_array($employee->employee_type, $this->applicable_employment_types)) {
            return false;
        }

        return true;
    }
}

