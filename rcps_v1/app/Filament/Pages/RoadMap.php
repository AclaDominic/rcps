<?php

namespace App\Filament\Pages;

use App\Models\Epic;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;


class RoadMap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.road-map';

    protected static ?string $slug = 'road-map';

    protected static ?int $navigationSort = 5;

    public $project;

    public Epic|null $epic = null;

    public bool $ticket = false;

    protected $listeners = [
        'closeEpicDialog' => 'closeDialog',
        'closeTicketDialog' => 'closeDialog',
        'updateEpic'
    ];

    protected static function getNavigationLabel(): string
    {
        return __('Road Map');
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public function mount()
    {
        $p = request()->get('p');
        if ($p && $project = $this->projectQuery()->where('id', $p)->first()) {
            $this->project = $project;
        } else {
            $this->project = $this->projectQuery()->first();
        }
        if ($this->project) {
            $this->form->fill([
                'selectedProject' => $this->project->id
            ]);
        } else {
            $this->form->fill();
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedProject')
                ->placeholder(__('Project'))
                ->disableLabel()
                ->searchable()
                ->extraAttributes([
                    'class' => 'min-w-[16rem]'
                ])
                ->disablePlaceholderSelection()
                ->required()
                ->options(function () {
                    return $this->projectQuery()
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray();
                })
        ];
    }

    public function filter(): void
    {
        $data = $this->form->getState();
        $project = $data['selectedProject'];
        $this->project = Project::where('id', $project)->first();
        $this->dispatchBrowserEvent('projectChanged', [
            'url' => route('road-map.data', $this->project),
            'start_date' => Carbon::parse($this->project->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($this->project->end_date)->format('Y-m-d'),
            'scroll_to' => Carbon::parse($this->project->start_date)->format('Y-m-d')
        ]);
    }

    public function createTicket(): void
    {
        if (!auth()->user()->hasRoleType(['CORE', 'MANAGER'])) {
            Notification::make()
                ->title('Access Denied')
                ->body('You are not allowed to create tickets.')
                ->danger()
                ->send();

            return;
        }
        $this->ticket = true;
    }

    public function createEpic(): void
    {
        $this->epic = new Epic();
        $this->epic->project_id = $this->project->id;
    }

    public function updateEpic(int $epicId): void
    {
        if (!auth()->user()->hasRoleType(['CORE', 'MANAGER'])) {
            Notification::make()
                ->title('Access Denied')
                ->body('You are not allowed to edit this item.')
                ->danger()
                ->send();

            return;
        }

        $this->epic = Epic::where('id', $epicId)->first();
    }

    public function closeDialog(bool $refresh): void
    {
        $this->epic = null;
        $this->ticket = false;
        if ($refresh) {
            $this->filter();
        }
    }

    private function projectQuery(): Builder
    {
         return Project::where(function ($query) {
                $query->where('owner_id', auth()->user()->id)
                    ->orWhereHas('users', function ($q) {
                        $q->where('users.id', auth()->user()->id);
                    })
                    ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->user()->id)
                                ->orWhere('responsible_id', auth()->user()->id);
                        });
                    });
            });
    }
}
