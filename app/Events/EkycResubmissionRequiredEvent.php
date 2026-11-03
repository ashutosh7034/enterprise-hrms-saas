<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EkycResubmissionRequiredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public ?string $remarks = null,
        public ?string $deadline = null
    ) {}
}

