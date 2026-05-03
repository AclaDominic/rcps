<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Allow CORE users to bypass all checks.
     */
    public function viewAny(User $user)
    {
        if (auth()->user()->hasRoleType(['CORE'])) {
            return $user->can('List projects');
        }

        return $user->can('List projects');
    }

    public function view(User $user, Project $project)
    {

        if (auth()->user()->hasRoleType(['CORE'])) {
            return $user->can('View project');
        }
        
        return $user->can('View project')
            && (
                $project->owner_id === $user->id
            );
    }

    public function create(User $user)
    {
        return $user->can('Create project');
    }

    public function update(User $user, Project $project)
    {
        if (auth()->user()->hasRoleType(['CORE'])) {
            return $user->can('Update project');
        }
        
        return $user->can('Update project')
            && (
                $project->owner_id === $user->id
            );
    }
    

    public function delete(User $user, Project $project)
    {
        return $user->can('Delete project');
    }
}
