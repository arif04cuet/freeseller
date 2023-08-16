<?php

namespace App\Providers;

use App\Events\NewOrderCreated;
use App\Events\OrderItemApproved;
use App\Listeners\ActivateUser;
use App\Listeners\ChangeOrderStatusWhenItemApproved;
use App\Listeners\CreateWallet;
use App\Listeners\SendNewOrderNotifications;
use App\Listeners\SendNewSignupEmailNotificationToAdmins;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            // SendNewSignupEmailNotificationToAdmins::class
        ],
        NewOrderCreated::class => [
            SendNewOrderNotifications::class
        ],
        OrderItemApproved::class => [
            ChangeOrderStatusWhenItemApproved::class
        ],
        Verified::class => [
            CreateWallet::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
