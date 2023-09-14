<?php

namespace App\Filament\Resources\HubOrderResource\Pages;

use App\Filament\Resources\HubOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

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
}
