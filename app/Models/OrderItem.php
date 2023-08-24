<?php

namespace App\Models;

use App\Enum\OrderItemStatus;
use App\Events\OrderItemApproved;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'sku_id',
        'quantity',
        'wholesaler_price',
        'wholesaler_id',
        'reseller_price',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'status' => OrderItemStatus::class,
        'wholesaler_price' => 'int',
        'reseller_price' => 'int',
        'total_amount' => 'int'
    ];

    //relations

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
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

    public function markAsApproved(): void
    {
        if ($this->status == OrderItemStatus::WaitingForWholesalerApproval) {
            $this->forceFill(['status' => OrderItemStatus::Approved->value])->save();
            OrderItemApproved::dispatch($this);
        }
    }
}
