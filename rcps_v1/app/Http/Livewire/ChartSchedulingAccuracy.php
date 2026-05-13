<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ChartSchedulingAccuracy extends Component
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

        // Send updated chart data to frontend
        $this->dispatchBrowserEvent('chart-data-scheduling-accuracy-updated',$this->getChartData());

        $this->emitSelf('$refresh');
    }

    private function getChartData()
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE']);

        $query = Ticket::query();

        // Project filter
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

        // Date filters
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

        // Compute averages
        $avgGreedy = round((clone $query)->where('dependency_mode', 1)->avg('scheduling_accuracy') ?? 0, 2);
        $avgDivide = round((clone $query)->where('dependency_mode', 2)->avg('scheduling_accuracy') ?? 0, 2);

        // Get completion percentage
        $totalTickets = (clone $query)->count();
        $completedTickets = (clone $query)->whereHas('status', function($q) {
            $q->where('type', 'completed');
        })->count();
        $completionPercentage = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Scheduling Accuracy (%)',
                    'data' => [$avgGreedy, $avgDivide],
                    'backgroundColor' => [
                        'rgba(75, 192, 192, 0.6)',  // Greedy
                        'rgba(153, 102, 255, 0.6)', // Divide & Conquer
                    ],
                    'borderColor' => [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => ['Greedy', 'Divide & Conquer'],
            'completionPercentage' => $completionPercentage
        ];
    }

    public function getChartDataPublicly()
    {
        return $this->getChartData();
    }

    public function render()
    {
        $chartData = $this->getChartData();
        return view('livewire.chart-scheduling-accuracy', [
            'chartSchedulingAccuracy' => $chartData,
        ]);
    }
}
