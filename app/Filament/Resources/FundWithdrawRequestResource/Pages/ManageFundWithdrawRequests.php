<?php

namespace App\Filament\Resources\FundWithdrawRequestResource\Pages;

use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\FundWithdrawRequestResource;
use App\Models\User;
use App\Traits\RecordCountTrait;
use App\Traits\UseSimplePagination;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageFundWithdrawRequests extends ManageRecords
{
    use UseSimplePagination;
    use RecordCountTrait;

    protected static string $resource = FundWithdrawRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->modalSubmitAction(
                    function (StaticAction $action) {
                        $minimumBalance = self::getResource()::minimumBalance();
                        $maxWithdrawAmount = (int) (auth()->user()->balanceFloat - $minimumBalance);
                        return $maxWithdrawAmount > 0 ? $action : false;
                    }
                )
                ->using(function (array $data): Model {

                    $user = auth()->user();
                    $data['user_id'] = $user->id;
                    $data['status'] = WalletRechargeRequestStatus::Pending->value;

                    $item = $this->getModel()::create($data);

                    // send notification to superadmin
                    $superadmin = User::platformOwner();

                    User::sendMessage(
                        users: $superadmin,
                        title: 'New fund withdrawal request submitted from business = ' . $user->business->name,
                        url: route('filament.app.resources.fund-withdraw-requests.index', ['tableSearch' => $item->id]),
                        sent_email: true
                    );

                    return $item;
                }),
        ];
    }
}
