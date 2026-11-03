<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_date',
        'shift_id',
        'first_in',
        'last_out',
        'total_punches',
        'punch_records_json',
        'total_work_minutes',
        'break_minutes',
        'actual_worked_minutes',
        'late_arrival_minutes',
        'early_departure_minutes',
        'overtime_minutes',
        'status',
        'regularization_status',
        'regularization_id',
        'is_overnight',
        'is_reconciled',
        'reconciliation_notes',
        'is_locked',
        'calculated_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'first_in' => 'datetime',
        'last_out' => 'datetime',
        'punch_records_json' => 'array',
        'total_punches' => 'integer',
        'total_work_minutes' => 'integer',
        'break_minutes' => 'integer',
        'actual_worked_minutes' => 'integer',
        'late_arrival_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'is_overnight' => 'boolean',
        'is_reconciled' => 'boolean',
        'is_locked' => 'boolean',
        'calculated_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function regularization()
    {
        return $this->belongsTo(AttendanceRegularization::class, 'regularization_id');
    }

    public function overtimeRecord()
    {
        return $this->hasOne(OvertimeRecord::class);
    }

    public function getFormattedWorkDurationAttribute(): string
    {
        $hours = intdiv($this->actual_worked_minutes, 60);
        $mins = $this->actual_worked_minutes % 60;
        return sprintf('%02dh %02dm', $hours, $mins);
    }

    public function getFormattedTotalDurationAttribute(): string
    {
        $hours = intdiv($this->total_work_minutes, 60);
        $mins = $this->total_work_minutes % 60;
        return sprintf('%02dh %02dm', $hours, $mins);
    }
}

