<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLockAmount extends Model
{
    use HasFactory;

    protected $guarded = [];

    //relations

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
