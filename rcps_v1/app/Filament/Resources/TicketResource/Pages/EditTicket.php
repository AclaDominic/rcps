<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function authorizeAccess(): void
    {
        // Allow CORE users to always access edit page
        if (auth()->check() &&  auth()->user()->hasRoleType(['CORE'])) {
            return;
        }

        parent::authorizeAccess();
    }

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
