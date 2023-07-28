<?php

namespace App\Models;

use App\Enum\OptionValueType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

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


    //relations
    protected static function booted()
    {

        static::addGlobalScope('mine', function (Builder $builder) {
            $builder
                ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                    $query->where('owner_id', auth()->user()->id);
                });
        });
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


    //helpers

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
                    ->options($attribute->values->pluck('label', 'id'));
            })
            ->toArray();


        $fields = array_merge($varients, [
            Forms\Components\TextInput::make('quantity')->numeric()
        ]);


        if ($productType->is_varient_price)
            $fields[] =  Forms\Components\TextInput::make('price')->numeric();

        $fields[] = SpatieMediaLibraryFileUpload::make('images')
            ->multiple()
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
