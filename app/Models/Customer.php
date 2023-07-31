<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'is_inside_dhaka',
        'address'
    ];

    protected $casts = [
        'is_inside_dhaka' => 'boolean'
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



    //relations

    public function resellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_reseller', 'customer_id', 'reseller_id')
            ->withTimestamps();
    }
}
