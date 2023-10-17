<?php

namespace App\Jobs;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderCancelledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function handle(): void
    {
        $order = $this->order;

        User::sendMessage(
            users: User::platformOwner(),
            title: 'Order has been cancelled. Order # = ' . $order->id,
            url: route('filament.app.resources.orders.index', ['tableSearch' => $order->id]),
            sent_email: true
        );

        $order->wholesalers(OrderItemStatus::Returned)
            ->each(
                function ($wholesaler) use ($order) {
                    User::sendMessage(
                        users: $wholesaler,
                        title: 'Order has been cancelled. Please collect your product from hub within 3 days. Order # = ' . $order->id,
                        url: route('filament.app.resources.wholesaler.orders.index', ['tableSearch' => $order->id])
                    );
                }
            );


        User::sendMessage(
            users: $order->reseller,
            title: 'Order has been cancelled. Order # = ' . $order->id,
            url: route('filament.app.resources.orders.index', ['tableSearch' => $order->id])
        );
    }
}
