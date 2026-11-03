<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'accrued_balance',
        'consumed_balance',
        'reserved_balance',
        'carry_forward_balance',
        'expired_balance',
        'adjustment_credit',
        'adjustment_debit',
        'closing_balance',
        'available_balance',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'opening_balance' => 'decimal:2',
            'accrued_balance' => 'decimal:2',
            'consumed_balance' => 'decimal:2',
            'reserved_balance' => 'decimal:2',
            'carry_forward_balance' => 'decimal:2',
            'expired_balance' => 'decimal:2',
            'adjustment_credit' => 'decimal:2',
            'adjustment_debit' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Recalculates closing and available balance based on current component balances.
     */
    public function recalculateBalances(): self
    {
        $this->closing_balance = round(
            (float) $this->opening_balance +
            (float) $this->accrued_balance +
            (float) $this->carry_forward_balance +
            (float) $this->adjustment_credit -
            (float) $this->consumed_balance -
            (float) $this->expired_balance -
            (float) $this->adjustment_debit,
            2
        );

        $this->available_balance = round(
            (float) $this->closing_balance - (float) $this->reserved_balance,
            2
        );

        return $this;
    }
}

