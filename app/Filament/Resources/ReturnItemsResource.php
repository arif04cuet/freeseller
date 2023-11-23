<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Filament\Resources\ReturnItemsResource\Pages;
use App\Filament\Resources\ReturnItemsResource\RelationManagers;
use App\Models\Business;
use App\Models\OrderItem;
use App\Models\ReturnItems;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
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
                'sku'
            ])->latest();
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('sku')
                    ->label('Image')
                    ->defaultImageUrl(fn (Model $record) => $record->sku->getMedia("*")->first()?->getUrl('thumb')),
                Tables\Columns\TextColumn::make('sku.name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('return_qnt'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->preload()
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
                        //->visible(fn () => auth()->user()->isHubManager())
                        ->icon('heroicon-o-envelope')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('OTP has been sent to wholesaler.')
                        ->fillForm(
                            function (Collection $records): array {

                                $otp = random_int(100000, 999999);
                                $item = $records->first();
                                logger($otp);
                                Notification::make()
                                    ->success()
                                    ->title('OTP sent to wholesaler')
                                    ->send();

                                User::sendMessage(
                                    users: $item->wholesaler,
                                    title: 'OTP to collect return product for order=' . $item->order->id,
                                    body: 'OTP : ' . $otp,
                                    actions: [
                                        Action::make('read')
                                            ->button()
                                            ->markAsRead()
                                    ]
                                );

                                return [
                                    'sent_otp' => $otp
                                ];
                            }
                        )
                        ->form([
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
                                        'is_returned_to_wholesaler' => true
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
