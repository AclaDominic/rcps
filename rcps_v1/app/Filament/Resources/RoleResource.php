<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Filament\Forms\Components\Select;
use App\Models\Permission;
use App\Models\PermissionParent;
use App\Models\Role;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-open';

    protected static ?int $navigationSort = 3;

    protected static function getNavigationLabel(): string
    {
        return __('Roles');
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
       $groups = PermissionParent::where('is_active', 'Y')
        ->with('permissions')
        ->get();

        $fieldsetSchemas = [];

        foreach ($groups as $group) {
            if ($group->permissions->isEmpty()) continue;

            $fieldsetSchemas[] = Forms\Components\Fieldset::make($group->name)
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->label(false)
                        ->relationship('permissions', 'name')
                        ->options($group->permissions->pluck('name', 'id')->toArray())
                        ->columns(2),
                ]);
        }


        return $form->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(1)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Permission name'))
                                    ->unique(table: Permission::class, column: 'name')
                                    ->maxLength(255)
                                    ->required(),

                                Select::make('role_type')
                                    ->label(__('Role Type'))
                                    ->options([
                                        'CORE' => 'CORE',
                                        'MANAGER' => 'MANAGER',
                                        'STAFF' => 'STAFF',
                                    ])
                                    ->required(),
                                ...$fieldsetSchemas,
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Permission name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('role_type')
                    ->label(__('Role Type'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TagsColumn::make('permissions.name')
                    ->label(__('Permissions'))
                    ->limit(2),

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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
