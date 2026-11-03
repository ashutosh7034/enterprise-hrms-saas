<?php

namespace App\Events;

use App\Models\LeaveApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationDecidedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeaveApplication $application,
        public string $decision,
        public ?string $remarks = null
    ) {}
}

