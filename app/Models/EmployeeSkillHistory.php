<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSkillHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'employee_skill_id',
        'skill_name',
        'previous_level',
        'new_level',
        'previous_score',
        'new_score',
        'change_reason',
        'assessment_notes',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_score' => 'decimal:1',
            'new_score' => 'decimal:1',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(EmployeeSkill::class, 'employee_skill_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

