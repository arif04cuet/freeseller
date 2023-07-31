<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResellerList extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public static function booted()
    {
        static::creating(fn ($model) => $model->user_id = auth()->user()->id);
    }
    //relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
