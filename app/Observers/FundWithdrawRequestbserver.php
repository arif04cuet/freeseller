<?php

namespace App\Observers;

use App\Models\FundWithdrawRequest;

class FundWithdrawRequestbserver
{
    /**
     * Handle the FundWithdrawRequest "created" event.
     */
    public function created(FundWithdrawRequest $fundWithdrawRequest): void
    {

        //lock the requested amount
        $this->lockAmount($fundWithdrawRequest);
    }

    /**
     * Handle the FundWithdrawRequest "updated" event.
     */
    public function updated(FundWithdrawRequest $fundWithdrawRequest): void
    {

        if (!$fundWithdrawRequest->isApproved()) {
            $this->lockAmount($fundWithdrawRequest);
        }
    }

    /**
     * Handle the FundWithdrawRequest "deleted" event.
     */
    public function deleted(FundWithdrawRequest $fundWithdrawRequest): void
    {
        //
    }

    /**
     * Handle the FundWithdrawRequest "restored" event.
     */
    public function restored(FundWithdrawRequest $fundWithdrawRequest): void
    {
        //
    }

    /**
     * Handle the FundWithdrawRequest "force deleted" event.
     */
    public function forceDeleted(FundWithdrawRequest $fundWithdrawRequest): void
    {
        //
    }

    public function lockAmount(FundWithdrawRequest $fundWithdrawRequest): void
    {
        if ($fundWithdrawRequest->lockAmount()->exists()) {
            $fundWithdrawRequest->lockAmount()->delete();
        }

        $fundWithdrawRequest->lockAmount()->create([
            'user_id' => $fundWithdrawRequest->user->id,
            'amount' => $fundWithdrawRequest->amount,
        ]);
    }
}
