<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCarryForwardBatch extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'batch_number',
        'from_year',
        'to_year',
        'leave_type_id',
        'is_dry_run',
        'total_employees_processed',
        'total_days_carried_forward',
        'total_days_expired',
        'status',
        'executed_by',
        'execution_log_json',
    ];

    protected function casts(): array
    {
        return [
            'from_year' => 'integer',
            'to_year' => 'integer',
            'is_dry_run' => 'boolean',
            'total_employees_processed' => 'integer',
            'total_days_carried_forward' => 'decimal:2',
            'total_days_expired' => 'decimal:2',
            'execution_log_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}

