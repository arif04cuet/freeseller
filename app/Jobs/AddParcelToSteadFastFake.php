<?php

namespace App\Jobs;

use App\Enum\OrderStatus;
use App\Http\Integrations\SteadFast\Requests\AddParcelRequest;
use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddParcelToSteadFastFake
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function handle(): void
    {

        $order = $this->order;

        $order->update([
            'consignment_id' => uniqid('fake-'),
            'tracking_code' => uniqid('fake-'),
            'status' => OrderStatus::HandOveredToCourier->value,
            'sent_to_courier_at' => now()
        ]);

        Notification::make()
            ->title('Order successfully sent to Courier')
            ->success()
            ->send();
    }
}
