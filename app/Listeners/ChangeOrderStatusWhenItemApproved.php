<?php

namespace App\Listeners;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\OrderItemApproved;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChangeOrderStatusWhenItemApproved
{

    public function handle(OrderItemApproved $event): void
    {
        $item = $event->item;
        $order = $item->order;


        if ($order->status->value == OrderStatus::WaitingForWholesalerApproval->value)
            $order->forceFill(['status' => OrderStatus::Processing->value])->save();

        //check all items
        $notApproved = $order->items->filter(fn ($item) => $item->status->value != OrderItemStatus::Approved->value);
        if ($notApproved->count() == 0) {

            $order->forceFill(['status' => OrderStatus::WaitingForHubCollection->value])->save();

            $addressId = $item->wholesaler->address->address_id;

            if ($manager = User::getHubManagerByAddress($addressId))

                User::sendMessage(
                    users: $manager,
                    title: 'Order no =' . $order->id . ' has been awaiting for collection. please collect',
                    url: route('filament.resources.hub/orders.index', ['tableSearchQuery' => $order->id])
                );
        }
    }
}
