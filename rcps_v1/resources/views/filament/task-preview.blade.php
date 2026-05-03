<div class="p-2 border rounded bg-gray-50 mt-2 text-sm">
    @php
        $timePerSubtask = $ai_subtask_count > 0 ? $estimated_time / $ai_subtask_count : 0;
    @endphp
    <p><b>Main Task:</b> {{ $main_task_name }}</p>
    <p><b>Estimated Time:</b> {{ $estimated_time }} hrs</p>
    <p><b>Subtasks:</b> {{ $ai_subtask_count }}</p>
    <p><b>Time per Subtask:</b> {{ round($timePerSubtask, 2) }} hrs</p>
</div>