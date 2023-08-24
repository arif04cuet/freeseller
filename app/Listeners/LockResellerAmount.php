<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Models\Order;
use App\Models\UserLockAmount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LockResellerAmount
{

    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;
        $lockAmount = (int) $order->total_payable - (int) $order->cod;

        if ($lockAmount > 0) {

            UserLockAmount::create([
                'user_id' => $order->reseller->id,
                'order_id' => $order->id,
                'amount' => $lockAmount
            ]);
        }
    }
}
