<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\AiTaskPreview;
use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketPriority;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Services\TaskComputationsService;

class CreateProject extends CreateRecord
{
    private $TaskComputationsService;

    public function __construct()
    {
        $this->TaskComputationsService = new TaskComputationsService();
    }

    protected static string $resource = ProjectResource::class;

    public $previewData = null;

    public $aiResult = [];

    public $aiResults = [];

    public $totalEstimatedHours = 0;

    public $selectedTaskUuid = null;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // $algorithmMode = $data['algorithm_mode'] ?? 'divide_conquer';
            $algorithmMode = 'comparison';
            // Store AI results for later use
            $aiResults = $this->aiResults ?? [];
            
            if ($algorithmMode === 'comparison') {
                // CREATE 2 PROJECTS FOR COMPARATIVE STUDY
                $projects = $this->createComparativeStudyProjects($data, $aiResults);
                
                // Create comparison dashboard entry
                $comparison = $this->createComparisonRecord($projects);
                
                // Return first project (or the comparison record)
                return $projects['greedy'];
            } else {
                // CREATE SINGLE PROJECT
                return $this->createSingleProjectWithAlgorithm($data, $aiResults, $algorithmMode);
            }
        });
    }

    protected function createComparisonRecord($projects)
    {
        try {
            $greedyProject = $projects['greedy'];
            $divideConquerProject = $projects['divide_conquer'];
            $comparisonId = $projects['comparison_id'] ?? uniqid('comp_');
            
            // Just log and return comparison ID
            Log::info('Comparative study created', [
                'comparison_id' => $comparisonId,
                'greedy_project' => [
                    'id' => $greedyProject->id,
                    'name' => $greedyProject->name
                ],
                'divide_conquer_project' => [
                    'id' => $divideConquerProject->id,
                    'name' => $divideConquerProject->name
                ],
                'user_id' => auth()->id(),
                'created_at' => now()->toISOString()
            ]);
            
            // You can also create a notification or activity
            \Filament\Notifications\Notification::make()
                ->title('Comparative Study Created')
                ->body("Comparison ID: {$comparisonId}")
                ->success()
                ->send();
            
            return $comparisonId;
            
        } catch (\Exception $e) {
            Log::error('Error in createComparisonRecord: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a unique ticket prefix from the project name.
     */
    protected function generateUniqueTicketPrefix(string $name, string $suffix = ''): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
        if (strlen($base) < 2) {
            $base = 'PRJ';
        }
        $unique = $base . ($suffix ? '-' . $suffix : '') . '-' . strtolower(substr(uniqid(), -4));
        // Ensure uniqueness against existing rows
        $counter = 0;
        while (\App\Models\Project::withTrashed()->where('ticket_prefix', $unique)->exists()) {
            $unique = $base . ($suffix ? '-' . $suffix : '') . '-' . strtolower(substr(uniqid(), -4)) . $counter;
            $counter++;
        }
        return $unique;
    }

    protected function createComparativeStudyProjects($data, $aiResults)
    {
        $originalName = $data['name'];
        $comparisonId = uniqid();
        
        // Create Greedy Algorithm Project
        $greedyData = $data;
        $greedyData['name'] = $originalName . ' - Greedy Algorithm';
        $greedyData['description'] = ($data['description'] ?? '') . "\n\n[Comparative Study - Greedy Algorithm]";
        $greedyData['metadata'] = array_merge(
            $data['metadata'] ?? [],
            [
                'original_project_name' => $originalName,
                'algorithm' => 'greedy',
                'comparison_id' => $comparisonId,
                'is_comparison_test' => true,
                'paired_project_id' => null, // Will be set after creating both
            ]
        );
        
        $greedyProject = Project::create([
            'name' => $originalName,
            'description' => $greedyData['description'],
            'owner_id' => $greedyData['owner_id'],
            'status_id' => $greedyData['status_id'],
            'start_date' => $greedyData['start_date'],
            'end_date' => $greedyData['end_date'],
            'type' => $greedyData['type'],
            'dependency_mode' => 1,
            'comparison_id' => $comparisonId,
            'ticket_prefix' => $this->generateUniqueTicketPrefix($originalName, 'G'),
            'metadata' => $greedyData['metadata']
        ]);
        
        // Create Divide & Conquer Algorithm Project  
        $divideConquerData = $data;
        $divideConquerData['name'] = $originalName . ' - Divide & Conquer Algorithm';
        $divideConquerData['description'] = ($data['description'] ?? '') . "\n\n[Comparative Study - Divide & Conquer Algorithm]";
        $divideConquerData['metadata'] = array_merge(
            $data['metadata'] ?? [],
            [
                'original_project_name' => $originalName,
                'algorithm' => 'divide_conquer',
                'comparison_id' => $comparisonId,
                'is_comparison_test' => true,
                'paired_project_id' => $greedyProject->id,
            ]
        );
        
        $divideConquerProject = Project::create([
            'name' => $originalName,
            'description' => $divideConquerData['description'],
            'owner_id' => $divideConquerData['owner_id'],
            'status_id' => $divideConquerData['status_id'],
            'start_date' => $divideConquerData['start_date'],
            'end_date' => $divideConquerData['end_date'],
            'type' => $divideConquerData['type'],
            'dependency_mode' => 2,
            'comparison_id' => $comparisonId,
            'ticket_prefix' => $this->generateUniqueTicketPrefix($originalName, 'DC'),
            'metadata' => $divideConquerData['metadata']
        ]);
        
        // Update greedy project with paired ID
        $greedyProject->update([
            'metadata->paired_project_id' => $divideConquerProject->id
        ]);
        
        // Create tickets for both projects if AI tasks are enabled
        if (isset($data['create_tasks_now']) && $data['create_tasks_now'] && !empty($data['add_task'])) {
            $this->createTasksForComparativeStudy(
                $greedyProject, 
                $divideConquerProject, 
                $data, 
                $aiResults
            );
        }
        
        
        return [
            'greedy' => $greedyProject,
            'divide_conquer' => $divideConquerProject,
            'comparison_id' => $comparisonId
        ];
    }

    protected function createTasksForComparativeStudy($greedyProject, $divideConquerProject, $data, $aiResults)
    {
        $greedyAiResults = $aiResults['greedy'] ?? $aiResults;
        $divideConquerAiResults = $aiResults['divide_conquer'] ?? $aiResults;
        
        // Create tasks for Greedy project
        foreach ($data['add_task'] as $taskData) {
            $this->createTaskWithSubtasks(
                $greedyProject, 
                $taskData, 
                $greedyAiResults, 
                'greedy'
            );
        }
        
        // Create tasks for Divide & Conquer project  
        foreach ($data['add_task'] as $taskData) {
            $this->createTaskWithSubtasks(
                $divideConquerProject, 
                $taskData, 
                $divideConquerAiResults, 
                'divide_conquer'
            );
        }
    }

    protected function createTaskWithSubtasks($project, $taskData, $aiResults, $algorithmMode = 'divide_conquer')
    {
        try {
            // Get AI preview based on algorithm mode
            $aiPreview = $this->getAiPreviewForAlgorithm($aiResults, $algorithmMode);
            
            if (!$aiPreview) {
                Log::warning('No AI preview found for algorithm', [
                    'algorithm' => $algorithmMode,
                    'project_id' => $project->id
                ]);
                return;
            }

            // Get task UUID from preview
            $taskUuid = array_key_first($aiPreview);
            $subtasksArray = $aiPreview[$taskUuid] ?? [];
            
            if (empty($subtasksArray)) {
                Log::warning('Empty subtasks array in AI preview');
                return;
            }

            // Calculate total hours
            $totalHours = array_reduce($subtasksArray, function($sum, $task) {
                return $sum + (float) ($task['estimated_hours'] ?? $task['estimation'] ?? 0);
            }, 0);

            // Get max order for main tasks
            $maxOrder = Ticket::where('project_id', $project->id)
                ->whereNull('parent_ticket_id')
                ->max('order') ?? 0;

            // Calculate priority based on algorithm
            $mainTaskPriority = $this->calculatePriorityBasedOnAlgorithm(
                $aiPreview, 
                $algorithmMode,
                $taskData
            );

            // 1. Create main task with algorithm-specific metadata
            $mainTask = Ticket::create([
                'project_id' => $project->id,
                'name' => $taskData['main_task_name'] ?? 'Untitled Task',
                'content' => $taskData['main_task_description'] ?? null,
                'estimation' => $totalHours,
                'owner_id' => $taskData['owner_id'] ?? $project->owner_id,
                'responsible_id' => $taskData['responsible_id'] ?? $taskData['owner_id'] ?? $project->owner_id,
                'priority_id' => $mainTaskPriority,
                'order' => $maxOrder + 1,
                'status_id' => 1,
                'type_id' => 1,
                'dependency_mode' => $this->getDependencyModeForAlgorithm($algorithmMode),
                'metadata' => json_encode([
                    'ai_generated' => true,
                    'algorithm_mode' => $algorithmMode,
                    'comparison_id' => $project->metadata['comparison_id'] ?? null,
                    'complexity_score' => $aiPreview['complexity_data']['score'] ?? null,
                    'complexity_level' => $aiPreview['complexity_data']['level'] ?? null,
                    'created_at' => now()->toISOString()
                ])
            ]);

            $createdSubtasks = [];
            
            // 2. Create subtasks with algorithm-specific logic
            foreach ($subtasksArray as $index => $subtaskData) {
                $subtask = $this->createSubtask(
                    $project, 
                    $mainTask, 
                    $subtaskData, 
                    $index, 
                    $algorithmMode,
                    $taskData
                );
                
                $createdSubtasks[] = $subtask;
            }

            // 3. Create dependencies with algorithm-specific logic
            $this->createDependenciesBasedOnAlgorithm(
                $createdSubtasks, 
                $subtasksArray, 
                $algorithmMode
            );

            Log::info('Created task with AI preview', [
                'project_id' => $project->id,
                'main_task_id' => $mainTask->id,
                'algorithm' => $algorithmMode,
                'subtask_count' => count($createdSubtasks),
                'total_hours' => $totalHours
            ]);
            
            // Clean up preview data if needed
            AiTaskPreview::where('session_id', session()->getId())->delete();

            return $mainTask;

        } catch (\Exception $e) {
            Log::error('Error creating task with subtasks: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'algorithm' => $algorithmMode,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create task: ' . $e->getMessage());
        }
    }

    protected function createSingleProjectWithAlgorithm($data, $aiResults, $algorithmMode)
    {
        try {
            // Use the original data
            $projectData = $data;
            
            // Add algorithm-specific metadata
            $projectData['metadata'] = array_merge(
                $data['metadata'] ?? [],
                [
                    'algorithm' => $algorithmMode,
                    'ai_generated' => !empty($aiResults),
                    'comparison_id' => null,
                    'is_comparison_test' => false,
                    'created_at' => Carbon::now()
                ]
            );
            
            // Set dependency mode based on algorithm
            $projectData['dependency_mode'] = $this->getDependencyModeForAlgorithm($algorithmMode);
            
            // Create the project
            $project = Project::create([
                'name' => $projectData['name'],
                'description' => $projectData['description'] ?? '',
                'owner_id' => $projectData['owner_id'],
                'status_id' => $projectData['status_id'] ?? 1,
                'start_date' => $projectData['start_date'],
                'end_date' => $projectData['end_date'] ?? null,
                'type' => $projectData['type'] ?? 'standard',
                'dependency_mode' => $projectData['dependency_mode'],
                'ticket_prefix' => $this->generateUniqueTicketPrefix($projectData['name']),
                'metadata' => $projectData['metadata']
            ]);
            
            // Create tasks if enabled
            if (isset($data['create_tasks_now']) && $data['create_tasks_now'] && !empty($data['add_task'])) {
                $this->createTasksForSingleProject($project, $data, $aiResults, $algorithmMode);
            }
            
            Log::info('Single project created with algorithm', [
                'project_id' => $project->id,
                'algorithm' => $algorithmMode,
                'has_tasks' => isset($data['create_tasks_now']) && $data['create_tasks_now']
            ]);
            
            return $project;
            
        } catch (\Exception $e) {
            Log::error('Error in createSingleProjectWithAlgorithm: ' . $e->getMessage(), [
                'algorithm' => $algorithmMode,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create project: ' . $e->getMessage());
        }
    }

    protected function createTasksForSingleProject($project, $data, $aiResults, $algorithmMode)
    {
        foreach ($data['add_task'] as $taskData) {
            $this->createTaskWithSubtasks(
                $project, 
                $taskData, 
                $aiResults, 
                $algorithmMode
            );
        }
    }

    /**
     * Get AI preview data based on algorithm mode
     */
    protected function getAiPreviewForAlgorithm($aiResults, $algorithmMode)
    {
        // Kung may algorithm-specific na preview structure
        if (isset($aiResults[$algorithmMode])) {
            return $aiResults[$algorithmMode];
        }
        
        // Kung unified ang structure, gamitin ang general preview
        if (isset($aiResults['preview'])) {
            return $aiResults['preview'];
        }
        
        // Default: use first available preview
        return is_array($aiResults) ? reset($aiResults) : $aiResults;
    }

    /**
     * Calculate priority based on algorithm
     */
    protected function calculatePriorityBasedOnAlgorithm($aiPreview, $algorithmMode, $taskData)
    {
        $service = new TaskComputationsService();
        
        if ($algorithmMode === 'greedy') {
            // Greedy: Higher priority for tasks with immediate value
            return $service->calculateGreedyPriority($aiPreview, $taskData);
        } else {
            // Divide & Conquer: Priority based on complexity
            return $service->calculateMainTaskPriorityFromAiPreview($aiPreview);
        }
    }

    /**
     * Get dependency mode based on algorithm
     */
    protected function getDependencyModeForAlgorithm($algorithmMode)
    {
        // 1 = Sequential, 2 = Divide & Conquer, 3 = Greedy
        return $algorithmMode === 'greedy' ? 1 : 2;
    }

    /**
     * Create subtask with algorithm-specific adjustments
     */
    protected function createSubtask($project, $mainTask, $subtaskData, $index, $algorithmMode, $taskData)
    {
        $computations = new TaskComputationsService();
        
        // Adjust estimations based on algorithm
        $estimation = $this->adjustEstimationForAlgorithm(
            $subtaskData, 
            $algorithmMode,
            $index
        );
        
        // Adjust priority based on algorithm
        $priorityId = $this->adjustSubtaskPriorityForAlgorithm(
            $subtaskData,
            $algorithmMode,
            $index
        );
        
        return Ticket::create([
            'project_id' => $project->id,
            'parent_ticket_id' => $mainTask->id,
            'name' => $subtaskData['title'] ?? 'Unnamed Subtask',
            'content' => $subtaskData['description'] ?? null,
            'estimation' => $estimation,
            'owner_id' => $taskData['owner_id'] ?? $project->owner_id,
            'priority_id' => $priorityId,
            'responsible_id' => $subtaskData['responsible_id'] ?? $taskData['owner_id'] ?? $project->owner_id,
            'order' => $subtaskData['order'] ?? ($index + 1),
            'dependency_mode' => $this->getDependencyModeForAlgorithm($algorithmMode),
            'type_id' => 1,
            'status_id' => 1,
            'start_date' => $computations->calculateSubtaskStartDate($project, $index, $algorithmMode),
            'due_date' => $computations->calculateSubtaskDueDate($project, $subtaskData, $index, $algorithmMode),
            'metadata' => json_encode([
                'ai_generated' => true,
                'algorithm' => $algorithmMode,
                'is_critical_path' => $subtaskData['is_critical_path'] ?? false,
                'resource_intensity' => $subtaskData['resource_intensity'] ?? 2,
                'skill_requirements' => $subtaskData['skill_requirements'] ?? [],
                'risk_level' => $subtaskData['risk_level'] ?? 'medium',
                'parallelizable' => $this->adjustParallelizableForAlgorithm(
                    $subtaskData, 
                    $algorithmMode
                ),
                'dependencies' => $subtaskData['dependencies'] ?? [],
                'priority_score' => $subtaskData['priority_score'] ?? 0,
                'priority_range' => $subtaskData['priority_range'] ?? 'normal',
                'comparison_index' => $index
            ])
        ]);
    }

    /**
     * Create dependencies with algorithm-specific logic
     */
    protected function createDependenciesBasedOnAlgorithm($subtasks, $subtasksArray, $algorithmMode)
    {
        $computations = new TaskComputationsService();
        
        if ($algorithmMode === 'greedy') {
            // Greedy: Minimal dependencies, maximize parallelization
            // $computations->createGreedyDependencies($subtasks, $subtasksArray);
        } else {
            // Divide & Conquer: Structured dependencies
            $computations->createDependenciesBetweenSubtasks($subtasks, $subtasksArray);
        }
    }

    /**
     * Adjust estimation based on algorithm
     */
    protected function adjustEstimationForAlgorithm($subtaskData, $algorithmMode, $index)
    {
        $baseEstimation = floatval(
            $subtaskData['estimated_hours'] ?? 
            $subtaskData['estimation'] ?? 
            0
        );
        
        if ($algorithmMode === 'greedy') {
            // Greedy: May adjust estimations based on priority
            // Example: Reduce time for high-priority tasks
            $priorityScore = $subtaskData['priority_score'] ?? 0;
            
            if ($priorityScore > 7) {
                // High priority tasks get 20% time reduction (optimistic)
                return $baseEstimation * 0.8;
            }
        }
        
        return $baseEstimation;
    }

    protected function adjustSubtaskPriorityForAlgorithm($subtaskData, $algorithmMode, $index)
    {
        $basePriority = $subtaskData['priority_id'] ?? 2;
        
        if ($algorithmMode === 'greedy') {
            // Greedy: Adjust priority based on quick win potential
            $estimation = floatval($subtaskData['estimated_hours'] ?? $subtaskData['estimation'] ?? 0);
            
            // Quick tasks get higher priority in greedy
            if ($estimation <= 2) {
                return max(1, $basePriority - 1); // Increase priority
            } elseif ($estimation >= 16) {
                return min(5, $basePriority + 1); // Decrease priority
            }
        }
        
        return $basePriority;
    }

    protected function adjustParallelizableForAlgorithm($subtaskData, $algorithmMode)
    {
        $baseValue = $subtaskData['parallelizable'] ?? true;
        
        if ($algorithmMode === 'greedy') {
            // Greedy favors parallelizable tasks more
            return $baseValue === true ? true : false;
        }
        
        return $baseValue;
    }

    public function previewTask($taskUuid)
    {
        $this->selectedTaskUuid = $taskUuid;
        
        $preview = AiTaskPreview::where('task_uuid', $taskUuid)
            ->where('session_id', session()->getId())
            ->first();

        if ($preview) {

            $totalTasks = count($preview->generated_tasks) ?? 0;

            $totalHours = $preview->total_hours ?? 0;

            $tasks = json_decode(json_encode($preview->generated_tasks), true);

            foreach ($tasks as &$task) {
                // Load user once
                $user = User::find($task['responsible_id']);

                // Attach readable info
                $task['responsible_name'] = $user?->name ?? 'Unknown';

                // Example: Add priority name (kung may Priority model ka)
                $task['priority_name'] = TicketPriority::find($task['priority_id'])->name ?? 'N/A';
            }

            $this->previewData = [
                'tasks' => $tasks,
                'summary' => [
                    'project_name' => $preview->project_name,
                    'total_tasks' => count($preview->generated_tasks),
                    'total_hours' => $preview->total_hours,
                    'main_task_name' => $preview->main_task_name,
                    'main_task_description' => $preview->main_task_description,
                    'start_date'=>Carbon::parse($preview->start_date)->format('F d, Y'),
                    'end_date'=>Carbon::parse($preview->end_date)->format('F d, Y'),
                    'day_required'=> number_format(($preview->total_hours ?? 0) / 8, 2),
                    'average_total' => ($totalHours != 0)?number_format($totalHours / $totalTasks, 2):0
                ]
            ];
             $this->dispatchBrowserEvent('open-task-preview', [
                'previewData' => $this->previewData,
            ]);
        } else {
            $this->previewData = null;
            // Optional: Show error message
            $this->dispatchBrowserEvent('notify', [
                'message' => 'No AI tasks found. Please generate tasks first.',
                'type' => 'error'
            ]);
        }
    }

    public function generateAiTasks($data)
    {
        try {
            $taskUuid = $data['task_uuid'] ?? 'default';

            $data['add_task'] = $this->data['add_task'][$taskUuid];

            $data['add_subtasks'] = $this->data['add_task'][$taskUuid]['add_subtask'];
           
            $algorithmMode = $this->data['algorithm_mode'] ?? 'divide_conquer'; // GET ALGORITHM FROM FORM

            $algorithmMode = $data['mode'] ?? 'divide_conquer';

            if ($algorithmMode === 'comparison') {
                // COMPARATIVE STUDY: Generate both algorithms
                return $this->generateComparativeAiTasks($data, $taskUuid);
            } else {
                // SINGLE ALGORITHM: Generate based on selected algorithm
                return $this->generateSingleAiTasks($data, $taskUuid, $algorithmMode);
            }
            
        } catch (\Exception $e) {
            Log::error($e);
            $this->dispatchBrowserEvent('ai-generation-error', [
                'message' => 'AI generation failed: ' . $e->getMessage()
            ]);
        }
    }

    protected function generateSingleAiTasks($data, $taskUuid, $algorithmMode)
    {
        // Generate subtasks with specified algorithm
        $result = $this->generateAiSubtasks($data, $algorithmMode);

        $generatedTasks = $result['tasks'];

        $projectComplexity = $result['complexity'];
        
        // Calculate total hours
        $totalHours = collect($generatedTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });
    
   
        $this->aiResults[$taskUuid][$algorithmMode] = $generatedTasks;

        $this->aiResults[$taskUuid]['is_comparative'] = true;
  
        // Show success notification
        $this->dispatchBrowserEvent('notify', [
            'message' => ucfirst(str_replace('_', ' ', $algorithmMode)) . ' AI tasks generated successfully!',
            'task_uuid' => $taskUuid,
            'count' => count($generatedTasks),
            'total_hours' => $totalHours,
            'algorithm' => $algorithmMode
        ]);
    }

    protected function generateComparativeAiTasks($data, $taskUuid)
    {
        // Generate for Divide & Conquer

        $divideConquerResult = $this->generateAiSubtasks($data, 'divide_conquer');

        $dcTasks = $divideConquerResult['tasks'];

        $dcTotalHours = collect($dcTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });

        // Build per-user hour totals from D&C so Greedy knows about them.
        // This ensures "Workload after assignment" in Greedy reflects the
        // combined load (D&C hours + Greedy hours) rather than Greedy-only.
        $dcWorkloadPerUser = [];
        foreach ($dcTasks as $task) {
            if (!empty($task['responsible_id'])) {
                $uid = $task['responsible_id'];
                $dcWorkloadPerUser[$uid] = ($dcWorkloadPerUser[$uid] ?? 0) + floatval($task['estimated_hours'] ?? 0);
            }
        }

        // Generate for Greedy (seeded with D&C assignments so its reason texts
        // already reflect the combined D&C + Greedy workload from the start)
        $greedyResult = $this->generateAiSubtasks($data, 'greedy', $dcWorkloadPerUser);
        $greedyTasks = $greedyResult['tasks'];

        $greedyTotalHours = collect($greedyTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });

        // Build per-user Greedy hour totals to retroactively update D&C reason texts.
        // D&C ran first so it doesn't know about Greedy's assignments yet.
        $greedyHoursPerUser = [];
        foreach ($greedyTasks as $task) {
            if (!empty($task['responsible_id'])) {
                $uid = $task['responsible_id'];
                $greedyHoursPerUser[$uid] = ($greedyHoursPerUser[$uid] ?? 0) + floatval($task['estimated_hours'] ?? 0);
            }
        }
        $dcTasks = $this->TaskComputationsService->updateReasonTextsWithCrossAlgorithmHours($dcTasks, $greedyHoursPerUser);

        // Store in component for preview
        $this->aiResults[$taskUuid] = [
            'divide_conquer' => $dcTasks,
            'greedy' => $greedyTasks,
            'is_comparative' => true
        ];
    
        // Generate comparison summary
        $comparisonSummary = $this->generateComparisonSummary($dcTasks, $greedyTasks);

        $this->dispatchBrowserEvent('notify', [
            'message' => 'Comparative AI tasks generated successfully!',
            'task_uuid' => $taskUuid,
            'comparative' => true,
            'summary' => $comparisonSummary
        ]);
    
        return true;
    }

    protected function generateComparisonSummary($dcTasks, $greedyTasks)
    {
        $dcHours = collect($dcTasks)->sum('estimated_hours');

        $greedyHours = collect($greedyTasks)->sum('estimated_hours');
        
        $dcParallel = collect($dcTasks)->where('parallelizable', true)->count();

        $greedyParallel = collect($greedyTasks)->where('parallelizable', true)->count();
        
        $dcDependencies = collect($dcTasks)->sum(function($task) {
            return count($task['dependencies'] ?? []);
        });

        $greedyDependencies = collect($greedyTasks)->sum(function($task) {
            return count($task['dependencies'] ?? []);
        });
     
        return [
            'hours_difference' => $greedyHours - $dcHours,
            'parallel_difference' => $greedyParallel - $dcParallel,
            'dependencies_difference' => $greedyDependencies - $dcDependencies,
            'divide_conquer' => [
                'total_hours' => $dcHours,
                'parallel_tasks' => $dcParallel,
                'total_dependencies' => $dcDependencies,
                'task_count' => count($dcTasks)
            ],
            'greedy' => [
                'total_hours' => $greedyHours,
                'parallel_tasks' => $greedyParallel,
                'total_dependencies' => $greedyDependencies,
                'task_count' => count($greedyTasks)
            ]
        ];
    }

    protected function generateAiSubtasks($value, $algorithmMode = 'divide_conquer', $crossAlgorithmHours = [])
    {
        $start_date = $value['start_date'];
        $end_date = $value['end_date'];

        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);    
        
        // Calculate total available hours
        $totalDays = $end->diffInDays($start);
        $totalAvailableHours = $totalDays * 8;

        $subTaskCount = count($value['add_subtasks']);

        // Calculate main project complexity first
        $mainProjectComplexity = $this->TaskComputationsService->calculateProjectComplexity(array_merge($value['add_task'], [
            'ai_subtask_count' => $subTaskCount
        ]));

        $mainCritical = $this->TaskComputationsService->isCriticalPath($mainProjectComplexity['level_num']);

        $subtasks=[];

        foreach ($value['add_subtasks'] as $subtask) {
            // Merge main task context into subtask
            $subtaskForComplexity = array_merge($subtask, [
                'main_task_name' => $value['add_task']['main_task_name'] ?? '',
                'main_task_description' => $value['add_task']['main_task_description'] ?? '',
                'ai_subtask_count' => $subTaskCount
            ]);

            // Calculate subtask complexity
            $subtaskComplexity = $this->TaskComputationsService->calculateProjectComplexity($subtaskForComplexity);

            $subtasks[] = $this->TaskComputationsService->generateTaskMetrics(
                $subtaskComplexity,
                $totalAvailableHours,
                $subtaskForComplexity,
                $mainCritical,
                $algorithmMode
            );
        }

        // PROCESS BASED ON ALGORITHM
        $processedTasks = $this->processSubtasksBasedOnAlgorithm(
            $subtasks,
            $value,
            $algorithmMode,
            $crossAlgorithmHours
        );

        return [
            'tasks' => $processedTasks,
            'complexity' => $mainProjectComplexity,
            'algorithm' => $algorithmMode,
            'tasks_total_hours' => array_sum(array_column($processedTasks, 'estimated_hours'))
        ];
    }

    /**
     * Process subtasks based on algorithm type
     */
    protected function processSubtasksBasedOnAlgorithm($subtasks, $value, $algorithmMode, $crossAlgorithmHours = [])
    {
        foreach ($subtasks as $i => &$subtask) {
            // Ensure numeric estimated hours
            $subtask['estimated_hours'] = floatval($subtask['estimated_hours'] ?? 1.0);
            
            // Set order
            $subtask['order'] = $i + 1;
            
            // Algorithm-specific defaults
            if ($algorithmMode === 'greedy') {
                // GREEDY DEFAULTS
                $subtask['parallelizable'] = $subtask['parallelizable'] ?? true;
                $subtask['is_critical_path'] = $subtask['is_critical_path'] ?? false;
                $subtask['resource_intensity'] = $subtask['resource_intensity'] ?? 1; // Lower for greedy
                $subtask['risk_level'] = $subtask['risk_level'] ?? 'low';
                $subtask['dependencies'] = $subtask['dependencies'] ?? []; // Minimize dependencies
                
                // Greedy-specific fields
                $subtask['quick_win_score'] = $subtask['quick_win_score'] ?? 
                    ($subtask['estimated_hours'] <= 2 ? 9 : 
                    ($subtask['estimated_hours'] <= 4 ? 7 : 5));
                
                $subtask['immediate_impact'] = $subtask['immediate_impact'] ?? 
                    (($subtask['is_critical_path'] ?? false) ? 8 : 6);
                
                $subtask['effort_to_value_ratio'] = $subtask['effort_to_value_ratio'] ?? 
                    ($subtask['estimated_hours'] > 0 ? 
                    ($subtask['immediate_impact'] / $subtask['estimated_hours']) : 1.0);
                    
            } else {
                // DIVIDE & CONQUER DEFAULTS
                $subtask['parallelizable'] = $subtask['parallelizable'] ?? true;
                $subtask['is_critical_path'] = $subtask['is_critical_path'] ?? ($i === 0);
                $subtask['resource_intensity'] = $subtask['resource_intensity'] ?? 2;
                $subtask['risk_level'] = $subtask['risk_level'] ?? 'medium';
                $subtask['dependencies'] = $subtask['dependencies'] ?? [];
            }
            
            // Common fields
            $subtask['skill_requirements'] = $subtask['skill_requirements'] ?? [];
            $subtask['priority_base_score'] = $subtask['priority_base_score'] ?? 50;
            
            // Add algorithm metadata
            $subtask['algorithm'] = $algorithmMode;
        }
  
        // Apply algorithm-specific prioritization
        if ($algorithmMode === 'greedy') {
            $prioritizedTasks = $this->TaskComputationsService->applyGreedyPrioritization($subtasks, $value);
        } else {
            $prioritizedTasks = $this->TaskComputationsService->applyEnhancedPrioritization($subtasks, $value);
        }

        // Assign responsible persons
        $finalTasks = $this->TaskComputationsService->assignResponsibleWithWorkloadBalance(
            $prioritizedTasks,
            $algorithmMode,
            $crossAlgorithmHours
        );

        return $finalTasks;
    }
}
