<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SandwichLeavePolicy extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'leave_type_id',
        'policy_name',
        'is_enabled',
        'include_intervening_weekly_offs',
        'include_intervening_holidays',
        'location_id',
        'department_id',
        'category_id',
        'effective_from',
        'effective_to',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'include_intervening_weekly_offs' => 'boolean',
            'include_intervening_holidays' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'priority' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

