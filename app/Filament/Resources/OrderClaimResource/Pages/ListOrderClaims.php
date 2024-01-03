<?php

namespace App\Filament\Resources\OrderClaimResource\Pages;

use App\Filament\Resources\OrderClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrderClaims extends ListRecords
{
    protected static string $resource = OrderClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
