<?php

namespace App\Filament\Pages\Auth;

use App\Enum\AddressType;
use App\Enum\BusinessType;
use App\Models\Address;
use App\Models\Business;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as AuthEditProfile;
use Filament\Pages\Concerns;
use Filament\Pages\SimplePage;
use Filament\Panel;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/**
 * @property Form $form
 */
class EditProfile extends AuthEditProfile
{

    public function backAction(): Action
    {
        return Action::make('back')
            ->label('Back to Dashboard')
            ->url(filament()->getUrl())
            ->color('gray');
    }


    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {

        $user = User::find($data['id']);
        $data['business'] = $user->business;
        $data['address'] = $user->address;
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {

        $record->update($data);

        if (!$record->isReseller() || !$record->isWholesaler())
            return $record;

        //for reseller and wholesaler
        $record = $record->fresh();
        $record->business()->update($data['business']);

        if ($record->isReseller())
            $record->address()->update($data['address']);

        return $record;
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
                    ->unique(table: config('filament-breezy.user_model'), ignoreRecord: true),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                Forms\Components\TextInput::make('mobile')
                    ->label('Mobile')
                    ->rules('numeric|digits_between:11,11')
                    ->regex('/^(?:\+?88|0088)?01[3-9]\d{8}$/i')
                    ->placeholder('01xxxxxxxxx')
                    ->unique(table: config('filament-breezy.user_model'), ignoreRecord: true)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('avatar')
                    ->label('Photo')
                    ->avatar()
                    ->visible(fn () => !(auth()->user()->isReseller() || auth()->user()->isWholesaler()))
                    ->collection('avatar'),

                Forms\Components\Fieldset::make('Business Information')
                    ->visible(fn () => auth()->user()->isReseller() || auth()->user()->isWholesaler())
                    ->schema([

                        Forms\Components\Select::make('business.type')
                            ->label('Type')
                            ->options(BusinessType::array())
                            ->enum(BusinessType::class)
                            ->reactive()
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('business.name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('business.slogan')
                            ->label('Slogan')
                            ->required(),
                        Forms\Components\FileUpload::make('business.logo')
                            ->image()
                            ->imageEditor()
                            ->directory('logo')
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),

                        Forms\Components\TextInput::make('business.estd_year')
                            ->label('Estd. Year')
                            ->type('number')
                            ->minLength(4)
                            ->maxLength(4)
                            ->rules('min:4,max:4')
                            ->required(),
                        Forms\Components\TextInput::make('address.address')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('business.type') == BusinessType::Reseller->value)
                            ->label('Address')
                            ->required(),

                        Forms\Components\TextInput::make('business.url')
                            ->label('FB/Website Url')
                            ->url()
                            ->columnSpanFull()
                            ->required(),


                    ]),
            ]);
    }
}
