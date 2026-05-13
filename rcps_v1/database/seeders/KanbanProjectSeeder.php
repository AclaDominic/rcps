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
        $dcProject = Project::create([
            'name' => 'Board D&C Test Project',
            'description' => 'Project for testing Divide & Conquer rules',
            'status_id' => 1, // Active
            'owner_id' => $admin->id,
            'ticket_prefix' => 'DC',
            'status_type' => 'public',
            'type' => 'kanban',
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
        ]);
        $dcProject->users()->attach($admin->id, ['role' => 'Developer']);

        // Main Task 1
        $dcMain1 = Ticket::create([
            'project_id' => $dcProject->id,
            'name' => 'DC Main Task 1',
            'content' => 'First main task for D&C',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 2, // D&C
            'estimation' => rand(2, 8),
        ]);

        // Subtask 1.1
        $dcSub1_1 = Ticket::create([
            'project_id' => $dcProject->id,
            'parent_ticket_id' => $dcMain1->id,
            'name' => 'DC Subtask 1.1',
            'content' => 'Subtask 1.1',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 2,
            'estimation' => rand(2, 8),
        ]);

        // Subtask 1.2
        $dcSub1_2 = Ticket::create([
            'project_id' => $dcProject->id,
            'parent_ticket_id' => $dcMain1->id,
            'name' => 'DC Subtask 1.2',
            'content' => 'Subtask 1.2 (Depends on 1.1)',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 2,
            'estimation' => rand(2, 8),
        ]);

        // Relation
        TicketRelation::create([
            'ticket_id' => $dcSub1_2->id,
            'relation_id' => $dcSub1_1->id,
            'type' => 'depends_on',
            'sort' => 1
        ]);

        // Main Task 2
        $dcMain2 = Ticket::create([
            'project_id' => $dcProject->id,
            'name' => 'DC Main Task 2',
            'content' => 'Second main task for D&C',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 2,
            'estimation' => rand(2, 8),
        ]);

        // Subtask 2.1
        Ticket::create([
            'project_id' => $dcProject->id,
            'parent_ticket_id' => $dcMain2->id,
            'name' => 'DC Subtask 2.1',
            'content' => 'Subtask 2.1',
            'owner_id' => $admin->id,
            'responsible_id' => $admin->id,
            'status_id' => $pendingStatus->id,
            'priority_id' => 2,
            'type_id' => 1,
            'dependency_mode' => 2,
            'estimation' => rand(2, 8),
        ]);


        // 2. Greedy Project
        $greedyProject = Project::create([
            'name' => 'Board Greedy Test Project',
            'description' => 'Project for testing Greedy rules',
            'status_id' => 1,
            'owner_id' => $admin->id,
            'ticket_prefix' => 'GR',
            'status_type' => 'public',
            'type' => 'kanban',
            'start_date' => now(),
            'end_date' => now()->addMonths(1),
        ]);
        $greedyProject->users()->attach($admin->id, ['role' => 'Developer']);

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
