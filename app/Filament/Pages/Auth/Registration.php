<?php

namespace App\Filament\Pages\Auth;

use App\Enum\AddressType;
use App\Enum\BusinessType;
use App\Models\Address;
use App\Models\Role;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DB;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register;
use Filament\Forms;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use FilamentBreezy;
use Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules\Password;

class Registration extends Register
{

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $this->registerUser();

        return app(RegistrationResponse::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-breezy::default.fields.name'))
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label(__('filament-breezy::default.fields.email'))
                    ->required()
                    ->email()
                    ->unique(table: config('filament-breezy.user_model')),
                Forms\Components\TextInput::make('password')
                    ->label(__('filament-breezy::default.fields.password'))
                    ->required()
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->rules([
                        'required',
                        Password::min(8)
                    ]),
                Forms\Components\TextInput::make('password_confirm')
                    ->label(__('filament-breezy::default.fields.password_confirm'))
                    ->required()
                    ->password()
                    ->same('password'),
                Forms\Components\TextInput::make('mobile')
                    ->label('Mobile')
                    ->rules('numeric|digits_between:11,11')
                    ->regex('/^(?:\+?88|0088)?01[15-9]\d{8}$/i')
                    ->placeholder('01xxxxxxxxx')
                    ->unique(table: config('filament-breezy.user_model'))
                    ->required(),

                Forms\Components\Fieldset::make('Business Information')
                    ->schema([

                        Forms\Components\Select::make("business_type")
                            ->label('Type')
                            ->options(BusinessType::array())
                            ->enum(BusinessType::class)
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make("business_name")
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make("business_url")
                            ->label('FB/Website Url')
                            ->url()
                            ->required(),
                        Forms\Components\TextInput::make("business_estd_year")
                            ->label('Estd. Year')
                            ->type('number')
                            ->minLength(4)
                            ->maxLength(4)
                            ->rules('min:4,max:4')
                            ->required(),

                        Forms\Components\Fieldset::make('Select closest Hub')
                            ->schema([
                                Forms\Components\Select::make('division')
                                    ->options(Address::whereType(AddressType::Division->value)->pluck('name', 'id'))
                                    ->required()
                                    ->reactive(),
                                Forms\Components\Select::make('district')
                                    ->options(fn (\Filament\Forms\Get $get) => Address::query()
                                        ->whereType(AddressType::District->value)
                                        ->whereParentId($get('division'))
                                        ->pluck('name', 'id'))
                                    ->reactive(),

                                Forms\Components\Select::make('upazila')
                                    ->options(fn (\Filament\Forms\Get $get) => Address::query()
                                        ->whereType(AddressType::Upazila->value)
                                        ->whereParentId($get('district'))
                                        ->pluck('name', 'id'))
                                    ->reactive(),

                                Forms\Components\Select::make('union')
                                    ->options(fn (\Filament\Forms\Get $get) => Address::query()
                                        ->whereType(AddressType::Union->value)
                                        ->whereParentId($get('upazila'))
                                        ->pluck('name', 'id'))
                                    ->reactive(),
                                Forms\Components\Select::make('hub')
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('business_type') == BusinessType::Wholesaler->value)
                                    ->required()
                                    ->options(fn (\Filament\Forms\Get $get) => Address::query()
                                        ->whereType(AddressType::Hub->value)
                                        ->whereParentId($get('union'))
                                        ->pluck('name', 'id')),

                                Forms\Components\TextInput::make("address")
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('business_type') == BusinessType::Reseller->value)
                                    ->label('Address')
                                    ->required(),
                            ])->columns(1),

                        Forms\Components\Checkbox::make('consent_to_terms')->label('I consent to the terms of service and privacy policy.')->required()

                    ])
            ]);
    }

    protected function prepareModelData($data): array
    {

        $preparedData = $data;
        $preparedData['is_active'] = 0;

        if ($data['business_type'] == BusinessType::Wholesaler->value) {
            $preparedData["addressData"]['address_id'] = $preparedData['hub_id'] = $data['hub'];
        } else {

            $preparedData["addressData"]['address_id'] = $data['union'] ?? $data['upazila'] ?? $data['district'] ?? $data['division'];
            $preparedData["addressData"]['addressData'] = $data['address'];
        }


        $preparedData["business"]["type"] = $data['business_type'];
        $preparedData["business"]["name"] = $data['business_name'];
        $preparedData["business"]["estd_year"] = $data['business_estd_year'];
        $preparedData["business"]["url"] = $data['business_url'];


        return $preparedData;
    }

    public function registerUser()
    {
        $preparedData = $this->prepareModelData($this->form->getState());

        DB::transaction(function () use ($preparedData) {

            $user = $this->getUserModel()::create($preparedData);

            app()->bind(
                \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
                \Filament\Listeners\Auth\SendEmailVerificationNotification::class,
            );

            //create address
            $user->address()->create($preparedData['addressData']);

            //create business
            $user->business()->create($preparedData['business']);

            //add roles to user
            $roleName = BusinessType::array()[$preparedData['business_type']];
            $role = Role::whereName($roleName)->first();
            $user->assignRole($role);

            event(new Registered($user));

            Filament::auth()->login($user);

            session()->regenerate();
        });
        // // Notification::make()->title(__('filament-breezy::default.verification.before_proceeding'),)->success()->send();

        // return redirect()->to(config('filament-breezy.registration_redirect_url'));
    }
}
