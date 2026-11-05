<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeConfirmationRecord extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'employee_id',
        'company_id',
        'action_type',
        'previous_probation_end_date',
        'new_probation_end_date',
        'confirmation_effective_date',
        'confirmation_letter_path',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_probation_end_date' => 'date',
            'new_probation_end_date' => 'date',
            'confirmation_effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

