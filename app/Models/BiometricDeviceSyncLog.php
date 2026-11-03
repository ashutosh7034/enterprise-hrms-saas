<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricDeviceSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_device_id',
        'sync_start_time',
        'sync_end_time',
        'records_received',
        'records_processed',
        'records_duplicate',
        'records_failed',
        'status',
        'error_message',
        'raw_payload_preview',
    ];

    protected $casts = [
        'sync_start_time' => 'datetime',
        'sync_end_time' => 'datetime',
        'records_received' => 'integer',
        'records_processed' => 'integer',
        'records_duplicate' => 'integer',
        'records_failed' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }
}

