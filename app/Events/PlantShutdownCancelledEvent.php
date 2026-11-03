<?php

namespace App\Events;

use App\Models\PlantShutdown;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlantShutdownCancelledEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public PlantShutdown $shutdown) {}
}

