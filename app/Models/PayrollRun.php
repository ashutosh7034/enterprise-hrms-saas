<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'payroll_period_id',
        'run_batch_number',
        'year',
        'month',
        'total_eligible_employees',
        'total_processed_employees',
        'total_gross_earnings',
        'total_statutory_deductions',
        'total_other_deductions',
        'total_deductions',
        'total_net_payout',
        'total_employer_contributions',
        'status',
        'processed_by',
        'approved_by',
        'approved_at',
        'rejection_remarks',
        'validation_errors_json',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'total_eligible_employees' => 'integer',
        'total_processed_employees' => 'integer',
        'total_gross_earnings' => 'decimal:2',
        'total_statutory_deductions' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_payout' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
        'approved_at' => 'datetime',
        'validation_errors_json' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollEmployees(): HasMany
    {
        return $this->hasMany(PayrollRunEmployee::class);
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(PayrollApprovalStep::class);
    }
}

