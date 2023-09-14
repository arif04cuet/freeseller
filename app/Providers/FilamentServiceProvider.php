<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentView;

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

            DatabaseNotifications::pollingInterval('10s');

            FilamentAsset::register([
                Js::make('example-local-script',  asset('js/enable-push.js')),
            ]);

            FilamentView::registerRenderHook(
                'panels::head.start',
                fn (): string =>  new HtmlString('<link rel="manifest" href="/manifest.json" />'),
            );

            // Filament::pushMeta([
            //     new HtmlString('<link rel="manifest" href="/manifest.json" />'),
            // ]);



        });
    }
}
