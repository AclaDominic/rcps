<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['name' => 'Active', 'color' => '#00ff00', 'is_default' => true],
            ['name' => 'On Hold', 'color' => '#ffff00', 'is_default' => false],
            ['name' => 'Completed', 'color' => '#ff0000', 'is_default' => false],
        ];
        
        foreach ($statuses as $status) {
            ProjectStatus::firstOrCreate(['name' => $status['name']], $status);
        }
    }
}
