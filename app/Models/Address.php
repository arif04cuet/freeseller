<?php

namespace App\Models;

use App\Enum\AddressType;
use App\Enum\SystemRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Permission\Models\Role;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'type'];

    protected $casts = [
        'type' => AddressType::class
    ];

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
        # code...
    }
}
