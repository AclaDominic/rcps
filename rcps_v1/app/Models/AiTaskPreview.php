<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTaskPreview extends Model
{
    protected $fillable = [
        'task_uuid',
        'project_name',
        'start_date',
        'end_date',
        'main_task_name', 
        'main_task_description',
        'ai_subtask_count',
        'ai_description',
        'generated_tasks',
        'total_hours',
        'session_id',
        'user_id',
        'complexity_data',
        'comparative_data',
        'algorithm_mode'
    ];

    protected $casts = [
        'generated_tasks' => 'array',
        'comparative_data' => 'array'
    ];

    // Scope for temporary tasks (session-based)
    public function scopeTemporary($query, $sessionId = null)
    {
        return $query->where('session_id', $sessionId ?? session()->getId());
    }

    // Scope for user's tasks
    public function scopeForUser($query, $userId = null)
    {
        return $query->where('user_id', $userId ?? auth()->id());
    }

    // Get task by UUID
    public function scopeForTask($query, $taskUuid)
    {
        return $query->where('task_uuid', $taskUuid);
    }

    // Clean up old temporary tasks
    public function scopeCleanupOld($query, $days = 1)
    {
        return $query->where('created_at', '<', now()->subDays($days));
    }
}