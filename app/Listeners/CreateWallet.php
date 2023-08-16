<?php

namespace App\Listeners;

use App\Enum\SystemRole;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateWallet
{

    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user->hasAnyRole([
            SystemRole::Wholesaler->value,
            SystemRole::Reseller->value
        ])) {
            $event->user->deposit(0);
        }
    }
}
