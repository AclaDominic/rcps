<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\Ticket;

class AlgorithmMetrics extends Component
{
    public $projects; // list of project names
    public $selectedProjectName;
    public $metrics = [];

    public function mount()
    {
        $groupedByName = Project::get()
            ->groupBy('name')
            ->map(function ($projects, $name) {
                return [
                    'name' => $name,
                    'comparison_ids' => $projects->pluck('comparison_id')->unique()->values(),
                    'projects' => $projects
                ];
            });

        $this->projects = $groupedByName;

        $this->loadMetrics();
    }

    public function updatedSelectedProjectName()
    {
        $this->loadMetrics();
    }

    public function loadMetrics()
    {
        if (!$this->selectedProjectName) {
            $this->metrics = $this->getEmptyMetrics();
            return;
        }

        $this->metrics = $this->calculateProjectMetrics($this->selectedProjectName);
    }

    public function calculateProjectMetrics($projectName)
    {
        $projectID = Project::where('name',$projectName)->pluck('id');
        // Fetch tickets for the project by name
        $tickets = Ticket::whereIn('project_id',$projectID)->get();

        $ticketCount = $tickets->count();

        // Distribution by algorithm
        $actualDistribution = $tickets->groupBy('dependency_mode')->map(function($group, $mode) use ($ticketCount) {
            return [
                'algorithm' => $mode == 2 ? 'Divide & Conquer' : 'Greedy',
                'count' => $group->count(),
                'percentage' => $ticketCount > 0 ? round(($group->count() / $ticketCount) * 100, 1) : 0,
                'exists' => true
            ];
        });

        // Fixed order algorithms
        $fixedOrder = [
            ['algorithm' => 'Greedy', 'mode' => 1],
            ['algorithm' => 'Divide & Conquer', 'mode' => 2]
        ];

        $algorithmDistribution = collect($fixedOrder)->map(function($algo) use ($actualDistribution) {
            $existing = $actualDistribution->firstWhere('algorithm', $algo['algorithm']);
            return $existing ?? [
                'algorithm' => $algo['algorithm'],
                'count' => 0,
                'percentage' => 0,
                'exists' => false
            ];
        });

        // Primary algorithm
        $existingAlgos = $algorithmDistribution->where('exists', true);
        $primary = $existingAlgos->first();
        $primaryAlgorithm = $primary['algorithm'] ?? 'Not Specified';
        $primaryPercentage = $primary['percentage'] ?? 0;

        // Performance metrics (even if execution_time is null)
        $completedTickets = $tickets->whereNotNull('execution_time');

        return [
            'primary_algorithm' => $primaryAlgorithm,
            'primary_algorithm_percentage' => $primaryPercentage,
            'algorithm_distribution' => $algorithmDistribution,
            'total_tasks' => $ticketCount,
            'completed_tasks' => $completedTickets->count(),
            'avg_execution_time' => $this->normalizeExecutionTime($completedTickets->avg('execution_time') ?? 0),
            'avg_utilization' => $completedTickets->avg('resource_utilization') ?? 0,
            'avg_accuracy' => $completedTickets->avg('scheduling_accuracy') ?? 0,
            'algorithm_metrics' => $this->getAlgorithmSpecificMetrics($tickets),
        ];
    }

    protected function getAlgorithmSpecificMetrics($tickets)
    {
        $metrics = [];
        $allAlgorithms = [
            ['mode' => 1, 'name' => 'Greedy'],
            ['mode' => 2, 'name' => 'Divide & Conquer']
        ];

        foreach ($allAlgorithms as $algo) {
            $mode = $algo['mode'];
            $name = $algo['name'];
            $group = $tickets->where('dependency_mode', $mode);
            $completed = $group->whereNotNull('execution_time');

            if ($group->count() > 0) {
                $metrics[] = [
                    'algorithm' => $name,
                    'total_tasks' => $group->count(),
                    'completed_tasks' => $completed->count(),
                    'avg_execution' => $completed->avg('execution_time') ?? 0,
                    'avg_utilization' => $completed->avg('resource_utilization') ?? 0,
                    'avg_accuracy' => $completed->avg('scheduling_accuracy') ?? 0,
                    'exists' => true
                ];
            } else {
                $metrics[] = [
                    'algorithm' => $name,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_execution' => 0,
                    'avg_utilization' => 0,
                    'avg_accuracy' => 0,
                    'exists' => false,
                    'message' => 'No tasks using this algorithm in current project',
                ];
            }
        }

        return $metrics;
    }

    protected function normalizeExecutionTime($time)
    {
        if ($time <= 0) return 0;
        return round(log($time + 1) * 10, 1);
    }

    protected function getEmptyMetrics()
    {
        return [
            'primary_algorithm' => 'Not Specified',
            'algorithm_distribution' => collect(),
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'avg_execution_time' => 0,
            'avg_utilization' => 0,
            'avg_accuracy' => 0,
            'algorithm_metrics' => [],
        ];
    }

    public function render()
    {
        return view('livewire.algorithm-metrics');
    }
}
