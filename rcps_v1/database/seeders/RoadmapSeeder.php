<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketHour;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RoadmapSeeder extends Seeder
{
    public function run()
    {
        $user = User::first() ?? User::factory()->create();
        
        $projectStatus = ProjectStatus::where('name', 'Active')->first() ?? ProjectStatus::first();
        $ticketType = TicketType::first();
        $ticketPriority = TicketPriority::first();
        $ticketStatus = TicketStatus::first();
        $activity = Activity::first();

        // Create a Project for Roadmap
        $project = Project::create([
            'name' => 'Roadmap Demo Project',
            'description' => 'Project to showcase the roadmap functionality',
            'owner_id' => $user->id,
            'status_id' => $projectStatus ? $projectStatus->id : 1,
            'ticket_prefix' => 'ROAD',
            'status_type' => 'public',
            'type' => 'kanban',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(5),
        ]);

        // Attach user to project
        $project->users()->attach($user->id, ['role' => 'Manager']);

        // Create Sprints (which auto-creates Epics)
        $sprints = [
            [
                'name' => 'Q1 Foundation',
                'starts_at' => Carbon::now()->subDays(10),
                'ends_at' => Carbon::now()->addDays(20),
            ],
            [
                'name' => 'Q2 Integration',
                'starts_at' => Carbon::now()->addDays(21),
                'ends_at' => Carbon::now()->addDays(50),
            ],
            [
                'name' => 'Q3 Polish',
                'starts_at' => Carbon::now()->addDays(51),
                'ends_at' => Carbon::now()->addDays(80),
            ],
        ];

        foreach ($sprints as $sprintData) {
            $sprint = Sprint::create(array_merge($sprintData, [
                'project_id' => $project->id,
                'description' => 'Sprint for ' . $sprintData['name'],
            ]));

            // Create some tickets for this sprint/epic
            for ($i = 1; $i <= 3; $i++) {
                $ticket = Ticket::create([
                    'project_id' => $project->id,
                    'name' => 'Task ' . $i . ' for ' . $sprint->name,
                    'content' => 'Content for Task ' . $i,
                    'owner_id' => $user->id,
                    'responsible_id' => $user->id,
                    'priority_id' => $ticketPriority ? $ticketPriority->id : 1,
                    'status_id' => $ticketStatus ? $ticketStatus->id : 1,
                    'type_id' => $ticketType ? $ticketType->id : 1,
                    'estimation' => rand(5, 15),
                    'sprint_id' => $sprint->id,
                ]);

                // Log some hours to show progress
                if ($activity) {
                    TicketHour::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $user->id,
                        'value' => rand(1, (int)$ticket->estimation),
                        'comment' => 'Logged hours for ' . $ticket->name,
                        'activity_id' => $activity->id,
                    ]);
                }
            }
        }
    }
}
