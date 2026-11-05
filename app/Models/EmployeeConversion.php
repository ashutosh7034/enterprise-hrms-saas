<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeConversion extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'employee_id',
        'company_id',
        'from_employee_type',
        'to_employee_type',
        'from_designation_id',
        'to_designation_id',
        'from_department_id',
        'to_department_id',
        'from_location_id',
        'to_location_id',
        'conversion_effective_date',
        'new_salary_structure',
        'confirmation_required',
        'remarks',
        'status',
        'initiated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'conversion_effective_date' => 'date',
            'confirmation_required' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fromDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'from_designation_id');
    }

    public function toDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'to_designation_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

