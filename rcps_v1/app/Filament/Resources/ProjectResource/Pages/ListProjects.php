<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        // If CORE user, show all projects including soft-deleted ones
       if (auth()->check() && auth()->user()->hasRoleType(['CORE'])) {
            return parent::getTableQuery()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->with(['owner', 'status']);
        }

        // Otherwise, show only owned or assigned projects
        return parent::getTableQuery()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('users', fn($q) => $q->where('users.id', $user->id));
            });
    }
}
