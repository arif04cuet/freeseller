<?php

namespace App\Jobs;

use App\Enum\TransactionMetaText;
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
        $order = $this->order->refresh();

        DB::transaction(function () use ($order) {

            $platformPer = (int) config('freeseller.platform_fee');
            $codPer = (int) config('freeseller.cod_fee');
            $ownerAccount = User::platformOwner();

            $floatFn = fn ($number) => number_format($number, 2, '.', '');
            $percentageFn = fn ($amount, $percentage) => $floatFn((($percentage / 100) * $amount));


            //add order amount to owner account first
            $amount = ($order->collected_cod - $order->courier_charge);
            $currier_cod = (int) config('freeseller.steadfast_cod_percentange');
            $dipositedAmount = $amount - $percentageFn($amount, $currier_cod);

            $ownerAccount->depositFloat($floatFn($dipositedAmount), [
                'description' => TransactionMetaText::ORDER_AMOUNT_DIPOSITED->getLabel($order),
                'order' => $order->id
            ]);


            //deposit and deduct from wholesalers
            $wholesalersAmount = $order->wholsalersAmount();
            foreach ($wholesalersAmount as $id => $amount) {

                $wholesaler = User::find($id);
                $amount = $floatFn($amount);

                $ownerAccount->forceTransferFloat($wholesaler, $amount, [
                    'description' => TransactionMetaText::PRODUCT_VALUE_DIPOSITED->getLabel($order),
                    'order' => $order->id
                ]);

                $wholesaler->forceTransferFloat($ownerAccount, $percentageFn($amount, $platformPer), [
                    'description' => TransactionMetaText::PLATFORM_FEE_DEDUCTED->getLabel($order),
                    'order' => $order->id
                ]);

                $wholesaler->forceTransferFloat($ownerAccount, $percentageFn($amount, $codPer), [
                    'description' => TransactionMetaText::COD_FEE_DEDUCTED->getLabel($order),
                    'order' => $order->id
                ]);
            }

            //deposit and deduct from reseller

            $reseller = $order->reseller;

            //release lock amount if any
            if ($order->lockAmount()->exists()) {
                $order->lockAmount()->delete();
            }

            if ($order->collected_cod > 0) {
                $ownerAccount->forceTransferFloat($reseller, $floatFn($order->collected_cod), [
                    'description' => TransactionMetaText::COD_VALUE_DIPOSITED->getLabel($order),
                    'order' => $order->id
                ]);
            }

            $reseller->forceTransferFloat($ownerAccount, $floatFn($order->courier_charge), [
                'description' => TransactionMetaText::COURIER_FEE_DEDUCTED->getLabel($order),
                'order' => $order->id
            ]);
            $reseller->forceTransferFloat($ownerAccount, $floatFn($order->packaging_charge), [
                'description' => TransactionMetaText::PACKAGING_FEE_DEDUCTED->getLabel($order),
                'order' => $order->id
            ]);

            $productFee = array_sum($wholesalersAmount);
            if ($productFee) {
                $reseller->forceTransferFloat($ownerAccount, $floatFn($productFee), [
                    'description' => TransactionMetaText::PRODUCT_COST_DEDUCTED->getLabel($order),
                    'order' => $order->id
                ]);

                $reseller->forceTransferFloat($ownerAccount, $percentageFn($order->profit, $platformPer), [
                    'description' => TransactionMetaText::PLATFORM_FEE_DEDUCTED->getLabel($order),
                    'order' => $order->id
                ]);
                $reseller->forceTransferFloat($ownerAccount, $percentageFn($order->profit, $codPer), [
                    'description' => TransactionMetaText::COD_FEE_DEDUCTED->getLabel($order),
                    'order' => $order->id
                ]);
            }


            //update order
            $order->forceFill(['delivered_at' => now()])->save();
        });
    }
}
