<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {

            Filament::pushMeta([
                new HtmlString('<link rel="manifest" href="/manifest.json" />'),
            ]);

            Filament::registerScripts([
                asset('js/index.js'),
            ]);

            Filament::registerNavigationGroups([
                'Reseller',
                'Wholesaler',
                'Hub',
                'Catalog',
                'Settings',
            ]);
        });
    }
}
