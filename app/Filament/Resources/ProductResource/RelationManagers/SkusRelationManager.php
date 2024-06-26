<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enum\OrderItemStatus;
use App\Enum\SystemRole;
use App\Models\AttributeValue;
use App\Models\OrderItem;
use App\Models\ResellerList;
use App\Models\Sku;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;

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
            ->modifyQueryUsing(
                fn (Builder $query) => $query
                    ->with('myResellerLists')
                    ->orderBy('deleted_at')
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ])
            )
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getMedia('sharees'),
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Color')
                    ->formatStateUsing(fn (Model $record) => array_reverse(explode('–', $record->name))[0]),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->hidden(true),
                Tables\Columns\TextColumn::make('myResellerLists.name')
                    ->label('In your List')
                    // ->getStateUsing(
                    //     fn (Model $record) => $record->resellerLists()->where('user_id', auth()->id())->pluck('name')->toArray()
                    // )
                    // ->toggleable(isToggledHiddenByDefault: fn () => auth()->user()->isWholesaler())
                    ->badge(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (RelationManager $livewire) => $livewire->ownerRecord->isOwner())
                    ->using(function (Tables\Actions\CreateAction $action, RelationManager $livewire, array $data): Model {

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
                                    $attributeValue && $sku->attributeValues()->save($attributeValue);
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
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (RelationManager $livewire, Model $record) => $livewire->ownerRecord->isOwner() && !$record->trashed()

                    )
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {

                        $data['1'] = $record->getColorAttributeValue()->id;
                        $data['images'] = $record->getMedia();

                        return $data;
                    })->using(function (Tables\Actions\EditAction $action, Model $record, array $data): Model {

                        $dataForSku = collect($data)->only('quantity', 'price')->toArray();

                        $pendingOrderQnt = OrderItem::query()
                            ->where('status', OrderItemStatus::WaitingForWholesalerApproval->value)
                            ->where('sku_id', $record->id)
                            ->sum('quantity');

                        if ($dataForSku['quantity'] < $pendingOrderQnt) {
                            Notification::make()
                                ->title('You have pending order qnt=' . $pendingOrderQnt)
                                ->danger()
                                ->send();
                            $action->halt();
                        }

                        $record->update($dataForSku);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Archive')
                    ->modalHeading('Archive it?')
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
                            ->relationship(
                                name: 'resellerLists',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(auth()->user()),
                            )
                            //->options(auth()->user()->lists->pluck('name', 'id'))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                                        return $rule
                                            ->where('name', $get('name'))
                                            ->where('user_id', auth()->user()->id);
                                    }, ignoreRecord: true),
                            ])

                            ->default(fn () => auth()->user()->lists()?->latest()?->first()?->id)
                            ->required(),
                    ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => true,
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

            if ($value = AttributeValue::find($valueId)) {
                $names[] = $value->label;
            }
        }

        //$names[] = (int)($product->is_varient_price ? $data['price'] : $product->price);

        return collect($names)->implode('-');
    }
}
