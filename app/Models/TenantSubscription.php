<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'subscription_code',
        'status',
        'starts_at',
        'ends_at',
        'grace_period_ends_at',
        'activated_at',
        'suspended_at',
        'cancelled_at',
        'renewal_type',
        'notes',
        'metadata',
        'created_by_platform_user_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function createdByPlatformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by_platform_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isGracePeriod(): bool
    {
        if ($this->status === 'grace_period') {
            return true;
        }

        if ($this->status === 'active' && $this->ends_at && $this->ends_at->isPast()) {
            return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
        }

        return false;
    }

    public function isExpired(): bool
    {
        if (in_array($this->status, ['expired', 'cancelled'])) {
            return true;
        }

        if ($this->status === 'active' || $this->status === 'trial') {
            if ($this->ends_at && $this->ends_at->isPast()) {
                return $this->grace_period_ends_at ? $this->grace_period_ends_at->isPast() : true;
            }
        }

        return false;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isValid(): bool
    {
        if ($this->isSuspended() || $this->isCancelled()) {
            return false;
        }

        if ($this->isActive() || $this->isTrial() || $this->isGracePeriod()) {
            return true;
        }

        return false;
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->ends_at) {
            return 9999;
        }
        return (int) now()->diffInDays($this->ends_at, false);
    }
}

