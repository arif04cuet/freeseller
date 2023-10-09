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

        if (!$user->is_active) {

            Notification::make()
                ->title('You have successfully created your account but your account is under verification. You will be notified via email when activated')
                ->success()
                ->send();

            Filament::auth()->logout();
        }
    }
}
