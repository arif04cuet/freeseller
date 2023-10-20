<?php

namespace App\Listeners;

use App\Enum\SystemRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Verified;

class NewSignupNotification
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        $owner = User::platformOwner();

        $userType = SystemRole::Reseller->name;
        $url = route('filament.app.resources.resellers.index', ['tableSearch' => $user->mobile]);

        if ($user->isWholesaler()) {
            $userType = SystemRole::Wholesaler->name;
            $url = route('filament.app.resources.wholesalers.index', ['tableSearch' => $user->mobile]);

            $hubId = $user->hub_id;

            if ($manager = User::getHubManagerByAddress($hubId)) {
                // send to hub manager
                User::sendMessage(
                    users: $manager,
                    title: 'A new wholesaler has been signed up at your hub. please review and activate.',
                    url: $url,
                    sent_email: true
                );
            }
        }

        // send to platform owner
        User::sendMessage(
            users: $owner,
            title: 'A new ' . $userType . ' has been signed up',
            url: $url,
            sent_email: true
        );
    }
}
