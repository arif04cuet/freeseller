<?php

namespace App\Filament\Resources\ListResource\Pages;

use App\Filament\Resources\ListResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLists extends ManageRecords
{
    protected static string $resource = ListResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
