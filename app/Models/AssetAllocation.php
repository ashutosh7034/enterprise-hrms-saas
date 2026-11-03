<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAllocation extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'company_id',
        'allocated_date',
        'condition_at_issue',
        'acknowledged_by_employee',
        'acknowledged_at',
        'allocated_by',
        'return_date',
        'condition_at_return',
        'received_by',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'allocated_date' => 'date',
            'return_date' => 'date',
            'acknowledged_by_employee' => 'boolean',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function allocatedByUser(): BelongsTo
    {
        return $this->allocator();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->receiver();
    }

    public function damageAssessments(): HasMany
    {
        return $this->hasMany(AssetDamageAssessment::class);
    }
}

