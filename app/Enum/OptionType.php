<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum OptionType: string
{
    use EnumToArray;

    case Product = 'product';

    case Wholesaler = 'wholesaler';

    case Reseller = 'reseller';
}
