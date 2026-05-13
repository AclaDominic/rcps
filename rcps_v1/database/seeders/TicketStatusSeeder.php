<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    private array $data = [
        [
            'name' => 'Todo',
            'color' => '#cecece',
            'is_default' => true,
            'order' => 1,
            'type' => 'pending'
        ],
        [
            'name' => 'In progress',
            'color' => '#ff7f00',
            'is_default' => false,
            'order' => 2,
            'type' => 'active'
        ],
        [
            'name' => 'Done',
            'color' => '#008000',
            'is_default' => false,
            'order' => 3,
            'type' => 'completed'
        ],
        [
            'name' => 'Review',
            'color' => '#00ffff', // Cyan
            'is_default' => false,
            'order' => 4,
            'type' => 'active' // Review is an active phase
        ],
        [
            'name' => 'Completed',
            'color' => '#ffff00', // Yellow
            'is_default' => false,
            'order' => 5,
            'type' => 'completed'
        ],
        [
            'name' => 'Archived',
            'color' => '#ff0000',
            'is_default' => false,
            'order' => 6,
            'type' => 'completed'
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data as $item) {
            TicketStatus::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
