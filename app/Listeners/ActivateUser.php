<?php

namespace App\Listeners;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Verified;

class ActivateUser
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user->autoActivation()) {
            $user->markAsActive();
        }

        $user->fresh();

        if (! $user->is_active) {

            Notification::make()
                ->title('Your account is inactive. Please wait untill activated')
                ->danger()
                ->send();

            Filament::auth()->logout();
        }
    }
}
