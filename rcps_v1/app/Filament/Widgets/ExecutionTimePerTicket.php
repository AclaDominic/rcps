<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\LineChartWidget;
use App\Models\Project;

class ExecutionTimePerTicket extends LineChartWidget
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
        return __('Execution Time');
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

       $query = Ticket::query()->orderBy('execution_time');

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

        $metrics = $query->get();

        $greedyData = [];
        $divideData = [];
        $labels = [];

        foreach ($metrics as $metric) {
            $labels[] = 'Ticket ' . $metric->code;
            if ($metric->dependency_mode == 1) {
                $greedyData[] = $metric->execution_time;
                $divideData[] = null;
            } else {
                $greedyData[] = null;
                $divideData[] = $metric->execution_time;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Greedy',
                    'data' => $greedyData,
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, .2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Divide & Conquer',
                    'data' => $divideData,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, .2)',
                    'fill' => true,
                ]
            ],
            'labels' => $labels,
        ];
    }
}
