<?php

namespace App\Filament\Resources\WholesalerOrderResource\Pages;

use App\Enum\OrderStatus;
use App\Filament\Resources\WholesalerOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ListRecords;

class ManageWholesalerOrders extends ManageRecords
{
    protected static string $resource = WholesalerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {

        return [

            'All' => ListRecords\Tab::make()
                ->badge(static::getResource()::getEloquentQuery()->count())
                ->query(fn ($query) => $query),

            OrderStatus::WaitingForWholesalerApproval->name => ListRecords\Tab::make()
                ->badge(
                    static::getResource()::getEloquentQuery()->whereIn('status', [
                        OrderStatus::WaitingForWholesalerApproval->value,
                        OrderStatus::Processing->value,
                    ])->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with(['reseller'])
                        ->mine()
                        ->whereIn('status', [
                            OrderStatus::WaitingForWholesalerApproval->value,
                            OrderStatus::Processing->value,
                        ])
                        ->latest()
                ),

            OrderStatus::HandOveredToCourier->name => ListRecords\Tab::make()
                ->badge(
                    static::getResource()::getEloquentQuery()
                        ->where('status', OrderStatus::HandOveredToCourier->value)->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with(['reseller'])
                        ->mine()
                        ->where('status', OrderStatus::HandOveredToCourier->value)
                        ->latest()

                ),

            OrderStatus::Cancelled->name => ListRecords\Tab::make()
                ->label('Return')
                ->badge(
                    static::getResource()::getEloquentQuery()
                        ->whereIn('status', [
                            OrderStatus::Cancelled->value,
                            OrderStatus::Partial_Delivered->value,
                        ])->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with(['reseller'])
                        ->mine()
                        ->whereIn('status', [
                            OrderStatus::Cancelled->value,
                            OrderStatus::Partial_Delivered->value,
                        ])
                        ->latest()
                ),

            OrderStatus::Delivered->name => ListRecords\Tab::make()
                ->badge(
                    static::getResource()::getEloquentQuery()->where('status', OrderStatus::Delivered->value)->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with(['reseller'])
                        ->mine()
                        ->where('status', OrderStatus::Delivered->value)
                        ->latest()
                ),


        ];
    }
}
