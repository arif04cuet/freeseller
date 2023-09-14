<?php

namespace App\Http\Controllers;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Filament\Http\Responses\Auth\Contracts\EmailVerificationResponse;

class EmailVerificationController extends Controller
{


    public function __invoke(Request $request): EmailVerificationResponse
    {
        $id = $request->id;
        $hash =  $request->hash;

        $user = User::find($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if (!$user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));

            if (!Filament::auth()->check())
                Filament::auth()->login($user);
        }

        return app(EmailVerificationResponse::class);
    }
}
