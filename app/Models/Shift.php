<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, BelongsToCompany, Auditable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'location_id',
        'department_id',
        'category_id',
        'code',
        'name',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'grace_period_minutes',
        'half_day_threshold_minutes',
        'full_day_threshold_minutes',
        'is_overnight',
        'weekly_off_days',
        'status',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'weekly_off_days' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'break_duration_minutes' => 'integer',
        'grace_period_minutes' => 'integer',
        'half_day_threshold_minutes' => 'integer',
        'full_day_threshold_minutes' => 'integer',
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

    public function rosters()
    {
        return $this->hasMany(EmployeeShiftRoster::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function getFormattedTimingsAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);
        $overnight = $this->is_overnight ? ' (+1 day)' : '';
        return "{$start} - {$end}{$overnight}";
    }

    public function getPlannedDurationMinutesAttribute(): int
    {
        $start = \Carbon\Carbon::createFromTimeString($this->start_time);
        $end = \Carbon\Carbon::createFromTimeString($this->end_time);
        if ($this->is_overnight || $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        return $start->diffInMinutes($end);
    }
}

