<?php

namespace App\Events;

use App\Models\LeaveApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationSubmittedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeaveApplication $application) {}
}

