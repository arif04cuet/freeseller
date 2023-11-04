<?php

namespace App\Models;

use App\Enum\BusinessType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Business extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => BusinessType::class,
    ];

    public static function booted()
    {

        static::creating(function ($model) {
            $model->number = self::unique_code(9);
        });
    }
    //scopes
    public function scopeReseller(Builder $builder): void
    {
        $builder->whereType(BusinessType::Reseller->value);
    }
    public function scopeWholesaler(Builder $builder): void
    {
        $builder->whereType(BusinessType::Wholesaler->value);
    }
    //helper functions

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100);
    }

    public static function unique_code($limit)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }
}
