<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_code',
        'name',
        'description',
        'billing_interval',
        'price',
        'currency',
        'employee_limit',
        'company_limit',
        'status',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'employee_limit' => 'integer',
        'company_limit' => 'integer',
        'metadata' => 'array',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasFeature(string $featureCode): bool
    {
        $feature = $this->features->firstWhere('feature_code', $featureCode);
        return $feature ? (bool) $feature->is_enabled : false;
    }

    public function getFeatureLimit(string $featureCode): ?int
    {
        $feature = $this->features->firstWhere('feature_code', $featureCode);
        return $feature?->limit_value;
    }
}

