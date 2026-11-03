<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRegularization extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_record_id',
        'attendance_date',
        'regularization_type',
        'requested_in_time',
        'requested_out_time',
        'reason',
        'status',
        'applied_by',
        'manager_id',
        'manager_action_at',
        'manager_remarks',
        'hr_id',
        'hr_action_at',
        'hr_remarks',
        'original_status',
        'new_status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'requested_in_time' => 'datetime',
        'requested_out_time' => 'datetime',
        'manager_action_at' => 'datetime',
        'hr_action_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hr()
    {
        return $this->belongsTo(User::class, 'hr_id');
    }
}

