<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FnfSettlement extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'settlement_number',
        'resignation_date',
        'last_working_date',
        'notice_period_required_days',
        'notice_period_served_days',
        'notice_shortfall_days',
        'unpaid_salary_days',
        'unpaid_salary_amount',
        'leave_encashment_days',
        'leave_encashment_amount',
        'gratuity_amount',
        'notice_shortfall_deduction',
        'loan_outstanding_deduction',
        'asset_damage_fine_deduction',
        'other_deductions_amount',
        'gross_settlement_amount',
        'total_deductions_amount',
        'net_settlement_amount',
        'settlement_type',
        'status',
        'verified_by',
        'approved_by',
        'settled_at',
        'statement_pdf_path',
        'remarks',
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_date' => 'date',
        'notice_period_required_days' => 'integer',
        'notice_period_served_days' => 'integer',
        'notice_shortfall_days' => 'integer',
        'unpaid_salary_days' => 'decimal:2',
        'unpaid_salary_amount' => 'decimal:2',
        'leave_encashment_days' => 'decimal:2',
        'leave_encashment_amount' => 'decimal:2',
        'gratuity_amount' => 'decimal:2',
        'notice_shortfall_deduction' => 'decimal:2',
        'loan_outstanding_deduction' => 'decimal:2',
        'asset_damage_fine_deduction' => 'decimal:2',
        'other_deductions_amount' => 'decimal:2',
        'gross_settlement_amount' => 'decimal:2',
        'total_deductions_amount' => 'decimal:2',
        'net_settlement_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function components(): HasMany
    {
        return $this->hasMany(FnfSettlementComponent::class);
    }
}

