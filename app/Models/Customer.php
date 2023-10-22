<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_inside_dhaka' => 'boolean',
    ];

    //scopes

    public function scopeMine(Builder $builder): void
    {
        $builder
            ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                $query->whereHas('resellers', function ($q) {
                    return $q->where('reseller_id', auth()->user()->id);
                });
            });
    }

    //accessors

    public function address(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->formateAddress($value)
        );
    }
    //relations

    public function district(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'district_id');
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'upazila_id');
    }

    public function resellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_reseller', 'customer_id', 'reseller_id')
            ->withTimestamps();
    }

    public function formateAddress($address): string
    {
        $this->loadMissing(['upazila', 'district']);


        if ($upazila = $this->upazila)
            $address .= ', ' . $upazila->name;

        if ($district = $this->district)
            $address .= ', ' . $district->name;

        return $address;
    }
}
