<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum OptionValueType: string
{
    use EnumToArray;

    case Boolean = 'boolean';

    case Text = 'text';

    case Multi = 'multi';
}
