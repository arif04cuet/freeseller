<?php

namespace App\Models;

use App\Enum\BusinessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'estd_year',
        'type',
        'url',
    ];

    protected $casts = [
        'type' => BusinessType::class,
    ];

    public static function booted()
    {

        static::creating(function ($model) {
            $model->number = self::unique_code(9);
        });
    }

    //helper functions
    public static function unique_code($limit)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }
}
