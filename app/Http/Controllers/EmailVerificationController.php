<?php

namespace App\Http\Controllers;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class EmailVerificationController extends Controller
{


    public function __invoke(string $id, string $hash): RedirectResponse
    {

        $user = User::find($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(config("filament.home_url"));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->markAsActive();

            if (!Filament::auth()->check())
                Filament::auth()->login($user);
        }

        return redirect(config("filament.home_url"));
    }
}
