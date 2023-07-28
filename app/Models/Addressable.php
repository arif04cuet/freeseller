<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Addressable extends Model
{
    use HasFactory;

    protected $fillable = ['addressable_id', 'addressable_type', 'address_id', 'address'];

    //relations

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
