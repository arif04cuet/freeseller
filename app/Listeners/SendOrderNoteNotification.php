<?php

namespace App\Listeners;

use App\Events\OrderNoteAdded;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderNoteNotification
{

    public function handle(OrderNoteAdded $event): void
    {
        $order = $event->order;
        $note = $event->note;

        User::sendMessage(
            users: $order->hub->allHubMembers(),
            title: 'New note added for order =' . $order->id,
            body: $note,
            url: route('filament.app.resources.hub.orders.index', ['tableSearch' => $order->id])
        );
    }
}
