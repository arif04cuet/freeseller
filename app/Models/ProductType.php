<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ProductType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_varient_price',
    ];

    protected $casts = [
        'is_varient_price' => 'boolean',
    ];

    //relations

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function options(): MorphToMany
    {
        return $this->morphToMany(Option::class, 'optionable');
    }
}
