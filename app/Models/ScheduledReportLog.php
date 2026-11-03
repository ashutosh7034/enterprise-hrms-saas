<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_report_id',
        'status',
        'recipients_count',
        'error_message',
        'file_path',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'dispatched_at' => 'datetime',
        ];
    }

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }
}

