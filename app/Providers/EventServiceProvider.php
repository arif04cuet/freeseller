<?php

namespace App\Providers;

use App\Events\NewOrderCreated;
use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderItemApproved;
use App\Events\OrderPartialDelivered;
use App\Listeners\ActivateUser;
use App\Listeners\AddSkuNumnerToImage;
use App\Listeners\ChangeOrderStatusWhenItemApproved;
use App\Listeners\CreateWallet;
use App\Listeners\DecrementItemStockQuantity;
use App\Listeners\DisburseOrderAmountAction;
use App\Listeners\LockResellerAmount;
use App\Listeners\NewSignupNotification;
use App\Listeners\OrderCancelledListener;
use App\Listeners\OrderDeliveryNotificationToOwner;
use App\Listeners\OrderDisbursmentForPartialDelivery;
use App\Listeners\SendNewOrderNotifications;
use App\Listeners\SendNewSignupEmailNotificationToAdmins;
use App\Listeners\SendOrderCancelledNotificationListener;
use App\Models\FundWithdrawRequest;
use App\Models\OrderItem;
use App\Observers\FundWithdrawRequestbserver;
use App\Observers\OrderItemObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;

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
            SendNewOrderNotifications::class,
            //LockResellerAmount::class,
        ],
        OrderItemApproved::class => [
            ChangeOrderStatusWhenItemApproved::class,
            //DecrementItemStockQuantity::class
        ],
        Verified::class => [
            ActivateUser::class,
            CreateWallet::class,
            NewSignupNotification::class
        ],

        MediaHasBeenAdded::class => [
            //AddSkuNumnerToImage::class
        ],
        OrderDelivered::class => [
            DisburseOrderAmountAction::class,
        ],
        OrderPartialDelivered::class => [
            OrderDisbursmentForPartialDelivery::class,
        ],
        OrderCancelled::class => [
            OrderCancelledListener::class,
        ]

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        FundWithdrawRequest::observe(FundWithdrawRequestbserver::class);
        OrderItem::observe(OrderItemObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
