<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Support extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 8;

    protected function getColumns(): int
    {
        return count($this->getCachedStats());
    }


    protected function getStats(): array
    {

        /** @var App\Models\User $user */
        $user = auth()->user();

        $cards = [];

        $support_number = config('freeseller.support_number');
        $label = 'FreeSeller Support';
        $cards[] = Stat::make($label, $support_number)
            ->color('success');

        if ($user->isWholesaler()) {

            $hub = $user->hub;
            $label = 'Your Hub: ' . $hub->name;
            $support_number = $hub->manager()?->mobile;
            $cards[] = Stat::make($label, $support_number)
                ->color('success');
        }

        return $cards;
    }
}
