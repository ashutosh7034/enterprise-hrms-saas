<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLifecycleEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'event_type',
        'previous_value',
        'new_value',
        'effective_date',
        'reason',
        'remarks',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_value' => 'array',
            'new_value' => 'array',
            'effective_date' => 'date',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActionDescriptionAttribute(): string
    {
        return $this->reason ?? $this->event_type;
    }

    public function getOldValuesAttribute(): ?array
    {
        return $this->previous_value;
    }

    public function getNewValuesAttribute(): ?array
    {
        return $this->new_value;
    }
}
