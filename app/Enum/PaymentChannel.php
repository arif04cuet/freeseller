<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum PaymentChannel: string
{
    use EnumToArray;

    case Bank = 'bank';

    case bKash = 'bkash';
}
