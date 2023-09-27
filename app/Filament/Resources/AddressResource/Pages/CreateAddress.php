<?php

namespace App\Filament\Resources\AddressResource\Pages;

use App\Enum\AddressType;
use App\Filament\Resources\AddressResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAddress extends CreateRecord
{
    protected static string $resource = AddressResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parent_id'] = match ($data['type']) {
            AddressType::Upazila->value => $data[AddressType::District->value],
            AddressType::Union->value => $data[AddressType::Upazila->value],
            AddressType::Hub->value => $data[AddressType::Union->value],
        };

        $data = collect($data)->only(['name', 'parent_id', 'type'])->toArray();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
