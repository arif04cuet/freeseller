<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CurrentBalance extends BaseWidget
{
    protected function getCards(): array
    {
        $user = auth()->user();
        $balance = $user->balance;
        $pendingBalance = 0;
        $lockAmount = $user->lockAmount->sum('amount');

        $cards = [];

        if ($user->isReseller() || $user->isWholesaler() || $user->isSuperAdmin()) {
            $cards[] = Card::make('Available Balance (TK)', $balance)
                ->description('Balance you can windraw')
                ->color('success');
            $cards[] = Card::make('Pending Balance (TK)', $pendingBalance)
                ->description('Balance for your in-progress orders')
                ->color('warning');
        }
        if ($user->isReseller())
            $cards[] = Card::make('Lock Amount (TK)', $lockAmount)
                ->description('Balance, currently locked ')
                ->color('danger');

        return $cards;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isWholesaler() || $user->isReseller();
    }
}
