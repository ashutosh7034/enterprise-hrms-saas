<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCodeConfiguration extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'company_id',
        'prefix',
        'company_prefix',
        'branch_prefix',
        'department_prefix',
        'financial_year_prefix',
        'number_length',
        'starting_sequence_number',
        'separator',
        'format_template',
        'reset_sequence_annually',
        'reset_sequence_per_branch',
        'reset_sequence_per_company',
        'allow_manual_employee_code',
        'uniqueness_scope',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number_length' => 'integer',
            'starting_sequence_number' => 'integer',
            'reset_sequence_annually' => 'boolean',
            'reset_sequence_per_branch' => 'boolean',
            'reset_sequence_per_company' => 'boolean',
            'allow_manual_employee_code' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

