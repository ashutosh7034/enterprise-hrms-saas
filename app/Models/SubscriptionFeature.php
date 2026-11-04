<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'feature_code',
        'feature_name',
        'is_enabled',
        'limit_value',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit_value' => 'integer',
        'metadata' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}

