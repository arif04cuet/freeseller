<?php

namespace App\Http\Livewire;

use App\Enum\BusinessType;
use JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Register as FilamentBreezyRegister;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use JeffGreco13\FilamentBreezy\FilamentBreezy;
use Illuminate\Auth\Events\Registered;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;

class Register extends FilamentBreezyRegister
{
    // Define the new attributes
    public $consent_to_terms, $business_type, $business_name, $business_address, $business_estd_year, $mobile;

    // Override the getFormSchema method and merge the default fields then add your own.
    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    // ...

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
                        ->rules(app(FilamentBreezy::class)->getPasswordRules()),
                    Forms\Components\TextInput::make('password_confirm')
                        ->label(__('filament-breezy::default.fields.password_confirm'))
                        ->required()
                        ->password()
                        ->same('password'),
                    Forms\Components\TextInput::make('mobile')
                        ->label('Mobile')
                        ->type('number')
                        ->rules('numeric|digits_between:11,11')
                        ->placeholder('01xxxxxxxxx')
                        ->unique(table: config('filament-breezy.user_model'))
                        ->required(),

                    Forms\Components\Fieldset::make('Business Information')
                        ->schema([


                            Forms\Components\Select::make("business_type")
                                ->label('Type')
                                ->options(BusinessType::array())
                                ->enum(BusinessType::class)
                                ->required(),
                            Forms\Components\TextInput::make("business_name")
                                ->label('Name')
                                ->required(),
                            Forms\Components\TextInput::make("business_estd_year")
                                ->label('Estd. Year')
                                ->type('number')
                                ->rules('min:4,max:4')
                                ->required(),
                            Forms\Components\Textarea::make("business_address")
                                ->label('Address')
                                ->required(),
                            Forms\Components\Checkbox::make('consent_to_terms')->label('I consent to the terms of service and privacy policy.')->required()

                        ])->columns(1)
                ])
        ];
    }

    // Use this method to modify the preparedData before the register() method is called.
    protected function prepareModelData($data): array
    {
        $preparedData = parent::prepareModelData($data);
        $preparedData['consent_to_terms'] = $this->consent_to_terms;
        $preparedData['is_active'] = 0;
        $preparedData['mobile'] = $this->mobile;

        $preparedData["business"]["type"] = $this->business_type;
        $preparedData["business"]["name"] = $this->business_name;
        $preparedData["business"]["address"] = $this->business_address;
        $preparedData["business"]["estd_year"] = $this->business_estd_year;

        return $preparedData;
    }

    public function register()
    {
        $preparedData = $this->prepareModelData($this->form->getState());
        $user = config('filament-breezy.user_model')::create($preparedData);

        event(new Registered($user));
        Filament::auth()->login($user, true);

        //create business
        $user->business()->create($preparedData['business']);

        //add roles to user
        $roleName = BusinessType::array()[$this->business_type];
        $role = Role::whereName($roleName)->first();
        $user->assignRole($role);

        // Notification::make()->title(__('filament-breezy::default.verification.before_proceeding'),)->success()->send();

        return redirect()->to(config('filament-breezy.registration_redirect_url'));
    }
}
