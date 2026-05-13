<?php

namespace App\Models;

use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusUpdated;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ticket extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $casts = [
        'estimation' => 'float',
        'execution_time' => 'float',
        'scheduling_accuracy' => 'float',
        'metadata'=> 'array'
    ];

    protected $fillable = [
        'name', 'content', 'owner_id', 'responsible_id',
        'status_id', 'project_id', 'code', 'order', 'type_id',
        'priority_id', 'estimation', 'epic_id', 'sprint_id',
        'dependency_mode', 'execution_time', 'resource_utilization',
        'scheduling_accuracy', 'metrics_date','main_task_id','parent_ticket_id',
        'start_date','due_date', 'metadata', 'assignment_assessment'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Ticket $item) {
            $date = Carbon::now();

            $lastRecord = Ticket::whereYear('created_at', $date->format('Y'))->max('id');

            $lastTwoDigitsOfYear = $date->format('y');

            $filler = str_pad($lastRecord + 1, 5, '0', STR_PAD_LEFT);

            $item->code =  $item->type->name.'-'.$lastTwoDigitsOfYear . $filler;

        });

        static::created(function (Ticket $item) {
            TicketRelation::where('ticket_id', $item->id)
                ->whereNull('relation_id')
                ->delete();
            if ($item->sprint_id && $item->sprint->epic_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => $item->sprint->epic_id]);
            }
            foreach ($item->watchers as $user) {
                $user->notify(new TicketCreated($item));
            }
        });

        static::updating(function (Ticket $item) {
            $old = Ticket::where('id', $item->id)->first();

            // Ticket activity based on status
            $oldStatus = $old->status_id;
            if ($oldStatus != $item->status_id) {
                TicketActivity::create([
                    'ticket_id' => $item->id,
                    'old_status_id' => $oldStatus,
                    'new_status_id' => $item->status_id,
                    'user_id' => auth()->user()->id
                ]);
                foreach ($item->watchers as $user) {
                    $user->notify(new TicketStatusUpdated($item));
                }
            }

            // Ticket sprint update
            $oldSprint = $old->sprint_id;
            if ($oldSprint && !$item->sprint_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => null]);
            }
            $sprint = $item->sprint()->withTrashed()->first();
            if ($item->sprint_id && $sprint && $sprint->epic_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => $sprint->epic_id]);
            }
        });

        static::updated(function (Ticket $item) {
            if ($item->wasChanged('status_id') && $item->parent_ticket_id) {
                $mainTask = Ticket::find($item->parent_ticket_id);
                if ($mainTask) {
                    $subtasks = Ticket::where('parent_ticket_id', $mainTask->id)->with('status')->get();
                    
                    if ($subtasks->isEmpty()) return;

                    $lowestOrderSubtask = null;
                    $lowestOrder = PHP_INT_MAX;

                    foreach ($subtasks as $sub) {
                        $order = $sub->status ? $sub->status->order : 1;
                        if ($order < $lowestOrder) {
                            $lowestOrder = $order;
                            $lowestOrderSubtask = $sub;
                        }
                    }

                    $newStatusId = $mainTask->status_id;
                    if ($lowestOrderSubtask) {
                        $newStatusId = $lowestOrderSubtask->status_id;
                    }

                    if ($mainTask->status_id !== $newStatusId) {
                        $mainTask->status_id = $newStatusId;
                        $mainTask->save();
                    }
                }
            }
        });
    }

    public static function rules(): array
    {
        return [
            'estimation' => ['numeric', 'min:0.1', 'max:100'],
            'execution_time' => ['numeric', 'min:0'],
            'resource_utilization' => ['numeric', 'min:0', 'max:100']
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id', 'id')->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')->withTrashed();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'type_id', 'id')->withTrashed();
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id', 'id')->withTrashed();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class, 'ticket_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_subscribers', 'ticket_id', 'user_id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(TicketRelation::class, 'ticket_id', 'id');
    }

    public function hours(): HasMany
    {
        return $this->hasMany(TicketHour::class, 'ticket_id', 'id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class, 'ticket_id', 'id');
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class, 'epic_id', 'id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id', 'id');
    }

    public function sprints(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id', 'id');
    }

    public function watchers(): Attribute
    {
        return new Attribute(
            get: function () {
                $users = $this->project->users;
                $users->push($this->owner);
                if ($this->responsible) {
                    $users->push($this->responsible);
                }
                return $users->unique('id');
            }
        );
    }

    public function totalLoggedHours(): Attribute
    {
        return new Attribute(
            get: function () {
                $seconds = $this->hours->sum('value') * 3600;
                return CarbonInterval::seconds($seconds)->cascade()->forHumans();
            }
        );
    }

    public function totalLoggedSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value') * 3600;
            }
        );
    }

    public function totalLoggedInHours(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value');
            }
        );
    }

    public function estimationForHumans(): Attribute
    {
        return new Attribute(
            get: function () {
                return CarbonInterval::seconds($this->estimationInSeconds)->cascade()->forHumans();
            }
        );
    }

    public function estimationInSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->estimation) {
                    return null;
                }
                return $this->estimation * 3600;
            }
        );
    }

    public function estimationProgress(): Attribute
    {
        return new Attribute(
            get: function () {
                return (($this->totalLoggedSeconds ?? 0) / ($this->estimationInSeconds ?? 1)) * 100;
            }
        );
    }

    public function completudePercentage(): Attribute
    {
        return new Attribute(
            get: fn() => $this->estimationProgress
        );
    }

    public function scopeStatusType($query, $type)
    {
        return $query->whereHas('status', function($q) use ($type) {
            $q->where('type', $type);
        });
    }

    public function dependents()
    {
        return $this->hasMany(TicketRelation::class, 'relation_id')
                    ->where('type', 'depends_on')
                    ->with('ticket');
    }

}
