<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Epic;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHour;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GeneralSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Generate Users
        User::factory(10)->create();
        $allUsers = User::all();

        // 1.5 Generate Roles and Assign to Users
        $roles = [
            ['name' => 'Project Manager', 'role_type' => 'MANAGER'],
            ['name' => 'Developer', 'role_type' => 'MEMBER'],
            ['name' => 'QA Engineer', 'role_type' => 'MEMBER'],
        ];
        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $allRoles = \App\Models\Role::whereIn('role_type', ['MANAGER', 'MEMBER'])->get();

        foreach ($allUsers as $user) {
            if ($user->roles->count() === 0) {
                $user->assignRole($allRoles->random());
            }
        }

        // 2. Ensure Project Statuses
        $statuses = [
            ['name' => 'Active', 'color' => '#00ff00', 'is_default' => true],
            ['name' => 'On Hold', 'color' => '#ffff00', 'is_default' => false],
            ['name' => 'Completed', 'color' => '#ff0000', 'is_default' => false],
        ];
        
        foreach ($statuses as $status) {
            ProjectStatus::firstOrCreate(['name' => $status['name']], $status);
        }
        $projectStatuses = ProjectStatus::all();

        // 3. Generate Projects
        for ($i = 1; $i <= 5; $i++) {
            $startDate = Carbon::now()->subMonths(rand(1, 6))->startOfDay();
            // Start and end date should be at least 1 month apart
            $endDate = (clone $startDate)->addMonths(rand(1, 12))->addDays(rand(1, 30))->endOfDay();
            
            $project = Project::create([
                'name' => 'Mock Project ' . $i,
                'description' => 'This is a description for mock project ' . $i,
                'status_id' => $projectStatuses->random()->id,
                'owner_id' => $allUsers->random()->id,
                'ticket_prefix' => 'PRJ' . $i,
                'status_type' => 'public',
                'type' => 'kanban',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Assign users to project
            $projectUsers = $allUsers->random(rand(3, 7));
            foreach ($projectUsers as $user) {
                // Ensure no duplicate attachments if user is already attached
                if (!$project->users()->where('user_id', $user->id)->exists()) {
                    $project->users()->attach($user->id, ['role' => 'Developer']);
                }
            }

            // 4. Generate Sprints and Epics
            for ($j = 1; $j <= 2; $j++) {
                $sprintStart = (clone $startDate)->addDays(($j - 1) * 14);
                $sprintEnd = (clone $sprintStart)->addDays(14);
                
                $sprint = Sprint::create([
                    'name' => 'Sprint ' . $j . ' for PRJ' . $i,
                    'starts_at' => $sprintStart,
                    'ends_at' => $sprintEnd,
                    'description' => 'Mock sprint description',
                    'project_id' => $project->id,
                    'started_at' => $j == 1 ? clone $sprintStart : null,
                ]);

                // 5. Generate Tickets
                $ticketTypes = TicketType::all();
                $ticketPriorities = TicketPriority::all();
                $ticketStatuses = TicketStatus::all();

                for ($k = 1; $k <= 5; $k++) {
                    $ticket = Ticket::create([
                        'name' => 'Mock Ticket ' . $k . ' for Sprint ' . $j,
                        'content' => 'This is a mock ticket description.',
                        'owner_id' => $project->owner_id,
                        'responsible_id' => $projectUsers->random()->id,
                        'status_id' => $ticketStatuses->random()->id,
                        'project_id' => $project->id,
                        'type_id' => $ticketTypes->random()->id,
                        'priority_id' => $ticketPriorities->random()->id,
                        'estimation' => rand(2, 8),
                        'sprint_id' => $sprint->id, // Epic is automatically assigned in Ticket creating/updating boot method if sprint has epic
                    ]);

                    // Generate comments
                    TicketComment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $projectUsers->random()->id,
                        'content' => 'Looks good to me.'
                    ]);

                    // Generate logged hours
                    $activities = Activity::all();
                    if ($activities->count() > 0) {
                        TicketHour::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $projectUsers->random()->id,
                            'value' => rand(1, 4),
                            'comment' => 'Worked on this task',
                            'activity_id' => $activities->random()->id,
                        ]);
                    }
                }
            }
        }
    }
}
