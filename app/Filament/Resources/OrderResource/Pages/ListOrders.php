<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderStatus;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrderResource\Widgets\OrderInstruction::class,
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
                        ->with('items')
                        ->mine()
                        ->whereIn('status', [
                            OrderStatus::WaitingForWholesalerApproval->value,
                            OrderStatus::Processing->value,
                        ])
                        ->latest()
                ),
            OrderStatus::WaitingForHubCollection->name => ListRecords\Tab::make()
                ->badge(
                    static::getResource()::getEloquentQuery()->where('status', OrderStatus::WaitingForHubCollection->value)->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with('items')
                        ->mine()
                        ->where('status', OrderStatus::WaitingForHubCollection->value)
                        ->latest()
                ),
            OrderStatus::HandOveredToCourier->name => ListRecords\Tab::make()
                ->badge(
                    static::getResource()::getEloquentQuery()
                        ->where('status', OrderStatus::HandOveredToCourier->value)->count()
                )
                ->query(
                    fn ($query) => $query
                        ->with('items')
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
                        ->with('items')
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
                        ->with('items')
                        ->mine()
                        ->where('status', OrderStatus::Delivered->value)
                        ->latest()
                ),


        ];
    }
}
