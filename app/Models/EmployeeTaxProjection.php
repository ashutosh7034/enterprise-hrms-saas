<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTaxProjection extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'financial_year',
        'projected_annual_gross',
        'standard_deduction',
        'net_taxable_income',
        'projected_annual_tax',
        'cess_amount',
        'net_annual_tax_liability',
        'cumulative_tds_deducted',
        'remaining_months',
        'monthly_tds_calculated',
    ];

    protected $casts = [
        'projected_annual_gross' => 'decimal:2',
        'standard_deduction' => 'decimal:2',
        'net_taxable_income' => 'decimal:2',
        'projected_annual_tax' => 'decimal:2',
        'cess_amount' => 'decimal:2',
        'net_annual_tax_liability' => 'decimal:2',
        'cumulative_tds_deducted' => 'decimal:2',
        'remaining_months' => 'integer',
        'monthly_tds_calculated' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

