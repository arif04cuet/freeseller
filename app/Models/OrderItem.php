<?php

namespace App\Models;

use App\Enum\OrderItemStatus;
use App\Events\OrderItemApproved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderItemStatus::class,
        'wholesaler_price' => 'int',
        'reseller_price' => 'int',
        'total_amount' => 'int',
        'quantity' => 'int',
        'return_qnt' => 'int',
        'is_returned_to_wholesaler' => 'boolean',
        'return_arrived_at' => 'datetime',
        'return_received_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    //scopes

    public function scopeActive(Builder $builder): void
    {
        $builder->where('status', '!=', OrderItemStatus::Cancelled->value);
    }
    //relations

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesaler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wholesaler_id');
    }

    //accessors

    public function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) $value
        );
    }

    //helpers
    public function returnedMessage(): string
    {
        $this->loadMissing('sku');
        return 'Your product ' . $this->sku->name . ' has been returned.
        please collect it from hub within 3 days.';
    }

    public function markAsApproved(): void
    {
        if ($this->status == OrderItemStatus::WaitingForWholesalerApproval) {
            $this->forceFill([
                'status' => OrderItemStatus::Approved->value,
                'approved_at' => now(),
            ])->save();
            OrderItemApproved::dispatch($this);
        }
    }

    public function markAsCancelled(): void
    {
        if ($this->status != OrderItemStatus::Cancelled) {
            $this->update(['status' => OrderItemStatus::Cancelled->value]);
        }
    }
}
