<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\BusinessType;
use App\Enum\SystemRole;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail, HasName
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'mobile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];


    //scopes

    public function scopeResellers(Builder $builder): void
    {
        $builder->role(SystemRole::Reseller->name);
    }

    public function scopeWholesalers(Builder $builder): void
    {
        $builder->role(SystemRole::Wholesaler->name);
    }


    //relations

    public function address(): MorphOne
    {
        return $this->morphOne(Addressable::class, 'addressable');
    }

    public function business(): HasMany
    {
        return $this->hasMany(Business::class);
    }


    //functions

    public function getFilamentName(): string
    {
        return $this->name . ' (' . Str::headline($this->roles->first()->name) . ')';
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }
    public function color()
    {
        if (!$this->roles->count())
            return '';

        return match ($this->roles->first()->name) {
            BusinessType::Manufacturer->name => 'secondary',
            BusinessType::Wholesaler->name => 'primary',
            BusinessType::Reseller->name => 'success',
            default => ''
        };
    }
    public function markAsActive()
    {
        $this->is_active = 1;
        $this->save();
    }
}
