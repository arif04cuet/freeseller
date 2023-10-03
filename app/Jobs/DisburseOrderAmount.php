<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DisburseOrderAmount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function handle(): void
    {
        $order = $this->order;

        DB::transaction(function () use ($order) {

            $platformPer = (int) config('freeseller.platform_fee');
            $codPer = (int) config('freeseller.cod_fee');
            $ownerAccount = User::platformOwner();

            $floatFn = fn ($number) => number_format($number, 2, '.', '');
            $percentageFn = fn ($amount, $percentage) => $floatFn((($percentage / 100) * $amount));


            //add order amount to owner account first
            $amount = ($order->cod - $order->courier_charge);
            $dipositedAmount = $amount - $percentageFn($amount, 1);

            $ownerAccount->depositFloat($floatFn($dipositedAmount), [
                'description' => 'Order amount for order#' . $order->id,
                'order' => $order->id
            ]);


            //deposit and deduct from wholesalers
            $wholesalers = $order->wholsalersAmount();
            foreach ($wholesalers as $id => $amount) {

                $wholesaler = User::find($id);
                $amount = $floatFn($amount);

                $ownerAccount->forceTransferFloat($wholesaler, $amount, [
                    'description' => 'Products amount diposited for order #' . $order->id,
                    'order' => $order->id
                ]);

                $wholesaler->forceTransferFloat($ownerAccount, $percentageFn($amount, $platformPer), [
                    'description' => 'Platform fee for order #' . $order->id,
                    'order' => $order->id
                ]);

                $wholesaler->forceTransferFloat($ownerAccount, $percentageFn($amount, $codPer), [
                    'description' => 'Cod fee for order #' . $order->id,
                    'order' => $order->id
                ]);
            }

            //deposit and deduct from reseller

            $reseller = $order->reseller;

            //release lock amount if any
            if ($order->lockAmount()->exists()) {
                $lockAmount = $floatFn($order->lockAmount->amount);
                $reseller->forceTransferFloat($ownerAccount, $lockAmount, [
                    'description' => 'Transfering lock amount for order #' . $order->id,
                    'order' => $order->id
                ]);
                $order->lockAmount()->delete();
            }

            $rAmount = $order->cod - $order->total_payable;

            if ($rAmount > 0) {
                $ownerAccount->forceTransferFloat($reseller, $floatFn($rAmount), [
                    'description' => 'Profit for order #' . $order->id,
                    'order' => $order->id
                ]);
            }

            $reseller->forceTransferFloat($ownerAccount, $percentageFn($order->profit, $platformPer), [
                'description' => 'Platform fee for order #' . $order->id,
                'order' => $order->id
            ]);
            $reseller->forceTransferFloat($ownerAccount, $percentageFn($order->profit, $codPer), [
                'description' => 'Cod fee for order #' . $order->id,
                'order' => $order->id
            ]);

            //update order
            $order->forceFill(['delivered_at' => now()])->save();


            SendOrderDeliveredNotification::dispatch($order->refresh());
        });
    }
}
