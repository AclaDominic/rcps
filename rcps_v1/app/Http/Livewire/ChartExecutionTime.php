<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChartExecutionTime extends Component
{
    public $projectId = null;
    public $filterType = null;
    public $dateFrom = null;
    public $dateTo = null;
    public $selectedMonth = null;
    public $selectedWeek = null;
    public $selectedYear = null;

    protected $listeners = ['filterUpdated' => 'applyFilter'];

    public function applyFilter($data)
    {
        $this->projectId = $data['projectId'] ?? null;
        $this->filterType = $data['filterType'] ?? null;
        $this->dateFrom = $data['dateFrom'] ?? null;
        $this->dateTo = $data['dateTo'] ?? null;
        $this->selectedMonth = $data['selectedMonth'] ?? null;
        $this->selectedWeek = $data['selectedWeek'] ?? null;
        $this->selectedYear = $data['selectedYear'] ?? null;

        $this->dispatchBrowserEvent('chart-data-execution-time-updated', $this->getChartData());

        $this->emitSelf('$refresh');
    }

    public function render()
    {
        $chartData = $this->getChartData();

        return view('livewire.chart-execution-time', [
            'chartData' => $chartData,
        ]);
    }

    public function getChartDataPublicly()
    {
        return $this->getChartData();
    }

    protected function getChartData(): array
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);

        $query = Ticket::query();

        // Apply project filter
        if ($this->projectId) {
            $project = Project::find($this->projectId);
            if ($project && $project->comparison_id) {
                $pairedProjectIds = Project::where('comparison_id', $project->comparison_id)->pluck('id');
                $query->whereIn('project_id', $pairedProjectIds);
            } else {
                $query->where('project_id', $this->projectId);
            }
        } elseif (!$isPrivileged) {
            $allowedProjectIds = Project::where(function ($query) {
                $query->where('owner_id', auth()->id())
                    ->orWhereHas('users', fn($q) => $q->where('users.id', auth()->id()))
                    ->orWhereHas('tickets', fn($q) =>
                        $q->where('owner_id', auth()->id())
                        ->orWhere('responsible_id', auth()->id())
                    );
            })->pluck('id');

            $query->whereIn('project_id', $allowedProjectIds);
        }

        // Date filters (example same as before)
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween(DB::raw('DATE(metrics_date)'), [$this->dateFrom, $this->dateTo]);
        } elseif ($this->selectedMonth && $this->selectedYear) {
            $query->whereMonth('metrics_date', $this->selectedMonth)
                ->whereYear('metrics_date', $this->selectedYear);
        } elseif ($this->selectedWeek && $this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear)
                ->whereWeek('metrics_date', $this->selectedWeek);
        } elseif ($this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear);
        }

        // Get completion percentage
        $totalTickets = (clone $query)->count();
        $completedTickets = (clone $query)->whereHas('status', function($q) {
            $q->where('type', 'completed');
        })->count();
        $completionPercentage = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;

        // Get averages for Theorized (estimation)
        $avgGreedyEst = round((clone $query)->where('dependency_mode', 1)->avg('estimation') ?? 0, 2);
        $avgDivideEst = round((clone $query)->where('dependency_mode', 2)->avg('estimation') ?? 0, 2);

        // Get averages for Actual (execution_time)
        $avgGreedyAct = round((clone $query)->where('dependency_mode', 1)->avg('execution_time') ?? 0, 2);
        $avgDivideAct = round((clone $query)->where('dependency_mode', 2)->avg('execution_time') ?? 0, 2);

        $showActual = $completionPercentage >= 50;

        $datasets = [
            [
                'label' => 'Average Theorized Time (hours)',
                'data' => [$avgGreedyEst, $avgDivideEst],
                'backgroundColor' => [
                    'rgba(255, 159, 64, 0.3)',  // Light Orange
                    'rgba(54, 162, 235, 0.3)',  // Light Blue
                ],
                'borderColor' => [
                    'rgba(255, 159, 64, 1)',
                    'rgba(54, 162, 235, 1)',
                ],
                'borderWidth' => 1
            ]
        ];

        if ($showActual) {
            $datasets[] = [
                'label' => 'Average Actual Time (hours)',
                'data' => [$avgGreedyAct, $avgDivideAct],
                'backgroundColor' => [
                    'rgba(255, 159, 64, 0.8)',  // Darker Orange
                    'rgba(54, 162, 235, 0.8)',  // Darker Blue
                ],
                'borderColor' => [
                    'rgba(255, 159, 64, 1)',
                    'rgba(54, 162, 235, 1)',
                ],
                'borderWidth' => 1
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => ['Greedy', 'Divide & Conquer'],
            'completionPercentage' => $completionPercentage,
            'showActual' => $showActual
        ];
    }
}
