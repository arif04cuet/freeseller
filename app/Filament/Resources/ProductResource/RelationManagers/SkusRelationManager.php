<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enum\SystemRole;
use App\Models\AttributeValue;
use App\Models\ResellerList;
use App\Models\Sku;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $label = '';
    protected static ?string $title = 'Varient & Quantity';

    protected static ?string $recordTitleAttribute = 'quantity';


    public function form(Form $form): Form
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

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('resellerLists'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getMedia("sharees")
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->hidden(true),
                Tables\Columns\TextColumn::make('resellerLists.name')
                    ->label('In your List')
                    ->badge(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (RelationManager $livewire) => $livewire->ownerRecord->isOwner())
                    ->using(function (Tables\Actions\CreateAction $action, HasRelationshipTable $livewire, array $data): Model {


                        DB::beginTransaction();

                        try {

                            $product = $livewire->ownerRecord;
                            $data['sku'] = uniqid($product->id);
                            $data['name'] = static::generateSkuName($product, $data);

                            $dataForSku = collect($data)->only(static::getSkuFields())->toArray();

                            $sku = $livewire->getRelationship()->create($dataForSku);

                            //save data to attribute_value_sku table
                            collect($data)->except(static::getSkuFields())
                                ->each(function ($value, $attribute) use ($sku) {
                                    $attributeValue = AttributeValue::find($value);
                                    $sku->attributeValues()->save($attributeValue);
                                });

                            DB::commit();

                            //add watermark

                            //static::addTextToImage($sku);

                            return $sku;
                        } catch (\Exception $e) {
                            DB::rollback();
                            Notification::make()
                                ->danger()
                                ->title('Something went wrong!')
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->actions([

                Tables\Actions\EditAction::make()
                    ->visible(fn (RelationManager $livewire) => $livewire->ownerRecord->isOwner())
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {

                        $data['1'] = $record->getColorAttributeValue()->id;
                        $data['images'] = $record->getMedia();

                        return $data;
                    })->using(function (Tables\Actions\EditAction $action, Model $record, array $data): Model {

                        $dataForSku = collect($data)->only('quantity', 'price')->toArray();
                        $record->update($dataForSku);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (RelationManager $livewire) => $livewire->ownerRecord->isOwner()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('add_to_lilst')
                    ->visible(auth()->user()->hasRole(SystemRole::Reseller->value))
                    ->label('Add to List')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Products added to specified list')
                    ->action(function (Tables\Actions\BulkAction $action, Collection $records, array $data): void {
                        ResellerList::find($data['list'])
                            ->skus()
                            ->syncWithoutDetaching($records->pluck('id')->toArray());

                        $action->success();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        Forms\Components\Select::make('list')
                            ->label('List')
                            ->options(auth()->user()->lists->pluck('name', 'id'))
                            ->default(fn () => auth()->user()->lists()->latest()->first()->id)
                            ->required(),
                    ])
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->resellerLists->count() == 0,
            );
    }

    public static function getSkuFields()
    {
        return ['name', 'sku', 'quantity', 'price'];
    }

    public static function generateSkuName($product, $data)
    {
        $attributes = collect($data)->except('sku', 'quantity', 'price')->toArray();

        $names = [$product->name];

        foreach ($attributes as $attributeId => $valueId) {

            if ($value = AttributeValue::find($valueId))
                $names[] = $value->label;
        }

        //$names[] = (int)($product->is_varient_price ? $data['price'] : $product->price);

        return collect($names)->implode("-");
    }
}
