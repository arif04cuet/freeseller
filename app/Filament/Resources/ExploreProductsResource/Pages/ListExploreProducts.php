<?php

namespace App\Filament\Resources\ExploreProductsResource\Pages;

use App\Filament\Resources\ExploreProductsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExploreProducts extends ListRecords
{
    protected static string $resource = ExploreProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
