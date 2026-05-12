<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;

class DashboardMetricsSeeder extends Seeder
{
    public function run()
    {
        $user = User::first() ?? User::factory()->create();
        
        $comparisonId = uniqid('comp_');
        $baseName = 'Chart Demo Project';
        
        // 1. Create Greedy Project
        $greedyProject = Project::create([
            'name' => $baseName . ' - Greedy Algorithm',
            'description' => 'Demo project to test dashboard metrics (Greedy)',
            'owner_id' => $user->id,
            'status_id' => 1,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'type' => 'scrum',
            'dependency_mode' => 1,
            'comparison_id' => $comparisonId,
            'ticket_prefix' => 'DEMO-G-' . rand(100, 999),
        ]);

        // 2. Create Divide & Conquer Project
        $divideProject = Project::create([
            'name' => $baseName . ' - Divide & Conquer Algorithm',
            'description' => 'Demo project to test dashboard metrics (Divide & Conquer)',
            'owner_id' => $user->id,
            'status_id' => 1,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'type' => 'scrum',
            'dependency_mode' => 2,
            'comparison_id' => $comparisonId,
            'ticket_prefix' => 'DEMO-DC-' . rand(100, 999),
        ]);

        $tasks = [
            ['name' => 'Design Database Schema', 'estimation' => 8, 'g_actual' => 7.5, 'dc_actual' => 8.2, 'g_acc' => 93.75, 'dc_acc' => 97.5, 'g_util' => 93.75, 'dc_util' => 102.5],
            ['name' => 'Develop API Endpoints', 'estimation' => 12, 'g_actual' => 14, 'dc_actual' => 11, 'g_acc' => 83.33, 'dc_acc' => 91.66, 'g_util' => 116.66, 'dc_util' => 91.66],
            ['name' => 'Frontend Integration', 'estimation' => 10, 'g_actual' => 9, 'dc_actual' => 12, 'g_acc' => 90, 'dc_acc' => 80, 'g_util' => 90, 'dc_util' => 120],
            ['name' => 'User Authentication', 'estimation' => 6, 'g_actual' => 6, 'dc_actual' => 5, 'g_acc' => 100, 'dc_acc' => 83.33, 'g_util' => 100, 'dc_util' => 83.33],
            ['name' => 'Payment Gateway', 'estimation' => 16, 'g_actual' => 20, 'dc_actual' => 15, 'g_acc' => 75, 'dc_acc' => 93.75, 'g_util' => 125, 'dc_util' => 93.75],
        ];

        $today = Carbon::now();
        $dateOffset = 0;

        foreach ($tasks as $index => $task) {
            $metricsDate = $today->copy()->subDays(5 - $dateOffset);
            $dateOffset++;
            
            // Greedy Task
            Ticket::create([
                'project_id' => $greedyProject->id,
                'name' => $task['name'],
                'content' => 'Description for ' . $task['name'],
                'estimation' => $task['estimation'],
                'owner_id' => $user->id,
                'responsible_id' => $user->id,
                'priority_id' => 2,
                'order' => $index,
                'status_id' => 3, // Assuming 3 is completed
                'type_id' => 1,
                'dependency_mode' => 1,
                'execution_time' => $task['g_actual'],
                'scheduling_accuracy' => $task['g_acc'],
                'resource_utilization' => $task['g_util'],
                'metrics_date' => $metricsDate->toDateString(),
                'code' => $greedyProject->ticket_prefix . '-' . (1000 + $index),
                'metadata' => json_encode([
                    'comparison_index' => 'Task: ' . $task['name'],
                ])
            ]);

            // D&C Task
            Ticket::create([
                'project_id' => $divideProject->id,
                'name' => $task['name'],
                'content' => 'Description for ' . $task['name'],
                'estimation' => $task['estimation'],
                'owner_id' => $user->id,
                'responsible_id' => $user->id,
                'priority_id' => 2,
                'order' => $index,
                'status_id' => 3,
                'type_id' => 1,
                'dependency_mode' => 2,
                'execution_time' => $task['dc_actual'],
                'scheduling_accuracy' => $task['dc_acc'],
                'resource_utilization' => $task['dc_util'],
                'metrics_date' => $metricsDate->toDateString(),
                'code' => $divideProject->ticket_prefix . '-' . (1000 + $index),
                'metadata' => json_encode([
                    'comparison_index' => 'Task: ' . $task['name'],
                ])
            ]);
        }
    }
}
