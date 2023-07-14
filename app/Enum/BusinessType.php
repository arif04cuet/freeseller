<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum BusinessType: string
{
    use EnumToArray;

    case Manufacturer = 'manufacturer';
    case Wholesaler = 'wholesaler';
    case Reseller = 'reseller';
}
