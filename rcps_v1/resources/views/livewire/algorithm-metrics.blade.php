{{-- resources/views/livewire/algorithm-metrics.blade.php --}}
<div class="p-6 space-y-6">
    <!-- Project Selector -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Algorithm Performance Dashboard</h2>
        
        <select wire:model="selectedProjectName" wire:change="loadMetrics" 
                class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select Project</option>
            @foreach($projects as $group)
                <option value="{{ $group['key'] }}">
                    {{ $group['name'] }} 
                </option>
            @endforeach
        </select>
    </div>

    @if($selectedProjectName && !empty($metrics))
    <!-- Primary Algorithm Card -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h3 class="text-lg font-semibold mb-4">Primary Algorithm Used</h3>
        <div class="flex items-center space-x-4">
            <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium 
                {{ $metrics['primary_algorithm'] == 'Divide & Conquer' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                <strong>{{ $metrics['primary_algorithm'] }}</strong>
            </span>
            <div class="text-sm text-gray-600">
               <span>Used in {{ $metrics['primary_algorithm_percentage'] }}% of tasks</span>
            </div>
        </div>
    </div>

    <!-- Algorithm Distribution -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h3 class="text-lg font-semibold mb-4">Algorithm Distribution</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($metrics['algorithm_distribution'] as $algo)
            <div class="flex items-center justify-between p-3 border rounded-lg {{ !$algo['exists'] ? 'bg-gray-50 opacity-75' : '' }}">
                <div class="flex items-center space-x-2">
                    <span class="font-medium">{{ $algo['algorithm'] }}</span>
                    @if(!$algo['exists'])
                        <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">Not used</span>
                    @endif
                </div>
                <div class="flex items-center space-x-2">
                    @if($algo['exists'])
                        <span class="text-sm text-gray-600">{{ $algo['count'] }} tasks</span>
                        <span class="text-sm font-semibold text-blue-600">{{ $algo['percentage'] }}%</span>
                    @else
                        <span class="text-sm text-gray-500">0 tasks</span>
                        <span class="text-sm font-semibold text-gray-400">0%</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h3 class="text-lg font-semibold mb-4">Performance Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 border rounded-lg">
                <div class="text-2xl font-bold {{ $metrics['avg_execution_time'] < 1 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($metrics['avg_execution_time'], 2) }}x
                </div>
                <div class="text-sm text-gray-600">Execution Time</div>
            </div>
            <div class="text-center p-4 border rounded-lg">
                <div class="text-2xl font-bold text-purple-600">
                    {{ number_format($metrics['avg_utilization'], 1) }}%
                </div>
                <div class="text-sm text-gray-600">Resource Utilization</div>
            </div>
            <div class="text-center p-4 border rounded-lg">
                <div class="text-2xl font-bold {{ $metrics['avg_accuracy'] > 80 ? 'text-green-600' : 'text-orange-600' }}">
                    {{ number_format($metrics['avg_accuracy'], 1) }}%
                </div>
                <div class="text-sm text-gray-600">Scheduling Accuracy</div>
            </div>
        </div>
    </div>

    <!-- Algorithm Comparison -->
    <div class="bg-white rounded-lg shadow border p-6">
    <h3 class="text-lg font-semibold mb-4">Algorithm Performance Comparison</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($metrics['algorithm_metrics'] as $algoMetrics)
        <div class="border rounded-lg p-4 {{ !$algoMetrics['exists'] ? 'bg-gray-50 opacity-75' : '' }}">
            <h4 class="font-semibold text-lg mb-3">{{ $algoMetrics['algorithm'] }}</h4>
            
            @if(!$algoMetrics['exists'])
                <!-- Reminder for non-existing algorithms -->
                <div class="mb-3 p-2 bg-yellow-100 border border-yellow-300 rounded text-sm text-yellow-800">
                    ⓘ {{ $algoMetrics['message'] }}
                </div>
            @endif
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>Tasks:</span>
                    <span>
                        @if($algoMetrics['exists'])
                            {{ $algoMetrics['completed_tasks'] }}/{{ $algoMetrics['total_tasks'] }} completed
                        @else
                            <span class="text-gray-500">No data</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Execution Time:</span>
                    <span class="{{ $algoMetrics['avg_execution'] < 1 ? 'text-green-600' : 'text-red-600' }}">
                        @if($algoMetrics['exists'])
                            {{ number_format($algoMetrics['avg_execution'], 2) }}x
                        @else
                            <span class="text-gray-500">-</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Utilization:</span>
                    <span>
                        @if($algoMetrics['exists'])
                            {{ number_format($algoMetrics['avg_utilization'], 1) }}%
                        @else
                            <span class="text-gray-500">-</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Accuracy:</span>
                    <span class="{{ $algoMetrics['avg_accuracy'] > 80 ? 'text-green-600' : 'text-orange-600' }}">
                        @if($algoMetrics['exists'])
                            {{ number_format($algoMetrics['avg_accuracy'], 1) }}%
                        @else
                            <span class="text-gray-500">-</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
    @else
    <div class="bg-white rounded-lg shadow border p-12 text-center">
        <div class="text-gray-500 text-lg">Select a project to view algorithm metrics</div>
    </div>
    @endif
</div>