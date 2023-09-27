<?php

namespace App\Models;

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
        $builder->when(! $loggedInUser->isSuperAdmin(), function ($q) use ($loggedInUser) {
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
        DB::transaction(function () {
            $this->forceFill([
                'status' => WalletRechargeRequestStatus::Approved->value,
                'action_taken_at' => now(),
            ])->save();

            $reseller = $this->user;
            $platform = User::platformOwner();
            $amount = $this->amount;

            // diposit to platform account
            $platform->deposit($amount, ['description' => 'deposited by '.$reseller->name]);

            //transfer account to reseller
            $platform->forceTransfer($reseller, $amount, ['description' => 'wallet recharged']);

            //send notification
            $tnxId = $this->tnx_id;
            User::sendMessage(
                users: $reseller,
                title: 'Wallet rechange request has been approved with tnx_id = '.$tnxId,
                url: route('filament.resources.wallet-recharge-requests.index', ['tableSearchQuery' => $tnxId])
            );
        });
    }
}
