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
            $query->where('project_id', $this->projectId);
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
            $query->whereRaw('YEAR(metrics_date) = ?', [$this->selectedYear])
                ->whereRaw('WEEK(metrics_date, 1) = ?', [$this->selectedWeek]);
        } elseif ($this->selectedYear) {
            $query->whereYear('metrics_date', $this->selectedYear);
        }

        // Get average execution_time by dependency_mode
        $avgGreedy = round((clone $query)->where('dependency_mode', 1)->avg('execution_time') ?? 0, 2);
        $avgDivide = round((clone $query)->where('dependency_mode', 2)->avg('execution_time') ?? 0, 2);

        return [
            'datasets' => [
                [
                    'label' => 'Average Execution Time (hours)',
                    'data' => [$avgGreedy, $avgDivide],
                    'backgroundColor' => [
                        'rgba(255, 159, 64, 0.6)',  // Greedy - orange
                        'rgba(54, 162, 235, 0.6)',  // Divide & Conquer - blue
                    ],
                    'borderColor' => [
                        'rgba(255, 159, 64, 1)',
                        'rgba(54, 162, 235, 1)',
                    ],
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => ['Greedy', 'Divide & Conquer'],
        ];
    }
}
