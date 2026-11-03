<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'loan_application_number',
        'loan_type',
        'principal_amount',
        'interest_rate_percent',
        'tenure_months',
        'monthly_emi',
        'disbursed_amount',
        'disbursed_date',
        'total_recovered',
        'remaining_balance',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate_percent' => 'decimal:2',
        'tenure_months' => 'integer',
        'monthly_emi' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'disbursed_date' => 'date',
        'total_recovered' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function emiSchedules(): HasMany
    {
        return $this->hasMany(EmployeeLoanEmiSchedule::class)->orderBy('installment_number');
    }
}

