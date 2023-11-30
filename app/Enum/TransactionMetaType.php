<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionMetaType: string implements HasLabel, HasColor
{
    use EnumToArray;

    case Order = 'order';

    case Rejected = 'rejected';

    case Approved = 'approved';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Rejected => 'danger',
            self::Approved => 'success'
        };
    }
}
