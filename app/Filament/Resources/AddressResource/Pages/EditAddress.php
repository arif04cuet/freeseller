<?php

namespace App\Filament\Resources\AddressResource\Pages;

use App\Enum\AddressType;
use App\Filament\Resources\AddressResource;
use App\Models\Address;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAddress extends EditRecord
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_id'] = auth()->id();

        switch ($data['type']) {
            case AddressType::Upazila->value:
                $district = Address::find($data['parent_id']);
                $data['district'] = $district->id;
                $data['division'] =  $district->parent_id;
                break;
            case AddressType::Union->value:
                $upazila = Address::with('parent')->find($data['parent_id']);
                $district = $upazila->parent;
                $data['upazila'] = $upazila->id;
                $data['district'] = $district->id;
                $data['division'] =  $district->parent_id;
                break;
            case AddressType::Hub->value:
                $union = Address::with('parent')->find($data['parent_id']);
                $upazila = $union->parent;
                $district = $upazila->parent;

                $data['union'] = $union->id;
                $data['upazila'] = $upazila->id;
                $data['district'] = $district->id;
                $data['division'] =  $district->parent_id;
                break;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
