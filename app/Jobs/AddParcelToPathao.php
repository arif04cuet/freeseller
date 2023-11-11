<?php

namespace App\Jobs;

use App\Enum\Courier;
use App\Enum\OrderStatus;
use App\Http\Integrations\Pathao\Requests\AddPathaoParcelRequest;
use App\Http\Integrations\SteadFast\Requests\AddParcelRequest;
use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddParcelToPathao
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function handle(): void
    {
        $request = new AddPathaoParcelRequest($this->order);
        $response = $request->send();
        $errors = $response->ok() ? [] : ['server' => ['Something went wrong']];

        if (collect($errors)->count()) {

            $error = collect(collect($errors)->first())->implode(',');

            Notification::make()
                ->title($error)
                ->danger()
                ->send();

            return;
        }

        $consignment = $response->json('data');
        if ($order = Order::find($consignment['merchant_order_id'])) {

            $order->update([
                'consignment_id' => $consignment['consignment_id'],
                'tracking_code' => $consignment['consignment_id'],
                'status' => OrderStatus::HandOveredToCourier->value,
                'courier' => Courier::Pathao->value
            ]);

            Notification::make()
                ->title('Order successfully sent to Courier')
                ->success()
                ->send();
        }
    }
}
