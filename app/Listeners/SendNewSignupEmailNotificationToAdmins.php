<?php

namespace App\Listeners;

use App\Notifications\NewSignupAdminNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

class SendNewSignupEmailNotificationToAdmins
{
    public function handle(Registered $event)
    {
        $user = $event->user;

        // admin users who will get notifications
        $admins = User::whereEmail('arif04cuet@gmail.com')->first();
        Notification::send($admins,new NewSignupAdminNotification($user))
    }
}
