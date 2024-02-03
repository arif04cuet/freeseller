<?php

namespace App\Listeners;

use App\Enums\GeoLevel;
use App\Enums\Ngo;
use App\Models\District;
use App\Models\Union;
use App\Models\Upazila;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddUserDataToSessionAfterLogin
{

    public function handle(Login $event): void
    {
        /** @var App\Models\User $user */
        $user = $event->user;

        $data = [
            'name' => $user->name,
            'role' => $user->roles->first()->name,
            'avatar_url' => $user->getFilamentAvatarUrl(),
            'filament_name' => $user->getFilamentName(),
        ];

        if ($user->isBusiness()) {
            $data['business_id'] = $user->id_number;
            $data['business_name'] = $user->business->name;
        }

        session()->put('user_info', $data);
    }
}
