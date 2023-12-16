<?php

namespace App\Observers;

use App\Enum\OrderStatus;
use App\Models\OrderItem;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     */
    public function creating(OrderItem $orderItem): void
    {
        // add order items after wholesaler approval.
        // make status back to waiting for whoesaler approval

        $order = $orderItem->order;
        if ($order->status == OrderStatus::WaitingForHubCollection) {
            $order->update([
                'status' => OrderStatus::WaitingForWholesalerApproval->value
            ]);
        }
    }

    /**
     * Handle the OrderItem "updated" event.
     */
    public function updated(OrderItem $orderItem): void
    {
        //
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        //
    }

    /**
     * Handle the OrderItem "restored" event.
     */
    public function restored(OrderItem $orderItem): void
    {
        //
    }

    /**
     * Handle the OrderItem "force deleted" event.
     */
    public function forceDeleted(OrderItem $orderItem): void
    {
        //
    }
}
