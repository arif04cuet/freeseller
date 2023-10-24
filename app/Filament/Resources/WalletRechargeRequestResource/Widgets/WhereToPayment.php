<?php

namespace App\Filament\Resources\WalletRechargeRequestResource\Widgets;

use Filament\Widgets\Widget;

class WhereToPayment extends Widget
{
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.resources.wallet-recharge-request-resource.widgets.where-to-payment';
    public static function canView(): bool
    {
        return auth()->user()->isReseller();
    }
}
