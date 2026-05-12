<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Project;
use Carbon\Carbon;

class ChartAccuracyPerTicket extends Component
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

        $this->dispatchBrowserEvent('chart-data-updated', $this->getChartData());

        $this->emitSelf('$refresh');
    }

    public function render()
    {
        $chartData = $this->getChartData();

        return view('livewire.chart-accuracy-per-ticket', [
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
        
        if ($this->projectId) {
            $project = Project::find($this->projectId);
            if ($project && $project->comparison_id) {
                $pairedProjectIds = Project::where('comparison_id', $project->comparison_id)->pluck('id');
                $query->whereIn('project_id', $pairedProjectIds);
            } else {
                $query->where('project_id', $this->projectId);
            }
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

        $grouped = [];
        foreach ($metrics as $metric) {
            $meta = json_decode($metric->metadata, true);
            $indexKey = $meta['comparison_index'] ?? $metric->name;
            
            if(!isset($grouped[$indexKey])) {
                $grouped[$indexKey] = [
                    'label' => 'Task: ' . $metric->name,
                    'greedy' => null,
                    'divide' => null,
                ];
            }
            if ($metric->dependency_mode == 1) {
                $grouped[$indexKey]['greedy'] = $metric->scheduling_accuracy;
            } else {
                $grouped[$indexKey]['divide'] = $metric->scheduling_accuracy;
            }
        }
        
        ksort($grouped);
        
        $labels = [];
        $greedyData = [];
        $divideData = [];
        
        foreach ($grouped as $item) {
            $labels[] = $item['label'];
            $greedyData[] = $item['greedy'];
            $divideData[] = $item['divide'];
        }

       return [
            'labels' => $labels,
            'greedyData' => $greedyData,
            'divideData' => $divideData,
        ];
    }
}
