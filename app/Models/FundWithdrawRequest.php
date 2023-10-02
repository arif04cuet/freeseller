<?php

namespace App\Models;

use App\Enum\WalletRechargeRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FundWithdrawRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $dates = ['approved_at'];

    protected $casts = [
        'status' => WalletRechargeRequestStatus::class,
    ];

    //relations

    /**
     * Get the user that owns the FundWithdrawRequest
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that owns the FundWithdrawRequest
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user that owns the FundWithdrawRequest
     */
    public function paymentChannel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class);
    }

    //helpers

    public function markAsApproved()
    {
        DB::transaction(function () {
            $this->forceFill([
                'status' => WalletRechargeRequestStatus::Approved->value,
                'approved_at' => now(),
            ])->save();

            $user = $this->user;
            $platform = User::platformOwner();

            $floatFn = fn ($number) => number_format($number, 2, '.', '');

            $amount = $floatFn($this->amount);

            // deduct from user wallet.
            $user->forceTransferFloat($platform, $amount, ['description' => 'Fund withdrawn amount transfered to platform account']);

            // deduct from platform account.
            $platform->withdrawFloat($amount, ['description' => 'Fund transfered to user (' . $user->name . ') bank acount']);

            //send notification

            User::sendMessage(
                users: $user,
                title: 'Fund withdrawal request has been approved with id = ' . $this->id,
                url: route('filament.resources.fund-withdraw-requests.index', ['tableSearch' => $this->id]),
            );
        });
    }
}
