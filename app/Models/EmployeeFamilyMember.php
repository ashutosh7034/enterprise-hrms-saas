<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'date_of_birth',
        'contact_number',
        'occupation',
        'is_dependent',
        'is_emergency_contact',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_dependent' => 'boolean',
            'is_emergency_contact' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

