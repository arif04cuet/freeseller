<?php

namespace App\Filament\Resources\WholesalerResource\Pages;

use App\Filament\Resources\WholesalerResource;
use App\Traits\RecordCountTrait;
use App\Traits\UseSimplePagination;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWholesalers extends ListRecords
{
    use UseSimplePagination;
    use RecordCountTrait;

    protected static string $resource = WholesalerResource::class;

    protected function getHeaderActions(): array
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
