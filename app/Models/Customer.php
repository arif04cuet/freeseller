<?php

namespace App\Models;

use App\Enum\Courier;
use App\Enum\OrderStatus;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_inside_dhaka' => 'boolean',
        'courier' => Courier::class
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

    //functions
    public function isFraud()
    {

        return DB::table('fraud_customers')->where('customer_id', $this->id)->exists();
    }

    // public function history()
    // {

    //     $this->orders()
    //         ->whereIn('status', [
    //             OrderStatus::Delivered->value,
    //             OrderStatus::Cancelled->value,
    //         ])
    //         ->whereNotNull('delivered_at')
    //         ->select([
    //             'customer_id',
    //             DB::raw("
    //                 CASE
    //                     WHEN (status == 'cacelled') THEN SUM(cod)
    //                     WHEN (status == 'delivered' and collected_cod is not null) THEN SUM(collected_cod)
    //                     ELSE 0
    //                 END as cod
    //         ")
    //         ])
    //         ->groupBy('customer_id')
    //         ->get();
    // }
    //relations

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function fraudMarkedByResellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'fraud_customers', 'customer_id', 'reseller_id')
            ->using(FraudCustomer::class)
            ->withPivot(['message', 'order_id'])
            ->withTimestamps();
    }

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
    //functions
    public function orderHistory(): Order
    {
        return  $this
            ->orders()
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status = "' . OrderStatus::Delivered->value . '" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN (status = "' . OrderStatus::Cancelled->value . '" and delivered_at is not null) THEN 1 ELSE 0 END) as cancelled_count')
            )
            ->first();
    }
    public function formateAddress($address): string
    {
        if ($this->courier == Courier::Pathao) {

            $districtId = $this->district_id;
            $upazilaId = $this->upazila_id;

            if ($upazilas = cache('pathao_district_' . $districtId))
                $address .= ', ' . $upazilas[$upazilaId];

            if ($districts = cache('pathao_district_list'))
                $address .= ', ' . $districts[$districtId];

            return $address;
        } else {
            $this->loadMissing(['upazila', 'district']);


            if ($upazila = $this->upazila)
                $address .= ', ' . $upazila->name;

            if ($district = $this->district)
                $address .= ', ' . $district->name;

            return $address;
        }
    }
}
