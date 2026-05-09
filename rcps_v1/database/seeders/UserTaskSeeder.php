<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use App\Models\TicketType;
use App\Models\ProjectStatus;

class UserTaskSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->info('No users found to assign tasks to.');
            return;
        }

        $project = Project::first();
        if (!$project) {
            $projectStatus = ProjectStatus::firstOrCreate(
                ['name' => 'Active'],
                ['color' => '#00ff00', 'is_default' => true]
            );

            $project = Project::create([
                'name' => 'Default Project',
                'description' => 'Default project for tasks',
                'status_id' => $projectStatus->id,
                'owner_id' => $users->first()->id,
                'type' => 'kanban',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);
            
            // Attach users to project
            foreach ($users as $user) {
                $project->users()->attach($user->id, ['role' => 'Developer']);
            }
        }

        $status = TicketStatus::where('is_default', true)->first() ?? TicketStatus::first();
        $priority = TicketPriority::where('is_default', true)->first() ?? TicketPriority::first();
        $type = TicketType::where('is_default', true)->first() ?? TicketType::first();

        if (!$status || !$priority || !$type) {
            $this->command->info('Missing TicketStatus, TicketPriority, or TicketType. Please run their seeders first.');
            return;
        }

        foreach ($users as $user) {
            // Create a task for each user
            Ticket::create([
                'name' => 'Task for ' . $user->name,
                'content' => 'This is a task automatically seeded for ' . $user->name,
                'owner_id' => $project->owner_id,
                'responsible_id' => $user->id,
                'status_id' => $status->id,
                'project_id' => $project->id,
                'type_id' => $type->id,
                'priority_id' => $priority->id,
                'estimation' => rand(2, 10),
            ]);
        }

        $this->command->info('Seeded tasks for ' . $users->count() . ' users.');
    }
}
