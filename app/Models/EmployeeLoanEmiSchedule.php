<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoanEmiSchedule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_loan_id',
        'installment_number',
        'due_month',
        'due_year',
        'emi_amount',
        'principal_component',
        'interest_component',
        'status',
        'payroll_run_id',
        'deducted_at',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'due_month' => 'integer',
        'due_year' => 'integer',
        'emi_amount' => 'decimal:2',
        'principal_component' => 'decimal:2',
        'interest_component' => 'decimal:2',
        'deducted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }
}

