<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantShutdown extends Model
{
    use HasFactory, BelongsToCompany, Auditable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'location_id',
        'department_id',
        'category_id',
        'shutdown_code',
        'title',
        'shutdown_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason',
        'compensation_rule',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function crewNominations()
    {
        return $this->hasMany(ShutdownCrewNomination::class);
    }

    /**
     * Check if a specific employee matches this shutdown's scope.
     */
    public function appliesToEmployee(Employee $employee): bool
    {
        if ($this->company_id !== $employee->company_id) {
            return false;
        }

        if ($this->location_id !== $employee->location_id) {
            return false;
        }

        if ($this->department_id && $this->department_id !== $employee->department_id) {
            return false;
        }

        if ($this->category_id && $this->category_id !== $employee->category_id) {
            return false;
        }

        return true;
    }
}

