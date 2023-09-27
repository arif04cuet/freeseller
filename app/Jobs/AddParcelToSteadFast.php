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

class AddParcelToSteadFast
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function handle(): void
    {
        $request = new AddParcelRequest($this->order);
        $response = $request->send();
        $errors = $response->ok() ? $response->json('errors') : ['server' => ['Something went wrong']];

        if (collect($errors)->count()) {

            $error = collect(collect($errors)->first())->implode(',');

            Notification::make()
                ->title($error)
                ->danger()
                ->send();

            return;
        }

        $consignment = $response->json('consignment');
        if ($order = Order::find($consignment['invoice'])) {

            $order->update([
                'consignment_id' => $consignment['consignment_id'],
                'tracking_code' => $consignment['tracking_code'],
                'status' => OrderStatus::Courier_In_Review->value,
            ]);

            Notification::make()
                ->title('Order successfully sent to Courier')
                ->success()
                ->send();
        }
    }
}
