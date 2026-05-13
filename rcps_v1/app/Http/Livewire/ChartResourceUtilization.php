<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ChartResourceUtilization extends Component
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

        $this->dispatchBrowserEvent('chart-data-resource-utilization-updated', $this->getChartData());
        $this->emitSelf('$refresh');
    }

    private function getFilteredQuery()
    {
        $query = Ticket::query();

        if ($this->projectId) {
            $project = Project::find($this->projectId);
            if ($project && $project->comparison_id) {
                $pairedProjectIds = Project::where('comparison_id', $project->comparison_id)->pluck('id');
                $query->whereIn('project_id', $pairedProjectIds);
            } else {
                $query->where('project_id', $this->projectId);
            }
        }

        // Apply filter type
        if ($this->filterType === 'date_range' && $this->dateFrom && $this->dateTo) {
            $query->whereBetween(DB::raw('DATE(metrics_date)'), [$this->dateFrom, $this->dateTo]);
        } elseif ($this->filterType === 'weekly' && $this->selectedMonth && $this->selectedWeek) {
            $query->whereMonth('metrics_date', $this->selectedMonth);
            
            if (DB::getDriverName() === 'pgsql') {
                $query->whereRaw("EXTRACT(WEEK FROM metrics_date) - EXTRACT(WEEK FROM date_trunc('month', metrics_date)) + 1 = ?", [$this->selectedWeek]);
            } else {
                $query->whereRaw('WEEK(metrics_date, 1) - WEEK(DATE_SUB(metrics_date, INTERVAL DAYOFMONTH(metrics_date)-1 DAY), 1) + 1 = ?', [$this->selectedWeek]);
            }
        } elseif ($this->filterType === 'monthly' && $this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear);
        } elseif ($this->filterType === 'yearly' && $this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear);
        }

        return $query;
    }

    private function getChartData()
    {
        $metrics = $this->getFilteredQuery()
            ->select(
                DB::raw('DATE(metrics_date) as date'),
                'dependency_mode',
                DB::raw('AVG(resource_utilization) as avg_util')
            )
            ->groupBy(DB::raw('DATE(metrics_date)'), 'dependency_mode')
            ->orderBy(DB::raw('DATE(metrics_date)'))
            ->get();

        $dates = $metrics->pluck('date')->unique()->values()->all();

        $greedyData = [];
        $divideData = [];

        foreach ($dates as $date) {
            $greedyValue = $metrics->firstWhere(fn($m) => $m->date === $date && (int) $m->dependency_mode === 1)?->avg_util ?? null;
            $divideValue = $metrics->firstWhere(fn($m) => $m->date === $date && (int) $m->dependency_mode === 2)?->avg_util ?? null;

            $greedyData[] = $greedyValue;
            $divideData[] = $divideValue;
        }

        // Bar Chart Summary (average per mode)
        $summary = $this->getFilteredQuery()
            ->select(
                'dependency_mode',
                DB::raw('AVG(resource_utilization) as avg_util')
            )
            ->groupBy('dependency_mode')
            ->pluck('avg_util', 'dependency_mode');

        $greedyAvg = $summary[1] ?? 0;
        $divideAvg = $summary[2] ?? 0;

        // Get completion percentage
        $totalTickets = $this->getFilteredQuery()->count();
        $completedTickets = $this->getFilteredQuery()->whereHas('status', function($q) {
            $q->where('type', 'completed');
        })->count();
        $completionPercentage = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;

        return [
            'labels' => $dates,
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
            'summary' => [
                'labels' => ['Greedy', 'Divide & Conquer'],
                'data' => [$greedyAvg, $divideAvg],
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                ],
                'borderColor' => [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                ],
            ],
            'completionPercentage' => $completionPercentage
        ];
    }

    public function render()
    {
        return view('livewire.chart-resource-utilization', [
            'chartResourceUtilization' => $this->getChartData(),
        ]);
    }
}
