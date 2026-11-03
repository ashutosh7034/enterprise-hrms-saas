<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollConfiguration extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'effective_from_date',
        'attendance_calculation_basis',
        'payroll_cutoff_day',
        'pay_day_of_month',
        'lop_deduction_basis',
        'ot_hourly_divisor',
        'ot_multiplier',
        'net_pay_rounding_rule',
        'is_active',
    ];

    protected $casts = [
        'effective_from_date' => 'date',
        'payroll_cutoff_day' => 'integer',
        'pay_day_of_month' => 'integer',
        'ot_hourly_divisor' => 'integer',
        'ot_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

