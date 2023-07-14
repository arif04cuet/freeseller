<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ActivateUser
{

    public function handle(Verified $event): void
    {
        $event->user->markAsActive();
    }
}
