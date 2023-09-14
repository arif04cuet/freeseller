<?php

namespace App\Filament\Resources\AllProductResource\Pages;

use App\Filament\Resources\AllProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Layout;

class ListAllProducts extends ListRecords
{
    protected static string $resource = AllProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableFiltersLayout(): ?string
    {
        return \Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible;
    }
}
