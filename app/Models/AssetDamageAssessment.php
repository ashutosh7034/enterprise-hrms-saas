<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDamageAssessment extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'asset_allocation_id',
        'fine_master_id',
        'company_id',
        'assessed_amount',
        'depreciated_value',
        'depreciation_strategy',
        'condition_evidence',
        'investigation_notes',
        'status',
        'assessed_by',
        'approved_by',
        'approved_at',
        'waiver_requested',
        'waiver_reason',
        'waived_amount',
        'waiver_approved_by',
        'net_recovery_amount',
        'recovery_method',
        'installment_count',
    ];

    protected function casts(): array
    {
        return [
            'assessed_amount' => 'decimal:2',
            'depreciated_value' => 'decimal:2',
            'waived_amount' => 'decimal:2',
            'net_recovery_amount' => 'decimal:2',
            'waiver_requested' => 'boolean',
            'approved_at' => 'datetime',
            'installment_count' => 'integer',
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(AssetAllocation::class, 'asset_allocation_id');
    }

    public function fineMaster(): BelongsTo
    {
        return $this->belongsTo(FineMaster::class, 'fine_master_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function assessedByUser(): BelongsTo
    {
        return $this->assessor();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->approver();
    }

    public function waiverApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiver_approved_by');
    }

    public function waiverApprovedByUser(): BelongsTo
    {
        return $this->waiverApprover();
    }

    public function recoveryRecords(): HasMany
    {
        return $this->hasMany(FineRecoveryRecord::class, 'damage_assessment_id');
    }
}

