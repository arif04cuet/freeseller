<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderClaimStatus: int implements HasColor, HasLabel
{
    use EnumToArray;

    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger'
        };
    }
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected'
        };
    }
}
