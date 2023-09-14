<?php

namespace App\Filament\Resources\WholesalerOrderResource\Pages;

use App\Filament\Resources\WholesalerOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWholesalerOrders extends ManageRecords
{
    protected static string $resource = WholesalerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
