<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderDeliveredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $title
    ) {
        //
    }

    public function handle(): void
    {
        $order = $this->order;

        User::sendMessage(
            users: $order->reseller,
            title: $this->title,
            url: route('filament.app.resources.orders.index', ['tableSearch' => $order->id])
        );

        $order->wholesalers()
            ->each(
                function ($wholesaler) use ($order) {
                    User::sendMessage(
                        users: $wholesaler,
                        title: 'Order has been delivered. Order # = ' . $order->id,
                        url: route('filament.app.resources.wholesaler.orders.index', ['tableSearch' => $order->id])
                    );
                }
            );
    }
}
