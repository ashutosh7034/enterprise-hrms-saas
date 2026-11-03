<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePeriodUnlockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_period_lock_id',
        'unlocked_by',
        'justification',
        'unlocked_at',
        're_locked_at',
        're_locked_by',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            're_locked_at' => 'datetime',
        ];
    }

    public function lock(): BelongsTo
    {
        return $this->belongsTo(LeavePeriodLock::class, 'leave_period_lock_id');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function reLocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 're_locked_by');
    }
}

