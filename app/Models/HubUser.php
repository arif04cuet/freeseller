<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class HubUser extends Model
{
    use HasFactory;

    protected $fillable = ['hub_id', 'user_id', 'role_id'];


    public static function booted()
    {
        static::deleting(function ($model) {
            $model->user()->delete();
        });
    }
    //relations

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'hub_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
