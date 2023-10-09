<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Models\UserLockAmount;

class LockResellerAmount
{
    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;

        $cp = $order->courier_charge + $order->packaging_charge;

        $lockAmount = (int) ($order->total_payable - ($cp)) - (int) $order->cod;

        $total = $lockAmount > 0 ? $cp + $lockAmount : $cp;

        UserLockAmount::create([
            'user_id' => $order->reseller->id,
            'order_id' => $order->id,
            'amount' => $total,
        ]);
    }
}
