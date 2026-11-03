<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FineRecoveryRecord extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'damage_assessment_id',
        'employee_id',
        'company_id',
        'installment_number',
        'amount',
        'payment_channel',
        'status',
        'paid_date',
        'receipt_reference',
        'recorded_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_date' => 'date',
            'installment_number' => 'integer',
        ];
    }

    public function damageAssessment(): BelongsTo
    {
        return $this->belongsTo(AssetDamageAssessment::class, 'damage_assessment_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->damageAssessment();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->recorder();
    }
}

