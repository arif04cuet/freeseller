<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewOrderNotifications implements ShouldQueue
{
    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;

        $order->loadMissing('items');

        $hubs = [];

        $order->items->each(function ($item) use (&$hubs) {
            $wholesaler = $item->wholesaler;
            $hubs[$wholesaler->hub_id][$wholesaler->id][] = $item;
        });

        //send notifications

        foreach ($hubs as $addressId => $items) {

            if ($manager = User::getHubManagerByAddress($addressId)) {
                User::sendMessage(
                    users: $manager,
                    title: 'New Order submitted. Order id =' . $order->id,
                    url: route('filament.app.resources.hub.orders.index', ['tableSearchQuery' => $order->id])
                );
            }

            $wholesalerIds = array_keys($items);

            if ($wholesalers = User::whereIn('id', $wholesalerIds)->get()) {
                User::sendMessage(
                    users: $wholesalers,
                    title: 'New Order submitted. Order id =' . $order->id,
                    url: route('filament.app.resources.wholesaler.orders.index', ['tableSearchQuery' => $order->id])
                );
            }
        }
    }
}
