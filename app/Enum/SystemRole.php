<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasLabel;

enum SystemRole: string implements HasLabel
{
    use EnumToArray;

    case Admin = 'admin';

    case Wholesaler = 'wholesaler';

    case Reseller = 'reseller';

    case HubManager = 'hub_manager';

    case HubMember = 'hub_member';

    public function getLabel(): ?string
    {

        return match ($this) {
            self::Admin => 'Admin',
            self::Wholesaler => 'Wholesaler',
            self::Reseller => 'Reseller',
            self::HubManager => 'Hub Manager',
            self::HubMember => 'Hub Member',
        };
    }
}
