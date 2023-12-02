<?php

namespace App\Models;

use App\Enum\AddressType;
use App\Enum\Courier;
use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Jobs\AddParcelToPathao;
use App\Jobs\AddParcelToSteadFast;
use App\Jobs\AddParcelToSteadFastFake;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderStatus::class,
        'courier' => Courier::class,
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_action_at' => 'datetime',
        'total_payable' => 'int',
        'total_saleable' => 'int',
        'profit' => 'int',
        'cod' => 'int',
        'courier_charge' => 'int',
        'total_amount' => 'int',
        'packaging_charge' => 'int',
        'collected_cod' => 'int'
    ];

    //scopes
    public function scopePending(Builder $builder): void
    {
        // $pending = [
        //     OrderStatus::Delivered->value,
        //     OrderStatus::Cancelled->value,
        // ];
        $builder->where('status', OrderStatus::HandOveredToCourier->value);
    }

    public function scopeMine(Builder $builder): void
    {
        $loggedInUser = auth()->user();
        $builder
            ->when($loggedInUser->isReseller(), function ($q) use ($loggedInUser) {
                return $q->whereBelongsTo($loggedInUser, 'reseller');
            })
            ->when($loggedInUser->isWholesaler(), function ($q) use ($loggedInUser) {
                return $q->whereRelation('items', 'wholesaler_id', $loggedInUser->id);
            })
            ->when($loggedInUser->isHubManager(), function ($q) use ($loggedInUser) {
                return $q->whereHas('items', function ($q) use ($loggedInUser) {
                    return $q->whereHas('wholesaler', function ($q) use ($loggedInUser) {
                        return $q->whereRelation('address', 'address_id', $loggedInUser->address->address_id);
                    });
                });
            });
    }

    //relations

    public function lockAmount(): MorphOne
    {
        return $this->morphOne(UserLockAmount::class, 'entity');
    }

    // public function lockAmount(): HasOne
    // {
    //     return $this->hasOne(UserLockAmount::class);
    // }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'hub_id')->where('type', AddressType::Hub->value);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(OrderCollection::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function deliveredBy(): ?BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function deliveredItems()
    {
        return $this->items()
            ->with(['wholesaler'])
            ->where('status', OrderItemStatus::Delivered->value)
            ->get();
    }
    public function approvedItems()
    {
        return $this->items()
            ->with(['wholesaler'])
            ->where('status', OrderItemStatus::Approved->value)
            ->get();
    }
    public function returnedItems()
    {
        return $this->items()
            ->with(['wholesaler'])
            ->where('status', OrderItemStatus::Returned->value)
            ->get();
    }
    //accessors

    public function trackingUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $this->getTrackingUrl()
        );
    }

    public function totalAmountForWholesaler(): Attribute
    {

        $sum = $this->getItemsByWholesaler(auth()->user())
            ->map(fn ($item) => $item->quantity * $item->wholesaler_price)
            ->sum();

        return Attribute::make(
            get: fn ($value) => (int) $sum
        );
    }

    public function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) $value
        );
    }

    // helpers

    public function getTrackingUrl(): string
    {
        $this->loadMissing(['customer']);

        return match ($this->courier) {
            Courier::Pathao => 'https://merchant.pathao.com/tracking?consignment_id=' . $this->consignment_id . '&phone=' . $this->customer->mobile,
            Courier::SteadFast => 'https://steadfast.com.bd/t/' . $this->tracking_code,
            default => ''
        };
    }
    public function calculateProfit(): array
    {
        $order = $this;

        $items = $this->returnedItems();


        $wholesalerPrice = $items->map(
            fn ($item) => ['wholesalerPrice' => $item->wholesaler_price * $item->return_qnt]
        )->sum('wholesalerPrice');

        $resellerPrice = $items->map(
            fn ($item) => ['resellerPrice' => $item->reseller_price * $item->return_qnt]
        )->sum('resellerPrice');


        $totalPayable = $order->total_payable - $wholesalerPrice;
        $totalSaleable = $order->total_saleable - $resellerPrice;

        return [
            'total_payable' => $totalPayable,
            'total_saleable' => $totalSaleable,
            'profit' => $order->collected_cod - $totalPayable,
        ];
    }
    public function wholsalersAmount(): array
    {
        $wholesalers = [];

        foreach ($this->deliveredItems() as $item) {
            $wholesalers[$item->wholesaler_id][] = $item->wholesaler_price * $item->quantity;
        }

        foreach ($wholesalers as $id => $amounts) {
            $wholesalers[$id] = array_sum($amounts);
        }

        return $wholesalers;
    }

    public function disburseAmount(): void
    {
        $wholesalerAmount = '';
        $resellerAmount = '';
        $platformAmount = '';
    }

    public function markAsDelivered(): void
    {
        $this->forceFill(['status' => OrderStatus::Delivered->value])->save();
    }

    public function wholesalers($itemStatus = OrderItemStatus::Delivered): EloquentCollection
    {
        return match ($itemStatus) {
            OrderItemStatus::Approved => $this->loadMissing('items.wholesaler')
                ->approvedItems()
                ->map(fn ($item) => $item->wholesaler)
                ->unique(fn ($item) => $item->id),
            OrderItemStatus::Returned => $this->loadMissing('items.wholesaler')
                ->returnedItems()
                ->map(fn ($item) => $item->wholesaler)
                ->unique(fn ($item) => $item->id),
            default => $this->loadMissing('items.wholesaler')
                ->deliveredItems()
                ->map(fn ($item) => $item->wholesaler)
                ->unique(fn ($item) => $item->id)
        };
    }

    public static function totalPayable(Collection|array $items, $customer_id = null): int
    {
        if (empty($items)) {
            return 0;
        }

        return (int) (self::totalWholesaleAmount($items) + self::courierCharge($items, $customer_id) + self::packgingCost());
    }

    public static function isSameCity($customer_id): bool
    {
        $customer = Customer::find($customer_id);

        // for Tangail
        return $customer->district_id == 52 ? true : false;
    }
    public static function courierCharge(Collection|array $items, $customer_id = null): int
    {

        $delivery_charge = (int) config('freeseller.delivery_charge');

        if (!is_null($customer_id) && self::isSameCity($customer_id))
            $delivery_charge = (int) config('freeseller.delivery_charge_same_city');


        $per_saree_weight = (int) config('freeseller.per_saree_weight');

        if (is_array($items)) {
            $items = collect($items);
        }

        $quantity = $items
            ->filter(fn ($item) => !empty($item['sku']) && !empty($item['quantity']))
            ->sum('quantity');

        if (empty($quantity)) {
            return 0;
        }

        // for temporary
        if ($quantity <= 3)
            return $delivery_charge;

        $kg = ceil(($quantity * $per_saree_weight) / 1000);

        if ($kg <= 1)
            $charge  = $delivery_charge;
        else
            $charge = (int) ($delivery_charge + ($kg - 1) * 20);

        return $charge;
    }

    public static function totalSubtotals(Collection|array $items): int
    {
        if (empty($items)) {
            return 0;
        }

        if (is_array($items)) {
            $items = collect($items);
        }

        return (int) $items->sum('subtotal');
    }

    public static function totalWholesaleAmount(Collection|array $items): int
    {

        if (is_array($items)) {
            $items = collect($items);
        }

        $amount = $items
            ->filter(fn ($item) => !empty($item['sku']) && !empty($item['quantity']))
            ->map(function ($item) {
                $sku = Sku::find($item['sku']);

                return [
                    'price' => $sku->price * $item['quantity'],
                ];
            })->sum('price');

        return $amount;
    }

    public static function packgingCost()
    {
        return (int) config('freeseller.packaging_fee');
    }

    public function deliverToCollector($collection)
    {
        $collection->forceFill(['collected_at' => now()])->save();



        //update items status for a wholesaler
        $this->items
            ->filter(fn ($item) => $item->wholesaler_id == $collection->wholesaler_id)
            ->each(
                function ($item) {

                    //update order item status
                    $item->forceFill(['status' => OrderItemStatus::DeliveredToHub->value])->save();

                    //deduct item stock
                    //$item->sku->decrement('quantity', $item->quantity);
                }
            );

        //send message to wholesaler
        User::sendMessage(
            users: User::find($collection->wholesaler_id),
            title: 'Product received from hub for order# ' . $this->id . '. thanks',
            url: route('filament.app.resources.wholesaler.orders.index', ['tableSearch' => $this->id])
        );

        //check all items collected?
        $notDelivered = $this->refresh()
            ->items
            ->filter(fn ($item) => $item->status->value != OrderItemStatus::DeliveredToHub->value);

        if ($notDelivered->count() == 0) {
            $this->forceFill(['status' => OrderStatus::ProcessingForHandOverToCourier->value])->save();
            // add order to steadfast as a parcel
            $order = $this->refresh();
            $this->addToCourier($order);
        }
    }
    public function addToCourier($order): void
    {
        if (config('freeseller.add_parcel_manually'))
            return;

        if (
            config('services.steadfast.enabled') &&
            (config('freeseller.default_courier') == Courier::SteadFast->value)
        ) {
            AddParcelToSteadFast::dispatch($order);
        } elseif (
            config('services.pathao.enabled') &&
            (config('freeseller.default_courier') == Courier::Pathao->value)
        ) {
            AddParcelToPathao::dispatch($order);
        } else
            AddParcelToSteadFastFake::dispatch($order);
    }
    public function getWholesalerWiseItems()
    {
        $wholesalers = [];

        foreach ($this->items as $item) {
            $wholesalers[$item->wholesaler_id][] = $item->sku_id;
        }

        //wholesalerId=>SkuId

        return $wholesalers;
    }

    public function addCollector($userId)
    {
        $user = User::find($userId);
        if (($user->isHubManager() || $user->isHubMember()) && ($this->status == OrderStatus::WaitingForHubCollection)) {

            $this->forceFill(['collector_id' => $user->id])->save();

            $code = random_int(100000, 999999);

            // User::sendMessage(
            //     users: $user,
            //     title: 'New order # ' . $this->id . ' assigned to you. please collect',
            //     body: 'OTP is ' . $code,
            //     url: route('filament.app.resources.hub.orders.index', ['tableSearch' => $this->id])
            // );

            //order collections table
            if ($this->collections->count() == 0) {

                foreach ($this->getWholesalerWiseItems() as $wholesalerId => $skuIds) {

                    $this->collections()->create([
                        'wholesaler_id' => $wholesalerId,
                        'collector_code' => $code,
                    ]);
                }
            }
        }
    }

    public function getItemsByWholesaler(User $wholesaler, $status = 'all'): Collection
    {
        $this->loadMissing('items');

        return $this->items
            ->filter(fn ($item) => $item->wholesaler_id == $wholesaler->id)
            ->filter(fn ($item) => $status != 'all' ? ($item->status->value == $status) : true);
    }
}
