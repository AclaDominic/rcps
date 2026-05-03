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


class CreateTicket_Old extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    public $previewData = null;

    public $aiResult = [];

    public $aiResults = [];

    public $totalEstimatedHours = 0;

    public $selectedTaskUuid = null;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $isAiTask = isset($data['ai_subtask_count']) && $data['ai_subtask_count'] > 0;
            Log::info($isAiTask);
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

            // 4. Log creation
            Log::info('Created task from AI preview', [
                'project_id' => $project->id,
                'main_task_id' => $mainTask->id,
                'subtask_count' => count($createdSubtasks),
            ]);

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

    // public function generateAiTasks($data)
    // {
    //     try {
    //         $project = Project::find($data['project_id']);

    //         $data['project_name'] = $project->name;

    //         $data['start_date'] = $project->start_date;

    //         $data['end_date'] = $project->end_date;

    //         $taskUuid = $data['task_uuid'] ?? 'default';

    //         return $this->generateSingleAiTasks($data, $taskUuid, 'divide_conquer');
            
    //     } catch (\Exception $e) {
    //         $this->dispatchBrowserEvent('ai-generation-error', [
    //             'message' => 'AI generation failed: ' . $e->getMessage()
    //         ]);
    //     }
    // }

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

        dd($this->aiResults);
        
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

    // protected function generateAiSubtasks($value, $algorithmMode = 'divide_conquer')
    // {
    //     $projectName = $value['project_name'];
    //     $main_task_name = $value['name'];
    //     $main_task_description = $value['content'];
    //     $start_date = $value['start_date'];
    //     $end_date = $value['end_date'];

    //     $start = Carbon::parse($start_date);
    //     $end = Carbon::parse($end_date);    

    //     $startformatted = $start->format('F d, Y');
    //     $endformatted = $end->format('F d, Y');
        
    //     // Calculate total available hours
    //     $totalDays = $end->diffInDays($start);
    //     $totalAvailableHours = $totalDays * 8;
        
    //     // Calculate project complexity
    //     $projectComplexity = (new TaskComputationsService)->calculateProjectComplexity($value);
        
    //     // GET ALGORITHM-SPECIFIC PROMPT
    //     $prompt = $this->getAlgorithmPrompt(
    //         $algorithmMode,
    //         $projectName,
    //         $main_task_name,
    //         $main_task_description,
    //         $startformatted,
    //         $endformatted,
    //         $totalDays,
    //         $totalAvailableHours,
    //         $projectComplexity,
    //         $value
    //     );

    //     $url = $this->getVpsUrl();

    //     $response = Http::timeout(300)
    //         ->withOptions(['connect_timeout' => 30])->post($url.'/api/chat', [
    //         'model' => 'llama3',
    //         'stream'=> false,
    //         'messages' => [
    //             [
    //                 'role' => 'user',
    //                 'content' => $prompt,
    //             ]
    //         ],
    //     ]);

    //      if ($response->failed()) {
    //         Log::error('HTTP Request Failed: ' . $response->body());
    //         throw new \Exception('HTTP Request Failed');
    //     }

    //     $body = json_decode($response->body(), true);
    //     $rawContent = $body['message']['content'] ?? '';

    //     // Extract JSON array from raw content
    //     $start = strpos($rawContent, '[');
    //     $end = strrpos($rawContent, ']');

    //     if ($start === false || $end === false) {
    //         throw new \Exception('AI response does not contain a valid JSON array.');
    //     }

    //     $jsonString = substr($rawContent, $start, $end - $start + 1);
    //     $subtasks = json_decode($jsonString, true);

    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         Notification::make()
    //             ->title('AI Generation Failed')
    //             ->body('AI returned invalid JSON. Please try generating again.')
    //             ->danger()
    //             ->seconds(4)
    //             ->send();
    //         throw new \Exception('AI responded with invalid JSON: ' . json_last_error_msg());
    //     }

    //     if (!is_array($subtasks) || empty($subtasks)) {
    //         Notification::make()
    //             ->title('AI Generation Failed')
    //             ->body('AI response is not a valid array.')
    //             ->danger()
    //             ->seconds(4)
    //             ->send();
    //         throw new \Exception('AI response is not a valid array.');
    //     }

    //     $subtasks = $subtasks ?? [];

    //     // PROCESS BASED ON ALGORITHM
    //     $processedTasks = $this->processSubtasksBasedOnAlgorithm(
    //         $subtasks, 
    //         $value, 
    //         $algorithmMode
    //     );
        
    //     return [
    //         'tasks' => $processedTasks,
    //         'complexity' => $projectComplexity,
    //         'algorithm' => $algorithmMode,
    //         'tasks_total_hours' => array_sum(array_column($processedTasks, 'estimated_hours'))
    //     ];
    // }

    protected function generateAiSubtasks($value, $algorithmMode = 'divide_conquer')
    {
        $start_date = $value['start_date'];
        $end_date = $value['end_date'];

        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);    

        // Calculate total available hours
        $totalDays = $end->diffInDays($start);
        
        $totalAvailableHours = $totalDays * 8;

        $subtasks=[];

        foreach($value['add_task'] as $subtask){

            $subtask['start_date'] = $value['start_date'];

            $subtask['end_date'] = $value['end_date'];

            $subtask['main_task_name'] = $value['name'];

            $subtask['main_task_description'] = $value['content'];

            $projectComplexity = (new TaskComputationsService)->calculateProjectComplexity($subtask);

            $subtasks[] = (new TaskComputationsService)->generateTaskMetrics($projectComplexity,$totalAvailableHours,$subtask);
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
        - AI Instructions: {$projectComplexity['factors']['ai_description']['label']} ({$projectComplexity['breakdown']['ai_description_words']} words)
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

    private function getVpsUrl()
    {
        $isLive = app()->environment('production');

        return $isLive ? 'http://72.60.198.142:11434' : 'http://localhost:11434';
    }
}
