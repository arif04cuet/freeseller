<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum SystemRole: string
{
    use EnumToArray;

    case Wholesaler = 'wholesaler';

    case Reseller = 'reseller';

    case HubManager = 'hub_manager';

    case HubMember = 'hub_member';
}
