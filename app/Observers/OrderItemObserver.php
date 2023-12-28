<?php

namespace App\Observers;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Models\OrderItem;
use App\Models\Sku;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        $orderItem->sku->decrement('quantity', $orderItem->quantity);

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
    public function updating(OrderItem $orderItem): void
    {
        $orderItem->loadMissing('sku');

        if ($orderItem->status == OrderItemStatus::WaitingForWholesalerApproval) {

            $oldQnt = $orderItem->getOriginal('quantity');

            // if product change on edit
            if ($orderItem->isDirty('sku_id')) {
                $oldSku = $orderItem->getOriginal('sku_id');
                Sku::find($oldSku)->increment('quantity', $oldQnt);
                $orderItem->sku->decrement('quantity', $orderItem->quantity);
            }

            // if product not change but quantity change on edit
            if (!$orderItem->isDirty('sku_id') && $orderItem->isDirty('quantity')) {
                $orderItem->sku->increment('quantity', $oldQnt);
                $orderItem->sku->decrement('quantity', $orderItem->quantity);
            }
        } elseif (
            in_array($orderItem->status, [OrderItemStatus::Cancelled, OrderItemStatus::Returned])
        ) {
            $orderItem->sku->increment('quantity', $orderItem->quantity);
        }
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
