<?php

namespace App\Models;

use App\Enum\OptionValueType;
use App\Enum\SystemRole;
use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Livewire\Component as Livewire;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'price',
        'owner_id',
        'product_type_id',
        'category_id',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime'
    ];


    protected static function booted()
    {
    }

    //scopes

    public function scopeMine(Builder $builder): void
    {
        $builder
            ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                $query->where('owner_id', auth()->user()->id);
            });
    }

    //relations

    public function resellerLists(): BelongsToMany
    {
        return $this->belongsToMany(ResellerList::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    //accessors
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) $value
        );
    }

    //helpers

    public function getAllImages()
    {
        $this->loadMissing('skus');

        $images = $this->getMedia("sharees");

        foreach ($this->skus as $sku) {
            $images = $images->merge($sku->getMedia('sharees'));
        }

        return $images;
    }
    public function isOwner()
    {
        return $this->owner_id == auth()->user()->id;
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(150);
    }

    public function getQuantities(): Collection
    {
        $this->load('skus');

        return $this->skus->map(function ($sku) {

            return [
                'color' => $sku->getColorAttributeValue()->label,
                'quantity' => $sku->quantity
            ];
        });
    }

    public function getVarientFormSchema(): array
    {
        $fields = [];
        $productType = $this->productType;

        //need to do dynamic it
        $varients = \App\Models\Attribute::get()
            ->map(function ($attribute) {

                return Forms\Components\Select::make($attribute->id)
                    ->label($attribute->name)
                    ->disabledOn('edit')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->reactive()
                    ->hint(function (Closure $get, $state, Livewire $livewire, Closure $set) use ($attribute) {

                        if ($state) {
                            $product = $livewire->ownerRecord;
                            $data = [$state => $state];
                            $skuName = SkusRelationManager::generateSkuName($product, $data);
                            $sku = $product->skus()->where('name', $skuName)->first();
                            if ($sku) {
                                Notification::make()
                                    ->danger()
                                    ->title('You already added this variation. please update quantity instead of create')
                                    ->send();
                                $set($attribute->id, 0);
                            }
                            return $state;
                        }
                    })
                    ->options($attribute->values->pluck('label', 'id'));
            })
            ->toArray();


        $fields = array_merge($varients, [
            Forms\Components\TextInput::make('quantity')->numeric()->required()
        ]);


        if ($productType->is_varient_price)
            $fields[] =  Forms\Components\TextInput::make('price')->numeric()->required();

        $fields[] = SpatieMediaLibraryFileUpload::make('images')
            ->multiple()
            ->required()
            ->enableReordering()
            ->panelLayout('grid')
            ->image()
            ->maxFiles(2)
            ->columnSpanFull()
            ->enableDownload()
            ->collection('sharees');


        return $fields;
    }
}
