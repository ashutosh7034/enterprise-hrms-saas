<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityMatchReview extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'employee_id',
        'blacklist_record_id',
        'match_type',
        'match_score',
        'match_details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_remarks',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'integer',
            'match_details' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function blacklistRecord(): BelongsTo
    {
        return $this->belongsTo(CentralBlacklistRecord::class, 'blacklist_record_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

