<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_code',
        'name',
        'slug',
        'status',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'notes',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function platformAuditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function activeSubscription(): ?TenantSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial', 'grace_period'])
            ->latest('id')
            ->first();
    }

    public function latestSubscription(): ?TenantSubscription
    {
        return $this->subscriptions()->latest('id')->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }
}

