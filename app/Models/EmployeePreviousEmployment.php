<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePreviousEmployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_name',
        'designation',
        'from_date',
        'to_date',
        'last_drawn_salary',
        'reason_for_leaving',
        'exit_status',
        'is_internal_company',
        'previous_employee_code',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'last_drawn_salary' => 'decimal:2',
            'is_internal_company' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

