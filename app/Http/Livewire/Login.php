<?php

namespace App\Http\Livewire;

use JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Login as BreezLogin;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Http\Livewire\Auth\Login as FilamentLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use JeffGreco13\FilamentBreezy\Events\LoginSuccess;
use JeffGreco13\FilamentBreezy\FilamentBreezy;

class Login extends BreezLogin
{

    protected function attemptAuth($data)
    {
        // ->attempt will actually log the person in, then the response sends them to the dashboard. We need to catch the auth, show the code prompt, then log them in.
        if (!Filament::auth()->attempt([
            $this->loginColumn => $data[$this->loginColumn],
            'password' => $data['password'],
            'is_active' => 1
        ], $data['remember'])) {
            $this->addError($this->loginColumn, __('filament::login.messages.failed'));

            return null;
        }
        event(new LoginSuccess(Filament::auth()->user()));

        return app(LoginResponse::class);
    }
}
