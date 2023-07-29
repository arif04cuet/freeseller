<?php

namespace App\Filament\Resources\WholesalerResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\WholesalerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWholesalers extends ListRecords
{
    protected static string $resource = WholesalerResource::class;

    protected function getActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->wholesalers()->mine();
    }
}
