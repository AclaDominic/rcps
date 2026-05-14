<?php

namespace App\Helpers;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TimeLog;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

trait KanbanScrumHelper
{

    public bool $sortable = true;

    public Project|null $project = null;

    public $users = [];
    public $types = [];
    public $priorities = [];
    public $includeNotAffectedTickets = false;
    public $main_task = null;

    public bool $ticket = false;

    protected function formSchema(): array
    {
        return [
            Grid::make([
                'default' => 2,
                'md' => 6
            ])
                ->schema([
                    Select::make('users')
                        ->label(__('Owners / Responsibles'))
                        ->multiple()
                        ->options(User::all()->pluck('name', 'id')),

                    Select::make('types')
                        ->label(__('Ticket types'))
                        ->multiple()
                        ->options(TicketType::all()->pluck('name', 'id')),

                    Select::make('priorities')
                        ->label(__('Ticket priorities'))
                        ->multiple()
                        ->options(TicketPriority::all()->pluck('name', 'id')),

                    Select::make('main_task')
                        ->label(__('Main Task'))
                        ->options(function() {
                            if (!$this->project) return [];
                            return Ticket::where('project_id', $this->project->id)
                                ->whereNull('parent_ticket_id')
                                ->pluck('name', 'id');
                        })
                        ->placeholder(__('All Tasks')),

                    Toggle::make('includeNotAffectedTickets')
                        ->label(__('Show only not affected tickets'))
                        ->columnSpan(2),

                    Placeholder::make('search')
                        ->label(new HtmlString('&nbsp;'))
                        ->content(new HtmlString('
                            <button type="button"
                                    wire:click="filter" wire:loading.attr="disabled"
                                    class="bg-primary-500 px-3 py-2 text-white rounded hover:bg-primary-600
                                    disabled:bg-primary-300">
                                ' . __('Filter') . '
                            </button>
                            <button type="button"
                                    wire:click="resetFilters" wire:loading.attr="disabled"
                                    class="ml-2 bg-gray-800 px-3 py-2 text-white rounded hover:bg-gray-900
                                    disabled:bg-gray-300">
                                ' . __('Reset filters') . '
                            </button>
                        ')),
                ]),
        ];
    }

    public function getStatuses(): Collection
    {
        $query = TicketStatus::query();
        if ($this->project && $this->project->status_type === 'custom') {
            $query->where('project_id', $this->project->id);
        } else {
            $query->whereNull('project_id');
        }
        return $query->orderBy('order')
            ->get()
            ->map(function ($item) {
                $query = Ticket::query();
                if ($this->project) {
                    $query->where('project_id', $this->project->id);
                }
                $query->where('status_id', $item->id);
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'color' => $item->color,
                    'size' => $query->count(),
                    'add_ticket' => $item->is_default && auth()->user()->can('Create ticket')
                ];
            });
    }

    public function getRecords(): Collection
    {
        $query = Ticket::query();
        if ($this->project->type === 'scrum') {
            $query->where('sprint_id', $this->project->currentSprint->id);
        }
        $query->with(['project', 'owner', 'responsible', 'status', 'type', 'priority', 'epic']);
        $query->where('project_id', $this->project->id);
        if (sizeof($this->users)) {
            $query->where(function ($query) {
                return $query->whereIn('owner_id', $this->users)
                    ->orWhereIn('responsible_id', $this->users);
            });
        }
        if (sizeof($this->types)) {
            $query->whereIn('type_id', $this->types);
        }
        if (sizeof($this->priorities)) {
            $query->whereIn('priority_id', $this->priorities);
        }
        if ($this->includeNotAffectedTickets) {
            $query->whereNull('responsible_id');
        }
        if ($this->main_task) {
            $query->where(function ($q) {
                $q->where('id', $this->main_task)
                  ->orWhere('parent_ticket_id', $this->main_task);
            });
        }
        $query->where(function ($query) {
            return $query->where('owner_id', auth()->user()->id)
                ->orWhere('responsible_id', auth()->user()->id)
                ->orWhereHas('project', function ($query) {
                    return $query->where('owner_id', auth()->user()->id)
                        ->orWhereHas('users', function ($query) {
                            return $query->where('users.id', auth()->user()->id);
                        });
                });
        });
        return $query->orderBy('id', 'asc')->get()
            ->map(fn(Ticket $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->name,
                'owner' => $item->owner,
                'type' => $item->type,
                'responsible' => $item->responsible,
                'project' => $item->project,
                'status' => $item->status->id,
                'priority' => $item->priority,
                'epic' => $item->epic,
                'relations' => $item->relations,
                'isMainTask' => is_null($item->parent_ticket_id),
                'totalLoggedHours' => $item->totalLoggedSeconds ? $item->totalLoggedHours : null
            ]);
    }

    public function recordUpdated(int $record, int $newIndex, int $newStatus): void
    {
        try {
            DB::beginTransaction();

            $ticket = Ticket::with(['status', 'relations.relation', 'timeLogs'])->find($record);
            $status = TicketStatus::find($newStatus);
            $oldStatus = $ticket->status;

            if (!$ticket) {
                Filament::notify('danger', __('Ticket not found'));
                return;
            }

            // 0. State Machine Validation
            $query = TicketStatus::query();
            if ($ticket->project_id) {
                $query->where(function($q) use ($ticket) {
                    $q->where('project_id', $ticket->project_id)
                      ->orWhereNull('project_id');
                });
            }
            $projectStatuses = $query->orderBy('order', 'asc')->get();

            $isValid = false;

            // Find current status index in the sorted list
            $currentStatusIndex = $projectStatuses->search(fn($s) => $s->id == $ticket->status_id);
            
            // Incremental move (to the very next status in line)
            if ($currentStatusIndex !== false) {
                $nextStatus = $projectStatuses->get($currentStatusIndex + 1);
                if ($nextStatus && $newStatus == $nextStatus->id) {
                    $isValid = true;
                }
            }

            // Fallback to 1st or 2nd column
            if (!$isValid) {
                $firstStatus = $projectStatuses->first();
                $secondStatus = $projectStatuses->skip(1)->first();

                if ($firstStatus && $newStatus == $firstStatus->id) {
                    $isValid = true;
                } elseif ($secondStatus && $newStatus == $secondStatus->id) {
                    $isValid = true;
                }
            }

            if (!$isValid) {
                DB::rollBack();
                Filament::notify('danger', __('Invalid status transition. You can only move incrementally or fall back to the first two columns.'));
                return;
            }

            // 1. Validate dependencies FIRST (before any changes)
            if ($ticket->dependency_mode == 2 || $ticket->dependency_mode == 3) {
                $validationErrors = $this->validateDependencies($ticket, $newStatus);

                if (!empty($validationErrors)) {
                    DB::rollBack();
                    Filament::notify('danger', implode('<br>', $validationErrors));
                    return;
                }
            }

            // 2. Update ticket attributes
            $ticket->order = $newIndex;
            $ticket->status_id = $newStatus;

            // 3. Handle time tracking based on status changes
            if ($this->shouldStartTimeLog($status, $oldStatus)) {
                $this->startTimeLog($ticket);
            } elseif ($this->shouldStopTimeLog($status, $oldStatus)) {
                $this->stopTimeLog($ticket);
            }

            // 4. Save the ticket FIRST
            if ($ticket->isDirty()) {
                $ticket->save();
            }

            // 5. Calculate metrics AFTER saving (if task is completed)
            if ($this->shouldCalculateMetrics($status, $oldStatus)) {
                $this->calculateExecutionMetrics($ticket);
                $ticket->save(); // Save again with metrics
            }

            DB::commit();
            Filament::notify('success', __('Ticket updated'));

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("Ticket update failed: " . $e->getMessage());
            Filament::notify('danger', __('Update failed: ') . $e->getMessage());
        }
    }

    protected function shouldStartTimeLog($newStatus, $oldStatus): bool
    {
        return $newStatus->type === 'active' && $oldStatus->type !== 'active';
    }

    protected function shouldStopTimeLog($newStatus, $oldStatus): bool
    {
        return $oldStatus->type === 'active' && $newStatus->type !== 'active';
    }

    protected function shouldCalculateMetrics($newStatus, $oldStatus): bool
    {
        return $newStatus->type === 'completed' && $oldStatus->type !== 'completed';
    }

    protected function startTimeLog(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $existingLog = TimeLog::where('ticket_id', $ticket->id)
                ->whereNull('end_time')
                ->lockForUpdate()
                ->exists();

            if (!$existingLog) {
                TimeLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'start_time' => now(),
                    'description' => 'Work started'
                ]);
            }
        });
    }

    protected function stopTimeLog(Ticket $ticket)
    {
        try {

            $log = TimeLog::where('ticket_id', $ticket->id)
                ->whereNull('end_time')
                ->latest()
                ->first();

            if ($log) {

                // Explicitly convert to Carbon instance
                $startTime = Carbon::parse($log->start_time);
                $endTime = now();
                $minutesWorked = $startTime->diffInMinutes($endTime);
                $hours = max(0.1, round($minutesWorked / 60, 2));

                $log->update([
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                    'hours' => $hours,
                    'description' => 'Work completed'
                ]);

            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to stop time log: " . $e->getMessage());
            Filament::notify('danger', __('Update failed: ') . $e->getMessage());
        }
    }


    protected function calculateExecutionMetrics(Ticket &$ticket): void
    {
        // Re-query to get fresh time log data
        $actualHours = $ticket->timeLogs->sum('hours');

        $ticket->execution_time = round($actualHours, 2);

        // FIXED: Correct scheduling accuracy calculation
        if ($ticket->estimation > 0) {
            $deviation = abs($ticket->estimation - $actualHours);
            $maxValue = max($ticket->estimation, $actualHours, 0.1); // Prevent division by zero
            $accuracy = (1 - ($deviation / $maxValue)) * 100;
            $ticket->scheduling_accuracy = max(0, min(100, round($accuracy, 2)));
        } else {
            $ticket->scheduling_accuracy = null;
        }

        // FIXED: Task-specific resource utilization
        if ($ticket->responsible_id) {
            $ticket->resource_utilization = $this->calculateTaskResourceUtilization(
                $ticket->responsible_id,
                $actualHours,
                (float)($ticket->estimation ?? 0.0)
            );
        } else {
            $ticket->resource_utilization = null;
        }

        // ADD: Set metrics date
        $ticket->metrics_date = now();
    }

    protected function calculateTaskResourceUtilization(int $userId, float $actualHours, float $estimatedHours): float
    {
        if ($estimatedHours <= 0) {
            return 0;
        }

        // Task-specific utilization: how much of estimated time was actually used
        // 100% = used exactly estimated time
        // >100% = took longer than estimated (over-utilized)
        // <100% = finished faster than estimated (under-utilized)
        $utilization = ($actualHours / $estimatedHours) * 100;

        return min(200, round($utilization, 2)); // Cap at 200% to avoid extreme values
    }

    // KEEP this for weekly overview (but use different field name)
    protected function calculateWeeklyResourceUtilization(int $userId): float
    {
        $user = User::find($userId);
        $weeklyCapacity = $user->weekly_hours_capacity ?? 40;

        $weeklyHours = TimeLog::where('user_id', $userId)
            ->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('hours');

        return min(100, round(($weeklyHours / $weeklyCapacity) * 100, 2));
    }

    protected function validateDependencies(Ticket $ticket, int $newStatusId): array
    {
        $errors = [];
        $newStatus = TicketStatus::findOrFail($newStatusId);
        $newOrder = $newStatus->order;
        $currentStatus = $ticket->status;
        $currentOrder = $currentStatus->order;

        // 1. Hierarchy Logic: Subtask cannot be ahead of Main Task
        if ($ticket->parent_ticket_id) {
            $mainTask = Ticket::with('status')->find($ticket->parent_ticket_id);
            if ($mainTask && $mainTask->status) {
                if ($newOrder > $mainTask->status->order) {
                    $errors[] = __("Hierarchy Block: Subtask cannot move ahead of its Main Task #{$mainTask->code} ('{$mainTask->status->name}'). Move the Main Task first.");
                }
            }
        }

        // 2. Implicit Sequence for Sub-tasks (D&C Logic)
        if ($ticket->parent_ticket_id && $ticket->dependency_mode == 2) {
            $previousSubTask = Ticket::where('parent_ticket_id', $ticket->parent_ticket_id)
                ->where('id', '<', $ticket->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($previousSubTask) {
                $prevStatusOrder = $previousSubTask->status->order;
                if ($newOrder > $prevStatusOrder) {
                    $errors[] = __("Sequential Block: Task #{$ticket->code} cannot move to '{$newStatus->name}' because Task #{$previousSubTask->code} is still in '{$previousSubTask->status->name}'.");
                }
            }
        }

        // 2b. Implicit Sequence for Main Tasks (D&C Logic)
        if (!$ticket->parent_ticket_id && $ticket->dependency_mode == 2) {
            $previousMainTask = Ticket::where('project_id', $ticket->project_id)
                ->whereNull('parent_ticket_id')
                ->where('id', '<', $ticket->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($previousMainTask) {
                $prevStatusOrder = $previousMainTask->status->order;
                if ($newOrder > $prevStatusOrder) {
                    $errors[] = __("Sequential Block: Main Task #{$ticket->code} cannot move to '{$newStatus->name}' because the preceding Main Task #{$previousMainTask->code} is still in '{$previousMainTask->status->name}'.");
                }
            }
        }

        // 3. Validate manual relations
        foreach ($ticket->relations as $relation) {
            if (!$relation->relation || !$relation->relation->status) {
                continue;
            }
            
            if ($relation->type === 'depends_on') {
                $depStatus = $relation->relation->status;
                $depOrder = $depStatus->order;
                
                if ($newOrder > $depOrder) {
                    $errors[] = __("Dependency Block: Prerequisite ticket #{$relation->relation->code} is only in column '{$relation->relation->status->name}'.");
                }
            }
        }

        // 4. Handle Backward Cascade (Pull subtasks back if Main moves back)
        if ($newOrder < $currentOrder && !$ticket->parent_ticket_id) {
            $subtasks = Ticket::where('parent_ticket_id', $ticket->id)->get();
            foreach ($subtasks as $sub) {
                if ($sub->status->order > $newOrder) {
                    $sub->status_id = $newStatusId;
                    $sub->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('Hierarchy Cascaded'))
                        ->body(__("Subtask #{$sub->code} pulled back to '{$newStatus->name}' to follow Main Task"))
                        ->warning()
                        ->send();
                }
            }
        }

        // 5. Handle Backward Cascade (D&C Implicit Sequence)
        if ($newOrder < $currentOrder && $ticket->parent_ticket_id) {
            $followers = Ticket::where('parent_ticket_id', $ticket->parent_ticket_id)
                ->where('id', '>', $ticket->id)
                ->get();

            foreach ($followers as $follower) {
                if ($follower->status->order > $newOrder) {
                    $follower->status_id = $newStatusId;
                    $follower->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('Sequence Cascaded'))
                        ->body(__("Task #{$follower->code} pulled back to '{$newStatus->name}' to follow #{$ticket->code}"))
                        ->warning()
                        ->send();
                }
            }
        }

        // 5b. Handle Backward Cascade (D&C Implicit Sequence for Main Tasks)
        if ($newOrder < $currentOrder && !$ticket->parent_ticket_id && $ticket->dependency_mode == 2) {
            $followers = Ticket::where('project_id', $ticket->project_id)
                ->whereNull('parent_ticket_id')
                ->where('id', '>', $ticket->id)
                ->get();

            foreach ($followers as $follower) {
                if ($follower->status->order > $newOrder) {
                    $follower->status_id = $newStatusId;
                    $follower->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('Main Sequence Cascaded'))
                        ->body(__("Main Task #{$follower->code} pulled back to '{$newStatus->name}' to follow #{$ticket->code}"))
                        ->warning()
                        ->send();
                }
            }
        }

        // 6. Handle Backward Cascade (Manual Relations)
        if ($newOrder < $currentOrder) {
            $dependents = $ticket->dependents()
                ->with('ticket.status')
                ->get();

            foreach ($dependents as $relation) {
                if (!$relation->ticket || !$relation->ticket->status) continue;
                
                $dependentTicket = $relation->ticket;
                $dependentOrder = $dependentTicket->status->order;
                
                if ($dependentOrder > $newOrder) {
                    $dependentTicket->status_id = $newStatusId;
                    $dependentTicket->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('Dependency Cascaded'))
                        ->body(__("Dependent Ticket #{$dependentTicket->code} moved back to '{$newStatus->name}'"))
                        ->warning()
                        ->send();
                }
            }
        }

        return $errors;
    }


    public function isMultiProject(): bool
    {
        return $this->project === null;
    }

    public function filter(): void
    {
        $this->getRecords();
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->filter();
    }

    public function createTicket(): void
    {
        $this->ticket = true;
    }

    public function closeTicketDialog(bool $refresh): void
    {
        $this->ticket = false;
        if ($refresh) {
            $this->filter();
        }
    }

    protected function kanbanHeading(): string|Htmlable
    {
        $heading = '<div class="w-full flex flex-col gap-1">';
        $heading .= '<a href="' . route('filament.pages.board') . '"
                            class="text-primary-500 text-xs font-medium hover:underline">';
        $heading .= __('Back to board');
        $heading .= '</a>';
        $heading .= '<div class="flex flex-col gap-1">';
        $heading .= '<span>' . __('Kanban');
        if ($this->project) {
            $heading .= ' - ' . $this->project->name . '</span>';
        } else {
            $heading .= '</span><span class="text-xs text-gray-400">'
                . __('Only default statuses are listed when no projects selected')
                . '</span>';
        }
        $heading .= '</div>';
        $heading .= '</div>';
        return new HtmlString($heading);
    }

    protected function scrumHeading(): string|Htmlable
    {
        $heading = '<div class="w-full flex flex-col gap-1">';
        $heading .= '<a href="' . route('filament.pages.board') . '"
                            class="text-primary-500 text-xs font-medium hover:underline">';
        $heading .= __('Back to board');
        $heading .= '</a>';
        $heading .= '<div class="flex flex-col gap-1">';
        $heading .= '<span>' . __('Scrum');
        if ($this->project) {
            $heading .= ' - ' . $this->project->name . '</span>';
        } else {
            $heading .= '</span><span class="text-xs text-gray-400">'
                . __('Only default statuses are listed when no projects selected')
                . '</span>';
        }
        $heading .= '</div>';
        $heading .= '</div>';
        return new HtmlString($heading);
    }

    protected function scrumSubHeading(): string|Htmlable|null
    {
        if ($this->project?->currentSprint) {
            return new HtmlString(
                '<div class="w-full flex flex-col gap-1">'
                . '<div class="w-full flex items-center gap-2">'
                . '<span class="bg-danger-500 px-2 py-1 rounded text-white text-sm">'
                . $this->project->currentSprint->name
                . '</span>'
                . '<span class="text-xs text-gray-400">'
                . __('Started at:') . ' ' . $this->project->currentSprint->started_at->format(__('Y-m-d')) . ' - '
                . __('Ends at:') . ' ' . $this->project->currentSprint->ends_at->format(__('Y-m-d')) . ' - '
                . ($this->project->currentSprint->remaining ?
                    (
                        __('Remaining:') . ' ' . $this->project->currentSprint->remaining . ' ' . __('days'))
                    : ''
                )
                . '</span>'
                . '</div>'
                . ($this->project->nextSprint ? '<span class="text-xs text-primary-500 font-medium">'
                    . __('Next sprint:') . ' ' . $this->project->nextSprint->name . ' - '
                    . __('Starts at:') . ' ' . $this->project->nextSprint->starts_at->format(__('Y-m-d'))
                    . ' (' . __('in') . ' ' . $this->project->nextSprint->starts_at->diffForHumans() . ')'
                    . '</span>'
                    . '</span>' : '')
                . '</div>'
            );
        } else {
            return null;
        }
    }

}
