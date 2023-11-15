<?php

namespace App\Filament\Resources\WholesalerOrderResource\Pages;

use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\WholesalerOrderResource;
use App\Services\OrderService;
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
        return OrderService::resource(static::$resource)::tabs(SystemRole::Wholesaler);
    }
}
