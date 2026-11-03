<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRecord extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_record_id',
        'ot_date',
        'shift_id',
        'day_type',
        'raw_calculated_minutes',
        'rounded_ot_minutes',
        'requested_minutes',
        'approved_minutes',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'remarks',
        'payroll_batch_id',
    ];

    protected $casts = [
        'ot_date' => 'date',
        'approved_at' => 'datetime',
        'raw_calculated_minutes' => 'integer',
        'rounded_ot_minutes' => 'integer',
        'requested_minutes' => 'integer',
        'approved_minutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getFormattedApprovedDurationAttribute(): string
    {
        $hours = intdiv($this->approved_minutes, 60);
        $mins = $this->approved_minutes % 60;
        return sprintf('%02dh %02dm', $hours, $mins);
    }
}

