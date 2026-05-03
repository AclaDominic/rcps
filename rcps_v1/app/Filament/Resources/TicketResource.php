<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketRelation;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\Actions\Modal\Actions\Action;
use Filament\Resources\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 2;

    protected static function getNavigationLabel(): string
    {
        return __('Tickets');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('project_id')
                                    ->label(__('Project'))
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($get, $set) {
                                        $project = Project::where('id', $get('project_id'))->first();
                                        $set('dependency_mode',$project->dependency_mode);
                                        if ($project?->status_type === 'custom') {
                                            $set(
                                                'status_id',
                                                TicketStatus::where('project_id', $project->id)
                                                    ->where('is_default', true)
                                                    ->first()
                                                    ?->id
                                            );
                                        } else {
                                            $set(
                                                'status_id',
                                                TicketStatus::whereNull('project_id')
                                                    ->where('is_default', true)
                                                    ->first()
                                                    ?->id
                                            );
                                        }
                                        $set('relation_id', null);
                                    })
                                    ->options(fn() => Project::where('owner_id', auth()->user()->id)
                                        ->orWhereHas('users', function ($query) {
                                            return $query->where('users.id', auth()->user()->id);
                                        }) 
                                        ->get()
                                        ->mapWithKeys(function ($project) {
                                            // Determine mode from tickets
                                            $hasDivideConquer = $project->dependency_mode;
                                            $mode = $hasDivideConquer == 2 ? ' (Divide & Conquer)' : ' (Greedy)';
                                            
                                            return [$project->id => $project->name . $mode];
                                        })
                                        ->toArray()
                                    )
                                    ->default(fn() => request()->get('project'))
                                    ->required(),

                                Forms\Components\Select::make('sprint_id')
                                    ->label(__('Sprint'))
                                    ->searchable()
                                    ->reactive()
                                    ->visible(fn ($get) => Project::where('id', $get('project_id'))->value('type') === 'scrum')
                                    ->columnSpan(2)
                                    ->options(function ($get) {
                                        return Sprint::where('project_id', $get('project_id'))->pluck('name', 'id')->toArray();
                                    })
                                    ->helperText('A Sprint is a short, time-boxed iteration (usually 1–4 weeks) in Scrum where tasks are executed. Use this if your project type is Scrum.'),

                                Forms\Components\Select::make('epic_id')
                                    ->label(__('Epic'))
                                    ->searchable()
                                    ->reactive()
                                    ->columnSpan(2)
                                    ->visible(fn ($get) => Project::where('id', $get('project_id'))->value('type') !== 'scrum')
                                    ->options(function ($get) {
                                        return Epic::where('project_id', $get('project_id'))->pluck('name', 'id')->toArray();
                                    })
                                    ->helperText('An Epic is a large body of work that can be broken down into smaller tasks/stories. Use this if your project is not Scrum-based.'),

                                 Forms\Components\TextInput::make('dependency_mode_id')
                                    ->default(function($get){
                                        return $get('dependency_mode');
                                    })
                                    ->reactive()
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
                                    })->hidden(),

                             
                                Forms\Components\Grid::make()
                                    ->columns(12)
                                    ->columnSpan(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(function (callable $get) {
                                                if($get('dependency_mode')==2){
                                                    return 'Main Ticket Name';
                                                }else{
                                                    return __('Ticket name');
                                                }
                                            })
                                            ->required()
                                            ->columnSpan(
                                                fn($livewire) => !($livewire instanceof CreateRecord) ? 10 : 12
                                            )
                                            ->reactive()
                                            ->maxLength(255),
                                    ]),

                               Forms\Components\RichEditor::make('content')
                                ->label(function (callable $get) {
                                    if($get('create_tasks_now')){
                                        return 'Main Ticket Description';
                                    }else{
                                        return __('Ticket content');
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->columnSpan(2),

                                Forms\Components\Grid::make()
                                ->columns(12)
                                ->columnSpan(3)
                                    ->schema([    
                                            Forms\Components\Select::make('owner_id')
                                                ->label(__('Ticket owner'))
                                                ->searchable()
                                                ->options(fn() => User::all()->pluck('name', 'id')->toArray())
                                                ->default(fn() => auth()->user()->id)
                                                ->required(fn ($get) => !$get('create_tasks_now'))
                                                ->columnSpan(3),

                                            Forms\Components\Select::make('responsible_id')
                                                ->label(__('Ticket responsible'))
                                                ->searchable()
                                                ->reactive()
                                                ->options(function () {
                                                        $priorityMap = TicketPriority::pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                                                            return [strtolower($name) => $id];
                                                        });

                                                        $users = User::with(['ticketsResponsible' => function ($q) {
                                                            $q->whereHas('status', fn ($query) =>
                                                                $query->whereIn('type', ['pending', 'active'])
                                                            );
                                                        }])->get();

                                                        return $users->mapWithKeys(function ($user) use ($priorityMap) {

                                                            $counts = [
                                                                'high' => 0,
                                                                'normal' => 0,
                                                                'low' => 0,
                                                            ];

                                                            foreach ($user->ticketsResponsible as $ticket) {
                                                                if ($ticket->priority_id == $priorityMap['high']) {
                                                                    $counts['high']++;
                                                                } elseif ($ticket->priority_id == $priorityMap['low']) {
                                                                    $counts['low']++;
                                                                } else {
                                                                    $counts['normal']++;
                                                                }
                                                            }

                                                            // PANEL REQUIREMENT:
                                                            // Weighted task load formula
                                                            $score = ($counts['high'] * 3) + ($counts['normal'] * 2) + ($counts['low']);

                                                            // UI Indicator
                                                            $badge = '🟢 [Available]';
                                                            if ($counts['high'] >= 3) $badge = '🔴 [Busy]';
                                                            elseif ($counts['high'] >= 1 || $counts['normal'] >= 3) $badge = '🟠 [Moderate]';

                                                            $label = "{$badge} {$user->name} | High: {$counts['high']}, Normal: {$counts['normal']}, Low: {$counts['low']} | Score: {$score}";
                                                            
                                                            return [$user->id => $label];
                                                        })->sortBy(function ($label) {
                                                            preg_match('/Score\:\s(\d+)/', $label, $m);
                                                            return $m[1] ?? 0; // sort lowest score first
                                                        })->toArray();
                                                    })
                                                ->visible(function (callable $get) {
                                                    if(in_array(strval($get('dependency_mode')), ['1'])){
                                                        return true;
                                                    }else{
                                                        return false;
                                                    }
                                                })
                                                ->required(function (callable $get) {
                                                    if(in_array(strval($get('dependency_mode')), ['1'])){
                                                        return true;
                                                    }else{
                                                        return false;
                                                    }
                                                })
                                                ->columnSpan(5),
                                    ]),

                                Forms\Components\Repeater::make('add_task')
                                    ->label('Add Sub Task')
                                    ->extraAttributes([
                                        'style' => 'max-height: 300px; overflow-y: auto; display: block;'
                                    ])
                                    ->visible(function (callable $get) {
                                        if(in_array(strval($get('dependency_mode')), ['2'])){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    })
                                     ->reactive()
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->orderable()
                                    ->minItems(1)
                                    ->defaultItems(0) 
                                    ->columnSpan(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('subtask_title')
                                                ->label('Sub Task Title')
                                                ->required()
                                                ->reactive()
                                                ->maxLength(255)
                                                ->required(function (callable $get) {
                                                    if(in_array(strval($get('dependency_mode')), ['2'])){
                                                        return true;
                                                    }else{
                                                        return false;
                                                    }
                                                })
                                               ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                                                    $items = $get('../../add_task') ?? [];

                                                    // Normalize current value
                                                    $currentTitle = trim(strtolower($state));

                                                    $duplicates = collect($items)
                                                        ->filter(fn ($item) => !empty($item['subtask_title']))
                                                        ->map(fn($item) => trim(strtolower($item['subtask_title'])))
                                                        ->filter(fn($title) => $title === $currentTitle)
                                                        ->count();

                                                    if ($duplicates > 1) {
                                                        $set('subtask_title', null);

                                                        throw ValidationException::withMessages([
                                                            'add_task.*.subtask_title' => 'This subtask title is already used.',
                                                        ]);
                                                    }
                                                }),
                                            
                                        Forms\Components\RichEditor::make('subtask_description')
                                            ->label(__('Sub Task Description'))
                                            ->required(function (callable $get) {
                                                if(in_array(strval($get('dependency_mode')), ['2'])){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            })
                                            ->reactive()
                                            ->columnSpan('full'),

                                        Forms\Components\Select::make('dependencies')
                                            ->label('Depends On')
                                            ->multiple()
                                            ->placeholder('No Dependency')
                                            ->options(function (callable $get, $component) {

                                                $items = $get('../../add_task') ?? [];
                                                $fieldPath = $component->getStatePath();
                                                $parts = explode('.', $fieldPath); 
                                                $currentKey = $parts[2] ?? null;

                                                // 2️⃣ Build options with continuous numbering
                                                $options = [];
                                                $counter = 1;

                                                foreach ($items as $key => $item) {
                                                    if (empty($item['subtask_title'])) continue; // skip empty titles

                                                    if ($key === $currentKey) { 
                                                        $counter++; // increment so numbering continues continue; // don't include self in options 
                                                        continue;
                                                    }
                            
                                                   if (!empty($item['dependencies']) && $item['dependencies'] === ($items[$currentKey]['subtask_title'] ?? null)) {
                                                        $counter++;
                                                        continue;
                                                    }

                                                    $options[$item['subtask_title']] = 'Subtask ' . $counter . ': ' . $item['subtask_title'];
                                                    $counter++;
                                                }

                                                return $options;
                                            })
                                            ->reactive()
                                            ->visible(function (callable $get) {
                                                return collect($get('../../add_task') ?? [])
                                                    ->filter(fn ($item) => !empty($item['subtask_title']))
                                                    ->count() > 1;
                                            })
                                            ->helperText('Select which previous subtask this depends on'),    

                                    ])->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Update a hidden field that tracks if repeater is valid
                                        $isValid = !empty($state) && collect($state)->every(function ($item) {
                                            return !empty($item['subtask_title']) && !empty($item['subtask_description']);
                                        });
                                        
                                        $set('repeater_is_valid', $isValid);
                                    }),    


                                Forms\Components\View::make('components.tickets.ai-button')
                                    ->visible(function (callable $get) {
                                        if(in_array(strval($get('dependency_mode')), ['2'])){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    })
                                    ->extraAttributes(['wire:key' => 'ai-task-button-' . uniqid()])
                                    ->reactive(),    
                                
                                Forms\Components\Grid::make()
                                    ->visible(function (callable $get) {
                                        if(in_array(strval($get('dependency_mode')), ['1'])){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    })
                                    ->columns(3)
                                    ->columnSpan(2)
                                    ->schema([
                                        Forms\Components\Select::make('status_id')
                                            ->label(__('Ticket status'))
                                            ->searchable()
                                            ->options(function ($get) {
                                                $project = Project::where('id', $get('project_id'))->first();
                                                if ($project?->status_type === 'custom') {
                                                    return TicketStatus::where('project_id', $project->id)
                                                        ->where('is_default',1)
                                                        ->get()
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                } else {
                                                    return TicketStatus::whereNull('project_id')
                                                        ->where('is_default',1)
                                                        ->get()
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }
                                            })
                                            ->default(function ($get) {
                                                $project = Project::where('id', $get('project_id'))->first();
                                                if ($project?->status_type === 'custom') {
                                                    return TicketStatus::where('project_id', $project->id)
                                                        ->where('is_default', true)
                                                        ->first()
                                                        ?->id;
                                                } else {
                                                    return TicketStatus::whereNull('project_id')
                                                        ->where('is_default', true)
                                                        ->first()
                                                        ?->id;
                                                }
                                            })
                                             ->required(function (callable $get) {
                                                if(in_array(strval($get('dependency_mode')), ['1'])){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }),

                                        Forms\Components\Select::make('type_id')
                                            ->label(__('Ticket type'))
                                            ->searchable()
                                            ->options(fn() => TicketType::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => TicketType::where('is_default', true)->first()?->id)
                                            ->required(function (callable $get) {
                                                if(in_array(strval($get('dependency_mode')), ['1'])){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }),

                                        Forms\Components\Select::make('priority_id')
                                            ->label(__('Ticket priority'))
                                            ->searchable()
                                            ->options(fn() => TicketPriority::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => TicketPriority::where('is_default', true)->first()?->id)
                                            ->required(function (callable $get) {
                                                if(in_array(strval($get('dependency_mode')), ['1'])){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }),
                                    ]),
                            ]),

                        Forms\Components\Grid::make()
                             ->visible(function (callable $get) {
                                if(in_array(strval($get('dependency_mode')), ['1'])){
                                    return true;
                                }else{
                                    return false;
                                }
                            })
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
                                    ->columnSpan(2)
                                    ->required(function (callable $get) {
                                        if(in_array(strval($get('dependency_mode')), ['1'])){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    })
                               
                            ]),
                    ]),
            ]);
    }

    private function getCurrentItemIndex($component)
    {
        // Get the field name and extract index
        $fieldName = $component->getName();
        
        // Field name pattern: add_task.0.depends_on
        if (preg_match('/add_task\.(\d+)\./', $fieldName, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }

    public static function tableColumns(bool $withProject = true): array
    {
        $columns = [];
        if ($withProject) {
            $columns[] = Tables\Columns\TextColumn::make('project.name')
                ->label(__('Project'))
                ->sortable()
                ->searchable();
        }
        $columns = array_merge($columns, [
            Tables\Columns\TextColumn::make('name')
                ->label(__('Ticket name'))
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('owner.name')
                ->label(__('Owner'))
                ->sortable()
                ->formatStateUsing(fn($record) => view('components.user-avatar', ['user' => $record->owner]))
                ->searchable(),

            Tables\Columns\TextColumn::make('responsible.name')
                ->label(__('Responsible'))
                ->sortable()
                ->formatStateUsing(fn($record) => view('components.user-avatar', ['user' => $record->responsible]))
                ->searchable(),

            Tables\Columns\TextColumn::make('status.name')
                ->label(__('Status'))
                ->formatStateUsing(fn($record) => new HtmlString('
                            <div class="flex items-center gap-2 mt-1">
                                <span class="filament-tables-color-column relative flex h-6 w-6 rounded-md"
                                    style="background-color: ' . $record->status->color . '"></span>
                                <span>' . $record->status->name . '</span>
                            </div>
                        '))
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('type.name')
                ->label(__('Type'))
                ->formatStateUsing(
                    fn($record) => view('partials.filament.resources.ticket-type', ['state' => $record->type])
                )
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('priority.name')
                ->label(__('Priority'))
                ->formatStateUsing(fn($record) => new HtmlString('
                            <div class="flex items-center gap-2 mt-1">
                                <span class="filament-tables-color-column relative flex h-6 w-6 rounded-md"
                                    style="background-color: ' . $record->priority->color . '"></span>
                                <span>' . $record->priority->name . '</span>
                            </div>
                        '))
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Created at'))
                ->dateTime()
                ->sortable()
                ->searchable(),
        ]);
        return $columns;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::tableColumns())
            ->filters([
               Tables\Filters\SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->multiple()
                    ->options(function () {
                        // Get accessible projects
                        $projects = Project::where('owner_id', auth()->id())
                            ->orWhereHas('users', function ($query) {
                                $query->where('users.id', auth()->id());
                            })
                            ->get();
                        
                        // Format options with mode indicator
                        $options = [];
                        foreach ($projects as $project) {
                            // Determine project mode
                            $mode = $project->dependency_mode == 2 ? ' (Divide & Conquer)' : ' (Greedy)';
                            $options[$project->id] = $project->name . $mode;
                        }
                        
                        return $options;
                    }),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->label(__('Owner'))
                    ->multiple()
                    ->options(fn() => User::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('responsible_id')
                    ->label(__('Responsible'))
                    ->multiple()
                    ->options(fn() => User::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label(__('Status'))
                    ->multiple()
                    ->options(fn() => TicketStatus::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('type_id')
                    ->label(__('Type'))
                    ->multiple()
                    ->options(fn() => TicketType::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label(__('Priority'))
                    ->multiple()
                    ->options(fn() => TicketPriority::all()->pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
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
