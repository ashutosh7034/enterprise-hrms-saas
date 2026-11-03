<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShutdownCrewNomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_shutdown_id',
        'employee_id',
        'work_date',
        'role_description',
        'compensation_type',
        'is_approved',
        'supervisor_id',
    ];

    protected $casts = [
        'work_date' => 'date',
        'is_approved' => 'boolean',
    ];

    public function shutdown()
    {
        return $this->belongsTo(PlantShutdown::class, 'plant_shutdown_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}

