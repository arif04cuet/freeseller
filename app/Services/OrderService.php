<?php

namespace App\Services;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;

final class OrderService
{
    public static $resource;

    public static function resource($resource): static
    {
        self::$resource = $resource;

        return new static();
    }

    public static function tabs(SystemRole $role = null): array
    {

        return [
            'All' => Tab::make()
                ->badge(self::$resource::getEloquentQuery()->count())
                ->query(
                    fn ($query) => $query
                ),
            OrderStatus::WaitingForWholesalerApproval->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->whereIn('status', [
                            OrderStatus::WaitingForWholesalerApproval,
                            OrderStatus::Processing,
                        ])->count()
                )
                ->query(
                    fn ($query) => $query->whereIn('status', [
                        OrderStatus::WaitingForWholesalerApproval->value,
                        OrderStatus::Processing->value,
                    ])
                ),

            OrderStatus::WaitingForHubCollection->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::WaitingForHubCollection)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::WaitingForHubCollection->value)
                ),
            OrderStatus::ProcessingForHandOverToCourier->getLabel() => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::ProcessingForHandOverToCourier)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::ProcessingForHandOverToCourier->value)
                ),
            OrderStatus::HandOveredToCourier->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::HandOveredToCourier)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::HandOveredToCourier->value)
                ),

            OrderStatus::Cancelled->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::Cancelled)
                        ->whereNotNull('delivered_at')
                        ->count()
                )
                ->query(
                    fn ($query) => $query
                        ->where('status', OrderStatus::Cancelled->value)
                        ->whereNotNull('delivered_at')
                ),
            OrderStatus::Partial_Delivered->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::Partial_Delivered)
                        ->count()
                )
                ->query(
                    fn ($query) => $query
                        ->where('status', OrderStatus::Partial_Delivered->value)
                ),
            OrderStatus::Delivered->name => Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('status', OrderStatus::Delivered)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('status', OrderStatus::Delivered->value)
                ),

            'Trashed' => Tab::make()
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
