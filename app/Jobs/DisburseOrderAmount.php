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

            $platformPer = (int)config('freeseller.platform_fee');
            $codPer = (int) config('freeseller.cod_fee');
            $percentage = $platformPer + $codPer;

            $ownerAmount = 0;

            //deduct from wholesalers
            $wholesalers = $order->wholsalersAmount();
            foreach ($wholesalers as $id => $amount) {

                $amount = (int)$amount;
                $ownerAmount = ($percentage / 100) * $amount;
                $depositAmount = $amount - $ownerAmount;
                $description = 'platform fee + cod fee ' . $percentage . 'percent deducted';

                User::find($id)->deposit($depositAmount, ['description' => $description]);
            }

            //deduct from reseller
            $profit = $order->profit;

            $percentageValue = ($percentage / 100) * $profit;
            $resellerAmount = $profit - 0;
            $description = 'platform fee + cod fee ' . $percentage . 'percent deducted';
            $order->reseller->deposit();
        });
    }
}
