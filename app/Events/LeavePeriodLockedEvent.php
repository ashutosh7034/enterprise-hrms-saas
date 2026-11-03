<?php

namespace App\Events;

use App\Models\LeavePeriodLock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeavePeriodLockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeavePeriodLock $lock) {}
}

