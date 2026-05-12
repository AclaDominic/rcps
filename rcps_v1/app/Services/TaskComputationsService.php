<?php 
namespace App\Services;

use App\Models\AiTaskPreview;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaskComputationsService
{
    /**
     * Calculate project/subtask complexity using keyword signals and structural factors.
     * Weights: keyword_complexity(35%) + subtasks(25%) + timeline_pressure(20%)
     *        + description_completeness(15%) + task_name(5%) = 1.00
     */
    public function calculateProjectComplexity(array $projectData): array
    {
        $factors = [];

        // --- Factor 1: Keyword Complexity (35%) ---
        // Technical keywords are real complexity signals; avoids word-padding games.
        $allText = strtolower(implode(' ', [
            strip_tags($projectData['main_task_name'] ?? ''),
            strip_tags($projectData['main_task_description'] ?? ''),
            strip_tags($projectData['subtask_title'] ?? ''),
            strip_tags($projectData['subtask_description'] ?? ''),
        ]));

        $highKeywords = [
            'api', 'oauth', 'authentication', 'authorization', 'encrypt', 'encryption',
            'microservice', 'kubernetes', 'docker', 'distributed', 'concurrent', 'real-time',
            'realtime', 'webhook', 'elasticsearch', 'redis', 'cache', 'queue', 'async',
            'migration', 'architecture', 'infrastructure', 'compliance', 'security',
            'algorithm', 'optimize', 'optimization', 'scalable', 'performance',
            'pipeline', 'multi-tenant', 'multitenant', 'rbac', 'jwt', 'ssl', 'tls',
            'load balancing', 'sharding', 'replication', 'failover', 'zero-downtime',
        ];

        $mediumKeywords = [
            'integrate', 'integration', 'configure', 'configuration', 'implement',
            'database', 'connect', 'validate', 'validation', 'monitor', 'monitoring',
            'automate', 'automation', 'workflow', 'notification', 'deploy', 'deployment',
            'report', 'dashboard', 'filter', 'search', 'upload', 'export', 'import',
        ];

        $highFound   = count(array_filter($highKeywords,   fn($k) => strpos($allText, $k) !== false));
        $mediumFound = count(array_filter($mediumKeywords, fn($k) => strpos($allText, $k) !== false));
        $keywordScore = min(100, ($highFound * 15) + ($mediumFound * 5));

        $factors['keyword_complexity'] = [
            'score'  => $keywordScore,
            'weight' => 0.35,
            'label'  => match (true) {
                $keywordScore >= 60 => 'High Technical Complexity',
                $keywordScore >= 30 => 'Moderate Technical Complexity',
                $keywordScore >= 10 => 'Some Technical Elements',
                default             => 'Basic Task',
            },
        ];

        // --- Factor 2: Subtask Count (25%) ---
        // More subtasks = more decomposition needed = higher complexity. No division.
        $subtaskCount = max(1, (int) ($projectData['ai_subtask_count'] ?? 1));
        $subtaskScore = match (true) {
            $subtaskCount >= 13 => 80,
            $subtaskCount >= 8  => 60,
            $subtaskCount >= 4  => 40,
            default             => 20,
        };

        $factors['subtasks'] = [
            'score'  => $subtaskScore,
            'weight' => 0.25,
            'label'  => match (true) {
                $subtaskCount >= 13 => 'Very Many',
                $subtaskCount >= 8  => 'Many',
                $subtaskCount >= 4  => 'Moderate',
                default             => 'Few',
            },
        ];

        // --- Factor 3: Timeline Pressure (20%) ---
        // Use days-per-task so short projects with few tasks aren't unfairly penalized.
        $startDate   = Carbon::parse($projectData['start_date'] ?? now());
        $endDate     = Carbon::parse($projectData['end_date'] ?? now());
        $totalDays   = max(1, $endDate->diffInDays($startDate));
        $daysPerTask = $totalDays / $subtaskCount;

        $timelineScore = match (true) {
            $daysPerTask <= 2  => 80,
            $daysPerTask <= 5  => 65,
            $daysPerTask <= 14 => 45,
            default            => 20,
        };

        $factors['timeline_pressure'] = [
            'score'  => $timelineScore,
            'weight' => 0.20,
            'label'  => match (true) {
                $daysPerTask <= 2  => 'Very Tight',
                $daysPerTask <= 5  => 'Tight',
                $daysPerTask <= 14 => 'Moderate',
                default            => 'Relaxed',
            },
        ];

        // --- Factor 4: Description Completeness (15%) ---
        // Signals how well-defined the task is; uses combined word count as a proxy.
        $mainDesc       = strip_tags($projectData['main_task_description'] ?? '');
        $subDesc        = strip_tags($projectData['subtask_description'] ?? '');
        $totalDescWords = str_word_count($mainDesc) + str_word_count($subDesc);

        $descScore = match (true) {
            $totalDescWords >= 150 => 80,
            $totalDescWords >= 80  => 60,
            $totalDescWords >= 30  => 40,
            default                => 20,
        };

        $factors['description_completeness'] = [
            'score'  => $descScore,
            'weight' => 0.15,
            'label'  => match (true) {
                $totalDescWords >= 150 => 'Thorough',
                $totalDescWords >= 80  => 'Detailed',
                $totalDescWords >= 30  => 'Basic',
                default                => 'Sparse',
            },
        ];

        // --- Factor 5: Task Name Signals (5%) ---
        $taskName        = strtolower(strip_tags($projectData['main_task_name'] ?? ''));
        $nameHighFound   = count(array_filter($highKeywords,   fn($k) => strpos($taskName, $k) !== false));
        $nameMediumFound = count(array_filter($mediumKeywords, fn($k) => strpos($taskName, $k) !== false));
        $nameScore       = min(100, ($nameHighFound * 30) + ($nameMediumFound * 15) + (str_word_count($taskName) >= 4 ? 20 : 10));

        $factors['task_name'] = [
            'score'  => $nameScore,
            'weight' => 0.05,
            'label'  => $nameHighFound > 0 ? 'Technical Name' : ($nameMediumFound > 0 ? 'Descriptive Name' : 'Simple Name'),
        ];

        // --- Total weighted score (weights sum to exactly 1.0) ---
        $totalScore = 0;
        foreach ($factors as $factor) {
            $totalScore += $factor['score'] * $factor['weight'];
        }

        // --- Complexity level ---
        $levelNum = match (true) {
            $totalScore >= 80 => 5,
            $totalScore >= 65 => 4,
            $totalScore >= 50 => 3,
            $totalScore >= 35 => 2,
            default           => 1,
        };

        $levelLabel = match ($levelNum) {
            5 => 'Very High',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            1 => 'Very Low',
        };

        return [
            'score'     => round($totalScore, 1),
            'level'     => $levelLabel,
            'level_num' => $levelNum,
            'factors'   => $factors,
            'breakdown' => [
                'total_days'            => $totalDays,
                'subtask_count'         => $subtaskCount,
                'days_per_task'         => round($daysPerTask, 1),
                'total_desc_words'      => $totalDescWords,
                'high_keywords_found'   => $highFound,
                'medium_keywords_found' => $mediumFound,
            ],
        ];
    }

    // Enhanced prioritization considering task ranges
    public function applyEnhancedPrioritization($tasks, $projectData)
    {
        if (empty($tasks)) {
            return [];
        }

        $totalTasks = count($tasks);
        $mainTaskPriority = $this->calculateMainTaskPriority($projectData);
        
        // Debug: Log the inputs
        Log::info('Priority Assignment Debug', [
            'total_tasks' => $totalTasks,
            'main_task_priority' => $mainTaskPriority,
            'task_count' => count($tasks)
        ]);
        
        // Calculate priority distribution based on task count
        $priorityRanges = $this->calculatePriorityDistribution($totalTasks, $mainTaskPriority);
        
        // Debug: Log the ranges
        Log::info('Priority Ranges', $priorityRanges);
        
        // Score each task
        $scoredTasks = [];
        foreach ($tasks as $index => $task) {
            $score = $this->calculateTaskPriorityScore($task, $index, $totalTasks);
            $scoredTasks[$index] = [
                'task' => $task,
                'score' => $score,
                'index' => $index
            ];
        }
        
        // Debug: Log scores
        foreach ($scoredTasks as $scored) {
            Log::info('Task Score', [
                'title' => $scored['task']['title'],
                'score' => $scored['score'],
                'is_critical' => $scored['task']['is_critical_path'] ?? false,
                'dependencies' => count($scored['task']['dependencies'] ?? [])
            ]);
        }
        
        // Sort by score (descending)
        usort($scoredTasks, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Assign priorities based on calculated distribution
        $assignedTasks = [];
        $priorityCounts = ['high' => 0, 'normal' => 0, 'low' => 0];
        
        // Calculate how many tasks should get each priority
        $highCount = max(1, ceil($priorityRanges['high'] * $totalTasks));
        $normalCount = max(1, ceil($priorityRanges['normal'] * $totalTasks));
        $lowCount = max(1, ceil($priorityRanges['low'] * $totalTasks));
        
        Log::info('Priority Count Targets', [
            'high' => $highCount,
            'normal' => $normalCount,
            'low' => $lowCount
        ]);
        
        foreach ($scoredTasks as $scored) {
            // Determine priority based on position in sorted list
            if ($priorityCounts['high'] < $highCount) {
                $priority = 1;
                $priorityKey = 'high';
            } elseif ($priorityCounts['normal'] < $normalCount) {
                $priority = 2;
                $priorityKey = 'normal';
            } else {
                $priority = 3;
                $priorityKey = 'low';
            }
            
            // Note: critical path tasks naturally rank higher via calculateTaskPriorityScore (+35pts),
            // so no hard override needed — let the distribution quota control the counts.
            $finalPriority = $priority;
            
            // Convert back to priority key
            if ($finalPriority == 1) $priorityKey = 'high';
            elseif ($finalPriority == 3) $priorityKey = 'low';
            else $priorityKey = 'normal';
            
            $scored['task']['priority_id'] = $finalPriority;
            $scored['task']['priority_score'] = $scored['score'];
            $scored['task']['priority_range'] = $priorityKey;
            $scored['task']['priority_name'] = $this->getPriorityName($finalPriority);
            
            $priorityCounts[$priorityKey]++;
            $assignedTasks[$scored['index']] = $scored['task'];
            
            Log::info('Assigned Priority', [
                'title' => $scored['task']['title'],
                'score' => $scored['score'],
                'assigned_priority' => $finalPriority,
                'priority_name' => $scored['task']['priority_name']
            ]);
        }
        
        // Sort back to original order
        ksort($assignedTasks);
        
        Log::info('Final Priority Distribution', $priorityCounts);
        
        return array_values($assignedTasks);
    }

    protected function getPriorityName($priorityId)
    {
        $names = [
            1 => 'High',
            2 => 'Normal',
            3 => 'Low'
        ];
        
        return $names[$priorityId] ?? 'Normal';
    }

    protected function calculateMainTaskPriority($projectData)
    {
        $score = 0;
        
        // Factor 1: Time urgency (45% weight)
        $startDate = Carbon::parse($projectData['start_date'] ?? now());
        $endDate = Carbon::parse($projectData['end_date'] ?? now()->addDays(30));
        $daysUntilStart = now()->diffInDays($startDate, false);
        $totalDays = $endDate->diffInDays($startDate);
        
        if ($daysUntilStart <= 2) {
            $score += 45; // Starts very soon
        } elseif ($daysUntilStart <= 7) {
            $score += 30; // Starts soon
        } elseif ($daysUntilStart <= 14) {
            $score += 15; // Starts in 2 weeks
        }
        
        // Factor 2: Timeline tightness (35% weight)
        if ($totalDays <= 7) {
            $score += 35; // Very tight timeline
        } elseif ($totalDays <= 14) {
            $score += 25; // Tight timeline
        } elseif ($totalDays <= 30) {
            $score += 15; // Moderate timeline
        }
        
        // Factor 3: Subtask count (20% weight)
        $subtaskCount = $projectData['ai_subtask_count'] ?? 5;
        
        if ($subtaskCount > 15) {
            $score += 20; // Many subtasks
        } elseif ($subtaskCount > 10) {
            $score += 15; // Moderate subtasks
        } elseif ($subtaskCount > 5) {
            $score += 5; // Few subtasks
        }
        
        // Convert score to priority (1=high, 2=normal, 3=low)
        if ($score >= 80) {
            return 1; // High priority
        } elseif ($score >= 50) {
            return 2; // Normal priority
        } else {
            return 3; // Low priority
        }
    }

    protected function adjustPriorityForFactors($task, $basePriority)
    {
        // Kept for backwards compatibility but no longer called from applyEnhancedPrioritization.
        // Priority distribution is handled purely by calculateTaskPriorityScore() + quota slots.
        return $basePriority;
    }

    protected function calculateRiskScore($riskLevel)
    {
        $riskLevel = strtolower($riskLevel);
        $riskScores = [
            'very low' => 5,
            'low' => 10,
            'medium' => 15,
            'high' => 20,
            'very high' => 25
        ];
        
        return $riskScores[$riskLevel] ?? 15; // Default to medium
    }

    protected function countDependantTasks($task, $totalTasks)
    {
        // This is a simplified version - in a real implementation,
        // you'd need the full task list to count dependants
        return count($task['dependencies'] ?? []);
    }

    // Calculate priority distribution based on task count ranges
    protected function calculatePriorityDistribution($totalTasks, $mainTaskPriority)
    {
        // For small number of tasks (like 3), adjust distribution
        if ($totalTasks <= 3) {
            // For very few tasks, use different logic
            if ($totalTasks == 1) {
                return ['high' => 1, 'normal' => 0, 'low' => 0];
            } elseif ($totalTasks == 2) {
                if ($mainTaskPriority == 1) {
                    return ['high' => 0.8, 'normal' => 0.2, 'low' => 0];
                } else {
                    return ['high' => 0.5, 'normal' => 0.5, 'low' => 0];
                }
            } elseif ($totalTasks == 3) {
                if ($mainTaskPriority == 1) {
                    return ['high' => 0.67, 'normal' => 0.33, 'low' => 0];
                } elseif ($mainTaskPriority == 3) {
                    return ['high' => 0.33, 'normal' => 0.33, 'low' => 0.34];
                } else {
                    return ['high' => 0.33, 'normal' => 0.67, 'low' => 0];
                }
            }
        }
        
        // Default distributions for larger task counts
        $ranges = [];
        
        if ($totalTasks <= 5) {
            $ranges['high'] = 0.6;  // 60% high
            $ranges['normal'] = 0.3; // 30% normal
            $ranges['low'] = 0.1;   // 10% low
        } elseif ($totalTasks <= 15) {
            $ranges['high'] = 0.3;  // 30% high
            $ranges['normal'] = 0.5; // 50% normal
            $ranges['low'] = 0.2;   // 20% low
        } else {
            $ranges['high'] = 0.2;  // 20% high
            $ranges['normal'] = 0.6; // 60% normal
            $ranges['low'] = 0.2;   // 20% low
        }
        
        // Adjust based on main task priority
        if ($mainTaskPriority == 1) { // High priority main task
            $ranges['high'] = min(0.9, $ranges['high'] + 0.2);
            $ranges['low'] = max(0, $ranges['low'] - 0.2);
        } elseif ($mainTaskPriority == 3) { // Low priority main task
            $ranges['high'] = max(0.1, $ranges['high'] - 0.2);
            $ranges['low'] = min(0.5, $ranges['low'] + 0.2);
        }
        
        // Normalize to ensure sum = 1
        $total = array_sum($ranges);
        foreach ($ranges as &$range) {
            $range = $range / $total;
        }
        
        return $ranges;
    }

    // Calculate task priority score considering multiple factors
    protected function calculateTaskPriorityScore($task, $position, $totalTasks)
    {
        $score = 0;
        
        // Critical path gets highest weight (35%)
        if ($task['is_critical_path'] ?? false) {
            $score += 35;
        }
        
        // Dependencies (25%)
        $dependencyCount = count($task['dependencies'] ?? []);
        $score += min($dependencyCount * 8, 25);
        
        // Resource intensity (20%)
        $resourceIntensity = $task['resource_intensity'] ?? 2;
        $score += min($resourceIntensity * 4, 20); // 1*4=4 … 5*4=20
        
        // Risk level (15%)
        $riskScore = $this->calculateRiskScore($task['risk_level'] ?? 'medium');
        $score += $riskScore;
        
        // Position in order (5%)
        $positionFactor = (($totalTasks - $position) / $totalTasks) * 5;
        $score += $positionFactor;
        
        // Cap at 100
        return min($score, 100);
    }

    /**
     * Assign responsible persons with workload balance
     * Enforces a daily session cap of 8h per user to distribute tasks realistically
     */
    public function assignResponsibleWithWorkloadBalance($tasks, $algorithmMode = 'divide_conquer', $crossAlgorithmHours = [])
    {
        if (empty($tasks)) {
            return $tasks;
        }

        // Dynamic: Get all eligible users (exclude admin/CORE users)
        // This adapts to any role configuration — no hardcoded role_type required
        $allEligibleUsers = User::whereHas('roles', function ($q) {
            $q->whereNotIn('role_type', ['CORE']);
        })->get();

        if ($allEligibleUsers->isEmpty()) {
            // Preserve form-selected assignments (via Target Role dropdown)
            foreach ($tasks as &$task) {
                if (!empty($task['responsible_id'])) {
                    $roleName = $task['target_role_name'] ?? 'selected role';
                    $task['assignment_reason'] = "Assigned via Target Role ({$roleName})";
                    continue;
                }
                $task['responsible_id'] = null;
                $task['responsible_name'] = 'Unassigned';
                $task['assignment_reason'] = 'No eligible users available';
            }
            return $tasks;
        }

        // Calculate existing DB workloads (from active tickets)
        $userWorkloads = $this->calculateCurrentWorkloads($allEligibleUsers);

        // Step 2: Get pending AI previews workload for this session
        $pendingWorkloads = $this->getPendingAiPreviewsWorkload($allEligibleUsers);

        // Combine current and pending workloads
        foreach ($userWorkloads as $userId => &$workload) {
            $workload += $pendingWorkloads[$userId] ?? 0;
        }

        // Precompute total historical ticket count per user (experience proxy)
        $userExperience = [];
        foreach ($allEligibleUsers as $user) {
            $userExperience[$user->id] = Ticket::where('responsible_id', $user->id)->count();
        }
        
        // Step 3: Sort tasks by priority and complexity
        usort($tasks, function($a, $b) {
            // Sort by priority (1=high, 2=normal, 3=low)
            $priorityA = $a['priority_id'] ?? 2;
            $priorityB = $b['priority_id'] ?? 2;
            
            if ($priorityA != $priorityB) {
                return $priorityA <=> $priorityB;
            }
            
            // Then by resource intensity (higher first)
            $intensityA = $a['resource_intensity'] ?? 2;
            $intensityB = $b['resource_intensity'] ?? 2;
            
            return $intensityB <=> $intensityA;
        });
        
        // Step 4: Assign responsible for each task
        foreach ($tasks as &$task) {
            $taskPriority   = $task['priority_id'] ?? 2;
            $estimatedHours = floatval($task['estimated_hours'] ?? 1);
            $skillRequirements = $task['skill_requirements'] ?? [];
            $isCritical = $task['is_critical_path'] ?? false;
            
            // If user was already chosen via Target Role dropdown, keep that exact user
            if (!empty($task['responsible_id'])) {
                $userId = $task['responsible_id'];
                $userWorkloads[$userId] = ($userWorkloads[$userId] ?? 0) + $estimatedHours;
                $roleName = $task['target_role_name'] ?? 'selected role';
                $task['assignment_reason'] = "Assigned via Target Role ({$roleName})";
                continue;
            }

            // Dynamic: Filter user pool by target role if specified
            $targetRoleName = $task['target_role_name'] ?? null;
            if ($targetRoleName) {
                // Narrow pool to users who have the specified role
                $taskEligibleUsers = $allEligibleUsers->filter(function ($user) use ($targetRoleName) {
                    return $user->hasRole($targetRoleName);
                });
            } else {
                // No target role specified — use all eligible users
                $taskEligibleUsers = $allEligibleUsers;
            }

            // Fallback: if no users match the target role, use the full pool
            if ($taskEligibleUsers->isEmpty()) {
                $taskEligibleUsers = $allEligibleUsers;
                $task['assignment_note'] = $targetRoleName
                    ? "No users with role '{$targetRoleName}' found, assigned from general pool"
                    : null;
            }

            // Select appropriate user from the filtered pool
            $assignmentResult = $this->selectResponsibleUser(
                $taskEligibleUsers,
                $userWorkloads,
                $taskPriority,
                $estimatedHours,
                $skillRequirements,
                $isCritical,
                $userExperience
            );
            
            $selectedUser = $assignmentResult['user'] ?? null;
            
            // Save candidates (other eligible users)
            $filteredCandidates = array_filter($assignmentResult['candidates'] ?? [], function($c) use ($selectedUser) {
                return !$selectedUser || $c['user']->id !== $selectedUser->id;
            });
            $selectedUserScore = $assignmentResult['user_score'] ?? 0;
            $task['selected_user_score'] = $selectedUserScore;
            $task['candidates'] = array_map(function($c) use ($selectedUserScore) {
                $reason = $c['reason'] ?? ("Score: " . round($c['score'], 1) . ". Workload: " . round($c['workload'], 1) . "h.");
                if ($c['score'] < $selectedUserScore) {
                    $diff = $selectedUserScore - $c['score'];
                    $reason .= ". Lower score by " . round($diff, 1) . " pts";
                }
                return [
                    'id' => $c['user']->id,
                    'name' => $c['user']->name,
                    'score' => $c['score'],
                    'workload' => $c['workload'],
                    'reason' => $reason
                ];
            }, array_values($filteredCandidates));

            if ($selectedUser) {
                $task['responsible_id'] = $selectedUser->id;
                $task['responsible_name'] = $selectedUser->name;
                
                // Update workload
                $userWorkloads[$selectedUser->id] += $estimatedHours;
                
                // Also get department if available
                if (isset($selectedUser->department)) {
                    $task['department'] = $selectedUser->department->name;
                }
            } else {
                $task['responsible_id'] = null;
                $task['responsible_name'] = 'Unassigned';
            }
        }

        return $tasks;
    }

    /**
     * Retroactively update reason texts with cross-algorithm hours.
     * Used in comparative mode to show the true combined workload
     * (e.g. D&C tasks updated with Greedy hours, and vice versa).
     */
    public function updateReasonTextsWithCrossAlgorithmHours(array $tasks, array $crossHoursPerUser, int $dailySessionLimit = 8): array
    {
        foreach ($tasks as &$task) {
            $uid = $task['responsible_id'] ?? null;
            if (!$uid) continue;

            $extraHours = floatval($crossHoursPerUser[$uid] ?? 0);
            if ($extraHours <= 0) continue;

            $newAfterLoad  = ($task['_after_load_raw'] ?? 0) + $extraHours;
            $newRemaining  = max(0, $dailySessionLimit - (($task['_session_hours_after'] ?? 0) + $extraHours));

            $task['assignment_reason'] = sprintf(
                '%s. Task priority: %s. Workload after assignment: %.1fh (capacity remaining today: %.1fh).',
                $task['_reason_detail']  ?? '',
                $task['_priority_label'] ?? 'Normal',
                $newAfterLoad,
                $newRemaining
            );

            // Keep raw values up-to-date in case this is called again
            $task['_after_load_raw']      = $newAfterLoad;
            $task['_session_hours_after'] = ($task['_session_hours_after'] ?? 0) + $extraHours;
        }
        unset($task);

        return $tasks;
    }

    // Calculate current workload from existing tickets
    public function calculateCurrentWorkloads($users)
    {
        $workloads = [];
        
        foreach ($users as $user) {
            // Get active tickets (not completed/cancelled) across ALL projects
            $activeTickets = Ticket::where('responsible_id', $user->id)
                ->whereNotIn('status_id', function($query) {
                    $query->select('id')
                        ->from('ticket_statuses')
                        ->whereIn('type', ['completed', 'cancelled']);
                })
                ->with('status')
                ->get();

            // Sum estimation hours (correct DB column name is 'estimation', not 'estimated_hours')
            $totalHours = $activeTickets->sum('estimation');

            // Add execution_time for active (in-progress) tickets
            $inProgressHours = $activeTickets
                ->filter(fn($t) => optional($t->status)->type === 'active')
                ->sum(fn($t) => (float) ($t->execution_time ?? 0));

            $workloads[$user->id] = $totalHours + $inProgressHours;
        }
        
        return $workloads;
    }

    // Get workload from pending  previews in current session
    protected function getPendingAiPreviewsWorkload($users)
    {
        $pendingWorkloads = [];
        
        // Get all  previews for current session that haven't been converted to tickets
        $pendingPreviews = AiTaskPreview::where('session_id', session()->getId())
            ->where('user_id', auth()->id())
            ->get();
        
        foreach ($pendingPreviews as $preview) {
            $tasks = $preview->generated_tasks ?? [];
            
            foreach ($tasks as $task) {
                if (isset($task['responsible_id']) && $task['responsible_id']) {
                    $userId = $task['responsible_id'];
                    $estimatedHours = floatval($task['estimated_hours'] ?? $task['estimation'] ?? 0);
                    
                    if (isset($pendingWorkloads[$userId])) {
                        $pendingWorkloads[$userId] += $estimatedHours;
                    } else {
                        $pendingWorkloads[$userId] = $estimatedHours;
                    }
                }
            }
        }
        
        return $pendingWorkloads;
    }

    // Select responsible user based on multiple factors
    public function selectResponsibleUser($users, $userWorkloads, $priority, $estimatedHours, $skillRequirements, $isCritical, $userExperience = [])
    {
        $eligibleUsers = [];
        $allCandidates = [];

        foreach ($users as $user) {
            $userId = $user->id;
            $currentWorkload = $userWorkloads[$userId] ?? 0;
            $experienceCount = $userExperience[$userId] ?? 0;

            // Calculate suitability score
            $score = $this->calculateUserSuitabilityScore(
                $user,
                $currentWorkload,
                $priority,
                $estimatedHours,
                $skillRequirements,
                $isCritical,
                $experienceCount
            );
            
            // Generate descriptive reason
            $reasons = [];
            $userMaxWorkload = $this->getUserMaxWorkload($user);
            
            if ($currentWorkload >= $userMaxWorkload) {
                $reasons[] = "At/Over capacity (" . round($currentWorkload, 1) . "h/" . $userMaxWorkload . "h)";
            } elseif ($currentWorkload > $userMaxWorkload * 0.7) {
                $reasons[] = "High workload (" . round($currentWorkload, 1) . "h)";
            }
            
            if ($priority == 1 && $experienceCount <= 5) {
                $reasons[] = "Low experience for high priority task";
            }
            
            if (!empty($skillRequirements)) {
                $skillMatch = $this->calculateSkillMatch($user, $skillRequirements);
                if ($skillMatch < 100) {
                    $reasons[] = "Skill match: " . round($skillMatch) . "%";
                }
            }
            
            if ($estimatedHours > 4 && $currentWorkload > 20) {
                $reasons[] = "Busy user for long task";
            }
            
            $reasonStr = implode(". ", $reasons);
            if (empty($reasonStr)) {
                $reasonStr = "Good match. Workload: " . round($currentWorkload, 1) . "h";
            } else {
                $reasonStr .= ". Score: " . round($score, 1);
            }
            
            $allCandidates[] = [
                'user' => $user,
                'score' => $score,
                'workload' => $currentWorkload,
                'reason' => $reasonStr
            ];

            if ($score > 0) {
                $eligibleUsers[] = [
                    'user' => $user,
                    'score' => $score,
                    'workload' => $currentWorkload,
                    'reason' => $reasonStr
                ];
            }
        }
        
        // Sort all candidates by score (descending)
        usort($allCandidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // If no eligible users, use least busy user
        if (empty($eligibleUsers)) {
            $leastBusy = $this->selectLeastBusyUser($users, $userWorkloads);
            $leastBusyScore = 0;
            if ($leastBusy) {
                foreach ($allCandidates as $c) {
                    if ($c['user']->id === $leastBusy->id) {
                        $leastBusyScore = $c['score'];
                        break;
                    }
                }
            }
            return [
                'user' => $leastBusy,
                'user_score' => $leastBusyScore,
                'candidates' => array_map(function($c) use ($leastBusy) {
                    if ($leastBusy && $c['user']->id === $leastBusy->id) {
                        $c['reason'] = "Selected as least busy fallback. " . $c['reason'];
                    }
                    return $c;
                }, $allCandidates)
            ];
        }
        
        // Sort by score (descending)
        usort($eligibleUsers, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return [
            'user' => $eligibleUsers[0]['user'],
            'user_score' => $eligibleUsers[0]['score'] ?? 0,
            'candidates' => $allCandidates
        ];
    }

    public function getRecommendedUserForRole($roleName, $priority = 2, $estimatedHours = 8, $workloads = null)
    {
        $users = \App\Models\User::role($roleName)->get();
        if ($users->isEmpty()) {
            return null;
        }
        
        if ($workloads === null) {
            $workloads = $this->calculateCurrentWorkloads($users);
        }
        
        $userExperience = [];
        foreach ($users as $user) {
            $userExperience[$user->id] = \App\Models\Ticket::where('responsible_id', $user->id)->count();
        }
        
        return $this->selectResponsibleUser($users, $workloads, $priority, $estimatedHours, [], false, $userExperience);
    }

    // Calculate user suitability score
    protected function calculateUserSuitabilityScore($user, $workload, $priority, $estimatedHours, $skillRequirements, $isCritical, $experienceCount = 0)
    {
        $score = 100; // Base score

        // 1. Workload factor (40% weight)
        $userMaxWorkload = $this->getUserMaxWorkload($user);
        $workloadPenalty = min($workload / $userMaxWorkload * 100, 100); // % of capacity used
        $score -= ($workloadPenalty * 0.4); // Up to -40 points

        // Extra penalty for being over capacity — differentiates 41h vs 314h
        $overCapacity = max(0, $workload - $userMaxWorkload);
        if ($overCapacity > 0) {
            $score -= min(40, ($overCapacity / $userMaxWorkload) * 40); // Up to -40 more points
        }

        // 2. Experience factor — high priority tasks need users with a track record
        if ($priority == 1) {
            if ($experienceCount === 0) {
                $score -= 50; // Never assigned a ticket — last resort for high priority
            } elseif ($experienceCount <= 5) {
                $score += 5;
            } elseif ($experienceCount <= 15) {
                $score += 15;
            } else {
                $score += 20;
            }
        }

        // 3. Priority matching (capacity-based)
        // Higher threshold (90%) so experienced users don't fall out of range too quickly.
        $capacityRatio = $userMaxWorkload > 0 ? $workload / $userMaxWorkload : 0;

        if ($priority == 1) {
            if ($capacityRatio <= 0.5) {
                $score += ($isCritical ? 25 : 20); // Plenty of capacity
            } elseif ($capacityRatio <= 0.8) {
                $score += ($isCritical ? 10 : 5);  // Moderate — still good
            } elseif ($capacityRatio <= 0.9) {
                // Getting full — no bonus, no penalty yet
            } else {
                $score -= ($isCritical ? 20 : 10); // >90% capacity — penalize
            }
        } elseif ($priority == 3) {
            // Low priority: new users and near-idle users are fine
            if ($experienceCount === 0 || $workload >= $userMaxWorkload * 0.8) {
                $score += 10;
            }
        }
        
        // 3. Skill matching (20% weight)
        if (!empty($skillRequirements)) {
            $skillMatch = $this->calculateSkillMatch($user, $skillRequirements);
            $score += ($skillMatch * 0.2);
        }
        
        // 4. Estimated hours consideration (10% weight)
        if ($estimatedHours > 4 && $workload > 20) {
            $score -= 10; // Don't assign long tasks to busy users
        }
        
        return max(0, $score); // Ensure non-negative score
    }

    // Get user's maximum recommended workload
    protected function getUserMaxWorkload($user)
    {
        // Default max workload is 40 hours
        $baseWorkload = 40;
        
        // Adjust based on user role/experience
        if ($user->hasRole('senior') || $user->experience_years >= 3) {
            $baseWorkload = 50; // Senior users can handle more
        } elseif ($user->hasRole('junior') || $user->experience_years <= 1) {
            $baseWorkload = 30; // Junior users less workload
        }
        
        // Adjust based on availability
        if (isset($user->availability) && $user->availability < 1.0) {
            $baseWorkload *= $user->availability;
        }
        
        return $baseWorkload;
    }

    // Calculate skill match percentage
    protected function calculateSkillMatch($user, $requiredSkills)
    {
        if (empty($requiredSkills)) {
            return 50; // Default 50% if no skills specified
        }
        
        // This assumes you have a skills relationship on User model
        $userSkills = [];
        
        if (method_exists($user, 'skills')) {
            $userSkills = $user->skills()->pluck('name')->toArray();
        } elseif (isset($user->skills)) {
            $userSkills = is_array($user->skills) ? $user->skills : json_decode($user->skills, true) ?? [];
        }
        
        // Convert to lowercase for comparison
        $userSkills = array_map('strtolower', $userSkills);
        $requiredSkills = array_map('strtolower', $requiredSkills);
        
        // Calculate match percentage
        $matchedSkills = array_intersect($requiredSkills, $userSkills);
        $matchPercentage = (count($matchedSkills) / count($requiredSkills)) * 100;
        
        return $matchPercentage;
    }

    // Fallback: Select least busy user
    protected function selectLeastBusyUser($users, $userWorkloads)
    {
        $leastBusyUser = null;
        $minWorkload = PHP_INT_MAX;
        
        foreach ($users as $user) {
            $workload = $userWorkloads[$user->id] ?? 0;
            if ($workload < $minWorkload) {
                $minWorkload = $workload;
                $leastBusyUser = $user;
            }
        }
        
        return $leastBusyUser;
    }

    /**
     * Calculate subtask start date with algorithm consideration
     */
    public function calculateSubtaskStartDate($project, $index, $algorithmMode = 'divide_conquer')
    {
        try {
            $projectStart = $project->start_date ?? now();
            
            if ($algorithmMode === 'greedy') {
                // Greedy: Start immediately with minimal offset
                return Carbon::parse($projectStart)->addHours($index * 2);
            } else {
                // Divide & Conquer: Staggered start based on dependencies
                return Carbon::parse($projectStart)->addDays($index);
            }
        } catch (\Exception $e) {
            Log::error('Error calculating subtask start date: ' . $e->getMessage());
            return now()->addDays($index);
        }
    }

     /**
     * Calculate subtask due date with algorithm consideration
     */
    public function calculateSubtaskDueDate($project, $subtaskData, $index, $algorithmMode = 'divide_conquer')
    {
        try {
            $estimation = floatval($subtaskData['estimated_hours'] ?? $subtaskData['estimation'] ?? 8);
            $projectStart = $project->start_date ?? now();
            
            if ($algorithmMode === 'greedy') {
                // Greedy: Aggressive due dates
                $bufferMultiplier = $estimation <= 4 ? 1.2 : 1.5;
                
                return Carbon::parse($projectStart)
                    ->addHours($index * 4)
                    ->addHours($estimation * $bufferMultiplier);
            } else {
                // Divide & Conquer: More conservative scheduling
                $bufferMultiplier = $estimation <= 8 ? 1.5 : 2.0;
                
                return Carbon::parse($projectStart)
                    ->addDays($index)
                    ->addHours($estimation * $bufferMultiplier);
            }
        } catch (\Exception $e) {
            Log::error('Error calculating subtask due date: ' . $e->getMessage());
            return now()->addDays($index + 7);
        }
    }

    public function calculateMainTaskPriorityFromAiPreview($aiPreview)
    {
        // Use complexity score to determine priority
        $complexityData = $aiPreview->complexity_data ?? [];
        $complexityScore = $complexityData['score'] ?? 50;
        
        if ($complexityScore >= 70) {
            return 1; // High priority
        } elseif ($complexityScore >= 40) {
            return 2; // Normal priority
        } else {
            return 3; // Low priority
        }
    }

    public function createDependenciesBetweenSubtasks($createdSubtasks, $generatedTasks)
    {
        // Map subtask title => created subtask ID
        $titleToId = collect($createdSubtasks)
            ->mapWithKeys(fn($subtask, $index) => [
                $generatedTasks[$index]['title'] ?? $subtask->name => $subtask->id
            ])
            ->toArray();

        foreach ($createdSubtasks as $index => $subtask) {
            $relations = [];

            $dependencies = $generatedTasks[$index]['dependencies'] ?? []; // user-defined titles

            foreach ($dependencies as $depTitle) {
                if (isset($titleToId[$depTitle])) {
                    $relations[] = [
                        'ticket_id' => $subtask->id,
                        'relation_id' => $titleToId[$depTitle],
                        'type' => 'depends_on',
                        'sort' => 1, // or whatever sort logic you want
                    ];
                }
            }

            // Parent relationship
            $relations[] = [
                'ticket_id' => $subtask->id,
                'relation_id' => $subtask->parent_ticket_id,
                'type' => 'parent_of',
                'sort' => 1
            ];

            // Save to DB
            foreach ($relations as $relationData) {
                \App\Models\TicketRelation::create($relationData);
            }
        }
    }

    /**
     * Calculate priority for Greedy Algorithm
     * Greedy prioritizes tasks with highest immediate value/benefit
    */
    public function calculateGreedyPriority($aiPreview, $taskData){
        try {
            // Get task UUID and subtasks
            $taskUuid = array_key_first($aiPreview);
            $subtasksArray = $aiPreview[$taskUuid] ?? [];
            
            if (empty($subtasksArray)) {
                return 2; // Default medium priority
            }

            // Greedy algorithm factors:
            // 1. Quick wins (tasks with shortest duration)
            // 2. High impact tasks
            // 3. Tasks with immediate dependencies
            // 4. Resource efficiency

            $priorityScore = 0;
            $factors = [];

            // Factor 1: Average duration of subtasks (shorter = higher priority in greedy)
            $totalHours = 0;
            $shortTaskCount = 0;
            
            foreach ($subtasksArray as $subtask) {
                $hours = floatval($subtask['estimated_hours'] ?? $subtask['estimation'] ?? 0);
                $totalHours += $hours;
                
                if ($hours <= 4) { // Tasks under 4 hours are "quick wins"
                    $shortTaskCount++;
                }
            }
            
            $avgHours = count($subtasksArray) > 0 ? $totalHours / count($subtasksArray) : 0;
            
            // Shorter average = higher priority (1-10 scale, inverted)
            $durationFactor = $avgHours > 0 ? min(10, max(1, 20 / $avgHours)) : 5;
            $factors['duration'] = $durationFactor;
            
            // Factor 2: Percentage of quick wins
            $quickWinFactor = count($subtasksArray) > 0 
                ? ($shortTaskCount / count($subtasksArray)) * 10 
                : 5;
            $factors['quick_wins'] = $quickWinFactor;

            // Factor 3: Critical path tasks
            $criticalCount = 0;
            foreach ($subtasksArray as $subtask) {
                if (($subtask['is_critical_path'] ?? false) === true) {
                    $criticalCount++;
                }
            }
            
            $criticalFactor = count($subtasksArray) > 0 
                ? ($criticalCount / count($subtasksArray)) * 10 
                : 5;
            $factors['critical_path'] = $criticalFactor;

            // Factor 4: Parallelizability (greedy loves parallel tasks)
            $parallelCount = 0;
            foreach ($subtasksArray as $subtask) {
                if (($subtask['parallelizable'] ?? true) === true) {
                    $parallelCount++;
                }
            }
            
            $parallelFactor = count($subtasksArray) > 0 
                ? ($parallelCount / count($subtasksArray)) * 10 
                : 5;
            $factors['parallelizable'] = $parallelFactor;

            // Factor 5: Resource intensity (lower = better for greedy)
            $totalResourceIntensity = 0;
            foreach ($subtasksArray as $subtask) {
                $totalResourceIntensity += $subtask['resource_intensity'] ?? 2;
            }
            
            $avgResourceIntensity = count($subtasksArray) > 0 
                ? $totalResourceIntensity / count($subtasksArray) 
                : 2;
            
            // Lower resource intensity = higher priority (1-10 scale, inverted)
            $resourceFactor = max(1, min(10, 6 - ($avgResourceIntensity - 1)));
            $factors['resource_efficiency'] = $resourceFactor;

            // Calculate weighted priority score
            $weights = [
                'duration' => 0.25,          // Most important for greedy
                'quick_wins' => 0.25,        // Quick wins are key
                'critical_path' => 0.15,     // Important but not primary
                'parallelizable' => 0.20,    // Parallel tasks are efficient
                'resource_efficiency' => 0.15
            ];
            
            foreach ($factors as $factor => $value) {
                $priorityScore += $value * ($weights[$factor] ?? 0.1);
            }

            // Convert to priority ID (1-5 scale, where 1 is highest)
            $priorityId = $this->convertGreedyScoreToPriorityId($priorityScore);
            
            Log::debug('Greedy priority calculation', [
                'priority_score' => $priorityScore,
                'priority_id' => $priorityId,
                'factors' => $factors,
                'weights' => $weights,
                'task_name' => $taskData['main_task_name'] ?? 'Unknown'
            ]);

            return $priorityId;

        } catch (\Exception $e) {
            Log::error('Error in calculateGreedyPriority: ' . $e->getMessage());
            return 2; // Default to medium priority on error
        }
    }


    /**
     * Create dependencies for Greedy Algorithm
     * Greedy minimizes dependencies to maximize parallel execution
     */
    public function createGreedyDependencies($subtasks, $subtasksArray)
    {
        try {
            if (count($subtasks) <= 1) {
                return; // No dependencies needed for single subtask
            }

            $createdDependencies = [];
            
            // Greedy approach: Only create essential dependencies
            // 1. Identify critical path tasks
            // 2. Create minimal sequential dependencies for critical path
            // 3. Allow non-critical tasks to run in parallel
            
            $criticalSubtasks = [];
            $nonCriticalSubtasks = [];
            
            // Separate critical and non-critical tasks
            foreach ($subtasks as $index => $subtask) {
                $subtaskData = $subtasksArray[$index] ?? [];
                
                if (($subtaskData['is_critical_path'] ?? false) === true) {
                    $criticalSubtasks[] = [
                        'subtask' => $subtask,
                        'data' => $subtaskData,
                        'index' => $index
                    ];
                } else {
                    $nonCriticalSubtasks[] = [
                        'subtask' => $subtask,
                        'data' => $subtaskData,
                        'index' => $index
                    ];
                }
            }
            
            // Create sequential dependencies for critical path (if any)
            if (count($criticalSubtasks) > 1) {
                for ($i = 0; $i < count($criticalSubtasks) - 1; $i++) {
                    $current = $criticalSubtasks[$i]['subtask'];
                    $next = $criticalSubtasks[$i + 1]['subtask'];
                    
                    // Check if dependency already exists in subtask data
                    $nextData = $criticalSubtasks[$i + 1]['data'];
                    $hasExistingDependency = in_array(
                        $criticalSubtasks[$i]['index'],
                        $nextData['dependencies'] ?? []
                    );
                    
                    if (!$hasExistingDependency) {
                        // Create dependency in ticket_relations table
                        \App\Models\TicketRelation::create([
                            'ticket_id' => $next->id,
                            'relation_id' => $current->id,
                            'type' => 'dependency', // or 'depends_on'
                            'sort' => $i,
                            'metadata' => json_encode([
                                'dependency_type' => 'finish-to-start',
                                'algorithm' => 'greedy',
                                'is_critical_path' => true,
                                'lag' => 0,
                                'created_by_ai' => true
                            ])
                        ]);
                        
                        $createdDependencies[] = [
                            'from' => $current->id,
                            'to' => $next->id,
                            'type' => 'critical_path'
                        ];
                    }
                }
            }
            
            // For non-critical tasks, only create dependencies if explicitly defined
            foreach ($nonCriticalSubtasks as $nonCritical) {
                $dependencies = $nonCritical['data']['dependencies'] ?? [];
                
                foreach ($dependencies as $depIndex) {
                    if (isset($subtasks[$depIndex])) {
                        $dependsOn = $subtasks[$depIndex];
                        
                        // Check if dependency already exists in ticket_relations
                        $exists = \App\Models\TicketRelation::where('ticket_id', $nonCritical['subtask']->id)
                            ->where('relation_id', $dependsOn->id)
                            ->where('type', 'dependency')
                            ->exists();
                        
                        if (!$exists) {
                            \App\Models\TicketRelation::create([
                                'ticket_id' => $nonCritical['subtask']->id,
                                'relation_id' => $dependsOn->id,
                                'type' => 'dependency',
                                'sort' => count($createdDependencies),
                                'metadata' => json_encode([
                                    'dependency_type' => 'finish-to-start',
                                    'algorithm' => 'greedy',
                                    'is_critical_path' => false,
                                    'lag' => 0,
                                    'created_by_ai' => true,
                                    'explicit_dependency' => true
                                ])
                            ]);
                            
                            $createdDependencies[] = [
                                'from' => $dependsOn->id,
                                'to' => $nonCritical['subtask']->id,
                                'type' => 'explicit'
                            ];
                        }
                    }
                }
            }
            
            Log::info('Created greedy dependencies', [
                'total_subtasks' => count($subtasks),
                'critical_tasks' => count($criticalSubtasks),
                'non_critical_tasks' => count($nonCriticalSubtasks),
                'dependencies_created' => count($createdDependencies),
                'dependency_details' => $createdDependencies
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating greedy dependencies: ' . $e->getMessage());
            // Fallback to standard dependency creation
            $this->createDependenciesBetweenSubtasks($subtasks, $subtasksArray);
        }
    }

       /**
     * Apply greedy algorithm prioritization to tasks
     * Greedy prioritizes: quick wins, high value/effort ratio, minimal dependencies
     */
    public function applyGreedyPrioritization($subtasks, $projectData)
    {
        try {
            if (empty($subtasks)) {
                return [];
            }

            $prioritizedTasks = [];
            
            // Calculate greedy scores for each task
            foreach ($subtasks as $task) {
                $task['greedy_score'] = $this->calculateGreedyTaskScore($task);
                $prioritizedTasks[] = $task;
            }

            // Sort by greedy score (descending - higher score = executed first)
            usort($prioritizedTasks, function($a, $b) {
                return ($b['greedy_score'] ?? 0) <=> ($a['greedy_score'] ?? 0);
            });

            // Assign priority by relative rank, not absolute score threshold.
            // Using absolute thresholds causes all quick-win tasks to be "High"
            // since Greedy naturally scores them high for scheduling purposes.
            $total = count($prioritizedTasks);
            $highCutoff   = (int) ceil($total * 0.30); // top 30% → High
            $normalCutoff = (int) ceil($total * 0.70); // next 40% → Normal, rest → Low

            foreach ($prioritizedTasks as $index => &$task) {
                if ($index < $highCutoff) {
                    $task['priority_id']    = 1;
                    $task['priority_range'] = 'high';
                } elseif ($index < $normalCutoff) {
                    $task['priority_id']    = 2;
                    $task['priority_range'] = 'normal';
                } else {
                    $task['priority_id']    = 3;
                    $task['priority_range'] = 'low';
                }
            }
            unset($task);

            // Reassign order based on greedy priority
            foreach ($prioritizedTasks as $index => &$task) {
                $task['order'] = $index + 1;
                
                // Adjust dependencies based on new order
                if (!empty($task['dependencies'])) {
                    $task['dependencies'] = $this->adjustDependenciesForGreedy(
                        $task['dependencies'], 
                        $prioritizedTasks
                    );
                }
            }

            Log::debug('Greedy prioritization applied', [
                'total_tasks' => count($prioritizedTasks),
                'high_priority_count' => count(array_filter($prioritizedTasks, fn($t) => $t['priority_id'] == 1)),
                'average_greedy_score' => array_sum(array_column($prioritizedTasks, 'greedy_score')) / count($prioritizedTasks)
            ]);

            return $prioritizedTasks;

        } catch (\Exception $e) {
            Log::error('Error in applyGreedyPrioritization: ' . $e->getMessage());
            return $subtasks; // Return original on error
        }
    }

    /**
     * Calculate greedy score for a single task
     * Higher score = better for greedy algorithm
     */
    protected function calculateGreedyTaskScore($task)
    {
        $score = 0;
        $maxScore = 100;
        
        // 1. QUICK WIN FACTOR (0-30 points)
        // Shorter tasks get higher score
        $estimatedHours = floatval($task['estimated_hours'] ?? 8);
        $quickWinFactor = 0;
        
        if ($estimatedHours <= 1) {
            $quickWinFactor = 30; // Very quick
        } elseif ($estimatedHours <= 2) {
            $quickWinFactor = 25;
        } elseif ($estimatedHours <= 4) {
            $quickWinFactor = 20;
        } elseif ($estimatedHours <= 8) {
            $quickWinFactor = 10;
        } else {
            $quickWinFactor = 5; // Long tasks get minimal points
        }
        
        $score += $quickWinFactor;
        
        // 2. DEPENDENCY FACTOR (0-25 points)
        // Fewer dependencies = better for greedy
        $dependencyCount = count($task['dependencies'] ?? []);
        $dependencyFactor = max(0, 25 - ($dependencyCount * 5));
        $score += $dependencyFactor;
        
        // 3. PARALLELIZATION FACTOR (0-20 points)
        $parallelizable = $task['parallelizable'] ?? true;
        $parallelFactor = $parallelizable ? 20 : 5;
        $score += $parallelFactor;
        
        // 4. RESOURCE INTENSITY FACTOR (0-15 points)
        // Lower resource intensity = better for greedy
        $resourceIntensity = $task['resource_intensity'] ?? 2;
        $resourceFactor = max(0, 15 - ($resourceIntensity * 3));
        $score += $resourceFactor;
        
        // 5. RISK FACTOR (0-10 points)
        $riskLevel = strtolower($task['risk_level'] ?? 'medium');
        $riskFactor = match($riskLevel) {
            'low' => 10,
            'medium' => 5,
            'high' => 0,
            default => 5
        };
        $score += $riskFactor;
        
        // 6. Add any greedy-specific scores if available
        if (isset($task['quick_win_score'])) {
            $score += ($task['quick_win_score'] * 0.5); // Convert 1-10 to 0-5
        }
        
        if (isset($task['effort_to_value_ratio'])) {
            $ratio = floatval($task['effort_to_value_ratio']);
            // Lower ratio = less effort per unit of value = better for greedy
            $score += min(10, max(0, 10 - $ratio));
        }
        
        // Ensure score is within bounds
        return min($maxScore, max(0, $score));
    }

    /**
     * Adjust dependencies for greedy algorithm
     * Greedy tries to minimize and simplify dependencies
     */
    protected function adjustDependenciesForGreedy($dependencies, $allTasks)
    {
        if (empty($dependencies)) {
            return [];
        }
        
        $adjustedDeps = [];
        
        foreach ($dependencies as $depTitle) {
            // Find the dependent task
            $depTask = $this->findTaskByTitle($depTitle, $allTasks);
            
            if ($depTask) {
                // Check if this dependency is really necessary for greedy
                // Greedy prefers to remove non-critical dependencies
                $isCritical = $depTask['is_critical_path'] ?? false;
                $depHours = floatval($depTask['estimated_hours'] ?? 0);
                
                // Keep dependency only if:
                // 1. It's on critical path, OR
                // 2. It's very short (quick win), OR  
                // 3. It has high greedy score
                $depScore = $depTask['greedy_score'] ?? 0;
                
                if ($isCritical || $depHours <= 2 || $depScore >= 70) {
                    $adjustedDeps[] = $depTitle;
                }
                // Otherwise, greedy would skip this dependency
            }
        }
        
        return $adjustedDeps;
    }

    /**
     * Find task by title in array
     */
    protected function findTaskByTitle($title, $tasks)
    {
        foreach ($tasks as $task) {
            if (($task['title'] ?? '') === $title) {
                return $task;
            }
        }
        return null;
    }

    /**
     * Convert greedy score to priority ID (1-5)
     */
    protected function convertGreedyScoreToPriorityId($score)
    {
        // 3-level priority system:
        // 1 = High, 2 = Medium, 3 = Low
        
        if ($score >= 70) return 1;     // High priority
        if ($score >= 40) return 2;     // Medium priority
        return 3;                       // Low priority
    }

    /**
     * Get priority range label from score
     */
    protected function getPriorityRangeFromScore($score)
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'normal';
        return 'low';
    }

    /**
     * Apply greedy assignment strategy
     */
    protected function applyGreedyAssignment($tasks)
    {
        // Group tasks by estimated hours
        $quickTasks = [];    // <= 2 hours
        $mediumTasks = [];   // 2-4 hours  
        $longTasks = [];     // > 4 hours
        
        foreach ($tasks as $task) {
            $hours = floatval($task['estimated_hours'] ?? 0);
            
            if ($hours <= 2) {
                $quickTasks[] = $task;
            } elseif ($hours <= 4) {
                $mediumTasks[] = $task;
            } else {
                $longTasks[] = $task;
            }
        }
        
        // Greedy: Assign quick tasks first (quick wins)
        $assignedTasks = [];
        
        // Process quick tasks first
        foreach ($quickTasks as $task) {
            $assignedTasks[] = $this->assignGreedyTask($task, $assignedTasks);
        }
        
        // Then medium tasks
        foreach ($mediumTasks as $task) {
            $assignedTasks[] = $this->assignGreedyTask($task, $assignedTasks);
        }
        
        // Finally long tasks
        foreach ($longTasks as $task) {
            $assignedTasks[] = $this->assignGreedyTask($task, $assignedTasks);
        }
        
        return $assignedTasks;
    }

    /**
     * Assign a single task using greedy strategy
     */
    protected function assignGreedyTask($task, $alreadyAssignedTasks)
    {
        // For greedy, we want to:
        // 1. Assign to someone who can do it quickly
        // 2. Balance workload but prioritize speed
        
        // If user was already chosen via Target Role dropdown, keep that exact user
        if (!empty($task['responsible_id'])) {
            $roleName = $task['target_role_name'] ?? 'selected role';
            $task['assignment_reason'] = "Assigned via Target Role ({$roleName})";
            return $task;
        }

        // Dynamic: Get all eligible team members (exclude admin/CORE users)
        $allTeamMembers = User::whereHas('roles', function ($q) {
            $q->whereNotIn('role_type', ['CORE']);
        })->get();
        
        // Filter by target role if specified
        $targetRoleName = $task['target_role_name'] ?? null;
        if ($targetRoleName) {
            $teamMembers = $allTeamMembers->filter(function ($user) use ($targetRoleName) {
                return $user->hasRole($targetRoleName);
            });
            // Fallback to all eligible if no role-specific users found
            if ($teamMembers->isEmpty()) {
                $teamMembers = $allTeamMembers;
                $task['assignment_note'] = "No users with role '{$targetRoleName}' found, assigned from general pool";
            }
        } else {
            $teamMembers = $allTeamMembers;
        }
        
        if ($teamMembers->isEmpty()) {
            $task['responsible_id'] = null;
            $task['responsible_name'] = 'Unassigned';
            $task['assignment_reason'] = 'No eligible users available';
            return $task;
        }
        
        // Find best match using greedy heuristic:
        // Choose team member with least current workload who has required skills
        $bestMember = null;
        $bestScore = -1;
        
        foreach ($teamMembers as $member) {
            $score = $this->calculateMemberGreedyScore($member, $task, $alreadyAssignedTasks);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMember = $member;
            }
        }
        
        if ($bestMember) {
            $task['responsible_id'] = $bestMember['id'];
            $task['responsible_name'] = $bestMember['name'];
        } else {
            $task['responsible_id'] = null;
            $task['responsible_name'] = 'Unassigned';
        }
        
        return $task;
    }

    /**
     * Calculate greedy score for team member assignment
     */
    protected function calculateMemberGreedyScore($member, $task, $assignedTasks)
    {
        $score = 0;
        
        // 1. Skill match (most important)
        $requiredSkills = $task['skill_requirements'] ?? [];
        $memberSkills = $member['skills'] ?? [];
        
        $skillMatch = count(array_intersect($requiredSkills, $memberSkills));
        $score += $skillMatch * 20;
        
        // 2. Current workload (greedy prefers less busy people for quick tasks)
        $currentWorkload = $this->calculateMemberWorkload($member['id'], $assignedTasks);
        $workloadScore = max(0, 50 - ($currentWorkload * 5));
        $score += $workloadScore;
        
        // 3. Task length preference (quick tasks to faster people)
        $taskHours = floatval($task['estimated_hours'] ?? 0);
        if ($taskHours <= 2 && $member['is_quick'] ?? false) {
            $score += 30; // Bonus for quick workers on quick tasks
        }
        
        return $score;
    }

    /**
     * Calculate current workload for a team member
     */
    protected function calculateMemberWorkload($memberId, $assignedTasks)
    {
        $totalHours = 0;
        
        foreach ($assignedTasks as $task) {
            if (($task['responsible_id'] ?? null) == $memberId) {
                $totalHours += floatval($task['estimated_hours'] ?? 0);
            }
        }
        
        return $totalHours;
    }

    /**
     * Generate task metrics per subtask
     */
    public function generateTaskMetrics(array $complexityResult, float $availableHours, array $value, ?bool $parentCritical = null, string $algorithmMode = 'divide_conquer'): array
    {
        $level = $complexityResult['level_num'] ?? 3;
        $isCritical = $parentCritical ?? $this->isCriticalPath($level);
        $isParallel = $this->isParallelizable($level);

        $estimatedHours = $this->estimateHours($level, $availableHours, $isParallel, $isCritical, $algorithmMode);

        return [
            'title' => $value['subtask_title'] ?? '',
            'description' => $value['subtask_description'] ?? '',
            'dependencies' => $algorithmMode === 'divide_conquer' ? ($value['dependencies'] ?? []) : [],
            'estimated_hours' => $estimatedHours,
            'order' => $value['order'] ?? 1,
            'is_critical_path' => $isCritical,
            'resource_intensity' => $this->resourceIntensity($level),
            'risk_level' => $this->riskLevel($level),
            'parallelizable' => $isParallel,
            'priority_base_score' => $this->priorityScore($level),
            'quick_win_score' => $this->quickWinScore($estimatedHours, $level),
            'immediate_impact' => $this->immediateImpact($isCritical, $this->priorityScore($level), $level),
            'effort_to_value_ratio' => $this->effortToValueRatio($estimatedHours, $this->immediateImpact($isCritical, $this->priorityScore($level), $level)),
            'responsible_id' => $value['responsible_id'] ?? null,
            'responsible_name' => $value['responsible_name'] ?? null,
            'target_role_name' => $value['target_role_name'] ?? null,
        ];
    }

    /**
     * Estimate hours with dynamic floor, adjusted per algorithm.
     *
     * Greedy: parallelizable tasks get a 15% reduction (quick-win focus, minimal
     *         dependencies). Non-parallelizable/critical tasks get a 10% increase
     *         (no structured breakdown to reduce sequential effort).
     * D&C:    baseline hours — structured decomposition keeps effort predictable.
     */
    protected function estimateHours(int $level, float $availableHours, bool $parallelizable, bool $isCritical, string $algorithmMode = 'divide_conquer'): float
    {
        $hoursMap = [
            1 => 2.0,
            2 => 3.0,
            3 => 5.0,
            4 => 6.5,
            5 => 8.0
        ];

        $minHours = ($level === 1 && $parallelizable && !$isCritical) ? 1.5 : 2.0;
        $base = max($minHours, min($hoursMap[$level] ?? 5.0, $availableHours));

        if ($algorithmMode === 'greedy') {
            if ($parallelizable && !$isCritical) {
                // Greedy thrives on parallelizable quick-win tasks — faster completion
                $base = round($base * 0.85, 1);
            } elseif (!$parallelizable || $isCritical) {
                // Sequential or critical tasks cost more without D&C's structured breakdown
                $base = round($base * 1.10, 1);
            }
        }

        return max($minHours, min($base, $availableHours));
    }

    protected function dynamicMinHours(int $level, bool $parallelizable, bool $isCritical): float
    {
        if ($level === 1 && $parallelizable && !$isCritical) {
            return 1.5;
        }

        return 2.0;
    }

    public function isCriticalPath(int $level): bool
    {
        return $level >= 4;
    }

    protected function resourceIntensity(int $level): int
    {
        return max(1, min(5, $level));
    }

    protected function riskLevel(int $level): string
    {
        return match ($level) {
            1, 2 => 'low',
            3    => 'medium',
            4, 5 => 'high',
            default => 'medium',
        };
    }

    protected function isParallelizable(int $level): bool
    {
        return $level <= 3;
    }

    protected function priorityScore(int $level): int
    {
        $score = 100;
        $score -= $level * 10;

        if ($this->isCriticalPath($level)) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    public function quickWinScore(float $estimatedHours, int $complexityLevel): int
    {
        $score = 10;

        // effort penalty
        if ($estimatedHours >= 7) {
            $score -= 2;
        } elseif ($estimatedHours >= 5) {
            $score -= 1;
        } elseif ($estimatedHours >= 3) {
            $score -= 0;
        } else {
            $score += 0;
        }

        // complexity penalty
        $score -= ($complexityLevel - 1);

        return max(1, min(10, $score));
    }

    public function immediateImpact(bool $isCritical, int $priorityScore, int $complexityLevel): int
    {
        $score = 5;

        if ($isCritical) {
            $score += 3;
        }

        if ($priorityScore >= 85) {
            $score += 2;
        } elseif ($priorityScore >= 70) {
            $score += 1;
        }

        if ($complexityLevel <= 2) {
            $score += 1;
        }

        return max(1, min(10, $score));
    }

    public function effortToValueRatio(float $estimatedHours, int $impactScore): float
    {
        if ($impactScore === 0) {
            return $estimatedHours;
        }

        return round($estimatedHours / $impactScore, 1);
    }
}
