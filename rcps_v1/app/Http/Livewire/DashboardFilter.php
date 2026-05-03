<?php 
namespace App\Http\Livewire;

use App\Models\Project;
use Livewire\Component;

class DashboardFilter extends Component
{
    public $projectId;
    public $filterType = 'date_range';

    public $dateFrom;
    public $dateTo;

    public $selectedMonth;
    public $selectedWeek;

    public $selectedYear;

    public function render()
    {
        return view('livewire.dashboard.filter', [
            'projects' => Project::whereNull('deleted_at')->pluck('name', 'id'),
            'years' => range(now()->year, now()->year - 5),
            'months' => [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ],
        ]);
    }

    public function applyFilter()
    {
        $this->emit('filterUpdated', [
            'projectId' => $this->projectId,
            'filterType' => $this->filterType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'selectedMonth' => $this->selectedMonth,
            'selectedWeek' => $this->selectedWeek,
            'selectedYear' => $this->selectedYear,
        ]);
    }
}


?>