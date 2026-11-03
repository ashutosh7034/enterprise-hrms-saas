<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'stage',
        'action',
        'action_by',
        'action_at',
        'remarks',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}

