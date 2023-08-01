<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewOrderNotifications implements ShouldQueue
{

    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;

        $order->loadMissing('items');

        $hubs = [];

        $order->items->each(function ($item) use (&$hubs) {
            $wholesaler = $item->wholesaler;
            $hubs[$wholesaler->address->address_id][$wholesaler->id][] = $item;
        });

        //send notifications

        foreach ($hubs as $addressId => $items) {

            if ($manager = User::getHubManagerByAddress($addressId))
                Notification::make()
                    ->title('New Order submitted. Order id =' . $order->id)
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url(route('filament.resources.hub/orders.index', ['tableSearchQuery' => $order->id]))
                    ])
                    ->sendToDatabase($manager);

            $wholesalerIds = array_keys($items);

            if ($wholesalers = User::whereIn('id', $wholesalerIds)->get())
                Notification::make()
                    ->title('New Order submitted. Order id =' . $order->id)
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url(route('filament.resources.wholesaler/orders.index', ['tableSearchQuery' => $order->id]))
                    ])
                    ->sendToDatabase($wholesalers);
        }
    }
}
