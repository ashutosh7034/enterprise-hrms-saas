<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollComponentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_employee_id',
        'salary_component_id',
        'component_type',
        'component_code',
        'component_name',
        'standard_amount',
        'earned_or_deducted_amount',
    ];

    protected $casts = [
        'standard_amount' => 'decimal:2',
        'earned_or_deducted_amount' => 'decimal:2',
    ];

    public function payrollRunEmployee(): BelongsTo
    {
        return $this->belongsTo(PayrollRunEmployee::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}

