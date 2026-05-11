<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionParent;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    private array $modules = [
        'permission', 'project', 'project status', 'role', 'ticket',
        'ticket priority', 'ticket status', 'ticket type', 'user',
        'activity', 'sprint'
    ];

    private array $pluralActions = [
        'List'
    ];

    private array $singularActions = [
        'View', 'Create', 'Update', 'Delete'
    ];

    private array $extraPermissions = [
        'Manage general settings', 'Import from Jira',
        'List timesheet data', 'View timesheet dashboard'
    ];

    private string $defaultRole = 'Default role';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create profiles
        foreach ($this->modules as $module) {
            $plural = Str::plural($module);
            $singular = $module;

            $parent = PermissionParent::firstOrCreate([
                'name' => ucfirst($module),
                'is_active' => 'Y'
            ]);

            foreach ($this->pluralActions as $action) {
                $permission = Permission::firstOrCreate([
                    'name' => $action . ' ' . $plural
                ]);
                $permission->permission_parent_id = $parent->id;
                $permission->save();
            }
            foreach ($this->singularActions as $action) {
                $permission = Permission::firstOrCreate([
                    'name' => $action . ' ' . $singular
                ]);
                $permission->permission_parent_id = $parent->id;
                $permission->save();
            }
        }

        $extraParent = PermissionParent::firstOrCreate([
            'name' => 'General',
            'is_active' => 'Y'
        ]);

        foreach ($this->extraPermissions as $permission) {
            $perm = Permission::firstOrCreate([
                'name' => $permission
            ]);
            $perm->permission_parent_id = $extraParent->id;
            $perm->save();
        }

        // Create default role
        $role = Role::firstOrCreate([
            'name' => $this->defaultRole,
            'role_type' => 'CORE'
        ]);
        $settings = app(GeneralSettings::class);
        $settings->default_role = $role->id;
        $settings->save();

        // Add all permissions to default role
        $role->syncPermissions(Permission::all()->pluck('name')->toArray());

        // Assign default role to first database user
        if ($user = User::first()) {
            $user->syncRoles([$this->defaultRole]);
        }
    }
}
