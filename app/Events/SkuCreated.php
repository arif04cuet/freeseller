<?php

namespace App\Events;

use App\Models\Sku;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SkuCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Sku $sku)
    {
    }
}
