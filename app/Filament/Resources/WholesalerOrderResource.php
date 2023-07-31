<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Filament\Resources\WholesalerOrderResource\Pages;
use App\Filament\Resources\WholesalerOrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\WholesalerOrder;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WholesalerOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Wholesaler';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $slug = 'wholesaler/orders';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'reseller'
            ])->mine();
    }

    protected static function getNavigationBadge(): ?string
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
                Tables\Columns\TextColumn::make('totalAmountForWholesaler'),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(fn (Model $record) => $record->getItemsByWholesaler(auth()->user())->sum('quantity')),
                Tables\Columns\BadgeColumn::make('status')
                    ->enum(OrderStatus::array())
                    ->colors([
                        'secondary' =>  OrderStatus::WaitingForWholesalerApproval->value,
                        'warning' =>  OrderStatus::Processing->value,
                        'success' => OrderStatus::Approved->value,
                        'danger' => OrderStatus::Cancelled->value,
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('items')
                    ->action(fn (Tables\Actions\Action $action, $data) => empty($data) ? $action->cancel() :  OrderItem::whereIn('id', $data['items'])->get()->each->markAsApproved())
                    ->modalButton('Approve')
                    ->modalHeading('Items details')
                    ->modalContent(fn (Model $record) => view('orders.items-status', [
                        'items' => $record->loadMissing('items.wholesaler')->getItemsByWholesaler(auth()->user())
                    ]))
                    ->form([
                        Forms\Components\CheckboxList::make('items')
                            ->visible(
                                fn (Model $record) => $record->loadMissing('items.sku')
                                    ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                                    ->count()
                            )
                            ->label('Approve or Reject Items')
                            ->options(
                                fn (Model $record) => $record->loadMissing('items.sku')
                                    ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                                    ->map(fn ($item) => [
                                        'id' => $item->id,
                                        'name' => $item->sku->name,
                                    ])
                                    ->pluck('name', 'id')
                            )
                            ->required(),
                    ])
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
