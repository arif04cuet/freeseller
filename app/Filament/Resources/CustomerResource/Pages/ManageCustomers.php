<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->uniqueField('mobile')
                ->fields([
                    ImportField::make('name')
                        ->label('name')
                        ->required(),
                    ImportField::make('mobile')
                        ->required()
                        ->label('name'),
                    ImportField::make('email')
                        ->label('name'),
                    ImportField::make('address')
                        ->required()
                        ->label('name'),

                ])->mutateAfterCreate(function (Model $model, $row) {

                    $model->resellers()->attach(auth()->user()->id);

                    return $model;
                })
        ];
    }
}
