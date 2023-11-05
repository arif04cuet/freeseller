<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;
use Livewire\Attributes\On;

class QuantityUpdate extends Column
{
    protected string $view = 'tables.columns.quantity-update';

    public $show = false;

    public function isShow()
    {
        return $this->show;
    }
}
