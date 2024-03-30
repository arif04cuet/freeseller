<?php

namespace App\Models;

use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use DB;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component as Livewire;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_at' => 'datetime',
        'offer_price_valid_from' => 'datetime',
        'offer_price_valid_to' => 'datetime',
        'offer_price' => 'int'
    ];

    protected static function booted()
    {
    }

    //scopes

    public function scopeSearch($query, $searchTerm)
    {
        return $query
            ->when($searchTerm, fn ($query) => $query->where('name', 'like', '%' . $searchTerm . '%'));
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['cat'], fn ($query, $cat) => $query->where('category_id', $cat));
    }
    public function scopeSort($query, $sort)
    {
        return $query
            ->when(
                $sort == 'price',
                fn ($query, $value) => $query
                    ->orderByRaw('CASE WHEN offer_price IS NOT NULL THEN LEAST(offer_price, price) ELSE price END')
            )->when(
                $sort == 'new',
                fn ($query, $value) => $query
                    ->orderBy('created_at', 'desc')
            )->when(
                $sort == 'sales',
                fn ($query, $value) => $query
                    ->select('products.*', DB::raw('SUM(order_items.quantity) as total_quantity_sold'))
                    ->join('order_items', 'products.id', '=', 'order_items.product_id')
                    ->groupBy('products.id')
                    ->orderByDesc('total_quantity_sold')
            );
    }

    public function scopeExplorerProducts(Builder $builder): void
    {
        $builder->with([
            'category' => fn ($q) => $q->select('id', 'name'),
            'productType' => fn ($q) => $q->select('id', 'name'),
            'media',
            'skus',
            'skus.media',
            'owner'
        ])->has('skus');
    }
    public function scopeMine(Builder $builder): void
    {
        $builder
            ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                $query->where('owner_id', auth()->user()->id);
            });
    }

    //relations

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
            get: fn ($value) => $this->getProductPrice($value)
        );
    }

    //helpers

    public function colorQuantity(): array
    {
        return $this->skus->map(
            function ($sku) {
                $color = array_reverse(explode('-', $sku->name))[0];
                return $color . '-' . $sku->quantity;
            }
        )->toArray();
    }
    public function getOfferPrice()
    {
        $price = null;

        if ($offerPrice = $this->offer_price) {

            $from = $this->offer_price_valid_from ?: Carbon::yesterday();
            $to = $this->offer_price_valid_to ?: Carbon::tomorrow();
            if (Carbon::now()->between($from, $to)) {
                $price = (int) $offerPrice;
            }
        }

        return $price;
    }
    public function getProductPrice($originalPrice)
    {
        $price = $this->getOfferPrice() ?? $originalPrice;

        return (int) $price;
    }

    public function getAllImages()
    {
        $this->loadMissing('skus');

        $images = $this->getMedia('sharees');

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
                'quantity' => $sku->quantity,
            ];
        });
    }

    public function getVarientFormSchema(): array
    {
        $fields = [];
        $productType = $this->productType;

        //need to do dynamic it
        $varients = \App\Models\Attribute::with('values')
            ->get()
            ->map(function ($attribute) {

                return Forms\Components\Select::make($attribute->id)
                    ->label($attribute->name)
                    ->disabledOn('edit')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->reactive()
                    ->hint(function (\Filament\Forms\Get $get, $state, Livewire $livewire, \Filament\Forms\Set $set) use ($attribute) {

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
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
        ]);

        if ($productType->is_varient_price) {
            $fields[] = Forms\Components\TextInput::make('price')->numeric()->required();
        }

        $fields[] = SpatieMediaLibraryFileUpload::make('images')
            ->multiple()
            ->required()
            ->enableReordering()
            ->panelLayout('grid')
            ->image()
            ->maxFiles(5)
            ->columnSpanFull()
            ->enableDownload()
            ->maxSize(1024)
            ->helperText('Each image size max 1 MB')
            ->collection('sharees');

        return $fields;
    }
}
