<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'skill_name',
        'skill_category',
        'proficiency_level',
        'proficiency_score',
        'years_of_experience',
        'has_certification',
        'certification_name',
        'certification_number',
        'issuing_authority',
        'certification_date',
        'certification_expiry_date',
        'certificate_file_path',
        'assessment_date',
        'assessed_by',
        'assessment_remarks',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'has_certification' => 'boolean',
            'certification_date' => 'date',
            'certification_expiry_date' => 'date',
            'assessment_date' => 'date',
            'proficiency_score' => 'decimal:1',
            'years_of_experience' => 'decimal:1',
        ];
    }

    public function getIsCertifiedAttribute(): bool
    {
        return (bool) $this->has_certification;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeSkillHistory::class, 'employee_skill_id')->latest();
    }
}

