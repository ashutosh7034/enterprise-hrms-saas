<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FineMaster extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'fine_code',
        'fine_name',
        'category',
        'asset_category_id',
        'default_amount',
        'effective_date',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'default_amount' => 'decimal:2',
        ];
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AssetDamageAssessment::class);
    }
}

