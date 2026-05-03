<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\PieChartWidget;
use App\Models\Project;

class SchedulingAccuracy extends PieChartWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Chart';
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = [
        'sm' => 1,
        'md' => 6,
        'lg' => 3
    ];

    public ?string $filter = null;

    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return true;
    }

    protected function getHeading(): string
    {
        return __('Scheduling Accuracy (Greedy vs D&C)');
    }

    protected function getFilters(): ?array
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);

        $projects = Project::when(!$isPrivileged, function ($query) {
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
        })->pluck('name', 'id')->toArray();

        return $isPrivileged ? ['' => 'All'] + $projects : $projects;

    }

    protected function getData(): array
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);
        
        $query = Ticket::query();

        if ($this->filter) {
            $query->where('project_id', $this->filter);
        } elseif (!$isPrivileged) {
            // Limit to only allowed projects if no filter is selected
            $allowedProjectIds = Project::where(function ($query) {
                $query->where('owner_id', auth()->id())
                    ->orWhereHas('users', function ($q) {
                        $q->where('users.id', auth()->id());
                    })
                    ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->id())
                                ->orWhere('responsible_id', auth()->id());
                        });
                    });
            })->pluck('id');

            $query->whereIn('project_id', $allowedProjectIds);
        }

        $avgGreedy = round((clone $query)->where('dependency_mode', 1)->avg('scheduling_accuracy') ?? 0, 2);
        $avgDivide = round((clone $query)->where('dependency_mode', 2)->avg('scheduling_accuracy') ?? 0, 2);
    
        return [
            'datasets' => [
                [
                    'label' => 'Scheduling Accuracy (%)',
                    'data' => [$avgGreedy, $avgDivide],
                    'backgroundColor' => [
                        'rgba(75, 192, 192, 0.6)',  // Greedy
                        'rgba(153, 102, 255, 0.6)', // D&C
                    ],
                    'borderColor' => [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Greedy', 'Divide & Conquer'],
        ];
    }
}
