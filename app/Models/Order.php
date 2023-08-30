<?php

namespace App\Models;

use App\Enum\AddressType;
use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Http\Integrations\SteadFast\Requests\AddBulkParcelRequest;
use App\Jobs\AddParcelToSteadFast;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderStatus::class,
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'total_payable' => 'int',
        'total_saleable' => 'int',
        'profit' => 'int',
        'cod' => 'int'
    ];

    //scopes
    public function scopePending(Builder $builder): void
    {
        $pending = [
            OrderStatus::Delivered->value,
            OrderStatus::Cancelled->value,
        ];
        $builder->whereNotIn('status', $pending);
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

    function lockAmount(): HasOne
    {
        return $this->hasOne(UserLockAmount::class);
    }
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }


    //accessors

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

    function wholsalersAmount(): array
    {
        $wholesalers = [];

        foreach ($this->items as $item) {
            $wholesalers[$item->wholesaler_id][] = $item->wholesaler_price;
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

    function wholesalers(): EloquentCollection
    {
        return $this->loadMissing('items.wholesaler')
            ->items
            ->map(fn ($item) => $item->wholesaler)
            ->unique(fn ($item) => $item->id);
    }
    public static function totalPayable(Collection | array $items): int
    {
        if (empty($items)) return 0;

        return (int) (self::totalWholesaleAmount($items) + self::courierCharge($items) + self::packgingCost());
    }

    public static function courierCharge(Collection | array $items): int
    {

        $delivery_charge = (int) config('freeseller.delivery_charge');
        $per_saree_weight = (int) config('freeseller.per_saree_weight');

        if (is_array($items))
            $items = collect($items);

        $quantity = $items
            ->filter(fn ($item) => !empty($item['sku']) && !empty($item['quantity']))
            ->sum('quantity');

        if (empty($quantity)) return 0;

        $kg = ($quantity * $per_saree_weight) / 1000;
        $charge = (int) ($delivery_charge + ($kg - 1) * 20);

        return $charge;
    }

    public static function totalSubtotals(Collection | array $items): int
    {
        if (empty($items)) return 0;

        if (is_array($items))
            $items = collect($items);

        return (int) $items->sum('subtotal');
    }


    public static function totalWholesaleAmount(Collection | array $items): int
    {


        if (is_array($items))
            $items = collect($items);

        $amount = $items
            ->filter(fn ($item) => !empty($item['sku']) && !empty($item['quantity']))
            ->map(function ($item) {
                $sku = Sku::find($item['sku']);
                return [
                    'price' => $sku->price * $item['quantity']
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

        // Notification::make()
        //     ->title('Success. please send products to collector')
        //     ->success()
        //     ->send();

        //update items status for a wholesaler
        $this->items
            ->filter(fn ($item) => $item->wholesaler_id == $collection->wholesaler_id)
            ->each(
                fn ($item) => $item->forceFill(['status' => OrderItemStatus::DeliveredToHub->value])->save()
            );

        //check all items collected?
        $notDelivered = $this->refresh()
            ->items
            ->filter(fn ($item) => $item->status->value != OrderItemStatus::DeliveredToHub->value);

        if ($notDelivered->count() == 0) {
            $this->forceFill(['status' => OrderStatus::ProcessingForHandOverToCourier->value])->save();
            // add order to steadfast as a parcel
            $order = $this->refresh();
            AddParcelToSteadFast::dispatchSync($order);
        }
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

            User::sendMessage(
                users: $user,
                title: 'New order # ' . $this->id . ' assigned to you. please collect',
                body: 'OTP is ' . $code,
                url: route('filament.resources.hub/orders.index', ['tableSearchQuery' => $this->id])
            );


            //order collections table
            if ($this->collections->count() == 0) {

                foreach ($this->getWholesalerWiseItems() as $wholesalerId => $skuIds) {

                    $this->collections()->create([
                        'wholesaler_id' => $wholesalerId,
                        'collector_code' => $code
                    ]);
                }
            }
        }
    }
    public function getItemsByWholesaler(User $wholesaler, $status = "all"): Collection
    {
        $this->loadMissing('items');

        return $this->items
            ->filter(fn ($item) => $item->wholesaler_id == $wholesaler->id)
            ->filter(fn ($item) => $status != 'all' ? ($item->status->value == $status) : true);
    }
}
