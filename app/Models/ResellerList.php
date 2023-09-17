<?php

namespace App\Models;

use App\Enum\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResellerList extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public static function booted()
    {
        static::creating(fn ($model) => $model->user_id = auth()->user()->id);
    }
    //relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skus(): BelongsToMany
    {
        return $this->belongsToMany(Sku::class)
            ->withTimestamps();
    }

    //helpers

    public static function hubsInList($listId)
    {
        return Address::query()
            ->whereHas('wholesalers', function ($q) use ($listId) {
                return $q->whereHas('products', function ($q) use ($listId) {
                    return $q->whereHas('skus', function ($q) use ($listId) {
                        return $q->whereHas('resellerLists', function ($q) use ($listId) {
                            return $q->where('reseller_lists.id', $listId);
                        });
                    });
                });
            })
            ->whereType(AddressType::Hub->value)
            ->pluck('name', 'id');
    }
}
