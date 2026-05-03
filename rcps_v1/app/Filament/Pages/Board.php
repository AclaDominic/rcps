<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Board extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-view-boards';

    protected static string $view = 'filament.pages.board';

    protected static ?string $slug = 'board';

    protected static ?int $navigationSort = 4;

    protected function getSubheading(): string|Htmlable|null
    {
        return __("In this section you can choose one of your projects to show it's Scrum or Kanban board");
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    protected static function getNavigationLabel(): string
    {
        return __('Board');
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    protected function getFormSchema(): array
    {
        return [
            Card::make()
                ->schema([
                    Grid::make()
                        ->columns(1)
                        ->schema([
                            Select::make('project')
                                ->label(__('Project'))
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->search())
                                ->helperText(__("Choose a project to show it's board"))
                                ->options(function () {
                                $user = auth()->user();

                                // If CORE user — show ALL projects
                                if ($user && $user->hasRoleType(['CORE'])) {
                                    return Project::get()->mapWithKeys(function ($project) {
                                        // Determine mode from tickets
                                        $hasDivideConquer = $project->tickets->contains('dependency_mode', 2);
                                        $mode = $hasDivideConquer ? ' (Divide & Conquer)' : ' (Greedy)';
                                        
                                        return [$project->id => $project->name . $mode];
                                    })
                                    ->toArray();
                                }

                                // Non-CORE users — show only related projects
                              return Project::with(['tickets' => function($query) {
                                        $query->select('project_id', 'dependency_mode');
                                    }])
                                    ->where(function ($query) use ($user) {
                                        $query->where('owner_id', $user->id)
                                            ->orWhereHas('users', function ($q) use ($user) {
                                                $q->where('users.id', $user->id);
                                            })
                                            ->orWhereHas('tickets', function ($q) use ($user) {
                                                $q->where(function ($subQ) use ($user) {
                                                    $subQ->where('owner_id', $user->id)
                                                        ->orWhere('responsible_id', $user->id);
                                                });
                                            });
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($project) {
                                        // Determine mode from tickets
                                        $hasDivideConquer = $project->tickets->contains('dependency_mode', 2);
                                        $mode = $hasDivideConquer ? ' (Divide & Conquer)' : ' (Greedy)';
                                        
                                        return [$project->id => $project->name . $mode];
                                    })
                                    ->toArray();
                            })
                        ]),
                ]),
        ];
    }

    public function search(): void
    {
        $data = $this->form->getState();
        $project = Project::find($data['project']);
        if ($project->type === "scrum") {
            $this->redirect(route('filament.pages.scrum/{project}', ['project' => $project]));
        } else {
            $this->redirect(route('filament.pages.kanban/{project}', ['project' => $project]));
        }
    }
}
