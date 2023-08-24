<?php

namespace App\Jobs;

use App\Enum\OrderStatus;
use App\Http\Integrations\SteadFast\Requests\AddParcelRequest;
use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        logger($response->json());
        if ($response->ok()) {

            $consignment = $response->json('consignment');

            if ($order = Order::find($consignment['invoice'])) {

                $order->update([
                    'consignment_id' => $consignment['consignment_id'],
                    'tracking_code' => $consignment['tracking_code'],
                    'status' => OrderStatus::Courier_In_Review->value
                ]);

                Notification::make()
                    ->title('Order successfully sent to Courier')
                    ->success()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Something went wrong!')
                ->danger()
                ->send();
        }
    }
}
