<?php

namespace App\Http\Livewire;

use App\Enum\AddressType;
use App\Enum\BusinessType;
use App\Models\Address;
use Closure;
use JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Register as FilamentBreezyRegister;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use JeffGreco13\FilamentBreezy\FilamentBreezy;
use Illuminate\Auth\Events\Registered;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class Register extends FilamentBreezyRegister
{
    // Define the new attributes
    public $consent_to_terms, $business_type, $business_name, $business_address, $business_estd_year, $mobile;

    public $division, $district, $upazila, $union, $hub, $address;
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
                                ->reactive()
                                ->required(),
                            Forms\Components\TextInput::make("business_name")
                                ->label('Name')
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
                                        ->reactive(),
                                    Forms\Components\Select::make('district')
                                        ->options(fn (Closure $get) => Address::query()
                                            ->whereType(AddressType::District->value)
                                            ->whereParentId($get('division'))
                                            ->pluck('name', 'id'))
                                        ->reactive(),

                                    Forms\Components\Select::make('upazila')
                                        ->options(fn (Closure $get) => Address::query()
                                            ->whereType(AddressType::Upazila->value)
                                            ->whereParentId($get('district'))
                                            ->pluck('name', 'id'))
                                        ->reactive(),

                                    Forms\Components\Select::make('union')
                                        ->options(fn (Closure $get) => Address::query()
                                            ->whereType(AddressType::Union->value)
                                            ->whereParentId($get('upazila'))
                                            ->pluck('name', 'id'))
                                        ->reactive(),
                                    Forms\Components\Select::make('hub')
                                        ->visible(fn (Closure $get) => $get('business_type') == BusinessType::Wholesaler->value)
                                        ->required()
                                        ->options(fn (Closure $get) => Address::query()
                                            ->whereType(AddressType::Hub->value)
                                            ->whereParentId($get('union'))
                                            ->pluck('name', 'id')),

                                    Forms\Components\TextInput::make("address")
                                        ->visible(fn (Closure $get) => $get('business_type') == BusinessType::Reseller->value)
                                        ->label('Address')
                                        ->required(),
                                ])->columns(1),

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

        if ($this->business_type == BusinessType::Wholesaler->value) {
            $preparedData["address"]['address_id'] = $this->hub;
            $preparedData['hub_id'] = $this->hub;
        } else {

            $preparedData["address"]['address_id'] = $this->getAddressId();
            $preparedData["address"]['address'] = $this->address;
        }


        $preparedData["business"]["type"] = $this->business_type;
        $preparedData["business"]["name"] = $this->business_name;
        $preparedData["business"]["estd_year"] = $this->business_estd_year;


        return $preparedData;
    }

    public function register()
    {
        $preparedData = $this->prepareModelData($this->form->getState());

        DB::transaction(function () use ($preparedData) {


            $user = config('filament-breezy.user_model')::create($preparedData);

            event(new Registered($user));
            Filament::auth()->login($user, true);

            //create address
            $user->address()->create($preparedData['address']);

            //create business
            $user->business()->create($preparedData['business']);

            //add roles to user
            $roleName = BusinessType::array()[$this->business_type];
            $role = Role::whereName($roleName)->first();
            $user->assignRole($role);
        });
        // Notification::make()->title(__('filament-breezy::default.verification.before_proceeding'),)->success()->send();

        return redirect()->to(config('filament-breezy.registration_redirect_url'));
    }

    public function getAddressId()
    {
        if ($this->union)
            return $this->union;

        if ($this->upazila)
            return $this->upazila;

        if ($this->district)
            return $this->district;

        if ($this->division)
            return $this->division;
    }
}
