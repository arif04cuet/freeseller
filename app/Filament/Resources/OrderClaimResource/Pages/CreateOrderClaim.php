<?php

namespace App\Filament\Resources\OrderClaimResource\Pages;

use App\Filament\Resources\OrderClaimResource;
use App\Models\OrderItem;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderClaim extends CreateRecord
{
    protected static string $resource = OrderClaimResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OrderClaimResource::addWhoesalers($data);
    }
}
