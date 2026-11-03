<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_structure_id',
        'salary_component_id',
        'calculation_type',
        'percentage',
        'percentage_of_component_id',
        'formula',
        'default_amount',
        'is_mandatory',
        'sort_order',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'default_amount' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }

    public function percentageOfComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'percentage_of_component_id');
    }
}

