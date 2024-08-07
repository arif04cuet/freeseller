<?php

namespace App\Filament\Resources\WalletRechargeRequestResource\Pages;

use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\WalletRechargeRequestResource;
use App\Filament\Resources\WalletRechargeRequestResource\Widgets\WhereToPayment;
use App\Models\User;
use App\Traits\RecordCountTrait;
use App\Traits\UseSimplePagination;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageWalletRechargeRequests extends ManageRecords
{
    //use UseSimplePagination;
    use RecordCountTrait;

    protected static string $resource = WalletRechargeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->isReseller())
                ->using(function (array $data): Model {

                    $user = auth()->user();
                    $data['user_id'] = $user->id;
                    $data['wallet_id'] = $user->wallet->id;
                    $data['status'] = WalletRechargeRequestStatus::Pending->value;

                    $item = $this->getModel()::create($data);

                    // send notification to superadmin
                    $superadmin = User::platformOwner();
                    $tnxId = $data['tnx_id'];

                    User::sendMessage(
                        users: $superadmin,
                        title: 'New wallet rechange request submitted with tnx_id = ' . $tnxId,
                        url: route('filament.app.resources.wallet-recharge-requests.index', ['tableSearch' => $tnxId]),
                        sent_email: true
                    );

                    return $item;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WhereToPayment::class
        ];
    }


    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Recharge request created succefully. after approving admin, recharge ammount will be added to your account.';
    }
}
