<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HubSupport extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {

        /** @var App\Models\User $user */
        $user = auth()->user();
        $support_number = config('freeseller.support_number');
        $label = 'Hub Suppor';

        $hub = $user->hub;
        $label = 'Your Hub: '.$hub->name;
        $support_number = $hub->manager()?->mobile;

        return [
            Stat::make($label, $support_number)
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        return $user->isWholesaler();
    }
}
