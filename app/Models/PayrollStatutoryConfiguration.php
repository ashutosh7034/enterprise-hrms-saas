<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollStatutoryConfiguration extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'effective_from_date',
        'pf_employee_rate',
        'pf_employer_rate',
        'pf_wage_ceiling',
        'pf_restrict_to_ceiling',
        'esi_employee_rate',
        'esi_employer_rate',
        'esi_wage_ceiling',
        'esi_include_overtime_in_ceiling',
        'lwf_employee_amount',
        'lwf_employer_amount',
        'lwf_deduction_frequency',
        'is_active',
    ];

    protected $casts = [
        'effective_from_date' => 'date',
        'pf_employee_rate' => 'decimal:2',
        'pf_employer_rate' => 'decimal:2',
        'pf_wage_ceiling' => 'decimal:2',
        'pf_restrict_to_ceiling' => 'boolean',
        'esi_employee_rate' => 'decimal:2',
        'esi_employer_rate' => 'decimal:2',
        'esi_wage_ceiling' => 'decimal:2',
        'esi_include_overtime_in_ceiling' => 'boolean',
        'lwf_employee_amount' => 'decimal:2',
        'lwf_employer_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

