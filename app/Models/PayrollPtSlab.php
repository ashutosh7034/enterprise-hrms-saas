<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPtSlab extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'state',
        'gender',
        'min_monthly_salary',
        'max_monthly_salary',
        'tax_amount',
        'special_month',
        'special_tax_amount',
    ];

    protected $casts = [
        'min_monthly_salary' => 'decimal:2',
        'max_monthly_salary' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'special_month' => 'integer',
        'special_tax_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

