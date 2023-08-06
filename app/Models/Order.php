<?php

namespace App\Models;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_no',
        'reseller_id',
        'customer_id',
        'total_amount',
        'note',
        'status',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'collected_at' => 'datetime'
    ];

    //scopes

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
    public function deliverToCollector($collection)
    {
        $collection->forceFill(['collected_at' => now()])->save();

        Notification::make()
            ->title('Success. please send products to collector')
            ->success()
            ->send();

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

        if ($notDelivered->count() == 0)
            $this->forceFill(['status' => OrderStatus::ProcessingForHandOverToCourier->value])->save();
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
