<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\WholesalerOrderResource\Pages;
use App\Models\Order;
use App\Models\User;
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

    public static function canViewAny(): bool
    {
        return static::can('viewAny') && auth()->user()->isWholesaler();
    }

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
            ->with(['reseller', 'items', 'customer'])
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
                    ->date(),
                Tables\Columns\TextColumn::make('totalAmountForWholesaler')
                    ->label('Total amount'),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Products')
                    ->getStateUsing(fn (Model $record) => $record->getItemsByWholesaler(auth()->user())->sum('quantity')),

                Tables\Columns\TextColumn::make('returned')
                    ->getStateUsing(
                        fn (Model $record) => $record->getItemsByWholesaler(auth()->user(), OrderItemStatus::Returned->value)
                            ->filter(fn ($item) => $item->is_returned_to_wholesaler)
                            ->count() ? 'Received' : ''
                    )
                    ->badge()
                    ->visible(
                        fn ($livewire) => in_array($livewire->activeTab, [
                            OrderStatus::Cancelled->name,
                            OrderStatus::Partial_Delivered->name,
                        ])
                    )
                    ->color(fn (string $state): string => match ($state) {
                        'Received' => 'success',
                        default => ''
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('cancelled_note')
                    ->wrap()
                    ->color('danger')
                    ->visible(
                        fn ($livewire) => $livewire->activeTab == 'Trashed'

                    ),
            ])
            ->filters([
                //
            ])
            ->actions([

                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('items')
                        ->label('Products')
                        ->icon('heroicon-o-bars-4')
                        ->color('success')
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
                            function (StaticAction $action, Order $record) {

                                $wholesalerPendingItems = $record
                                    ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                                    ->count();
                                return $wholesalerPendingItems && ($record->status != OrderStatus::Cancelled) ? $action->label('Approve All') : false;
                            }
                        )
                        ->modalCancelAction(false)
                        ->extraModalFooterActions([
                            Tables\Actions\Action::make('cancel')
                                ->label('Reject')
                                ->visible(fn (Model $record) => $record?->status == OrderStatus::WaitingForWholesalerApproval)
                                ->requiresConfirmation()
                                ->color('danger')
                                ->form([
                                    forms\Components\Textarea::make('cancel_note')
                                        ->required()
                                ])
                                ->cancelParentActions()
                                ->action(
                                    function (Model $record, array $data, $action) {

                                        $wholesaler = auth()->user();

                                        $record->update([
                                            'status' => OrderStatus::Cancelled->value,
                                            'cancelled_note' => $data['cancel_note'],
                                            'cancelled_by' => $wholesaler->id
                                        ]);
                                        $record->items()
                                            ->where('wholesaler_id', $wholesaler->id)
                                            ->update([
                                                'status' => OrderItemStatus::Cancelled->value
                                            ]);

                                        //send notification to reseller
                                        User::sendMessage(
                                            users: $record->reseller,
                                            title: 'Wholesaler cancelled the order#' . $record->id,
                                            body: $data['cancel_note'],
                                            url: route(
                                                'filament.app.resources.orders.index',
                                                [
                                                    'activeTab' => 'Trashed',
                                                    'tableSearch' => $record->id
                                                ]
                                            )
                                        );

                                        Notification::make()
                                            ->title('Order cancelled successfully')
                                            ->send();
                                    }
                                ),

                        ])
                        ->modalHeading(fn (Model $record) => 'Products list for order # ' . $record->id)
                        ->modalContent(fn (Model $record) => view('orders.items-status', [
                            'items' => $record->loadMissing('items.wholesaler')->getItemsByWholesaler(auth()->user()),
                        ])),

                    Tables\Actions\Action::make('track')
                        ->icon('heroicon-o-eye')
                        ->label('Track Order')
                        ->url(fn (Order $record) => $record->tracking_url)
                        ->visible(fn (Order $record) => $record->tracking_code)
                        ->openUrlInNewTab(),



                ])
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWholesalerOrders::route('/'),
        ];
    }
}
