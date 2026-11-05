<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'current_flat_plot',
        'current_street',
        'current_area',
        'current_city',
        'current_district',
        'current_state',
        'current_pincode',
        'current_country',
        'permanent_same_as_current',
        'permanent_flat_plot',
        'permanent_street',
        'permanent_area',
        'permanent_city',
        'permanent_district',
        'permanent_state',
        'permanent_pincode',
        'permanent_country',
    ];

    protected function casts(): array
    {
        return [
            'permanent_same_as_current' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getFormattedCurrentAddressAttribute(): string
    {
        $parts = array_filter([
            $this->current_flat_plot,
            $this->current_street,
            $this->current_area,
            $this->current_city,
            $this->current_district,
            $this->current_state ? "{$this->current_state} - {$this->current_pincode}" : $this->current_pincode,
            $this->current_country,
        ]);

        return implode(', ', $parts);
    }

    public function getFormattedPermanentAddressAttribute(): string
    {
        if ($this->permanent_same_as_current) {
            return $this->formatted_current_address;
        }

        $parts = array_filter([
            $this->permanent_flat_plot,
            $this->permanent_street,
            $this->permanent_area,
            $this->permanent_city,
            $this->permanent_district,
            $this->permanent_state ? "{$this->permanent_state} - {$this->permanent_pincode}" : $this->permanent_pincode,
            $this->permanent_country,
        ]);

        return implode(', ', $parts);
    }
}

