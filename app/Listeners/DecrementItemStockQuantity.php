<?php

namespace App\Listeners;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\OrderItemApproved;
use App\Models\User;

class DecrementItemStockQuantity
{
    public function handle(OrderItemApproved $event): void
    {
        $item = $event->item->loadMissing('sku');
        $item->sku->decrement('quantity', $item->quantity);
    }
}
