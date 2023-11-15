<?php

namespace App\Services;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;

final class OrderService
{
    public static $resource;

    public static function resource($resource): static
    {
        self::$resource = $resource;

        return new static();
    }

    public  static function tabs(SystemRole $role = null): array
    {
        return [
            'All' => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()->count()
                )
                ->query(
                    fn ($query) => $query
                ),
            OrderStatus::WaitingForWholesalerApproval->name => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->whereIn('status', [
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
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::WaitingForHubCollection->value)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::WaitingForHubCollection->value)
                ),
            OrderStatus::ProcessingForHandOverToCourier->name => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::ProcessingForHandOverToCourier->value)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::ProcessingForHandOverToCourier->value)
                ),
            OrderStatus::HandOveredToCourier->name => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::HandOveredToCourier->value)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::HandOveredToCourier->value)
                ),

            OrderStatus::Cancelled->name => ListRecords\Tab::make()
                ->label('Return')
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->whereIn('status', [
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
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::Delivered->value)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::Delivered->value)
                ),

            'Trashed' => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
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
