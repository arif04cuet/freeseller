<?php

namespace App\Models;

use App\Enum\AddressType;
use App\Enum\SystemRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => AddressType::class,
    ];

    //scopes
    public function scopeHubs(Builder $query): void
    {
        $query->whereType(AddressType::Hub->value);
    }
    //relations

    public function parent()
    {
        return $this->belongsTo(Address::class, 'parent_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Addressable::class);
    }

    public function wholesalers(): HasMany
    {
        return $this->hasMany(User::class, 'hub_id');
    }

    //helpers
    public function manager()
    {
        return User::query()
            ->role(SystemRole::HubManager->value)
            ->whereRelation('address', 'address_id', $this->id)
            ->first();
    }
}
