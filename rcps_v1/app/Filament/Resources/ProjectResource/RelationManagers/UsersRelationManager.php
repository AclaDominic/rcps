<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectUser;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $inverseRelationship = 'projectsAffected';

    protected bool $allowsDuplicates = false;

    public static function attach(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('User full name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role_label')
                    ->label(__('User role'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                            Forms\Components\Select::make('recordId')
                                ->label('User')
                                ->options(function (RelationManager $livewire): array {

                                    $project_id = $livewire->ownerRecord->id; 

                                    $attachedIds = ProjectUser::where('project_id',$project_id)->pluck('user_id')->toArray();
                        
                                    return User::whereNotIn('id', $attachedIds)->get()->mapWithKeys(function ($user) {
                                        $role = $user->roles->first()?->name;
                                        return [$user->id => $user->name . ($role ? " ({$role})" : '')];
                                    })->toArray();
                                    
                                })
                                ->searchable()
                                ->required(),
                        ]),
            ])
            ->actions([
                // Tables\Actions\EditAction::make()
                //     ->modalWidth('xl')
                //     ->form(fn (Tables\Actions\EditAction $action): array => [
                //         Forms\Components\Select::make('role')
                //             ->label(__('User role'))
                //             ->searchable()
                //             ->options(fn () => config('system.projects.affectations.roles.list'))
                //             ->required(),
                //     ]),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    protected function canDeleteAny(): bool
    {
        return false;
    }
}
