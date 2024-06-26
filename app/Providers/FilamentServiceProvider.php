<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;
use Illuminate\View\View;

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
                Js::make('example-local-script', asset('js/enable-push.js')),
                //Js::make('page-loader-external', 'https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js'),
            ]);

            FilamentView::registerRenderHook(
                'panels::auth.login.form.before',
                fn (): View => view('app.login-instruction'),
            );


            // Filament::pushMeta([
            //     new HtmlString('<link rel="manifest" href="/manifest.json" />'),
            // ]);

            Table::configureUsing(function (Table $table): void {
                $table
                    ->searchDebounce('1000ms')
                    ->paginationPageOptions([10, 20]);
            });
        });
    }
}
