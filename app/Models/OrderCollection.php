<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'wholesaler_id',
        'collector_code',
        'collected_at'
    ];

    protected $casts = [
        'collected_at' => 'datetime'
    ];


    //relations

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function wholesaler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wholesaler_id');
    }
}
