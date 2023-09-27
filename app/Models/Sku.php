<?php

namespace App\Models;

use App\Events\SkuCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Sku extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'product_id',
        'sku',
        'quantity',
        'price',
    ];

    protected $dispatchesEvents = [
        'created' => SkuCreated::class,
    ];

    //relations

    public function resellerLists(): BelongsToMany
    {
        return $this->belongsToMany(ResellerList::class)
            ->withTimestamps();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    //accessors

    public function price(): Attribute
    {
        $product = $this->product;
        $isVarientPrice = $product->productType->is_varient_price;

        return Attribute::make(
            get: fn ($value) => (int) ($isVarientPrice ? $value : $product->price)
        );
    }

    //helpers

    public function waterMarkText()
    {
        return '#' . $this->id;
    }

    public static function getQuantity($productId, $attributeValueId)
    {
        [$sku, $valueId] = explode('-', $attributeValueId);

        return self::query()
            ->where('product_id', $productId)
            ->whereHas('attributeValues', fn ($q) => $q->where('attribute_value_id', $valueId))
            ->first()
            ->quantity;
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(80);
    }

    public function getColorAttributeValue()
    {
        return $this->attributeValues()->where('attribute_id', 1)->get()->first();
    }
}
