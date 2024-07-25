<?php

namespace App\Models;

use App\Enum\PaymentChannel;
use App\Enum\WalletRechargeRequestStatus;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WalletRechargeRequest extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [

        'user_id',
        'wallet_id',
        'bank',
        'amount',
        'tnx_id',
        'status',
        'action_taken_at',
    ];

    protected $casts = [
        'status' => WalletRechargeRequestStatus::class,
        'action_taken_at' => 'datetime',
    ];

    //scopes

    public function scopeMine(Builder $builder): void
    {
        $loggedInUser = auth()->user();
        $builder->when(!$loggedInUser->isSuperAdmin(), function ($q) use ($loggedInUser) {
            return $q->whereBelongsTo($loggedInUser);
        });
    }

    //relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    //helpers

    public function markAsApproved()
    {
        $channel = $this->bank;
        DB::transaction(function () use ($channel) {
            $this->forceFill([
                'status' => WalletRechargeRequestStatus::Approved->value,
                'action_taken_at' => now(),
            ])->save();

            $reseller = $this->user;
            $platform = User::platformOwner();

            $floatFn = fn ($number) => number_format($number, 2, '.', '');

            $amount = $floatFn($this->amount);

            $rechargeId = $this->id;
            // diposit to platform account
            $platform->depositFloat($amount, [
                'description' => 'Wallet recharged by ' . $reseller->business->name,
                'wallet_recharge' => $rechargeId
            ]);

            //transfer account to reseller
            $platform->forceTransferFloat($reseller, $amount, [
                'description' => 'Wallet recharged',
                'wallet_recharge' => $rechargeId
            ]);

            //deduct 2% MFS cashout charge if case of bKash/Nagad
            if (in_array($channel, [
                PaymentChannel::bKash->value,
                PaymentChannel::Nagad->value,
            ])) {

                $percentageFn = fn ($amount, $percentage) => $floatFn((($percentage / 100) * $amount));
                $recharge_fee = $percentageFn($amount, 2);

                $reseller->forceTransferFloat($platform, $recharge_fee, [
                    'description' => 'Wallet recharged Fee',
                    'wallet_recharge_fee' => $rechargeId
                ]);
            }

            //send notification
            $tnxId = $this->tnx_id;
            User::sendMessage(
                users: $reseller,
                title: 'Wallet rechange request has been approved with tnx_id = ' . $tnxId,
                url: route('filament.app.resources.wallet-recharge-requests.index', ['tableSearch' => $tnxId])
            );
        });
    }
}
