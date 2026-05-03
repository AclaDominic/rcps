<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }
    
    public static function form(Form $form): Form
    {
        $role_id = User::find(auth()->user()->id)->roles()->first()->id;

        return $form
            ->schema([

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Full name'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email address'))
                                    ->email()
                                    ->required()
                                    ->rule(
                                        fn($record) => 'unique:users,email,'
                                            . ($record ? $record->id : 'NULL')
                                            . ',id,deleted_at,NULL'
                                    )
                                    ->maxLength(255),

                                Forms\Components\Select::make('role')
                                    ->label(__('Permission Role'))
                                    ->searchable()
                                    ->reactive()
                                    ->options(function () use ($role_id) {
                                        $query = \Spatie\Permission\Models\Role::query();
                                        if ($role_id !== 2) {
                                            $query->where('id', '!=', 2);
                                        }
                                        return $query->pluck('name', 'id')->toArray();
                                    })
                                     ->afterStateHydrated(function ($state, $set, $record) {
                                        // Make sure field gets hydrated correctly when editing
                                        if($record){
                                            $set('role', $record->roles->first()?->id ?? null);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, $set, $record) {
                                        if ($record) {
                                            $record->syncRoles([$state]); // assign the selected role
                                        }
                                    }),
                                ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Full name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email address'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TagsColumn::make('roles.name')
                    ->label(__('Roles'))
                    ->limit(2),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('Email verified at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('socials')
                    ->label(__('Linked social networks'))
                    ->view('partials.filament.resources.social-icon'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // If NOT Super Admin
        if (! $user->hasRoleType(['CORE'])) {
            if(! $user->hasRoleType(['MANAGER'])){
                 $query
                    // Exclude users who have roles: Super Admin or Project Manager (or any "manager"-type roles)
                    ->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('role_type', ['CORE', 'MANAGER']);
                    })
                    // Exclude the current user as well
                    ->where('id', '!=', $user->id);
            }
        }else{
             $query->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('role_type', ['CORE']);
                    })
                    // Exclude the current user as well
                    ->where('id', '!=', $user->id);
        }

        return $query;
    }
}
