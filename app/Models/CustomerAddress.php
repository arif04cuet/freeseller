<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];


    //accessors

    public function address(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->formateAddress($value)
        );
    }


    //relations

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'district_id');
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'upazila_id');
    }

    //helper

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
