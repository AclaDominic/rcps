<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use App\Models\TicketRelation;
use App\Services\TaskComputationsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;


class CreateTicket extends CreateRecord
{
    private $TaskComputationsService;

    public function __construct()
    {
        $this->TaskComputationsService = new TaskComputationsService();
    }

    protected static string $resource = TicketResource::class;

    public $previewData = null;

    public $aiResults = [];

    public $totalEstimatedHours = 0;

    public $selectedTaskUuid = null;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $isAiTask = isset($data['add_task']) && $data['add_task'] > 0;
         
            if ($isAiTask) {
               $ticket = $this->createTaskWithSubtasks($data);
            } else {
                $ticket = $this->createNormalTicket($data);
                
                // IMPORTANT: Handle relations for normal ticket
                $this->createRelationsForNormalTicket($ticket, $data);
            }
            
            return $ticket;
        });
    }

    protected function createNormalTicket(array $data): Model
    {
        $maxOrder = Ticket::where('project_id', $data['project_id'])
            ->whereNull('parent_ticket_id')
            ->max('order') ?? 0;

        return Ticket::create([
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'content' => $data['content'],
            'sprint_id' => $data['sprint_id'] ?? null,
            'epic_id' => $data['epic_id'] ?? null,
            'estimation' => $data['estimation'] ?? 0,
            'owner_id' => $data['owner_id'],
            'responsible_id' => $data['responsible_id'] ?? $data['owner_id'],
            'priority_id' => $data['priority_id'],
            'status_id' => $data['status_id'],
            'type_id' => $data['type_id'],
            'dependency_mode' => $data['dependency_mode_id'] ?? null,
            'order' => $maxOrder + 1
        ]);
    }

    protected function createRelationsForNormalTicket(Ticket $ticket, array $data): void
    {
        // Handle relations from the repeater field
        if (isset($data['relations']) && is_array($data['relations'])) {
            foreach ($data['relations'] as $relationData) {
                TicketRelation::create([
                    'ticket_id' => $ticket->id,
                    'relation_id' => $relationData['relation_id'],
                    'type' => $relationData['type'],
                    'sort' => $relationData['sort'] ?? 0 // Add default sort
                ]);
            }
        }
    }

    protected function createTaskWithSubtasks(array $data)
    {
        try {
            $service = new TaskComputationsService();

            $subtasksArray = $this->aiResults;

             if(empty($subtasksArray)) {
                Notification::make()
                    ->title('Creation Failed')
                    ->body('No AI tasks generated')
                    ->danger()
                    ->seconds(4)
                    ->send();
                throw new \Exception('No AI tasks generated');
            }

            $project_id = $data['project_id'];
    
            $maxOrder = Ticket::where('project_id', $project_id)
            ->whereNull('parent_ticket_id')
            ->max('order') ?? 0;
    
            $totalHours = array_reduce($subtasksArray, function($sum, $task) {
                return $sum + (float) $task['estimated_hours'];
            }, 0);

            $maxOrder = Ticket::where('project_id', $project_id)
                ->whereNull('parent_ticket_id') // Main tasks only
                ->max('order') ?? 0;

            $project = Project::find($project_id);

            // Calculate priority based on algorithm
            $mainTaskPriority = $service->calculateMainTaskPriorityFromAiPreview($subtasksArray);

            // 1. Create main task
            $mainTask = Ticket::create([
                'project_id' => $project->id,
                'name' => $data['name'] ?? 'Untitled Task',
                'content' => $data['content'] ?? null,
                'sprint_id' => $data['sprint_id'] ?? null,
                'epic_id' => $data['epic_id'] ?? null,
                'estimation' => $totalHours,
                'owner_id' => $data['owner_id'] ?? $project->owner_id,
                'responsible_id' => $data['responsible_id'] ?? $data['owner_id'] ?? $project->owner_id,
                'priority_id' => $mainTaskPriority,
                'order' => $maxOrder + 1,
                'status_id' => 1,
                'type_id' => 1,
                'dependency_mode' => 2,
                'metadata' => json_encode([
                    'ai_generated' => true,
                    'algorithm_mode' => 'divide_conquer',
                    'comparison_id' => $project->metadata['comparison_id'] ?? null,
                    'complexity_score' => $aiPreview['complexity_data']['score'] ?? null,
                    'complexity_level' => $aiPreview['complexity_data']['level'] ?? null,
                    'created_at' => now()->toISOString()
                ])
            ]);

            $createdSubtasks = []; // I-store ang created subtasks
            
            // 3. Create subtasks from AI preview
            foreach ($subtasksArray as $index => $subtaskData) {
                $subtask = Ticket::create([
                    'project_id' => $project_id,
                    'parent_ticket_id' => $mainTask->id,
                    'name' => $subtaskData['title'] ?? 'Unnamed Subtask',
                    'content' => $subtaskData['description'] ?? null,
                    'estimation' => floatval($subtaskData['estimated_hours'] ?? $subtaskData['estimation'] ?? 0),
                    'owner_id' => $data['owner_id'],
                    'priority_id' => $subtaskData['priority_id'] ?? 2,
                    'responsible_id' => $subtaskData['responsible_id'] ?? null,
                    'order' => $subtaskData['order'] ?? ($index + 1),
                    'dependency_mode' => 2, // Divide & Conquer
                    'type_id' => 1,
                    'status_id' => 1,
                    'start_date' => (new TaskComputationsService)->calculateSubtaskStartDate($project, $index),
                    'due_date' => (new TaskComputationsService)->calculateSubtaskDueDate($project, $subtaskData, $index),
                    'metadata' => json_encode([
                        'ai_generated' => true,
                        'is_critical_path' => $subtaskData['is_critical_path'] ?? false,
                        'resource_intensity' => $subtaskData['resource_intensity'] ?? 2,
                        'skill_requirements' => $subtaskData['skill_requirements'] ?? [],
                        'risk_level' => $subtaskData['risk_level'] ?? 'medium',
                        'parallelizable' => $subtaskData['parallelizable'] ?? true,
                        'dependencies' => $subtaskData['dependencies'] ?? [],
                        'priority_score' => $subtaskData['priority_score'] ?? 0,
                        'priority_range' => $subtaskData['priority_range'] ?? 'normal'
                    ])
                ]);

                $createdSubtasks[] = $subtask; // I-store para sa relations
            }

            // Create internal relations for subtasks (hindi yung user-defined)
            (new TaskComputationsService)->createDependenciesBetweenSubtasks($createdSubtasks, $subtasksArray);


            return $mainTask;

        } catch (\Exception $e) {
            Log::error('Error creating task with subtasks: ' . $e->getMessage());
            throw new \Exception('Failed to create task: ' . $e->getMessage());
        }
    }

    protected function determineMainTaskPriority($project, $taskData)
    {
        // Factors to consider:
        $daysUntilDeadline = now()->diffInDays($project->end_date);
        $projectType = $project->type; // 'kanban' or 'scrum'
        $taskComplexity = strlen($taskData['main_task_description'] ?? ''); // simple heuristic
        
        if ($daysUntilDeadline < 7 || $projectType === 'scrum') {
            return 1; // High - urgent deadline or sprint
        } elseif ($taskComplexity > 500) {
            return 1; // High - complex task
        } else {
            return 2; // Normal - standard task
        }
    }

    protected function createInternalSubtaskRelations($createdSubtasks, $generatedTasks): void
    {
        foreach ($createdSubtasks as $index => $subtask) {
            $relations = [];
            
             for ($i = 0; $i < $index; $i++) {
                $relations[] = [
                    'ticket_id' => $subtask->id,
                    'relation_id' => $createdSubtasks[$i]->id,
                    'type' => 'depends_on',
                    'sort' => $i + 1
                ];
            }
            
            // Parent relationship
            $relations[] = [
                'ticket_id' => $subtask->id,
                'relation_id' => $subtask->parent_ticket_id,
                'type' => 'parent_of', 
                'sort' => $index + 1
            ];
            
            // Save to database
            foreach ($relations as $relationData) {
                TicketRelation::create($relationData);
            }
        }
    }

     /**
     * Get dependency mode based on algorithm
     */
    protected function getDependencyModeForAlgorithm($algorithmMode)
    {
        // 1 = Sequential, 2 = Divide & Conquer, 3 = Greedy
        return $algorithmMode === 'greedy' ? 3 : 2;
    }

    public function generateAiTasks()
    {
        try {
            $data = $this->data;

            $project_id = $data['project_id'];

            $project = Project::find($project_id);

            $data['project_name'] = $project->name;

            $data['start_date'] = $project->start_date;

            $data['end_date'] = $project->end_date;

            $taskUuid = $data['task_uuid'] ?? 'default';

            return $this->generateSingleAiTasks($data, $taskUuid, 'divide_conquer');
            
        } catch (\Exception $e) {
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

        // Calculate total hours
        $totalHours = collect($generatedTasks)->sum(function($task) {
            return floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
        });

        $this->aiResults = $generatedTasks;

        // Show success notification
        $this->dispatchBrowserEvent('notify', [
            'message' => ucfirst(str_replace('_', ' ', $algorithmMode)) . ' AI tasks generated successfully!',
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
        
        // Generate for Greedy
        $greedyResult = $this->generateAiSubtasks($data, 'greedy');
        $greedyTasks = $greedyResult['tasks'];
        
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

    protected function generateAiSubtasks($value, $algorithmMode = 'divide_conquer')
    {
        $start_date = $value['start_date'];
        $end_date = $value['end_date'];

        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);    

        // Calculate total available hours
        $totalDays = $end->diffInDays($start);
        
        $totalAvailableHours = $totalDays * 8;
        
        $subTaskCount = count($value['add_task']);

        $mainProjectComplexity = $this->TaskComputationsService->calculateProjectComplexity(array_merge($value['add_task'], [
            'ai_subtask_count' => $subTaskCount
        ]));

        $mainCritical = $this->TaskComputationsService->isCriticalPath($mainProjectComplexity['level_num']);

        $subtasks=[];

        foreach($value['add_task'] as $subtask){

            $subtaskForComplexity = array_merge($subtask, [
                'main_task_name' => $value['name'] ?? '',
                'main_task_description' => $value['content'] ?? '',
                'ai_subtask_count' => $subTaskCount
            ]);

            $subtaskComplexity = $this->TaskComputationsService->calculateProjectComplexity($subtaskForComplexity);

            $subtasks[] = $this->TaskComputationsService->generateTaskMetrics(
                $subtaskComplexity,
                $totalAvailableHours,
                $subtaskForComplexity,
                $mainCritical
            );
        }
        
       
        // PROCESS BASED ON ALGORITHM
        $processedTasks = $this->processSubtasksBasedOnAlgorithm(
            $subtasks, 
            $value, 
            $algorithmMode
        );
        
        return [
            'tasks' => $processedTasks,
            'algorithm' => $algorithmMode,
            'tasks_total_hours' => array_sum(array_column($processedTasks, 'estimated_hours'))
        ];
    }

    /**
     * Process subtasks based on algorithm type
     */
    protected function processSubtasksBasedOnAlgorithm($subtasks, $value, $algorithmMode)
    {
        $computations = new TaskComputationsService();

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
            $prioritizedTasks = $computations->applyGreedyPrioritization($subtasks, $value);
        } else {
            $prioritizedTasks = $computations->applyEnhancedPrioritization($subtasks, $value);
        }
        
        // Assign responsible persons
        $finalTasks = $computations->assignResponsibleWithWorkloadBalance(
            $prioritizedTasks, 
            $algorithmMode
        );
        
        return $finalTasks;
    }
}
