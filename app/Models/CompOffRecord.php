<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOffRecord extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_record_id',
        'qualifying_work_date',
        'source_type',
        'eligible_work_minutes',
        'credited_days',
        'availed_days',
        'expiry_date',
        'status',
        'approved_by',
        'approval_remarks',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'qualifying_work_date' => 'date',
            'eligible_work_minutes' => 'integer',
            'credited_days' => 'decimal:2',
            'availed_days' => 'decimal:2',
            'expiry_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && now()->toDateString() > $this->expiry_date->toDateString();
    }
}

