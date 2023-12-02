<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Filament\Resources\SkusResource\Pages;
use App\Filament\Resources\SkusResource\RelationManagers;
use App\Models\OrderItem;
use App\Models\Sku;
use App\Tables\Columns\QuantityUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SkusResource extends Resource
{
    protected static ?string $model = Sku::class;

    protected static ?string $navigationGroup = 'Wholesaler';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'My Stock';
    protected static ?string $pluralModelLabel = 'My Stock';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->isWholesaler();
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->mine()
            ->orderBy('quantity', 'asc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->sum('quantity');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
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
                        ->searchable(),
                    Tables\Columns\TextColumn::make('product.price')
                        ->label('Price')
                        ->formatStateUsing(fn (Model $record, $state) => view('products.sku.price', ['product' => $record->product]))
                        ->html(),
                    Tables\Columns\TextColumn::make('id')
                        ->formatStateUsing(fn (Model $record) => '<b>' . $record->quantity . '</b>' . ' pieces available')
                        ->html(),
                    //QuantityUpdate::make('update_quantity')
                    Tables\Columns\TextInputColumn::make('quantity')
                        ->type('number')
                        ->rules(['required', 'numeric', 'min:0'])
                        ->updateStateUsing(function (Model $record, $state) {
                            $pendingOrderQnt = OrderItem::query()
                                ->where('status', OrderItemStatus::WaitingForWholesalerApproval->value)
                                ->sum('quantity');

                            if ($state < $pendingOrderQnt) {
                                Notification::make()
                                    ->title('You have pending order qnt=' . $pendingOrderQnt)
                                    ->danger()
                                    ->send();
                            } else {
                                $record->update(['quantity' => $state]);
                                Notification::make()
                                    ->title('Quantity Updated')
                                    ->success()
                                    ->send();
                            }
                        })
                ])->from('md'),

            ])
            ->filters([
                Tables\Filters\Filter::make('quantity')
                    ->default()
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->default(5)
                            ->label('Stock Under')
                    ])
                    ->query(
                        function (Builder $query, array $data): Builder {
                            return $query->when($data['value'], fn ($query) => $query->where('quantity', '<', $data['value']));
                        }
                    ),
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name', modifyQueryUsing: fn ($query) => $query->mine())
                    ->preload()
                    ->searchable(),

            ], layout: FiltersLayout::AboveContent)
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                //Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkuses::route('/')
        ];
    }
}
