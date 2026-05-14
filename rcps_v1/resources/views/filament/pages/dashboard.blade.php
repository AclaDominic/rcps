@php 
    $isPrivileged = auth()->user()->hasRoleType(['CORE']);
@endphp
<x-filament::page>
    <div class="grid grid-cols-1 gap-4">
         <div class="col-span-4 md:col-span-4">
            @foreach ($this->getProjectWidgets() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
    </div>
    @if ($isPrivileged)
        <x-filament::card class="mb-6">
            @livewire('dashboard-filter')
        </x-filament::card>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-12">
                        <x-filament::card class="mb-3">
                            <x-slot name="header">
                                 <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-semibold">
                                        {{ __('Execution Time') }}
                                    </h2>
                                    <span class="ml-2 text-gray-500 cursor-pointer group relative">
                                        <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 
                                                    2.21 0 4 1.343 4 3 
                                                    0 1.4-1.278 2.575-3.006 2.907
                                                    -.542.104-.994.54-.994 1.093m0 3h.01
                                                    M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>

                                        <!-- Tooltip -->
                                        <div class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg p-3 text-xs text-gray-700 hidden group-hover:block z-50">
                                           <div>
                                                <h3 class="font-bold mb-1">Guidelines</h3>
                                                <ul class="list-disc pl-4 space-y-1">
                                                    <li><b>Execution Time:</b> Represents the total accumulated hours for all project tasks.</li>
                                                    <li><b>Theorized vs Actual:</b> Compares the AI's optimized time estimation against real-world execution data.</li>
                                                    <li><b>Greedy Mode:</b> Auto-assigned tasks; measures total completion speed.</li>
                                                    <li><b>Divide & Conquer:</b> Manual dependency mode; includes coordination overhead.</li>
                                                </ul>
                                            </div>
                                            <br>
                                            <div>
                                                <h3 class="font-bold mb-1">How to Read the Chart:</h3>
                                                <ul class="list-disc pl-5 mt-1 space-y-1">
                                                    <li><b>X-Axis (Modes):</b> Greedy vs Divide & Conquer.</li>
                                                    <li><b>Y-Axis (Hours):</b> Total execution hours (theorized vs actual).</li>
                                                    <li><b>Bars:</b> Comparison of the AI's calculated efficiency against actual team performance.</li>
                                                    <li><b>Interpretation:</b> Shorter bars = faster project delivery.</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </span>
                                </div>
                            </x-slot>
                            <livewire:chart-execution-time wire:key="chart-execution-time-key" />
                        </x-filament::card>
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-12">
                        <x-filament::card class="mb-3">
                            <x-slot name="header">
                                 <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-semibold">
                                        {{ __('Scheduling Accurancy') }}
                                    </h2>
                                    <span class="ml-2 text-gray-500 cursor-pointer group relative">
                                        <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 
                                                    2.21 0 4 1.343 4 3 
                                                    0 1.4-1.278 2.575-3.006 2.907
                                                    -.542.104-.994.54-.994 1.093m0 3h.01
                                                    M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>

                                        <!-- Tooltip -->
                                        <div class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg p-3 text-xs text-gray-700 hidden group-hover:block z-50">
                                           <div>
                                                <h3 class="font-bold mb-1">Guidelines</h3>
                                                <ul class="list-disc pl-4 space-y-1">
                                                    <li><b>Scheduling Accuracy:</b> Measures how close the actual completion time is compared to the planned schedule.</li>
                                                    <li><b>Greedy Mode:</b> Tasks are assigned immediately; accuracy shows how often deadlines are met.</li>
                                                    <li><b>Divide & Conquer:</b> Dependencies are managed manually; accuracy may shift due to coordination overhead.</li>
                                                </ul>
                                            </div>
                                            <br>
                                            <div>
                                                <h3 class="font-bold mb-1">How to Read the Chart:</h3>
                                                <ul class="list-disc pl-5 mt-1 space-y-1">
                                                    <li><b>Greedy vs Divide & Conquer:</b> Each slice represents the average accuracy rate for that mode.</li>
                                                    <li><b>Closer to 100%:</b> More reliable scheduling.</li>
                                                    <li><b>Interpretation:</b> Compare which dependency mode achieves better adherence to planned schedules.</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </span>
                                </div>
                            </x-slot>
                            <livewire:chart-scheduling-accuracy wire:key="chart-scheduling-accuracy-key" />
                        </x-filament::card>
                    </div>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6">
                <x-filament::card class="mb-3">
                    <x-slot name="header">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold">
                                {{ __('Resource Utilization') }}
                            </h2>
                            <span class="ml-2 text-gray-500 cursor-pointer group relative">
                                <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 
                                            2.21 0 4 1.343 4 3 
                                            0 1.4-1.278 2.575-3.006 2.907
                                            -.542.104-.994.54-.994 1.093m0 3h.01
                                            M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <!-- Tooltip -->
                                <div class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg p-3 text-xs text-gray-700 hidden group-hover:block z-50">
                                     <div>
                                        <h3 class="font-bold mb-1">Guidelines</h3>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li><b>Resource Utilization:</b> Percentage of how much capacity (time/effort) of a user/team was consumed.</li>
                                            <li><b>Greedy Mode:</b> Auto-assigns tasks to the least loaded user, aiming for even workload distribution.</li>
                                            <li><b>Divide & Conquer:</b> Manual assignment with dependencies allowed, workload may vary by user.</li>
                                        </ul>
                                    </div>
                                    <br>
                                    <div>
                                        <h3 class="font-bold mb-1">How to Read the Charts:</h3>
                                        <ul class="list-disc pl-5 mt-1 space-y-1">
                                            <li><b>Line Chart:</b> Shows daily average resource utilization over time.</li>
                                            <li><b>X-Axis (Dates):</b> Time period (day/week/month) based on selected filter.</li>
                                            <li><b>Y-Axis (%):</b> Average utilization percentage (0–100%).</li>
                                            <li><b>Red Line (Greedy):</b> Trend of utilization under auto-assign mode.</li>
                                            <li><b>Blue Line (Divide & Conquer):</b> Trend under manual assignment mode.</li>
                                            <li><b>Bar Chart:</b> Compares the overall average utilization between the two modes.</li>
                                            <li><b>Interpretation:</b> Closer to ~70–85% means healthy workload. Near 100% = risk of overload, below 50% = underutilized.</li>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </x-slot>
                    <livewire:chart-resource-utilization wire:key="chart-resource-utilization-key" />
                </x-filament::card>
            </div>
        </div>
        
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-12">
                <x-filament::card class="mb-3">
                    <x-slot name="header">
                         <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold">
                                {{ __('Accuracy Per Ticket Over Time') }}
                            </h2>
                            <span class="ml-2 text-gray-500 cursor-pointer group relative">
                                <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 
                                            2.21 0 4 1.343 4 3 
                                            0 1.4-1.278 2.575-3.006 2.907
                                            -.542.104-.994.54-.994 1.093m0 3h.01
                                            M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <!-- Tooltip -->
                                <div class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg p-3 text-xs text-gray-700 hidden group-hover:block z-50">
                                    <div>
                                        <h3 class="font-bold mb-1">Guidelines</h3>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li><b>Estimation Accuracy:</b> Measures how close the actual execution time is to the original estimation.</li>
                                            <li><b>Greedy Mode:</b> Automatically assigns tasks to the least loaded user. No dependencies allowed.</li>
                                            <li><b>Divide & Conquer:</b> Allows you to set dependencies and manually assign users.</li>
                                        </ul>
                                    </div>
                                    <br>
                                    <div>
                                        <h3 class="font-bold mb-1">How to Read the Chart:</h3>
                                        <ul class="list-disc pl-5 mt-1 space-y-1">
                                            <li><b>X-Axis (Tickets):</b> Each point represents a ticket (by code).</li>
                                            <li><b>Y-Axis (Accuracy %):</b> Shows how close the ticket’s actual execution time was to its estimation.</li>
                                            <li><b>Greedy (Red Line):</b> Auto-assign mode performance over time.</li>
                                            <li><b>Divide & Conquer (Blue Line):</b> Manual dependency mode performance over time.</li>
                                            <li><b>Interpretation:</b> The closer the line is to 100%, the more accurate the estimations are.</li>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </x-slot>
                    
                    <livewire:chart-accuracy-per-ticket wire:key="chart-accuracy-per-ticket-key" />
                </x-filament::card>
            </div>
        </div>
        
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-12">
                <x-filament::card class="mb-3">
                    <x-slot name="header">
                         <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold">
                                {{-- {{ __('Accuracy Per Ticket Over Time') }} --}}
                            </h2>
                            <span class="ml-2 text-gray-500 cursor-pointer group relative">
                                <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 
                                            2.21 0 4 1.343 4 3 
                                            0 1.4-1.278 2.575-3.006 2.907
                                            -.542.104-.994.54-.994 1.093m0 3h.01
                                            M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <!-- Tooltip -->
                                <div class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg p-3 text-xs text-gray-700 hidden group-hover:block z-50">
                                    <div>
                                        <h3 class="font-bold mb-1">Metrics Calculation Guide</h3>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li><b>Primary Algorithm:</b> Most frequently used scheduling mode in this project</li>
                                            <li><b>Algorithm Distribution:</b> Percentage of tasks using each scheduling mode</li>
                                            <li><b>Execution Time Ratio:</b> Actual execution time ÷ baseline (1.0x = expected time)</li>
                                            <li><b>Resource Utilization:</b> Average percentage of allocated resources used (0-100%)</li>
                                            <li><b>Scheduling Accuracy:</b> How accurately tasks met their scheduled deadlines</li>
                                        </ul>
                                    </div>
                                    <br>
                                    <div>
                                        <h3 class="font-bold mb-1">Algorithm Assignment:</h3>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li><b>Greedy Mode:</b> dependency_mode = 1 (Auto-assign to least loaded user)</li>
                                            <li><b>Divide & Conquer:</b> dependency_mode = 2 (Manual assignment with dependencies)</li>
                                            <li><b>Unknown Algorithm:</b> Other dependency_mode values</li>
                                        </ul>
                                    </div>
                                    <br>
                                    <div>
                                        <h3 class="font-bold mb-1">Data Sources:</h3>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li><b>Total Tasks:</b> Count of all tickets in current project</li>
                                            <li><b>Completed Tasks:</b> Tickets with execution_time > 0</li>
                                            <li><b>Performance Metrics:</b> Averages from completed tasks only</li>
                                            <li><b>Algorithm Metrics:</b> Shows both algorithms even if not used</li>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </x-slot>
                    
                    <livewire:algorithm-metrics wire:key="algorithm-metrics-key" />
                </x-filament::card>
            </div>
        </div>
     @endif

    <div class="grid grid-cols-12 gap-2">
        <div class="col-span-3 md:col-span-3">
             @foreach ($this->getTicketsByPriority() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
        <div class="col-span-3 md:col-span-3">
             @foreach ($this->getTicketsByType() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
        <div class="col-span-6 md:col-span-6">
             @foreach ($this->getLatestProjects() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
         <div class="col-span-6 md:col-span-6">
             @foreach ($this->getLatestActivities() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
        <div class="col-span-6 md:col-span-6">
             @foreach ($this->getLatestTickets() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>
         <div class="col-span-12 md:col-span-12">
             @foreach ($this->getLatestComments() as $widget)
                @livewire($widget, [], key($widget))
            @endforeach
        </div>

    </div>

</x-filament::page>