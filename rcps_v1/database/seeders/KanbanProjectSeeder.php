<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketRelation;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class KanbanProjectSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('email', 'admintest2025@yopmail.com')->first() ?? User::first();

        if (!$admin) {
            $admin = User::factory()->create([
                'email' => 'admin@example.com',
                'name' => 'Admin'
            ]);
        }

        $pendingStatus = TicketStatus::where('type', 'pending')->first() ?? TicketStatus::first();

        // 1. D&C Project
        $dcProject = Project::updateOrCreate(
            ['ticket_prefix' => 'DC'],
            [
                'name' => 'Board D&C Test Project',
                'description' => 'Project for testing Divide & Conquer rules (Sequential Dependencies)',
                'status_id' => 1, // Active
                'owner_id' => $admin->id,
                'status_type' => 'public',
                'type' => 'kanban',
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
            ]
        );
        $dcProject->users()->syncWithoutDetaching([$admin->id => ['role' => 'Developer']]);

        // Clear existing tickets for this project to start fresh (Optional but cleaner for testing)
        Ticket::where('project_id', $dcProject->id)->delete();

        // Create 2 Main Tasks with multiple sub-tasks each
        for ($m = 1; $m <= 2; $m++) {
            $mainTask = Ticket::create([
                'project_id' => $dcProject->id,
                'name' => "DC Main Task $m",
                'content' => "Main task $m for Divide & Conquer processing.",
                'owner_id' => $admin->id,
                'responsible_id' => $admin->id,
                'status_id' => $pendingStatus->id,
                'priority_id' => 2,
                'type_id' => 1,
                'dependency_mode' => 2,
                'estimation' => rand(10, 20),
            ]);

            // Create 5 sub-tasks for each main task
            for ($s = 1; $s <= 5; $s++) {
                Ticket::create([
                    'project_id' => $dcProject->id,
                    'parent_ticket_id' => $mainTask->id,
                    'name' => "DC Subtask $m.$s",
                    'content' => "Sub-problem $s of Main Task $m.",
                    'owner_id' => $admin->id,
                    'responsible_id' => $admin->id,
                    'status_id' => $pendingStatus->id,
                    'priority_id' => 2,
                    'type_id' => 1,
                    'dependency_mode' => 2,
                    'estimation' => rand(1, 5),
                    'order' => $s, // Help with top-to-bottom visual order
                ]);
            }
        }


        // 2. Greedy Project
        $greedyProject = Project::updateOrCreate(
            ['ticket_prefix' => 'GR'],
            [
                'name' => 'Board Greedy Test Project',
                'description' => 'Project for testing Greedy rules',
                'status_id' => 1,
                'owner_id' => $admin->id,
                'status_type' => 'public',
                'type' => 'kanban',
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
            ]
        );
        $greedyProject->users()->syncWithoutDetaching([$admin->id => ['role' => 'Developer']]);

        // Main Task 1
        $greedyMain1 = Ticket::create([
            'project_id' => $greedyProject->id,
            'name' => 'Greedy Main Task 1',
            'content' => 'First main task for Greedy',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 3, // Greedy
            'estimation' => rand(2, 8),
        ]);

        // Subtask 1.1
        Ticket::create([
            'project_id' => $greedyProject->id,
            'parent_ticket_id' => $greedyMain1->id,
            'name' => 'Greedy Subtask 1.1',
            'content' => 'Subtask 1.1',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 3,
            'estimation' => rand(2, 8),
        ]);

        // Main Task 2
        $greedyMain2 = Ticket::create([
            'project_id' => $greedyProject->id,
            'name' => 'Greedy Main Task 2',
            'content' => 'Second main task for Greedy',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 3,
            'estimation' => rand(2, 8),
        ]);

        // Subtask 2.1
        Ticket::create([
            'project_id' => $greedyProject->id,
            'parent_ticket_id' => $greedyMain2->id,
            'name' => 'Greedy Subtask 2.1',
            'content' => 'Subtask 2.1',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 3,
            'estimation' => rand(2, 8),
        ]);
    }
}
