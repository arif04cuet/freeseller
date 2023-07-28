<?php

namespace App\Models;

use App\Enum\OptionType;
use App\Enum\OptionValueType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'field_for',
        'field_type',
        'placeholder',
        'error_message',
        'lang',
        'sort_order',
        'required',
        'length',
        'min',
        'max',
    ];

    protected $casts = [
        'field_for' => OptionType::class
    ];

    public function productTypes(): MorphToMany
    {
        return $this->morphedByMany(ProductType::class, 'optionable');
    }

    //scope

    public function scopeForProduct(Builder $query): void
    {
        $query->whereFieldFor(OptionType::Product->value);
    }

    public function scopeForWholesaler(Builder $query): void
    {
        $query->whereType(OptionType::Wholesaler->value);
    }

    public function scopeForReseller(Builder $query): void
    {
        $query->whereType(OptionType::Reseller->value);
    }
}
