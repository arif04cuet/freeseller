<?php

namespace App\Filament\Resources\SkusResource\Pages;

use App\Filament\Resources\SkusResource;
use App\Models\Sku;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSkuses extends ListRecords
{
    protected static string $resource = SkusResource::class;


    protected function getHeaderActions(): array
    {
        return [];
    }
}
