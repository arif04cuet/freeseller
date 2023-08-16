<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CurrentBalance extends BaseWidget
{
    protected function getCards(): array
    {
        $balance = auth()->user()->balance;

        return [
            Card::make('Current Balance', $balance)
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isWholesaler() || $user->isReseller();
    }
}
