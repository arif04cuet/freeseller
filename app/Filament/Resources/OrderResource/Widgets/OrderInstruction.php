<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use Filament\Widgets\Widget;

class OrderInstruction extends Widget
{
    protected static string $view = 'filament.resources.order-resource.widgets.order-instruction';
    protected int | string | array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()->isReseller();
    }
}
