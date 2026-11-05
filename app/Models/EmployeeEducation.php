<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_educations';

    protected $fillable = [
        'employee_id',
        'qualification_level',
        'qualification_name',
        'specialization',
        'institution_name',
        'board_university',
        'country',
        'state',
        'start_year',
        'passing_year',
        'percentage_or_cgpa',
        'grade',
        'result_status',
        'certificate_number',
        'certificate_file_path',
        'marksheet_file_path',
        'additional_doc_file_path',
        'is_verified',
        'verified_by',
        'verified_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'start_year' => 'integer',
            'passing_year' => 'integer',
        ];
    }

    public function getInstituteNameAttribute(): ?string
    {
        return $this->institution_name;
    }

    public function getDegreeLevelAttribute(): ?string
    {
        return $this->qualification_level;
    }

    public function getYearOfPassingAttribute(): ?int
    {
        return $this->passing_year;
    }

    public function getVerificationStatusAttribute(): string
    {
        return $this->is_verified ? 'Verified' : 'Pending';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

