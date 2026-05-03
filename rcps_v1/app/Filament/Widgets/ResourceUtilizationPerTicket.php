<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

class ResourceUtilizationPerTicket extends LineChartWidget
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
        return __('Resource Utilization Per Ticket');
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

        $metrics = $query->select(
                DB::raw('DATE(metrics_date) as date'),
                'dependency_mode',
                DB::raw('AVG(resource_utilization) as avg_util')
            )
            ->groupBy('date', 'dependency_mode')
            ->orderBy('date')
            ->get();

        $dates = $metrics->pluck('date')->unique()->sort()->values()->all();

        // Initialize arrays
        $greedyData = [];
        $divideData = [];

        foreach ($dates as $date) {
            $greedyValue = $metrics->firstWhere(fn($m) => $m->date === $date && $m->dependency_mode == 1)?->avg_util ?? 0;
            $divideValue = $metrics->firstWhere(fn($m) => $m->date === $date && $m->dependency_mode == 2)?->avg_util ?? 0;

            $greedyData[] = $greedyValue;
            $divideData[] = $divideValue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Greedy',
                    'data' => $greedyData,
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, .2)',
                    'fill' => false,
                ],
                [
                    'label' => 'Divide & Conquer',
                    'data' => $divideData,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, .2)',
                    'fill' => false,
                ]
            ],
            'labels' => $dates,
        ];
    }
}
