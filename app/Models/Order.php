<?php

namespace App\Models;

use App\Enum\OrderStatus;
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
        'status' => OrderStatus::class
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

    public function getItemsByWholesaler(User $wholesaler, $status = "all"): Collection
    {
        $this->loadMissing('items');

        return $this->items
            ->filter(fn ($item) => $item->wholesaler_id == $wholesaler->id)
            ->filter(fn ($item) => $status != 'all' ? ($item->status->value == $status) : true);
    }
}
