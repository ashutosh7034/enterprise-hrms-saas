<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'location_id',
        'asset_category_id',
        'name',
        'asset_code',
        'asset_tag',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'is_returnable',
        'condition',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'is_returnable' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function activeAllocation(): HasOne
    {
        return $this->hasOne(AssetAllocation::class)->where('status', 'Active')->latestOfMany();
    }

    public function currentAllocation(): HasOne
    {
        return $this->activeAllocation();
    }

    public function damageAssessments(): HasManyThrough
    {
        return $this->hasManyThrough(
            AssetDamageAssessment::class,
            AssetAllocation::class,
            'asset_id',
            'asset_allocation_id',
            'id',
            'id'
        );
    }
}

