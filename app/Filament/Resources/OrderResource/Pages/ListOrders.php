<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderResource;
use App\Services\OrderService;
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

        return OrderService::resource(static::$resource)->tabs(SystemRole::Reseller);
    }
}
