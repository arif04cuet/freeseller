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

            $percentageFn = fn ($amount, $percentage): float => (float) (($percentage / 100) * $amount);

            //deduct from wholesalers
            $wholesalers = $order->wholsalersAmount();
            foreach ($wholesalers as $id => $amount) {

                $wholesaler = User::find($id);
                $amount = (int) $amount;

                $ownerAccount->forceTransfer($wholesaler, $amount, ['description' => 'Products amount diposited for order #'.$order->id]);

                $wholesaler->forceTransfer($ownerAccount, $percentageFn($amount, $platformPer), ['description' => 'Platform fee for order #'.$order->id]);
                $wholesaler->forceTransfer($ownerAccount, $percentageFn($amount, $codPer), ['description' => 'Cod fee for order #'.$order->id]);
            }

            //deduct from reseller

            $reseller = $order->reseller;

            //release lock amount if any
            if ($order->lockAmount()->exists()) {
                $lockAmount = $order->lockAmount->amount;
                $reseller->forceTransfer($ownerAccount, $lockAmount, ['description' => 'Transfering lock amount for order #'.$order->id]);
                $order->lockAmount()->delete();
            }

            $rAmount = $order->cod - $order->total_payable;

            if ($rAmount > 0) {
                $ownerAccount->forceTransfer($reseller, $rAmount, ['description' => 'Profit for order #'.$order->id]);
            }

            $reseller->forceTransfer($ownerAccount, $percentageFn($order->profit, $platformPer), ['description' => 'Platform fee for order #'.$order->id]);
            $reseller->forceTransfer($ownerAccount, $percentageFn($order->profit, $codPer), ['description' => 'Cod fee for order #'.$order->id]);

            //update order
            $order->forceFill(['delivered_at' => now()])->save();
        });
    }
}
