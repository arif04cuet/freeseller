<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Models\UserLockAmount;

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
                'amount' => $lockAmount,
            ]);
        }
    }
}
