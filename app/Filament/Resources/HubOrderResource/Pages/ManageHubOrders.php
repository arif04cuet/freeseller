<?php

namespace App\Filament\Resources\HubOrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Filament\Resources\HubOrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ListRecords;

class ManageHubOrders extends ManageRecords
{
    protected static string $resource = HubOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function collect_product()
    {
        dd(func_get_args());
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return  OrderStatus::WaitingForHubCollection->name;
    }

    public function getTabs(): array
    {

        return [
            'All' => ListRecords\Tab::make()
                ->badge(
                    Order::query()->count()
                )
                ->query(
                    fn ($query) => $query
                ),
            OrderStatus::WaitingForWholesalerApproval->name => ListRecords\Tab::make()
                ->badge(
                    Order::query()->whereIn('status', [
                        OrderStatus::WaitingForWholesalerApproval->value,
                        OrderStatus::Processing->value,
                    ])->count()
                )
                ->query(
                    fn ($query) => $query->whereIn('status', [
                        OrderStatus::WaitingForWholesalerApproval->value,
                        OrderStatus::Processing->value,
                    ])
                ),

            OrderStatus::WaitingForHubCollection->name => ListRecords\Tab::make()
                ->badge(
                    Order::query()->where('status', OrderStatus::WaitingForHubCollection->value)->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::WaitingForHubCollection->value)
                ),
            OrderStatus::ProcessingForHandOverToCourier->name => ListRecords\Tab::make()
                ->badge(
                    Order::query()->where('status', OrderStatus::ProcessingForHandOverToCourier->value)->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::ProcessingForHandOverToCourier->value)
                ),
            OrderStatus::HandOveredToCourier->name => ListRecords\Tab::make()
                ->badge(
                    Order::query()->where('status', OrderStatus::HandOveredToCourier->value)->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::HandOveredToCourier->value)
                ),

            OrderStatus::Cancelled->name => ListRecords\Tab::make()
                ->label('Return')
                ->badge(
                    Order::query()->whereIn('status', [
                        OrderStatus::Cancelled->value,
                        OrderStatus::Partial_Delivered->value,
                    ])
                        ->whereDoesntHave('items', fn ($q) => $q->where('status', OrderItemStatus::Cancelled->value))
                        ->count()
                )
                ->query(
                    fn ($query) => $query->whereIn('status', [
                        OrderStatus::Cancelled->value,
                        OrderStatus::Partial_Delivered->value,
                    ])
                        ->whereDoesntHave('items', fn ($q) => $q->where('status', OrderItemStatus::Cancelled->value))
                ),

            OrderStatus::Delivered->name => ListRecords\Tab::make()
                ->badge(
                    Order::query()->where('status', OrderStatus::Delivered->value)->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::Delivered->value)
                ),

            'Trashed' => ListRecords\Tab::make()
                ->badge(
                    Order::query()
                        ->with('items')
                        ->whereHas('items', fn ($q) => $q->where('status', OrderItemStatus::Cancelled->value))
                        ->count()
                )
                ->query(
                    fn ($query) => $query->with('items')
                        ->whereHas('items', fn ($q) => $q->where('status', OrderItemStatus::Cancelled->value))
                ),


        ];
    }
}
