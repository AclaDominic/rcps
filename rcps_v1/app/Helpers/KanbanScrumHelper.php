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
        return $query->get()
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

            // 1. Validate dependencies FIRST (before any changes)
            if ($ticket->dependency_mode == 2) {
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
                $ticket->estimation
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
        $currentStatus = $ticket->status;
        
        $newOrder = $newStatus->order;

        // 1. Validate forward movement (dependencies must be at least at the same column)
        foreach ($ticket->relations as $relation) {
            if (!$relation->relation || !$relation->relation->status) {
                continue;
            }
            
            if ($relation->type === 'depends_on') {
                $depStatus = $relation->relation->status;
                $depOrder = $depStatus->order;
                
                if ($newOrder > $depOrder) {
                    $errors[] = __("Cannot move to {$newStatus->name}: Prerequisite ticket #{$relation->relation->code} is only in column {$depOrder}");
                }
            }
        }

        // 2. Validate order changes
        if ($ticket->isDirty('order')) {
            foreach ($ticket->relations as $relation) {
                if (!$relation->relation)
                    continue;

                if ($relation->type === 'depends_on' && $ticket->order < $relation->relation->order) {
                    $errors[] = __("Cannot reorder: Must come after ticket #{$relation->relation->code}");
                }
            }
        }

        // 3. Handle cascading updates for dependents when moving back
        // If current ticket is moving to a lower column (order), dependents cannot exceed it.
        $newOrder = $newStatus->order;
        $currentOrder = $currentStatus->order;

        if ($newOrder < $currentOrder) {
            $dependents = $ticket->dependents()
                ->with('ticket.status')
                ->get();

            foreach ($dependents as $relation) {
                if (!$relation->ticket || !$relation->ticket->status) continue;
                
                $dependentTicket = $relation->ticket;
                $dependentOrder = $dependentTicket->status->order;
                
                // If the dependent ticket is in a column "above" (higher order) than the new position of task a,
                // pull it back to the exact same column as task a.
                if ($dependentOrder > $newOrder) {
                    $dependentTicket->status_id = $newStatus->id;
                    $dependentTicket->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Status Cascaded')
                        ->body(__("Moved dependent Ticket #{$dependentTicket->code} to {$newStatus->name}"))
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
