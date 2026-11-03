<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnfSettlementComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'fnf_settlement_id',
        'component_category',
        'component_name',
        'amount',
        'calculation_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(FnfSettlement::class, 'fnf_settlement_id');
    }
}

