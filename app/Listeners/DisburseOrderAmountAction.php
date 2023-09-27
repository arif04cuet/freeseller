<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Jobs\DisburseOrderAmount;

class DisburseOrderAmountAction
{
    public function handle(OrderDelivered $event): void
    {
        DisburseOrderAmount::dispatchSync($event->order);
    }
}
