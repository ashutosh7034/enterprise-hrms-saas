<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentralBlacklistRecord extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'date_of_birth',
        'pan_number',
        'aadhaar_hash',
        'aadhaar_masked',
        'phone',
        'previous_employee_code',
        'previous_company_id',
        'reason_category',
        'evidence_notes',
        'supporting_document_path',
        'approval_status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_active',
        'whitelisted_at',
        'whitelisted_by',
        'whitelist_justification',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'approved_at' => 'datetime',
            'whitelisted_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function previousCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'previous_company_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requester(): BelongsTo
    {
        return $this->requestedBy();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approver(): BelongsTo
    {
        return $this->approvedBy();
    }

    public function whitelistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'whitelisted_by');
    }

    public function whitelister(): BelongsTo
    {
        return $this->whitelistedBy();
    }

    public function matchReviews(): HasMany
    {
        return $this->hasMany(IdentityMatchReview::class, 'blacklist_record_id');
    }
}

