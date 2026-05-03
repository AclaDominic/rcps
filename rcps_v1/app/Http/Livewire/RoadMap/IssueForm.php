<?php

namespace App\Http\Livewire\RoadMap;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketRelation;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class IssueForm extends Component implements HasForms
{
    use InteractsWithForms;

    public Project|null $project = null;

    protected static ?string $model = Ticket::class;

    public array $epics;

    public array $sprints;

    public function mount()
    {
        $this->initProject($this->project?->id);
        if ($this->project?->status_type === 'custom') {
            $defaultStatus = TicketStatus::where('project_id', $this->project->id)
                ->where('is_default', true)
                ->first()
                ?->id;
        } else {
            $defaultStatus = TicketStatus::whereNull('project_id')
                ->where('is_default', true)
                ->first()
                ?->id;
        }
        $this->form->fill([
            'project_id' => $this->project?->id ?? null,
            'owner_id' => auth()->user()->id,
            'status_id' => $defaultStatus,
            'type_id' => TicketType::where('is_default', true)->first()?->id,
            'priority_id' => TicketPriority::where('is_default', true)->first()?->id
        ]);
    }

    public function render()
    {
        return view('livewire.road-map.issue-form');
    }

    private function initProject($projectId): void
    {
        if ($projectId) {
            $this->project = Project::where('id', $projectId)->first();
        } else {
            $this->project = null;
        }
        $this->epics = $this->project ? $this->project->epics->pluck('name', 'id')->toArray() : [];
        $this->sprints = $this->project ? $this->project->sprints->pluck('name', 'id')->toArray() : [];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\Select::make('project_id')
                                ->label(__('Project'))
                                ->searchable()
                                ->reactive()
                                ->disabled($this->project != null)
                                ->columnSpan(2)
                                ->options(fn() => Project::where('owner_id', auth()->user()->id)
                                    ->orWhereHas('users', function ($query) {
                                        return $query->where('users.id', auth()->user()->id);
                                    })->pluck('name', 'id')->toArray()
                                )
                                ->afterStateUpdated(fn(Closure $get) => $this->initProject($get('project_id')))
                                ->required(),

                            Forms\Components\Select::make('sprint_id')
                                ->label(__('Sprint'))
                                ->searchable()
                                ->reactive()
                                ->visible(fn () => $this->project && $this->project->type === 'scrum')
                                ->columnSpan(2)
                                ->options(fn () => $this->sprints)
                                ->helperText('A Sprint is a short, time-boxed iteration (usually 1–4 weeks) in Scrum where tasks are executed. Use this if your project type is Scrum.'),

                            Forms\Components\Select::make('epic_id')
                                ->label(__('Epic'))
                                ->searchable()
                                ->reactive()
                                ->columnSpan(2)
                                ->required()
                                ->visible(fn () => $this->project && $this->project->type !== 'scrum')
                                ->options(fn () => $this->epics)
                                ->helperText('An Epic is a large body of work that can be broken down into smaller tasks/stories. Use this if your project is not Scrum-based.'),

                            Forms\Components\TextInput::make('name')
                                ->label(__('Ticket name'))
                                ->required()
                                ->columnSpan(4)
                                ->maxLength(255),
                        ]),

                        Forms\Components\Grid::make()
                        ->columns(12)
                            ->schema([    
                                Forms\Components\Select::make('dependency_mode')
                                        ->options([
                                            '1' => 'Greedy (No Dependencies)',
                                            '2' => 'Divide & Conquer (With Dependencies)',
                                        ])
                                        ->hint('<div>
                                            <h3 class="text-lg font-bold">Dependency Mode Guidelines</h3>
                                            <ul class="list-disc pl-5 mt-2 space-y-1">
                                                <li><b>Greedy Mode:</b> Automatically assigns tasks to the least loaded user. No dependencies allowed.</li>
                                                <li><b>Divide & Conquer:</b> Allows you to set dependencies between tickets and manually choose responsible users.</li>
                                            </ul>
                                        </div>')
                                        ->hintIcon('heroicon-o-question-mark-circle')
                                        ->default(null)
                                        ->reactive()
                                        ->helperText('Dependency management only available in Divide & Conquer mode')
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('relations', []); // Clear relations
                                            if ($state == '1') {
                                                // Auto-assign least loaded user (Greedy mode)
                                                $leastLoadedUserId = User::whereHas('ticketsResponsible.status', function ($q) {
                                                    $q->whereIn('type', ['pending', 'active']);
                                                })
                                                ->withCount(['ticketsResponsible as tickets_responsible_count' => function ($q) {
                                                    $q->whereHas('status', function ($query) {
                                                        $query->whereIn('type', ['pending', 'active']);
                                                    });
                                                }])
                                                ->orderBy('tickets_responsible_count', 'asc')
                                                ->pluck('id')
                                                ->first();
                                    
                                                $set('responsible_id', $leastLoadedUserId);
                                            } else {
                                                // Allow manual selection
                                                $set('responsible_id', null);
                                            }
                                        })
                                        ->required()
                                        ->columnSpan(4),    
                                Forms\Components\Select::make('owner_id')
                                        ->label(__('Ticket owner'))
                                        ->searchable()
                                        ->options(fn() => User::all()->pluck('name', 'id')->toArray())
                                        ->default(fn() => auth()->user()->id)
                                        ->required()
                                        ->columnSpan(3),

                                Forms\Components\Select::make('responsible_id')
                                                ->label(__('Ticket responsible'))
                                                ->searchable()
                                                ->reactive()
                                                ->options(function () {
                                                    // Load priorities for reference
                                                    $priorityMap = TicketPriority::pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                                                        return [strtolower($name) => $id];
                                                    });

                                                    $users = User::with(['ticketsResponsible' => function ($q) {
                                                        $q->whereHas('status', function ($query) {
                                                            $query->whereIn('type', ['pending', 'active']); // Optional: only active tickets
                                                        });
                                                    }])->get();

                                                    return $users->mapWithKeys(function ($user) use ($priorityMap) {
                                                        $counts = [
                                                            'high' => 0,
                                                            'normal' => 0,
                                                            'low' => 0,
                                                        ];

                                                        foreach ($user->ticketsResponsible as $ticket) {
                                                            $priorityId = $ticket->priority_id;
                                                            if ($priorityId == $priorityMap['high']) {
                                                                $counts['high']++;
                                                            } elseif ($priorityId == $priorityMap['low']) {
                                                                $counts['low']++;
                                                            } else {
                                                                $counts['normal']++;
                                                            }
                                                        }

                                                        // Label color simulation
                                                        $totalHigh = $counts['high'];
                                                        $labelPrefix = '🟢 [Available]';

                                                        if ($totalHigh >= 3) {
                                                            $labelPrefix = '🔴 [Busy]';
                                                        } elseif ($totalHigh >= 1) {
                                                            $labelPrefix = '🟠 [Moderate]';
                                                        }

                                                        $label = "{$labelPrefix} {$user->name} - High: {$counts['high']}, Normal: {$counts['normal']}, Low: {$counts['low']}";
                                                        return [$user->id => $label];
                                                    })->toArray();
                                                })
                                                ->visible(fn ($get) => in_array(strval($get('dependency_mode')), ['1', '2']))
                                                ->columnSpan(5),
                            ]),

                        Forms\Components\Grid::make()
                                ->columns(3)
                                ->columnSpan(2)
                                ->schema([
                                    Forms\Components\Select::make('status_id')
                                        ->label(__('Ticket status'))
                                        ->searchable()
                                        ->options(function ($get) {
                                            if ($this->project?->status_type === 'custom') {
                                                return TicketStatus::where('project_id', $this->project->id)
                                                    ->get()
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            } else {
                                                return TicketStatus::whereNull('project_id')
                                                    ->get()
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            }
                                        })
                                        ->required(),

                                    Forms\Components\Select::make('type_id')
                                        ->label(__('Ticket type'))
                                        ->searchable()
                                        ->options(fn() => TicketType::all()->pluck('name', 'id')->toArray())
                                        ->required(),

                                    Forms\Components\Select::make('priority_id')
                                        ->label(__('Ticket priority'))
                                        ->searchable()
                                        ->options(fn() => TicketPriority::all()->pluck('name', 'id')->toArray())
                                        ->required(),
                                ]),
                ]),

            Forms\Components\RichEditor::make('content')
                ->label(__('Ticket content'))
                ->required()
                ->columnSpan(2),

            Forms\Components\Grid::make()
                ->columnSpan(2)
                ->columns(12)
                ->schema([
                    Forms\Components\TextInput::make('estimation')
                                    ->label(__('Estimation time'))
                                     ->hint('<div>
                                        <h3 class="text-sm font-semibold">Estimation Guidelines</h3>
                                        <ul class="list-disc pl-5 mt-2 space-y-1 text-xs">
                                            <li><b>Keep it realistic:</b> Estimate based on actual effort, not ideal conditions.</li>
                                            <li><b>Use hours:</b> Enter the expected duration in hours (e.g., 2 = 2 hours).</li>
                                            <li><b>Breakdown tasks:</b> Large tasks? Split into smaller estimates for accuracy.</li>
                                            <li><b>Buffer time:</b> Add a small buffer for unexpected issues.</li>
                                            <li><b>Consistency:</b> Apply the same estimation approach across all tickets.</li>
                                        </ul>
                                    </div>')
                                    ->hintIcon('heroicon-o-question-mark-circle')
                                    ->numeric()
                                    ->columnSpan(3)
                                    ->required(),
                ]),
                Forms\Components\Repeater::make('relations')
                            ->label('Dependencies (Optional)')
                            ->itemLabel(function (array $state) {
                                $ticketRelation = TicketRelation::find($state['id'] ?? 0);
                                if ($ticketRelation) {
                                    return __(config('system.tickets.relations.list.' . $ticketRelation->type))
                                        . ' '
                                        . $ticketRelation->relation->name
                                        . ' (' . $ticketRelation->relation->code . ')';
                                }
                                return null;
                            })
                            ->visible(fn ($get) => strval($get('dependency_mode')) === '2') // 2 = Divide & Conquer
                        
                            ->collapsible()
                            ->collapsed()
                            ->orderable()
                            ->defaultItems(0)
                            ->default(function (callable $get, callable $set, $state, $livewire) {
                                // Only apply in Edit mode
                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->record) {
                                    $ticketId = $livewire->record->id;
                                    return \App\Models\TicketRelation::where('ticket_id', $ticketId)
                                        ->get()
                                        ->map(function ($relation) {
                                            return [
                                                'type' => $relation->type,
                                                'relation_id' => $relation->relation_id,
                                            ];
                                        })
                                        ->toArray();
                                }
                                return [];
                            })
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->columns(3)
                                    ->schema([

                                        Forms\Components\Select::make('type')
                                            ->label(__('Relation type'))
                                            ->hint('<div>
                                                <h3 class="text-sm font-semibold">Relation Type Guidelines</h3>
                                                <ul class="list-disc pl-5 mt-2 text-xs space-y-1">
                                                    <li><b>Related To:</b> Use when the ticket is connected but independent.</li>
                                                    <li><b>Blocked By:</b> Task cannot start until another ticket is finished.</li>
                                                    <li><b>Duplicate Of:</b> Marks this ticket as a duplicate of another.</li>
                                                    <li><b>Depends On:</b> Indicates this task should follow another, but not strictly blocked.</li>
                                                </ul>
                                            </div>')
                                            ->hintIcon('heroicon-o-question-mark-circle')
                                            ->required()
                                            ->searchable()
                                            ->options(config('system.tickets.relations.list'))
                                            ->default(fn() => config('system.tickets.relations.default')),
                        
                                        Forms\Components\Select::make('relation_id')
                                            ->label(__('Related ticket'))
                                            ->required()
                                            ->searchable()
                                            ->columnSpan(2)
                                            ->options(function ($livewire) {
                                                $query = Ticket::query();
                                                if ($livewire instanceof EditRecord && $livewire->record) {
                                                    $query->where('id', '<>', $livewire->record->id);
                                                }
                                                return $query->get()->pluck('name', 'id')->toArray();
                                            }),
                                    ]),
                            ]),
                           
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        Ticket::create($data);
        Filament::notify('success', __('Ticket successfully saved'));
        $this->cancel(true);
    }

    public function cancel($refresh = false): void
    {
        $this->emit('closeTicketDialog', $refresh);
    }

    protected function getDependencySuggestions($projectId, $currentTicketId)
    {
        $suggestions = [];
        
        // 1. Get existing relations from database
        $existingRelations = TicketRelation::where('ticket_id', $currentTicketId)
            ->pluck('relation_id')
            ->toArray();
        
        // 2. Suggest tickets from same epic
        $currentTicket = Ticket::find($currentTicketId);
        if ($currentTicket?->epic_id) {
            $epicTickets = Ticket::where('epic_id', $currentTicket->epic_id)
                ->where('project_id', $projectId)
                ->whereNotIn('id', array_merge([$currentTicketId], $existingRelations))
                ->limit(3)
                ->get();
                
            foreach ($epicTickets as $ticket) {
                $suggestions[] = [
                    'ticket_id' => $currentTicketId,
                    'relation_id' => $ticket->id,
                    'type' => 'depends_on',
                    'sort' => count($suggestions) + 1,
                    'reason' => 'Same epic: ' . $ticket->epic->name
                ];
            }
        }
        
        // 3. Suggest tickets with similar type
        if ($currentTicket?->type_id) {
            $similarTypeTickets = Ticket::where('type_id', $currentTicket->type_id)
                ->where('project_id', $projectId)
                ->whereNotIn('id', array_merge([$currentTicketId], $existingRelations))
                ->limit(2)
                ->get();
                
            foreach ($similarTypeTickets as $ticket) {
                $suggestions[] = [
                    'ticket_id' => $currentTicketId,
                    'relation_id' => $ticket->id,
                    'type' => 'relates_to',
                    'sort' => count($suggestions) + 1,
                    'reason' => 'Same type: ' . $ticket->type->name
                ];
            }
        }
        
        return $suggestions;
    }
}
