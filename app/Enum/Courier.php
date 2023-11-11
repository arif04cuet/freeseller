<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum Courier: string
{
    use EnumToArray;

    case SteadFast = 'steadfast';

    case Pathao = 'pathao';
}
