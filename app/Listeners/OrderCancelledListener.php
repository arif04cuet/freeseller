<?php

namespace App\Listeners;

use App\Enum\OrderItemStatus;
use App\Enum\TransactionMetaText;
use App\Events\OrderCancelled;
use App\Jobs\SendOrderCancelledNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderCancelledListener
{

    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        DB::transaction(function () use ($order) {

            //update order
            $updatedData = $order->calculateProfit();
            $updatedData['delivered_at'] = now();
            $order->forceFill($updatedData)->save();


            $ownerAccount = User::platformOwner();
            $floatFn = fn ($number) => number_format($number, 2, '.', '');


            //reseller calculations
            $reseller = $order->reseller;

            //release lock amount if any
            if ($order->lockAmount()->exists()) {
                $order->lockAmount()->delete();
            }

            $reseller->forceTransferFloat($ownerAccount, $floatFn($order->courier_charge), [
                'description' => TransactionMetaText::COURIER_FEE_DEDUCTED->getLabel($order),
                'order' => $order->id
            ]);
            $reseller->forceTransferFloat($ownerAccount, $floatFn($order->packaging_charge), [
                'description' => TransactionMetaText::PACKAGING_FEE_DEDUCTED->getLabel($order),
                'order' => $order->id
            ]);


            //notifications
            SendOrderCancelledNotification::dispatch($order->refresh());
        });
    }
}
