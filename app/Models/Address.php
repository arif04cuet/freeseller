<?php

namespace App\Models;

use App\Enum\AddressType;
use App\Enum\SystemRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    //helpers

}
