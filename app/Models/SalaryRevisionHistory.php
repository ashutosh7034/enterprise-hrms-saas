<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRevisionHistory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'previous_assignment_id',
        'new_assignment_id',
        'effective_from_date',
        'old_ctc_annual',
        'new_ctc_annual',
        'increment_percentage',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'effective_from_date' => 'date',
        'old_ctc_annual' => 'decimal:2',
        'new_ctc_annual' => 'decimal:2',
        'increment_percentage' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryAssignment::class, 'previous_assignment_id');
    }

    public function newAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryAssignment::class, 'new_assignment_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

