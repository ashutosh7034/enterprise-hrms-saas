<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStatutoryDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'uan_number',
        'gratuity_code',
        'pran',
        'has_pt',
        'pt_number',
        'has_pf',
        'pf_join_date',
        'pf_number',
        'fpf_number',
        'pf_new_version',
        'vpf_percentage',
        'pf_remarks',
        'pf_nationality',
        'pf_country',
        'is_international_worker',
        'pf_previous_member_id',
        'npr_number',
        'election_card_number',
        'ration_card_number',
        'statutory_education_qualification',
        'has_esic',
        'esic_number',
        'esic_old_number',
        'esic_imp_code',
        'esic_imp_name',
    ];

    protected function casts(): array
    {
        return [
            'has_pt' => 'boolean',
            'has_pf' => 'boolean',
            'has_esic' => 'boolean',
            'is_international_worker' => 'boolean',
            'pf_join_date' => 'date',
            'vpf_percentage' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

