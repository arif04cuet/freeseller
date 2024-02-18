<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FeeInformation extends Widget
{
    protected static ?int $sort = 4;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = [
        'default' => 2,
        'md' => 1,
    ];
    protected static string $view = 'filament.widgets.fee-information';
}
