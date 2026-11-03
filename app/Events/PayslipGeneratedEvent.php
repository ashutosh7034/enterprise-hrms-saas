<?php

namespace App\Events;

use App\Models\PayrollRunEmployee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayslipGeneratedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public PayrollRunEmployee $payslipRecord) {}
}

