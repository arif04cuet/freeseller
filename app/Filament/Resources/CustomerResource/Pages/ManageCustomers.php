<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
        // return [
        //     ImportAction::make()
        //         ->uniqueField('mobile')
        //         ->fields([
        //             ImportField::make('name')
        //                 ->label('name')
        //                 ->required(),
        //             ImportField::make('mobile')
        //                 ->required()
        //                 ->label('name'),
        //             ImportField::make('email')
        //                 ->label('name'),
        //             ImportField::make('address')
        //                 ->required()
        //                 ->label('name'),

        //         ])->mutateAfterCreate(function (Model $model, $row) {

        //             $model->resellers()->attach(auth()->user()->id);

        //             return $model;
        //         }),
        // ];
    }
}
