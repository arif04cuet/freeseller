<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\WholesalerOrderResource\Pages;
use App\Models\Order;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WholesalerOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Wholesaler';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'wholesaler/orders';

    protected static ?string $modelLabel = 'Wholesale Orders';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::Wholesaler->value,
            'super_admin',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['reseller'])
            ->mine()
            ->latest();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
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
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since(),
                Tables\Columns\TextColumn::make('totalAmountForWholesaler')
                    ->label('Total amount'),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Products')
                    ->getStateUsing(fn (Model $record) => $record->getItemsByWholesaler(auth()->user())->sum('quantity')),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('track')
                    ->label('Track Order')
                    ->url(fn (Order $record) => 'https://steadfast.com.bd/t/' . $record->tracking_code)
                    ->visible(fn (Order $record) => $record->tracking_code)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('items')
                    ->label('Products')
                    ->icon('heroicon-o-bars-4')
                    ->iconButton()
                    ->action(
                        function (Tables\Actions\Action $action, $data, Order $record) {

                            if (isset($data['collector_code'])) {

                                $collection = $record->collections->filter(fn ($item) => $item->wholesaler_id == auth()->user()->id)->first();

                                if (!$collection || ($collection->collector_code != $data['collector_code'])) {

                                    Notification::make()
                                        ->title('Code mismatch')
                                        ->danger()
                                        ->send();

                                    $action->halt();
                                }

                                $record->deliverToCollector($collection);
                            } else {
                                $record->getItemsByWholesaler(auth()->user())->each->markAsApproved();
                            }
                        }
                    )
                    ->modalSubmitAction(
                        fn (StaticAction $action, Order $record) =>
                        $record->status != OrderStatus::WaitingForWholesalerApproval ?
                            false : $action->label('Approve All')
                    )

                    ->modalHeading('Items details')
                    ->modalContent(fn (Model $record) => view('orders.items-status', [
                        'items' => $record->loadMissing('items.wholesaler')->getItemsByWholesaler(auth()->user()),
                    ]))
                    ->form([
                        // Forms\Components\CheckboxList::make('items')
                        //     ->visible(
                        //         fn (Model $record) => $record->loadMissing('items.sku')
                        //             ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                        //             ->count()
                        //     )
                        //     ->label('Approve or Reject Items')
                        //     ->options(
                        //         fn (Model $record) => $record->loadMissing('items.sku')
                        //             ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                        //             ->map(fn ($item) => [
                        //                 'id' => $item->id,
                        //                 'name' => $item->sku->name,
                        //             ])
                        //             ->pluck('name', 'id')
                        //     )
                        //     ->required(),

                        Forms\Components\TextInput::make('collector_code')
                            ->numeric()
                            ->required()
                            ->helperText('Ask 6 digits code from collector')
                            ->minLength(6)
                            ->maxLength(6)
                            ->visible(
                                fn (Order $record) => $record->collector?->id &&
                                    is_null($record->collections->filter(fn ($item) => $item->wholesaler_id == auth()->user()->id)->first()?->collected_at) &&
                                    auth()->user()->isWholesaler()
                            ),
                    ]),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWholesalerOrders::route('/'),
        ];
    }
}
