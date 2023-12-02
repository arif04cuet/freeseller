<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionMetaType: string implements HasLabel
{
    use EnumToArray;

    case Order = 'order';

    case FundWithdrawal = 'fund_withdrawal';
    case FundWithdrawalFee = 'fund_withdrawal_fee';
    case OrderDeliveryCharge = 'order_delivery_charge';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
