<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BiometricDevice extends Model
{
    use HasFactory, BelongsToCompany, Auditable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'location_id',
        'device_identifier',
        'device_name',
        'serial_number',
        'ip_address',
        'port',
        'device_type',
        'protocol',
        'status',
        'health_status',
        'secret_token',
        'last_sync_at',
        'last_heartbeat_at',
        'remarks',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'port' => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(BiometricDeviceSyncLog::class);
    }

    public function punches()
    {
        return $this->hasMany(BiometricPunchLog::class);
    }
}

