<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait RecordCountTrait
{
    public string | Htmlable | null $subh = null;

    public function getSubheading(): string | Htmlable | null
    {
        if (!$this->subh)
            $this->subh = 'Total = ' . $this->getTableRecords()->count();

        return $this->subh;
    }
}
