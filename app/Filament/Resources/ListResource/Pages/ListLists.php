<?php

namespace App\Filament\Resources\ListResource\Pages;

use App\Filament\Resources\ListResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLists extends ListRecords
{
    protected static string $resource = ListResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
