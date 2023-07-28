<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum BusinessType: string
{
    use EnumToArray;

    case Wholesaler = 'wholesaler';

    case Reseller = 'reseller';
}
