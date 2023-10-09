<?php

namespace App\Listeners;

use App\Enum\OrderItemStatus;
use App\Events\OrderPartialDelivered;
use App\Jobs\DisburseOrderAmount;
use App\Jobs\SendOrderDeliveredNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class OrderDisbursmentForPartialDelivery
{

    public function handle(OrderPartialDelivered $event): void
    {
        $order = $event->order;

        DisburseOrderAmount::dispatch($order);

        //send notifications
        $title = 'Order has been partially delivered. Order # = ' . $order->id;
        SendOrderDeliveredNotification::dispatch($order, $title);
    }
}
