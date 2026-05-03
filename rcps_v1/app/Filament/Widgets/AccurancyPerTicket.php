<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use Livewire\Attributes\On; 
use Carbon\Carbon;

class AccurancyPerTicket extends LineChartWidget
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
    public bool $isLoading = true;
    protected static bool $isLazy = false;
    protected static bool $isStatic = false;

    public ?string $projectId = null;
    public ?string $filterType = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $selectedMonth = null;
    public ?string $selectedWeek = null;
    public ?string $selectedYear = null;

    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public static function canView(): bool
    {
        return true;
    }

    protected function getHeading(): string
    {
        return __('Accuracy Per Ticket Over Time');
    }

    public function updateFilter($data)
    {
        $this->projectId = $data['projectId'] ?? null;
        $this->filterType = $data['filterType'] ?? null;
        $this->dateFrom = $data['dateFrom'] ?? null;
        $this->dateTo = $data['dateTo'] ?? null;
        $this->selectedMonth = $data['selectedMonth'] ?? null;
        $this->selectedWeek = $data['selectedWeek'] ?? null;
        $this->selectedYear = $data['selectedYear'] ?? null;

         $this->dispatchBrowserEvent('refreshChart');
    }

    public function updating($name, $value)
    {
        $this->isLoading = true;
    }

    public function updated($name, $value)
    {
        $this->isLoading = false;
    }

    public static function isLazy(): bool
    {
        return false;
    }

    public static function isStatic(): bool
    {
        return false;
    }

    
    protected function getData(): array
    {
        
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);

        $query = Ticket::query();
        
        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        // Example: apply a custom date range
        if ($this->filterType === 'date_range' && $this->dateFrom && $this->dateTo) {
            $query->whereBetween('metrics_date', [$this->dateFrom, $this->dateTo]);
        }

        // Example: apply monthly filter
        if ($this->filterType === 'monthly' && $this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear);
        }

        if ($this->filterType === 'weekly' && $this->selectedMonth && $this->selectedWeek && $this->selectedYear) {
            // Convert month name (e.g. "January") to number
            $monthNumber = Carbon::parse("1 {$this->selectedMonth}")->month;
            $year = (int) $this->selectedYear;

            // First day of the selected month
            $startOfMonth = Carbon::createFromDate($year, $monthNumber, 1);

            // Get start and end date for selected week number
            $week = (int) $this->selectedWeek;

            $startDate = $startOfMonth->copy()->addDays(7 * ($week - 1));
            $endDate = $startDate->copy()->addDays(6);

            // Clamp the endDate to the end of the month if it goes over
            if ($endDate->month !== $startOfMonth->month) {
                $endDate = $startOfMonth->copy()->endOfMonth();
            }

            // Apply filter to query
            $query->whereBetween('metrics_date', [$startDate->toDateString(), $endDate->toDateString()]);
        }


        if (!$isPrivileged) {
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
                $greedyData[] = $metric->scheduling_accuracy;
                $divideData[] = null;
            } else {
                $greedyData[] = null;
                $divideData[] = $metric->scheduling_accuracy;
            }
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
            'labels' => $labels,
        ];
    }
}
