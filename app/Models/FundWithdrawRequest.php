<?php

namespace App\Models;

use App\Enum\WalletRechargeRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FundWithdrawRequest extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $dates = ['approved_at'];

    protected $casts = [
        'status' => WalletRechargeRequestStatus::class,
    ];

    //relations

    public function lockAmount(): MorphOne
    {
        return $this->morphOne(UserLockAmount::class, 'entity');
    }

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
    public function isApproved()
    {
        return $this->status == WalletRechargeRequestStatus::Approved;
    }

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
            $fee = $floatFn($this->fund_transfer_fee);

            // deduct from user wallet.
            $user->forceTransferFloat($platform, $amount, [
                'description' => 'Fund withdrawn amount transfered to platform account',
                'fund_withdrawal' => $this->id
            ]);

            // deduct fee from user wallet.
            if ($fee > 0) {
                $user->forceTransferFloat($platform, $fee, [
                    'description' => 'Fund withdrawn Fee transfered to platform account',
                    'fund_withdrawal_fee' => $this->id
                ]);
            }
            // deduct from platform account.
            $platform->withdrawFloat($amount, [
                'description' => 'Fund transfered to user (' . $user->name . ') bank acount',
                'fund_withdrawal' => $this->id
            ]);

            //send notification

            User::sendMessage(
                users: $user,
                title: 'Your fund withdrawal request has been approved with id = ' . $this->id,
                url: route('filament.app.resources.fund-withdraw-requests.index', ['tableSearch' => $this->id]),
            );
        });
    }
}
