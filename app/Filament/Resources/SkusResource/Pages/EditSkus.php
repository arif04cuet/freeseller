<?php

namespace App\Filament\Resources\SkusResource\Pages;

use App\Filament\Resources\SkusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSkus extends EditRecord
{
    protected static string $resource = SkusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
