<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum AddressType: string
{
    use EnumToArray;

    case Division = 'division';

    case District = 'district';

    case Upazila = 'upazila';

    case Union = 'union';

    case Hub = 'hub';
}
