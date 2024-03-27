<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\AddressType;
use App\Enum\BusinessType;
use App\Enum\SystemRole;
use App\Notifications\AccountActivationNotification;
use App\Notifications\EmailNotification;
use App\Notifications\PushMessage;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWalletFloat;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

use function App\Helpers\minimumBalance;

class User extends Authenticatable implements HasName, MustVerifyEmail, Wallet, HasAvatar, FilamentUser, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasPushSubscriptions;
    use HasRoles;
    use HasWalletFloat;
    use InteractsWithMedia;

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
        'hub_id',
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
        // 'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public static function booted()
    {
        static::deleting(function ($user) {
            $user->address()->delete();
        });
    }

    //accessors


    //balance considering lock amount
    public function activeBalance(): Attribute
    {
        return Attribute::make(
            get: fn ($value): float => round(($this->balanceFloat - $this->lockAmount->sum('amount')), 2)
        );
    }

    public function lockAmountSum(): Attribute
    {
        return Attribute::make(
            get: fn ($value): int => (int) $this->lockAmount->sum('amount')
        );
    }


    public function idNumber(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {

                $baseNumber = config('freeseller.base_id_number');

                $id = '';

                if ($this->isWholesaler())

                    $id = 'W' . ($baseNumber + $attributes['id']);

                elseif ($this->isReseller())
                    $id = 'R' . ($baseNumber + $attributes['id']);
                else
                    $id = '';

                return $id;
            }
        );
    }

    //scopes

    public function scopeMine(Builder $builder): void
    {
        $loggedInUser = auth()->user();
        $builder->when(!$loggedInUser->isSuperAdmin(), function ($q) use ($loggedInUser) {
            return $q->whereHas('address', function ($addressable) use ($loggedInUser) {
                return $addressable->where('address_id', $loggedInUser->address->address_id);
            });
        });
    }

    public function scopeHubUsers(Builder $builder)
    {
        $builder->role([
            SystemRole::HubManager->value,
            SystemRole::HubMember->value,
        ]);
    }

    public function scopeResellers(Builder $builder): void
    {
        $builder->role(SystemRole::Reseller->value);
    }

    public function scopeWholesalers(Builder $builder): void
    {
        $builder->role(SystemRole::Wholesaler->value);
    }

    //relations
    public function lockAmount(): HasMany
    {
        return $this->hasMany(UserLockAmount::class);
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'hub_id')->where('type', AddressType::Hub->value);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_reseller', 'reseller_id', 'customer_id')
            ->withTimestamps();
    }

    public function skus(): HasManyThrough
    {
        return $this->hasManyThrough(
            Sku::class,
            Product::class,
            'owner_id',
            'product_id',
            'id',
            'id'
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'owner_id');
    }

    public function lists(): HasMany
    {
        return $this->hasMany(ResellerList::class);
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Addressable::class, 'addressable');
    }

    public function business(): HasOne
    {
        return $this->hasOne(Business::class)->latestOfMany();
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    //functions

    public function canPlaceOrder(): bool
    {
        return $this->isReseller() && ($this->activeBalance >= minimumBalance());
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {

        if (!$this->isBusiness())
            return $this->getMedia('avatar')->first()?->getUrl('thumb');

        return '/storage/' . $this->business?->logo;
    }

    public function autoActivation()
    {
        return config('app.user_auto_activation');
    }

    public static function platformOwner(): User
    {
        return User::find(1);
    }

    public static function sendMessage(
        Model|Authenticatable|Collection|array $users,
        string $title,
        string $body = '',
        string $url = '/',
        $sent_email = false,
        array $actions = [],
        string $color = 'success'
    ): void {


        if ($url != '/')
            $actions[] = Action::make('view')
                ->button()
                ->markAsRead()
                ->url($url);


        Notification::make()
            ->title($title)
            ->body($body)
            ->color($color)
            ->actions($actions)
            ->sendToDatabase($users);

        //send push Message
        if ($users instanceof Model) {
            $users->notify(new PushMessage($title, $body, $url));
            //send email
            if ($sent_email) {
                $users->notify(new EmailNotification($title, $body, $url));
            }
        } else {
            FacadesNotification::send($users, new PushMessage($title, $body, $url));
        }
    }

    public static function getHubManagerByAddress($addressId)
    {
        return self::query()
            ->whereHas('address', function ($q) use ($addressId) {
                return $q->where('address_id', $addressId);
            })
            ->role(SystemRole::HubManager->value)
            ->first();
    }

    public function canImpersonate()
    {
        return true;
    }

    public function getFilamentName(): string
    {
        $name = $this->name . ' (' . Str::headline($this->roles->first()->name) . ')';

        if ($this->isBusiness()) {
            $name = $this->business->name . ' ( ID : ' . $this->id_number . ' )';
        }

        return $name;
    }

    public function isBusiness()
    {
        return $this->hasRole([
            SystemRole::Wholesaler->value,
            SystemRole::Reseller->value
        ]);
    }


    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function isWholesaler()
    {
        return $this->hasRole(SystemRole::Wholesaler->value);
    }

    public function isReseller()
    {
        return $this->hasRole(SystemRole::Reseller->value);
    }

    public function isHubManager()
    {
        return $this->hasRole([
            SystemRole::HubManager->value,
            SystemRole::HubMember->value
        ]);
    }

    public function isHubMember()
    {
        return $this->hasRole(SystemRole::HubMember->value);
    }

    public function color()
    {
        if (!$this->roles->count()) {
            return '';
        }

        return match ($this->roles->first()->name) {
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
    public function markAsInActive()
    {
        $this->is_active = 0;
        $this->save();
    }

    public function toggleUser($state)
    {
        if ($state) {
            $this->markAsActive();
            $this->notify(new AccountActivationNotification());
            Notification::make()
                ->title('Activation email has been sent to reellers email ' . $this->email)
                ->success()
                ->send();
        } else {
            $this->markAsInActive();
        }
    }
}
