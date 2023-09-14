<?php

namespace App\Filament\Resources\FundWithdrawRequestResource\Pages;

use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\FundWithdrawRequestResource;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageFundWithdrawRequests extends ManageRecords
{
    protected static string $resource = FundWithdrawRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): Model {

                    $user = auth()->user();
                    $data['user_id'] = $user->id;
                    $data['status'] = WalletRechargeRequestStatus::Pending->value;

                    $item = $this->getModel()::create($data);

                    // send notification to superadmin
                    $superadmin = User::platformOwner();

                    User::sendMessage(
                        users: $superadmin,
                        title: 'New fund withdrawal request submitted from user = ' . $user->name,
                        url: route('filament.resources.fund-withdraw-requests.index', ['tableSearchQuery' => $item->id]),
                        sent_email: true
                    );

                    return $item;
                }),
        ];
    }
}
