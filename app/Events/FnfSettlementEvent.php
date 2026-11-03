<?php

namespace App\Events;

use App\Models\FnfSettlement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FnfSettlementEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FnfSettlement $settlement,
        public string $action // 'created', 'approved', 'paid'
    ) {}
}

