<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'date',
        'holiday_type',
        'location_id',
        'department_id',
        'category_id',
        'is_ground_staff_applicable',
        'is_paid',
        'year',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'year' => 'integer',
            'is_ground_staff_applicable' => 'boolean',
            'is_paid' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isApplicableToEmployee(Employee $employee): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        // Location Check: if holiday specifies a location, employee must match
        if ($this->location_id && $employee->location_id !== $this->location_id) {
            return false;
        }

        // Department Check: if holiday specifies department, employee must match
        if ($this->department_id && $employee->department_id !== $this->department_id) {
            return false;
        }

        // Category Check: if holiday specifies category, employee must match
        if ($this->category_id && $employee->category_id !== $this->category_id) {
            return false;
        }

        // Ground Staff Site Check: if location is a ground-staff site and holiday excludes ground staff
        if (!$this->is_ground_staff_applicable && $employee->location?->is_ground_staff_site) {
            return false;
        }

        return true;
    }
}

