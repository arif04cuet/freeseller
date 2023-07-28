<?php

namespace App\Traits;

use App\Enums\Attributes\Color;
use Illuminate\Support\Collection;
use ReflectionClassConstant;
use Illuminate\Support\Str;

trait EnumToArray
{

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }
    public static function collection(): Collection
    {
        return collect(self::array());
    }
}
