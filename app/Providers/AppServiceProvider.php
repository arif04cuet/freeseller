<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('cart', function ($app) {
            return new CartService($app->make(SessionManager::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
        Model::preventLazyLoading(!app()->isProduction());

        // if (app()->isLocal())
        //     $this->logQuesries();
    }

    function logQuesries()
    {
        DB::listen(function ($query) {
            File::append(
                storage_path('/logs/query.log'),
                $query->sql . ' [' . implode(', ', $query->bindings) . ']' . '-' . $query->time . PHP_EOL
            );
        });
    }
}
