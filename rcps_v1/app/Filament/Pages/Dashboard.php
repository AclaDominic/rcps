<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FavoriteProjects;
use App\Filament\Widgets\LatestActivities;
use App\Filament\Widgets\LatestComments;
use App\Filament\Widgets\LatestProjects;
use App\Filament\Widgets\LatestTickets;
use App\Filament\Widgets\TicketsByPriority;
use App\Filament\Widgets\TicketsByType;
use App\Filament\Widgets\ProjectCount;
use Filament\Pages\Dashboard as BasePage;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;
use Closure;

class Dashboard extends Page
{
    // protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-home'; // icon sa sidebar
    protected static string $view = 'filament.pages.dashboard'; // points to blade view
    protected static ?string $title = 'Dashboard'; // optional title
    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'dashboard'; // URL

    protected static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::$title ?? __('filament::pages/dashboard.title');
    }

    
    public static function getRoutes(): Closure
    {
        return function () {
            Route::get('/', static::class)->name(static::getSlug());
            Route::get('/dashboard', static::class)->name(static::getSlug());
        };
    }

    protected function getProjectWidgets(): array
    {
        return [
            ProjectCount::class,
        ];
    }

    protected function getFavoriteProjects(): array
    {
        return [
            FavoriteProjects::class,
        ];
    }

    protected function getLatestProjects(): array
    {
        return [
            LatestProjects::class,
        ];
    }

    protected function getLatestActivities(): array
    {
        return [
            LatestActivities::class,
        ];
    }

    protected function getLatestComments(): array
    {
        return [
            LatestComments::class,
        ];
    }

    protected function getLatestTickets(): array
    {
        return [
            LatestTickets::class,
        ];
    }

    protected function getTicketsByPriority(): array
    {
        return [
            TicketsByPriority::class,
        ];
    }

    protected function getTicketsByType(): array
    {
        return [
            TicketsByType::class,
        ];
    }


    protected function getOtherWidget(): array
    {
        return [
            FavoriteProjects::class,
            LatestProjects::class,
            LatestActivities::class,
            LatestComments::class,
            LatestTickets::class,
        ];
    }
}
