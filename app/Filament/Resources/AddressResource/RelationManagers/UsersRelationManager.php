<?php

namespace App\Filament\Resources\AddressResource\RelationManagers;

use App\Enum\AddressType;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Hub Manager & Members';

    public static function form(Form $form): Form
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
                    ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('user.mobile')
                    ->label('Mobile'),
                Tables\Columns\TextColumn::make('role.name')
                    ->formatStateUsing(fn ($state) => str()->headline($state))
                    ->label('Role Name'),
                Tables\Columns\IconColumn::make('user.is_active')
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
                    ->using(function (HasRelationshipTable $livewire, array $data): Model {

                        //create user
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'mobile' => $data['mobile'],
                            'password' => Hash::make($data['password'])
                        ]);

                        $role = Role::find($data['role_id']);
                        $user->assignRole($role);

                        event(new Registered($user));

                        $hubUser = $livewire->getRelationship()->create([
                            'user_id' => $user->id,
                            'role_id' => $data['role_id'],
                        ]);


                        return $hubUser;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {

                        $user = $record->user;
                        $data['name'] = $user->name;
                        $data['email'] = $user->email;
                        $data['mobile'] = $user->mobile;

                        return $data;
                    })->using(function (Model $record, array $data): Model {

                        $userData = [
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'mobile' => $data['mobile']
                        ];

                        if (!empty($data['password']))
                            $userData['password'] = Hash::make($data['password']);


                        $record->user->save($userData);

                        $record->update([
                            'role_id' => $data['role_id'],
                        ]);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (array $data) {
                        logger($data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
