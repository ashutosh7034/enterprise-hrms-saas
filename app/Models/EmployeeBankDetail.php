<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'bank_passbook_name',
        'pan_card_name',
        'aadhaar_card_name',
        'bank_name',
        'bank_branch',
        'bank_ifsc',
        'bank_account_number',
        'bank_account_type',
        'payment_mode',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        if (empty($this->bank_account_number)) {
            return 'N/A';
        }

        $length = strlen($this->bank_account_number);
        if ($length <= 4) {
            return $this->bank_account_number;
        }

        $visibleLast4 = substr($this->bank_account_number, -4);
        return str_repeat('•', max(0, $length - 4)) . ' ' . $visibleLast4;
    }
}

