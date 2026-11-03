<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTaxDeclaration extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'financial_year',
        'tax_regime',
        'sec_80c_amount',
        'sec_80d_amount',
        'sec_24_home_loan_interest',
        'other_exemptions_amount',
        'total_declared_deductions',
        'status',
        'verified_by',
    ];

    protected $casts = [
        'sec_80c_amount' => 'decimal:2',
        'sec_80d_amount' => 'decimal:2',
        'sec_24_home_loan_interest' => 'decimal:2',
        'other_exemptions_amount' => 'decimal:2',
        'total_declared_deductions' => 'decimal:2',
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
}

