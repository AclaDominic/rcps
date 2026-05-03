<?php

namespace App\Filament\Resources;

use App\Exports\ProjectHoursExport;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\AiTaskPreview;
use App\Models\Project;
use App\Models\ProjectFavorite;
use App\Models\ProjectStatus;
use App\Models\Ticket;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;

class ProjectResourceOLD
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive';

    protected static ?int $navigationSort = 1;

    protected static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public static function getEloquentQuery(): Builder
    {
        // Base query (exclude soft deleted)
        $query = self::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['owner', 'status'])
            ->whereNull('projects.deleted_at'); // ✅ remove soft-deleted rows

        // If CORE, show all active (non-deleted) projects
        if (auth()->check() && auth()->user()->hasRoleType(['CORE'])) {
            return $query;
        }

        // For non-CORE, only show owned or assigned projects (non-deleted only)
        return $query->where(function ($q) {
            $q->where('owner_id', auth()->id())
                ->orWhereHas('users', fn($q2) => $q2->where('users.id', auth()->id()));
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('cover')
                                    ->label(__('Cover image'))
                                    ->image()
                                    ->helperText(
                                        __('If not selected, an image will be generated based on the project name')
                                    )
                                    ->columnSpan(1),

                                Forms\Components\Grid::make()
                                    ->columnSpan(2)
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->columnSpan(2)
                                            ->columns(12)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label(__('Project name'))
                                                    ->required()
                                                    ->columnSpan(12)
                                                    ->maxLength(255)
                                                    ->reactive()
                                                    ->disabled(fn ($livewire) => 
                                                        $livewire instanceof \App\Filament\Resources\ProjectResource\Pages\EditProject
                                                    ),
                                            ]),

                                        Forms\Components\Select::make('owner_id')
                                            ->label(__('Project owner'))
                                            ->searchable()
                                            ->options(fn() => User::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => auth()->user()->id)
                                            ->required()
                                            ->reactive(),

                                        Forms\Components\Select::make('status_id')
                                            ->label(__('Project status'))
                                            ->searchable()
                                            ->options(fn() => ProjectStatus::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => ProjectStatus::where('is_default', true)->first()?->id)
                                            ->required()
                                            ->reactive(),
                                        
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label(__('Start Date'))
                                            ->reactive()
                                            ->required(),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label(__('End Date'))
                                            ->minDate(fn (callable $get) => $get('start_date'))
                                            ->disabled(fn (callable $get) => blank($get('start_date')))
                                            ->required()
                                            ->reactive()
                                    ]),

                                Forms\Components\RichEditor::make('description')
                                    ->label(__('Project description'))
                                    ->columnSpan(3),

                                Forms\Components\Select::make('type')
                                    ->label(__('Project type'))
                                    ->searchable()
                                    ->options([
                                        'kanban' => __('Kanban'),
                                        'scrum' => __('Scrum')
                                    ])
                                    ->reactive()
                                    ->default('kanban')
                                    ->helperText(function ($state) {
                                        if ($state === 'kanban') {
                                            return __('Display and move your project forward with issues on a powerful board.');
                                        } elseif ($state === 'scrum') {
                                            return __('Achieve your project goals with a board, backlog, and roadmap.');
                                        }
                                        return '';
                                    })
                                    ->required(),
                                
                                // NEW: Algorithm Selection for Comparative Study
                                Forms\Components\Select::make('algorithm_mode')
                                    ->label('Algorithm Mode for Comparative Study')
                                    ->options([
                                        // 'divide_conquer' => 'Single Project: Divide & Conquer Only',
                                        // 'greedy' => 'Single Project: Greedy Only', 
                                        'comparison' => 'Comparative Study: Create 2 Projects (Greedy vs Divide & Conquer)',
                                    ])
                                    ->default('comparison')
                                    ->reactive()
                                    ->required()
                                    ->helperText(function ($state) {
                                        if ($state === 'comparison') {
                                            return '⚠️ IMPORTANT: This will create TWO identical projects - one using Greedy algorithm, one using Divide & Conquer algorithm. Both will have the same tasks and AI-generated subtasks.';
                                        } elseif ($state === 'greedy') {
                                            return 'All tasks will use Greedy algorithm (sequential execution, no dependencies)';
                                        } else {
                                            return 'All tasks will use Divide & Conquer algorithm (with dependencies and parallel execution)';
                                        }
                                    })->hidden(fn ($livewire) => 
                                        $livewire instanceof \App\Filament\Resources\ProjectResource\Pages\EditProject ||
                                        $livewire instanceof \App\Filament\Resources\ProjectResource\Pages\ViewProject
                                    ),


                                Forms\Components\Toggle::make('create_tasks_now')
                                ->visible(function (callable $get) {
                                        // Always show if algorithm mode is not comparison
                                        if ($get('algorithm_mode') === 'comparison') {
                                            return true; // Show for comparison mode
                                        }
                                        return false;
                                    })
                                    ->label('Generate AI Subtasks Now?')
                                    ->reactive()
                                    ->disabled(function (callable $get) {
                                        // Disable toggle if required fields are empty
                                        return blank($get('name')) || 
                                            blank($get('start_date')) || 
                                            blank($get('end_date'));
                                    })
                                    ->helperText(function (callable $get) {
                                        $missingFields = [];
                                        
                                        if (blank($get('name'))) {
                                            $missingFields[] = 'project name';
                                        }
                                        if (blank($get('start_date'))) {
                                            $missingFields[] = 'start date';
                                        }
                                        if (blank($get('end_date'))) {
                                            $missingFields[] = 'end date';
                                        }
                                        
                                        if (!empty($missingFields)) {
                                            return 'Please fill in: ' . implode(', ', $missingFields) . ' to enable AI task generation';
                                        }
                                        
                                        $algorithmMode = $get('algorithm_mode');
                                        if ($algorithmMode === 'comparison') {
                                            return 'AI will generate subtasks for BOTH projects (Greedy and Divide & Conquer versions)';
                                        } else {
                                            return 'AI will generate subtasks using ' . 
                                                ($algorithmMode === 'greedy' ? 'Greedy' : 'Divide & Conquer') . 
                                                ' algorithm';
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (!$state) {
                                            // If the user disables toggle, clear task-related data
                                            $set('add_task', []);
                                        } else {
                                            // If enabling, ensure we have the required data
                                            if (blank($get('name')) || blank($get('start_date')) || blank($get('end_date'))) {
                                                $set('create_tasks_now', false);
                                            }
                                        }
                                    })
                                    ->hidden(fn ($livewire) => $livewire instanceof \App\Filament\Resources\ProjectResource\Pages\EditProject),
                                
                                // Warning for comparison mode
                                Forms\Components\View::make('components.reminders.algorithm-description')
                                    ->visible(fn ($get) => $get('algorithm_mode') === 'comparison')
                                    ->columnSpanFull(),
                            ]),
                        
                        // Tasks Section - Only visible when toggle is enabled AND required fields are filled
                        Forms\Components\Section::make('Tasks for AI Generation')
                            ->visible(function (callable $get) {
                                return $get('create_tasks_now') && 
                                    !blank($get('name')) && 
                                    !blank($get('start_date')) && 
                                    !blank($get('end_date'));
                            })
                            ->schema([
                                Forms\Components\View::make('components.reminders.ai-reminder-divide-conquer')
                                    ->visible(fn ($get) => $get('algorithm_mode') === 'divide_conquer')
                                    ->columnSpanFull(),

                                Forms\Components\View::make('components.reminders.ai-reminder-greedy')
                                    ->visible(fn ($get) => $get('algorithm_mode') === 'greedy')
                                    ->columnSpanFull(),

                                Forms\Components\View::make('components.reminders.ai-reminder-comparison')
                                    ->visible(fn ($get) => $get('algorithm_mode') === 'comparison')
                                    ->columnSpanFull(),

                                
                                Forms\Components\Repeater::make('add_task')
                                    ->label('Main Tasks to Decompose')
                                    ->extraAttributes([
                                        'style' => 'max-height: 300px; overflow-y: auto; display: block;'
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->orderable()
                                    ->minItems(1)
                                    ->defaultItems(0) 
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('main_task_name')
                                                    ->label('Main Task Name')
                                                    ->required()
                                                    ->reactive()
                                                    ->maxLength(255),

                                                Forms\Components\Select::make('owner_id')
                                                    ->label(__('Task Owner'))
                                                    ->searchable()
                                                    ->options(fn() => User::all()->pluck('name', 'id')->toArray())
                                                    ->default(fn() => auth()->user()->id)
                                                    ->required(),
                                            ]),
                                            
                                        Forms\Components\RichEditor::make('main_task_description')
                                            ->label(__('Main Task Description'))
                                            ->required()
                                            ->reactive()
                                            ->columnSpan('full')
                                            ->helperText('Detailed description for AI to understand the task scope'),
                                        
                                        Forms\Components\Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('ai_subtask_count')
                                                    ->label('Number of AI Subtasks')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(20)
                                                    ->default(0)
                                                    ->required()
                                                    ->reactive()
                                                    ->helperText('How many subtasks should AI generate?'),
                                            ]),
                                        
                                        // Forms\Components\Textarea::make('ai_description')
                                        //     ->label('Additional Instructions for AI')
                                        //     ->rows(3)
                                        //     ->required()
                                        //     ->reactive()
                                        //     ->helperText('Specific instructions for AI (e.g., focus areas, constraints, deliverables)'),
                                        
                                        Forms\Components\Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                Forms\Components\View::make('components.projects.ai-button')
                                                    ->extraAttributes(function ($get, $index) {
                                                        return [
                                                        'wire:key' => 'ai-task-button-' . uniqid(),
                                                        'data-index' => $index,
                                                        ];
                                                    })
                                                    ->viewData([
                                                        'index' => 0,
                                                    ])
                                                    ->reactive()
                                            ]),
                                    ])
                                    ->createItemButtonLabel('Add Another Main Task')
                                    ->helperText('Add main tasks that will be broken down into AI-generated subtasks.'),
                            ]),
                    ]),
            ]);
    }

    public function getDataForm()
    {
        return [
            'project_name'=>  $this->data['name'] ?? 'No project name'
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cover')
                    ->label(__('Cover image'))
                    ->formatStateUsing(fn($state) => new HtmlString('
                            <div style=\'background-image: url("' . $state . '")\'
                                 class="w-8 h-8 bg-cover bg-center bg-no-repeat"></div>
                        ')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Project name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('Project owner'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('Project status'))
                    ->formatStateUsing(fn($record) => new HtmlString('
                            <div class="flex items-center gap-2">
                                <span class="filament-tables-color-column relative flex h-6 w-6 rounded-md"
                                    style="background-color: ' . $record->status->color . '"></span>
                                <span>' . $record->status->name . '</span>
                            </div>
                        '))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TagsColumn::make('users.name')
                    ->label(__('Affected users'))
                    ->limit(2),

                Tables\Columns\BadgeColumn::make('type')
                    ->enum([
                        'kanban' => __('Kanban'),
                        'scrum' => __('Scrum')
                    ])
                    ->colors([
                        'secondary' => 'kanban',
                        'warning' => 'scrum',
                    ]),

                 Tables\Columns\BadgeColumn::make('dependency_mode')
                    ->label(__('Algorithm Used'))
                    ->enum([
                        '1' => __('Greedy'),
                        '2' => __('Divide and Conquer')
                    ])
                    ->colors([
                        'primary' => '1',
                        'warning' => '2',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label(__('Owner'))
                    ->multiple()
                    ->options(fn() => User::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label(__('Status'))
                    ->multiple()
                    ->options(fn() => ProjectStatus::all()->pluck('name', 'id')->toArray()),
            ])
            ->actions([

                Tables\Actions\Action::make('favorite')
                    ->label('')
                    ->icon('heroicon-o-star')
                    ->color(fn($record) => auth()->user()->favoriteProjects()
                        ->where('projects.id', $record->id)->count() ? 'success' : 'default')
                    ->action(function ($record) {
                        $projectId = $record->id;
                        $projectFavorite = ProjectFavorite::where('project_id', $projectId)
                            ->where('user_id', auth()->user()->id)
                            ->first();
                        if ($projectFavorite) {
                            $projectFavorite->delete();
                        } else {
                            ProjectFavorite::create([
                                'project_id' => $projectId,
                                'user_id' => auth()->user()->id
                            ]);
                        }
                        Filament::notify('success', __('Project updated'));
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('exportLogHours')
                        ->label(__('Export hours'))
                        ->icon('heroicon-o-document-download')
                        ->color('secondary')
                        ->action(fn($record) => Excel::download(
                            new ProjectHoursExport($record),
                            'time_' . Str::slug($record->name) . '.csv',
                            \Maatwebsite\Excel\Excel::CSV,
                            ['Content-Type' => 'text/csv']
                        )),

                    Tables\Actions\Action::make('kanban')
                        ->label(
                            fn ($record)
                                => ($record->type === 'scrum' ? __('Scrum board') : __('Kanban board'))
                        )
                        ->icon('heroicon-o-view-boards')
                        ->color('secondary')
                        ->url(function ($record) {
                            if ($record->type === 'scrum') {
                                return route('filament.pages.scrum/{project}', ['project' => $record->id]);
                            } else {
                                return route('filament.pages.kanban/{project}', ['project' => $record->id]);
                            }
                        }),
                ])->color('secondary'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SprintsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
            RelationManagers\StatusesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

}
