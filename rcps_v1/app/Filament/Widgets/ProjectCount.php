<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ProjectCount extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = [
        'sm' => 1,
        'md' => 6,
        'lg' => 6
    ];

    protected ?string $heading = 'Analytics';

    protected ?string $description = 'An overview of some analytics.';

    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return true;
    }

    protected function getHeading(): ?string
    {
        return 'Analytics';
    }

    protected function getDescription(): ?string
    {
        return 'An overview of some analytics.';
    }

    protected function getCards(): array
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);

        $total_projects = Project::when(!$isPrivileged, function ($query) {
            $query->where(function ($query) {
                $query->where('owner_id', auth()->user()->id)
                    ->orWhereHas('users', function ($query) {
                        $query->where('users.id', auth()->user()->id);
                    })
                    ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->user()->id)
                                ->orWhere('responsible_id', auth()->user()->id);
                        });
                    });
            });
        })->count();

        $total_projects_in_progress = Project::when(!$isPrivileged, function ($query) {
            $query->where(function ($query) {
                $query->where('owner_id', auth()->user()->id)
                    ->orWhereHas('users', function ($query) {
                        $query->where('users.id', auth()->user()->id);
                    })
                    ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->user()->id)
                                ->orWhere('responsible_id', auth()->user()->id);
                        });
                    });
            });
        })->where('status_id',2)->count();

        $total_projects_completed = Project::when(!$isPrivileged, function ($query) {
            $query->where(function ($query) {
                $query->where('owner_id', auth()->user()->id)
                    ->orWhereHas('users', function ($query) {
                        $query->where('users.id', auth()->user()->id);
                    })
                    ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->user()->id)
                                ->orWhere('responsible_id', auth()->user()->id);
                        });
                    });
            });
        })->where('status_id',5)->count();
    
        
         return [
            Card::make('Total Projects', $total_projects)
            ->extraAttributes(['class' => 'bg-primary-100 text-primary-800 filament-stats-overview-widget-card-icon-size primary-icon-color'])
             ->icon('heroicon-o-clipboard-list'),
            Card::make('Projects On Going', $total_projects_in_progress)
            ->extraAttributes(['class' => 'bg-warning-100 text-warning-800 filament-stats-overview-widget-card-icon-size warning-icon-color'])
            ->icon('heroicon-o-clock'),
            Card::make('Projects Completed', $total_projects_completed)
            ->extraAttributes(['class' => 'bg-success-100 text-success-800 filament-stats-overview-widget-card-icon-size success-icon-color'])
            ->icon('heroicon-o-check-circle'),
        ];
    }
}
