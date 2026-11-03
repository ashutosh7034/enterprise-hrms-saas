<?php

namespace App\Events;

use App\Models\PayrollRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollCycleEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PayrollRun $payrollRun,
        public string $action // 'submitted', 'approved', 'rejected', 'locked', 'disbursed'
    ) {}
}

