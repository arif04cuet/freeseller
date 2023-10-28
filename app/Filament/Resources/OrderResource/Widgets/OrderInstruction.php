<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;

class OrderInstruction extends Widget
{
    protected static string $view = 'filament.resources.order-resource.widgets.order-instruction';
    protected int | string | array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()->isReseller();
    }

    protected function getViewData(): array
    {

        return [
            'lock_amount' => config('freeseller.delivery_charge') + config('freeseller.packaging_fee'),
            'platform_fee' => config('freeseller.platform_fee'),
            'cod_fee' => config('freeseller.cod_fee'),
            'total_fee' => config('freeseller.platform_fee') + config('freeseller.cod_fee'),
        ];
    }
}
