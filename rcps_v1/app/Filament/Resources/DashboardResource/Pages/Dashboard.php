<?php

namespace App\Filament\Resources\DashboardResource\Pages;

use App\Filament\Resources\DashboardResource;
use Filament\Resources\Pages\Page;

class Dashboard extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-home'; // icon sa sidebar
    protected static string $view = 'filament.pages.dashboard'; // points to blade view
    protected static ?string $title = 'Custom Dashboard'; // optional title
    protected static ?int $navigationSort = 1; // ayusin ang order sa sidebar

    protected static ?string $slug = 'custom-dashboard'; // URL
}
