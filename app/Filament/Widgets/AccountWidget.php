<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 2;
    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::widgets.account-widget';
}
