<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSalaryAssignment extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'salary_structure_id',
        'effective_from_date',
        'effective_to_date',
        'ctc_annual',
        'gross_monthly',
        'component_values_json',
        'is_target_ot',
        'target_ot_amount',
        'is_pf_eligible',
        'is_esi_eligible',
        'status',
        'approved_by',
        'approved_at',
        'revision_notes',
    ];

    protected $casts = [
        'effective_from_date' => 'date',
        'effective_to_date' => 'date',
        'ctc_annual' => 'decimal:2',
        'gross_monthly' => 'decimal:2',
        'component_values_json' => 'array',
        'is_target_ot' => 'boolean',
        'target_ot_amount' => 'decimal:2',
        'is_pf_eligible' => 'boolean',
        'is_esi_eligible' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisionHistories(): HasMany
    {
        return $this->hasMany(SalaryRevisionHistory::class, 'new_assignment_id');
    }
}

