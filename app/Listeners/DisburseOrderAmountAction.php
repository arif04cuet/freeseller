<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Jobs\DisburseOrderAmount;
use App\Jobs\SendOrderDeliveredNotification;

class DisburseOrderAmountAction
{
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;

        DisburseOrderAmount::dispatch($order);

        //send notifications
        $title = 'Order has been delivered. Order # = ' . $order->id;
        SendOrderDeliveredNotification::dispatch($order, $title);
    }
}
