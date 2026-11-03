<?php

namespace App\Events;

use App\Models\EmployeeShiftAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftAssignedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public EmployeeShiftAssignment $assignment) {}
}

