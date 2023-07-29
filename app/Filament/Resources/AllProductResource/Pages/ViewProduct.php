<?php

namespace App\Filament\Resources\AllProductResource\Pages;

use App\Filament\Resources\AllProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = AllProductResource::class;
    protected static string $view = 'products.view';
}
