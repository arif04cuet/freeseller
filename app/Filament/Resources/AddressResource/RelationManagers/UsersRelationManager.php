<?php

namespace App\Filament\Resources\AddressResource\RelationManagers;

use App\Enum\AddressType;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Hub Manager & Members';

    protected static ?string $modelLabel = 'User';

    // protected function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()->whereHas('addressable', function ($query) {
    //         return $query->hubUsers();
    //     });
    // }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type->value == AddressType::Hub->value;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: config('filament-breezy.user_model')),

                Forms\Components\TextInput::make('password')
                    ->required(fn (?Model $record) => is_null($record))
                    ->rules('min:8'),

                Forms\Components\TextInput::make('mobile')
                    ->minLength(11)
                    ->maxLength(11)
                    ->numeric()
                    ->required()
                    ->placeholder('01xxxxxxxxx')
                    ->unique(table: config('filament-breezy.user_model')),

                Forms\Components\Select::make('role_id')
                    ->label('Role')
                    ->options(Role::getHubRoles()->pluck('label', 'id'))
                    ->required(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query->whereHas('addressable', function ($query) {
                    return $query->hubUsers();
                })
            )
            ->columns([
                Tables\Columns\TextColumn::make('addressable.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('addressable.email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('addressable.mobile')
                    ->label('Mobile'),
                // Tables\Columns\TagsColumn::make('roles')
                //     ->getStae(fn ($state) => str()->headline($state))
                //     ->label('Role Name'),
                Tables\Columns\IconColumn::make('addressable.is_active')
                    ->options([
                        'heroicon-o-x-circle' => false,
                        'heroicon-o-check-circle' => true,
                    ])
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ])
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->successNotificationTitle('User created. check user email to activate account.')
                    ->visible(fn (RelationManager $livewire) => $livewire->ownerRecord->type->value == AddressType::Hub->value)
                    ->using(function (RelationManager $livewire, array $data): Model {

                        //create user
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'mobile' => $data['mobile'],
                            'password' => Hash::make($data['password']),
                        ]);

                        //assign role
                        $role = Role::find($data['role_id']);
                        $user->assignRole($role);

                        //create address
                        $addressable = $user->address()->create([
                            'address_id' => $livewire->ownerRecord->id,
                        ]);

                        event(new Registered($user));

                        return $addressable;
                    }),
            ])
            ->actions([
                // Impersonate::make()
                //     ->impersonate(fn (Model $record) => $record->addressable),

                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {

                        $user = $record->addressable;
                        $data['name'] = $user->name;
                        $data['email'] = $user->email;
                        $data['mobile'] = $user->mobile;
                        $data['role_id'] = $user->roles->first()->id;

                        return $data;
                    })->using(function (Model $record, array $data): Model {

                        $userData = [
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'mobile' => $data['mobile'],
                        ];

                        if (!empty($data['password'])) {
                            $userData['password'] = Hash::make($data['password']);
                        }

                        $user = $record->addressable;
                        $user->save($userData);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Model $record) {
                        $record->addressable()->delete();
                        $record->delete();
                    }),

            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
