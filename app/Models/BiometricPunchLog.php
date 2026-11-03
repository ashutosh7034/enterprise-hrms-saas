<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricPunchLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'location_id',
        'biometric_device_id',
        'employee_id',
        'biometric_user_id',
        'punch_time',
        'sync_time',
        'direction',
        'punch_fingerprint',
        'raw_payload',
        'source',
        'processing_status',
        'is_duplicate',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'sync_time' => 'datetime',
        'processed_at' => 'datetime',
        'is_duplicate' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Generate deterministic fingerprint to detect duplicates without dropping punches.
     */
    public static function generateFingerprint(int $companyId, string $userId, string $punchTime, string $direction): string
    {
        return hash('sha256', "{$companyId}:{$userId}:{$punchTime}:{$direction}");
    }
}

