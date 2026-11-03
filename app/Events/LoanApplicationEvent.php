<?php

namespace App\Events;

use App\Models\EmployeeLoan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanApplicationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EmployeeLoan $loan,
        public string $action // 'submitted', 'approved', 'rejected', 'disbursed'
    ) {}
}

