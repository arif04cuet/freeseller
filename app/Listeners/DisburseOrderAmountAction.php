<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Jobs\DisburseOrderAmount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DisburseOrderAmountAction
{

    public function handle(OrderDelivered $event): void
    {
        DisburseOrderAmount::dispatchSync($event->order);
    }
}
