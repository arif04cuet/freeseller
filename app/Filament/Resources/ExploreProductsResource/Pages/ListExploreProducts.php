<?php

namespace App\Filament\Resources\ExploreProductsResource\Pages;

use App\Filament\Resources\ExploreProductsResource;
use App\Traits\UseSimplePagination;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExploreProducts extends ListRecords
{
    use UseSimplePagination;
    protected static string $resource = ExploreProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
