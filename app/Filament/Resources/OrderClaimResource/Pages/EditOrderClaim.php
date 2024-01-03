<?php

namespace App\Filament\Resources\OrderClaimResource\Pages;

use App\Filament\Resources\OrderClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderClaim extends EditRecord
{
    protected static string $resource = OrderClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return OrderClaimResource::addWhoesalers($data);
    }
}
