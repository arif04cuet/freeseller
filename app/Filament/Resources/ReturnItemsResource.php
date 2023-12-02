<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\ReturnItemsResource\Pages;
use App\Filament\Resources\ReturnItemsResource\RelationManagers;
use App\Models\Business;
use App\Models\OrderItem;
use App\Models\ReturnItems;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class ReturnItemsResource extends Resource
{
    protected static ?string $model = OrderItem::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Hub';
    protected static ?string $modelLabel = 'Return Items';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::HubManager->value,
            SystemRole::HubMember->value,
            'super_admin',
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->where('is_returned_to_wholesaler', 0)->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('order', function ($q) {
                $q->mine();
            })
            ->where('status', OrderItemStatus::Returned->value)
            ->with([
                'order',
                'wholesaler',
                'wholesaler.business',
                'sku'
            ])->latest();
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label(fn () => request()->get('activeTab') == 'Returned to Wholesaler' ? 'Received At' : 'Arrived at')
                    ->getStateUsing(
                        fn (Model $record) => !$record->is_returned_to_wholesaler ?
                            $record->order->delivered_at->since() : $record->return_received_at
                    ),
                Tables\Columns\TextColumn::make('order_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('Image')
                    ->html()
                    ->getStateUsing(fn (Model $record) => '<img src="' . $record->sku->getMedia('*')->first()?->getUrl('thumb') . '"/>'),
                Tables\Columns\TextColumn::make('sku.name')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('return_qnt'),
                Tables\Columns\TextColumn::make('wholesaler.business.name'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->preload()
                    ->searchable()
                    ->options(Business::query()->wholesaler()->pluck('name', 'id'))
                    ->query(
                        function (Builder $query, array $data): Builder {
                            $businessId = $data['value'];
                            //logger($businessId);
                            return $query
                                ->when($businessId, function ($q) use ($businessId) {
                                    return $q->whereHas('wholesaler', function ($q) use ($businessId) {
                                        return $q->whereRelation('business', 'id', $businessId);
                                    });
                                });
                        }
                    ),
            ])

            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status == OrderItemStatus::Returned &&
                    !$record->is_returned_to_wholesaler,
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('send_otp')
                        ->visible(fn () => auth()->user()->isHubManager())
                        ->icon('heroicon-o-envelope')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('OTP has been sent to wholesaler.')
                        ->fillForm(
                            function (Collection $records): array {

                                $parcels = $records->pluck('order_id')->unique();
                                $return_qnt = $records->sum('return_qnt');

                                $otp = random_int(100000, 999999);
                                $item = $records->first();
                                //logger($otp);
                                Notification::make()
                                    ->success()
                                    ->title('OTP sent to wholesaler')
                                    ->send();

                                User::sendMessage(
                                    users: $item->wholesaler,
                                    title: 'Product return OTP = ' . $otp,
                                    body: 'Total Products= ' . $return_qnt . ' and Total Orders = ' . $parcels->count() . ' (' . $parcels->implode(',') . ')',
                                    actions: [
                                        Action::make('read')
                                            ->button()
                                            ->markAsRead()
                                    ]
                                );

                                return [
                                    'sent_otp' => $otp,
                                    'orders' => $parcels->count(),
                                    'items' => $return_qnt
                                ];
                            }
                        )
                        ->form([
                            Forms\Components\Placeholder::make('orders')
                                ->hiddenLabel()
                                ->content(fn (Get $get, $state) => 'Total Products= ' . $get('items') . ' and Total Orders = ' . $get('orders')),
                            Forms\Components\TextInput::make('entered_otp')
                                ->label('Enter OTP to verify')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('sent_otp')
                                ->hidden()
                        ])
                        ->action(
                            function (Collection $records, Form $form, Tables\Actions\BulkAction $action) {

                                $data = $form->getRawState();

                                if ($data['entered_otp'] != $data['sent_otp']) {

                                    Notification::make()
                                        ->danger()
                                        ->title('OTP mismatched!')
                                        ->send();

                                    $action->halt();
                                }


                                OrderItem::query()
                                    ->whereIn('id', $records->pluck('id')->toArray())
                                    ->update([
                                        'is_returned_to_wholesaler' => true,
                                        'return_received_at' => now()
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Success!')
                                    ->send();
                            }
                        )
                ]),
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
            'index' => Pages\ListReturnItems::route('/'),
        ];
    }
}
