<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Pages\Auth\Login as AuthLogin;
use Illuminate\Validation\ValidationException;

class Login extends AuthLogin
{
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => 1,
        ];
    }

    protected function throwFailureValidationException(): never
    {
        $data = $this->form->getState();

        $msg = __('filament-panels::pages/auth/login.messages.failed');

        $user = User::whereEmail($data['email'])->first();
        if (!$user->is_active)
            $msg = 'This account is not active yet.';

        throw ValidationException::withMessages([
            'data.email' => $msg,
        ]);
    }
}
