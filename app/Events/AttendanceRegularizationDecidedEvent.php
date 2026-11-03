<?php

namespace App\Events;

use App\Models\AttendanceRegularization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRegularizationDecidedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AttendanceRegularization $regularization,
        public string $decision,
        public ?string $remarks = null
    ) {}
}

