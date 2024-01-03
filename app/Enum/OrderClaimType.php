<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderClaimType: int implements HasLabel
{
    use EnumToArray;

    case DeliveryCharge = 1;
    case Others = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DeliveryCharge => 'Delivery Charge',
            self::Others => 'Others'
        };
    }
}
