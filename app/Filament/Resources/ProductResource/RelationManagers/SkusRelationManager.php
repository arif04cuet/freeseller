<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\AttributeValue;
use App\Models\Sku;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $label = '';
    protected static ?string $title = 'Varient & Quantity';

    protected static ?string $recordTitleAttribute = 'quantity';


    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Forms\Components\Grid::make('varients')
                    ->columns(2)
                    ->schema(function (RelationManager $livewire): array {
                        return $livewire->ownerRecord->getVarientFormSchema();
                    }),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('color')
                    ->getStateUsing(fn (Model $record) => $record->getColorAttributeValue()->label),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->hidden(true),
                Tables\Columns\TextColumn::make('images')
                    ->getStateUsing(fn (Model $record) => view('products.image', compact('record'))->render())
                    ->html()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (HasRelationshipTable $livewire, array $data): Model {

                        $product = $livewire->ownerRecord;
                        $data['sku'] = uniqid($product->id);

                        $dataForSku = collect($data)->only('sku', 'quantity', 'price')->toArray();
                        $sku = $livewire->getRelationship()->create($dataForSku);

                        //save data to attribute_value_sku table
                        $attributes = collect($data)->except('sku', 'quantity')
                            ->each(function ($value, $attribute) use ($sku) {
                                $attributeValue = AttributeValue::find($value);
                                $sku->attributeValues()->save($attributeValue);
                            });


                        return $sku;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {

                        $data['1'] = $record->getColorAttributeValue()->id;
                        $data['images'] = $record->getMedia();

                        return $data;
                    })->using(function (Model $record, array $data): Model {

                        $dataForSku = collect($data)->only('quantity', 'price')->toArray();
                        $record->update($dataForSku);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
