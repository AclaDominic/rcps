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

class CreateProjectOLD
{
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
            'dependency_mode'=> 1,
            'comparison_id' => $comparisonId,
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
            'dependency_mode'=> 2,
            'comparison_id' => $comparisonId,
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
        Log::info('generateSingleAiTasks (1:ok)');
        // Generate subtasks with specified algorithm
        $result = $this->generateAiSubtasks($data, $algorithmMode);
        $generatedTasks = $result['tasks'];
        $projectComplexity = $result['complexity'];
        
        // Calculate total hours
        $totalHours = collect($generatedTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });
        Log::info('generateSingleAiTasks (2:ok)');
        // // Save to ai_task_previews table
        AiTaskPreview::updateOrCreate(
            [
                'task_uuid' => $taskUuid,
                'user_id' => auth()->id()
            ],
            [
                'project_name' => $data['project_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'main_task_name' => $data['main_task_name'],
                'main_task_description' => $data['main_task_description'],
                'ai_subtask_count' => $data['ai_subtask_count'],
                'generated_tasks' => $generatedTasks,
                'total_hours' => $totalHours,
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'algorithm_mode' => $algorithmMode, // Store algorithm used
                'complexity_data' => json_encode($projectComplexity)
            ]
        );
        Log::info('generateSingleAiTasks (3:ok)');
        $this->aiResults[$taskUuid][$algorithmMode] = $generatedTasks;
        $this->aiResults[$taskUuid]['is_comparative'] = true;
        Log::info('generateSingleAiTasks (4:ok)');
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
        Log::info('generateComparativeAiTasks (1:ok)');
        // Generate for Divide & Conquer
        $divideConquerResult = $this->generateAiSubtasks($data, 'divide_conquer');
        $dcTasks = $divideConquerResult['tasks'];
        $dcTotalHours = collect($dcTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });

        Log::info('generateComparativeAiTasks (2:ok)');
        // Generate for Greedy
        $greedyResult = $this->generateAiSubtasks($data, 'greedy');
        $greedyTasks = $greedyResult['tasks'];
        $greedyTotalHours = collect($greedyTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });
        Log::info('generateComparativeAiTasks (3:ok)');
        // Save comparative data
        AiTaskPreview::updateOrCreate(
            [
                'task_uuid' => $taskUuid,
                'user_id' => auth()->id()
            ],
            [
                'project_name' => $data['project_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'main_task_name' => $data['main_task_name'],
                'main_task_description' => $data['main_task_description'],
                'ai_subtask_count' => $data['ai_subtask_count'],
                'generated_tasks' => $dcTasks, // Default to D&C for backward compatibility
                'comparative_data' => [ // Store both algorithms
                    'divide_conquer' => [
                        'tasks' => $dcTasks,
                        'total_hours' => $dcTotalHours,
                        'complexity' => $divideConquerResult['complexity']
                    ],
                    'greedy' => [
                        'tasks' => $greedyTasks,
                        'total_hours' => $greedyTotalHours,
                        'complexity' => $greedyResult['complexity']
                    ]
                ],
                'total_hours' => $dcTotalHours,
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'algorithm_mode' => 'comparison',
                'complexity_data' => json_encode($divideConquerResult['complexity'])
            ]
        );
        Log::info('generateComparativeAiTasks (4:ok)');
        // Store in component for preview
        $this->aiResults[$taskUuid] = [
            'divide_conquer' => $dcTasks,
            'greedy' => $greedyTasks,
            'is_comparative' => true
        ];
        Log::info('generateComparativeAiTasks (5:ok)');
        // Generate comparison summary
        $comparisonSummary = $this->generateComparisonSummary($dcTasks, $greedyTasks);
         Log::info('generateComparativeAiTasks (6:ok)');
        $this->dispatchBrowserEvent('notify', [
            'message' => 'Comparative AI tasks generated successfully!',
            'task_uuid' => $taskUuid,
            'comparative' => true,
            'summary' => $comparisonSummary
        ]);
         Log::info('generateComparativeAiTasks (7:ok)');
        return true;
    }

    protected function generateComparisonSummary($dcTasks, $greedyTasks)
    {
        Log::info('generateComparisonSummary (1:ok)');
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
        Log::info('generateComparisonSummary (2:ok)');
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

    protected function generateAiSubtasks($value, $algorithmMode = 'divide_conquer')
    {
         Log::info('generateAiSubtasks (1:ok)');
        $projectName = $value['project_name'];
        $main_task_name = $value['main_task_name'];
        $main_task_description = $value['main_task_description'];
        $start_date = $value['start_date'];
        $end_date = $value['end_date'];

        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);    

        $startformatted = $start->format('F d, Y');
        $endformatted = $end->format('F d, Y');
        
        // Calculate total available hours
        $totalDays = $end->diffInDays($start);
        $totalAvailableHours = $totalDays * 8;
        Log::info('generateAiSubtasks (2:ok)');
        // Calculate project complexity
        $projectComplexity = (new TaskComputationsService)->calculateProjectComplexity($value);
        Log::info('generateAiSubtasks (3:ok)');
        // GET ALGORITHM-SPECIFIC PROMPT
        $prompt = $this->getAlgorithmPrompt(
            $algorithmMode,
            $projectName,
            $main_task_name,
            $main_task_description,
            $startformatted,
            $endformatted,
            $totalDays,
            $totalAvailableHours,
            $projectComplexity,
            $value
        );
        Log::info('generateAiSubtasks (4:ok)');
        $url = $this->getVpsUrl();
        Log::info( $url);
        $response = Http::timeout(300)
            ->withOptions(['connect_timeout' => 30])->post($url.'/api/chat', [
            'model' => 'llama3',
            'stream'=> false,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ],
        ]);
        Log::info('HTTP status: ' . $response->status());
        Log::info('generateAiSubtasks (5:ok)');
        if ($response->failed()) {
            Log::error('HTTP Request Failed: ' . $response->body());
            throw new \Exception('HTTP Request Failed');
        }

        $body = json_decode($response->body(), true);
        $rawContent = $body['message']['content'] ?? '';

        // Extract JSON array from raw content
        $start = strpos($rawContent, '[');
        $end = strrpos($rawContent, ']');

        if ($start === false || $end === false) {
            throw new \Exception('AI response does not contain a valid JSON array.');
        }

        $jsonString = substr($rawContent, $start, $end - $start + 1);
        $subtasks = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make()
                ->title('AI Generation Failed')
                ->body('AI returned invalid JSON. Please try generating again.')
                ->danger()
                ->seconds(4)
                ->send();
            throw new \Exception('AI responded with invalid JSON');
        }

        if (!is_array($subtasks) || empty($subtasks)) {
            Notification::make()
                ->title('AI Generation Failed')
                ->body('AI response is not a valid array.')
                ->danger()
                ->seconds(4)
                ->send();
            throw new \Exception('AI response is not a valid array.');
        }

        Log::info('generateAiSubtasks (8:ok)');
        Log::info('Response array: ', $subtasks); // ✅ Use second argument to log arrays

        $subtasks = $subtasks ?? [];
        Log::info('generateAiSubtasks (9:ok)');
        // PROCESS BASED ON ALGORITHM
        $processedTasks = $this->processSubtasksBasedOnAlgorithm(
            $subtasks, 
            $value, 
            $algorithmMode
        );
        Log::info('generateAiSubtasks (10:ok)');
        return [
            'tasks' => $processedTasks,
            'complexity' => $projectComplexity,
            'algorithm' => $algorithmMode,
            'tasks_total_hours' => array_sum(array_column($processedTasks, 'estimated_hours'))
        ];
    }

    /**
     * Get algorithm-specific prompt
     */
    protected function getAlgorithmPrompt(
        $algorithmMode,
        $projectName,
        $mainTaskName,
        $mainTaskDescription,
        $startDate,
        $endDate,
        $totalDays,
        $totalAvailableHours,
        $projectComplexity,
        $value
    ) {
        $baseContext = "
        You are an expert project management AI assistant. 
        I will give you a main task and you need to break it down into subtasks using the {$algorithmMode} methodology.

        PROJECT CONTEXT:
        Project Name: {$projectName}
        Start Date: {$startDate}
        End Date: {$endDate}
        Total Timeline: {$totalDays} days ({$totalAvailableHours} work hours)
        
        PROJECT COMPLEXITY ANALYSIS:
        Overall Complexity: {$projectComplexity['level']} ({$projectComplexity['score']}/100)
        - Duration: {$projectComplexity['factors']['duration']['label']} ({$projectComplexity['breakdown']['total_days']} days)
        - Task Description: {$projectComplexity['factors']['description']['label']} ({$projectComplexity['breakdown']['description_words']} words)
        - Subtask Count: {$projectComplexity['factors']['subtasks']['label']} ({$projectComplexity['breakdown']['subtask_count']} requested)
        - Task Name Complexity: {$projectComplexity['factors']['task_name']['label']}

        MAIN TASK TO DECOMPOSE:
        Task Name: {$mainTaskName}
        Task Description: {$mainTaskDescription}
        Required Subtasks: {$value['ai_subtask_count']} 
        ";

        if ($algorithmMode === 'divide_conquer') {
            return $baseContext . "

            USING DIVIDE & CONQUER ALGORITHM:
            ================================
            Apply these Divide & Conquer principles:
            1. DIVIDE: Break the main task into independent subproblems
            2. CONQUER: Solve each subproblem recursively
            3. COMBINE: Merge solutions to form the final solution

            TASK DECOMPOSITION STRATEGY:
            - Create a clear hierarchy of tasks
            - Ensure each subtask is self-contained
            - Identify natural breaking points
            - Consider task dependencies carefully

            DEPENDENCY MANAGEMENT:
            - Some tasks must be sequential (A before B)
            - Some can be parallel (A and B simultaneously)
            - Mark convergence points where outputs combine
            - Balance depth vs breadth of decomposition

            OUTPUT REQUIREMENTS:
            - Generate exactly {$value['ai_subtask_count']} subtasks
            - Each subtask should be 2-8 hours (realistic estimation)
            - Total hours should not exceed {$totalAvailableHours}
            - Include dependencies between tasks

            CRITICAL SUCCESS FACTORS:
            - Clear task boundaries
            - Realistic time estimates
            - Logical dependency flow
            - Balanced workload distribution

            JSON OUTPUT FORMAT - RETURN ONLY JSON ARRAY:
            [
                {
                    'title': 'Clear, specific task title',
                    'description': 'Detailed description with specific deliverables and acceptance criteria',
                    'estimated_hours': '3.5',
                    'order': '1',
                    'dependencies': ['task_title_of_dependency_1', 'task_title_of_dependency_2'],
                    'is_critical_path': true,
                    'resource_intensity': 2,
                    'skill_requirements': ['programming', 'design', 'testing'],
                    'risk_level': 'low',
                    'parallelizable': true,
                    'priority_base_score': 85
                }
            ]

            IMPORTANT: Return ONLY valid JSON array, no explanations or introductions.
            Make sure the JSON is properly formatted and can be parsed.
            ";
        } 
        
        elseif ($algorithmMode === 'greedy') {
            return $baseContext . "

            USING GREEDY ALGORITHM:
            ======================
            Apply these Greedy Algorithm principles:
            1. Make the locally optimal choice at each step
            2. Focus on immediate maximum benefit
            3. Don't worry about global optimization
            4. Quick wins and immediate value delivery

            GREEDY SELECTION STRATEGY:
            - Identify tasks with highest value-to-effort ratio
            - Prioritize quick wins (tasks under 4 hours)
            - Choose tasks that deliver immediate value
            - Focus on what can be done NOW for maximum benefit

            MINIMAL DEPENDENCIES:
            - Maximize parallel execution
            - Minimize sequential blocking
            - Independent tasks are preferred
            - Reduce dependencies as much as possible

            TIME-SENSITIVE OPTIMIZATION:
            - Shorter tasks first when possible
            - High impact early in timeline
            - Resource-efficient scheduling
            - Immediate deliverables prioritized

            OUTPUT REQUIREMENTS:
            - Generate exactly {$value['ai_subtask_count']} subtasks
            - Focus on tasks that can be completed quickly
            - Total hours should be optimistic but realistic
            - Minimize dependencies between tasks

            GREEDY-SPECIFIC FIELDS:
            - 'quick_win_score': 1-10 (how quickly can this deliver value)
            - 'immediate_impact': 1-10 (immediate benefit to project)
            - 'effort_to_value_ratio': decimal (value / effort, higher is better)

            CRITICAL SUCCESS FACTORS:
            - Quick delivery of value
            - Minimal blocking dependencies
            - High parallelization potential
            - Immediate project progress

            JSON OUTPUT FORMAT - RETURN ONLY JSON ARRAY:
            [
                {
                    'title': 'Clear, quick-win task title',
                    'description': 'Focused description on immediate deliverables',
                    'estimated_hours': '2.5',
                    'order': '1',
                    'dependencies': [], // Greedy minimizes dependencies
                    'is_critical_path': false, // Greedy focuses on value, not critical path
                    'resource_intensity': 1, // Greedy prefers low resource tasks
                    'skill_requirements': ['basic_skills'],
                    'risk_level': 'low',
                    'parallelizable': true,
                    'priority_base_score': 90,
                    'quick_win_score': 8,
                    'immediate_impact': 9,
                    'effort_to_value_ratio': 3.6
                }
            ]

            IMPORTANT: Return ONLY valid JSON array, no explanations or introductions.
            Make sure the JSON is properly formatted and can be parsed.
            Greedy algorithm should produce faster, more independent tasks.
            ";
        }
        
        // Fallback to divide_conquer if algorithm not recognized
        return $baseContext . "
        
        Generate {$value['ai_subtask_count']} subtasks for this project. RETURN JSON FORMAT ONLY
        
        JSON OUTPUT FORMAT - RETURN ONLY JSON ARRAY:
        [
            {
                'title': 'Task title',
                'description': 'Task description',
                'estimated_hours': '4.0',
                'order': '1',
                'dependencies': [],
                'is_critical_path': false,
                'resource_intensity': 2,
                'skill_requirements': [],
                'risk_level': 'medium',
                'parallelizable': true,
                'priority_base_score': 50
            }
        ]
        ";
    }

    /**
     * Process subtasks based on algorithm type
     */
    protected function processSubtasksBasedOnAlgorithm($subtasks, $value, $algorithmMode)
    {
        Log::info('processSubtasksBasedOnAlgorithm (1:ok)');
        $computations = new TaskComputationsService();
        Log::info('processSubtasksBasedOnAlgorithm (2:ok)');
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
         Log::info('processSubtasksBasedOnAlgorithm (3:ok)');
        // Apply algorithm-specific prioritization
        if ($algorithmMode === 'greedy') {
            $prioritizedTasks = $computations->applyGreedyPrioritization($subtasks, $value);
        } else {
            $prioritizedTasks = $computations->applyEnhancedPrioritization($subtasks, $value);
        }
         Log::info('processSubtasksBasedOnAlgorithm (4:ok)');
        // Assign responsible persons
        $finalTasks = $computations->assignResponsibleWithWorkloadBalance(
            $prioritizedTasks, 
            $algorithmMode
        );
         Log::info('processSubtasksBasedOnAlgorithm (5:ok)');
        return $finalTasks;
    }

    private function getVpsUrl()
    {
        $isLive = app()->environment('production');

        return $isLive ? 'http://72.60.198.142:11434' : 'http://localhost:11434';
    }
}
