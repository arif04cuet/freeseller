<?php

namespace App\Observers;

use App\Models\OrderClaim;
use App\Models\User;

class OrderClaimObserver
{
    /**
     * Handle the OrderClaim "created" event.
     */
    public function created(OrderClaim $orderClaim): void
    {
        $wholesalers = collect($orderClaim->wholesalers)->pluck('id')->toArray();
        User::sendMessage(
            users: User::find($wholesalers),
            title: 'You have a claim. Please review and resolve',
            url: '/'
        );
    }

    /**
     * Handle the OrderClaim "updated" event.
     */
    public function updated(OrderClaim $orderClaim): void
    {
    }

    /**
     * Handle the OrderClaim "deleted" event.
     */
    public function deleted(OrderClaim $orderClaim): void
    {
        //
    }

    /**
     * Handle the OrderClaim "restored" event.
     */
    public function restored(OrderClaim $orderClaim): void
    {
        //
    }

    /**
     * Handle the OrderClaim "force deleted" event.
     */
    public function forceDeleted(OrderClaim $orderClaim): void
    {
        //
    }
}
