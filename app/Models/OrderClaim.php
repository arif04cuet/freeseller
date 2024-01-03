<?php

namespace App\Models;

use App\Enum\OrderClaimStatus;
use App\Enum\OrderClaimType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OrderClaim extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => OrderClaimStatus::class,
        'type' => OrderClaimType::class,
        'order_items' => 'array',
        'wholesalers' => 'array',
        'is_payment_received' => 'boolean',
        'action_taken_at' => 'datetime'
    ];

    //relations
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    //helpers

}
