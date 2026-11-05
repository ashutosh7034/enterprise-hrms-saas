<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeVisaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'visa_required',
        'visa_type',
        'visa_number',
        'passport_number',
        'passport_issue_date',
        'passport_expiry_date',
        'passport_issuing_country',
        'visa_issue_date',
        'visa_expiry_date',
        'visa_issuing_country',
        'immigration_status',
        'work_permit_number',
        'work_permit_expiry_date',
        'country_of_employment',
        'visa_remarks',
    ];

    protected function casts(): array
    {
        return [
            'visa_required' => 'boolean',
            'passport_issue_date' => 'date',
            'passport_expiry_date' => 'date',
            'visa_issue_date' => 'date',
            'visa_expiry_date' => 'date',
            'work_permit_expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMaskedPassportAttribute(): string
    {
        if (empty($this->passport_number)) {
            return 'N/A';
        }
        $len = strlen($this->passport_number);
        if ($len <= 3) {
            return $this->passport_number;
        }
        return substr($this->passport_number, 0, 1) . str_repeat('•', $len - 3) . substr($this->passport_number, -2);
    }
}

