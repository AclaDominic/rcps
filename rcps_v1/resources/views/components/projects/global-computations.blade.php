@php
    $unique_id = uniqid();
    $globalModalId = 'global-comparative-preview-' . $unique_id;
@endphp
<div x-data="{
    isLoadingGlobal: false,
    hasGlobalTasks: false,
    global_comparative_preview: null,
    activeTab: 'divide_conquer',
    showTaskDetails: false,
    selectedTask: null,
    
    init() {
        Livewire.hook('message.processed', (message) => {
            const component = message.component;
            const globalResults = component.get('globalAiResults');
            
            if (globalResults) {
                setTimeout(() => {
                    this.processGlobalGeneratedTasks(globalResults);
                }, 50);
            }
        });
    },

    async handleGlobalGenerate() {
        this.isLoadingGlobal = true;
        try {
            await $wire.generateGlobalAiTasks();
        } catch(error) {
            console.error('Error generating global tasks', error);
        } finally {
            this.isLoadingGlobal = false;
        }
    },

    async handleGlobalPreview() {
        if (!this.hasGlobalTasks) {
            alert('Please generate computations first.');
            return;
        }
        $dispatch('open-modal', { id: '{{ $globalModalId }}' });
    },

    processGlobalGeneratedTasks(result) {
        if (!result || !result.divide_conquer || !result.greedy) return;
        
        this.hasGlobalTasks = true;
        
        const dcTasks = result.divide_conquer || [];
        const greedyTasks = result.greedy || [];

        this.global_comparative_preview = {
            project_name: 'Total Project Computation',
            algorithms: {
                divide_conquer: {
                    tasks: dcTasks,
                    total_tasks: dcTasks.length,
                    total_hours: dcTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0),
                    day_required: Math.ceil(dcTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0) / 8)
                },
                greedy: {
                    tasks: greedyTasks,
                    total_tasks: greedyTasks.length,
                    total_hours: greedyTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0),
                    day_required: Math.ceil(greedyTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0) / 8)
                }
            }
        };
    },

    getStaffBreakdown(algorithm) {
        const tasks = this.global_comparative_preview?.algorithms?.[algorithm]?.tasks || [];
        const breakdown = {};
        tasks.forEach(task => {
            const id = task.responsible_id || 'unassigned';
            const name = task.responsible_name || 'Unassigned';
            if (!breakdown[id]) {
                breakdown[id] = { name, taskCount: 0, totalHours: 0, tasks: [] };
            }
            breakdown[id].taskCount++;
            breakdown[id].totalHours += parseFloat(task.estimated_hours || 0);
            breakdown[id].tasks.push(task);
        });
        return Object.values(breakdown).sort((a, b) => b.totalHours - a.totalHours);
    },

    getUserAssignments() {
        const algorithm = this.activeTab === 'comparison' ? 'divide_conquer' : this.activeTab;
        const tasks = this.global_comparative_preview?.algorithms?.[algorithm]?.tasks || [];
        const assignments = {};
        
        tasks.forEach(task => {
            const id = task.responsible_id;
            if (!id) return;
            
            if (!assignments[id]) {
                assignments[id] = {
                    name: task.responsible_name || 'Unknown',
                    role: task.target_role_name || 'Assigned via workload balance',
                    tasks: []
                };
            }
            if (!assignments[id].tasks.some(t => t.title === task.title)) {
                assignments[id].tasks.push(task);
            }
        });
        
        return Object.values(assignments);
    },
    
    getRiskClass(riskLevel) {
        if (riskLevel === 'low') return 'bg-green-100 text-green-800';
        if (riskLevel === 'medium') return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    },

    getRiskText(riskLevel) {
        if (!riskLevel) return 'Medium';
        return riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1);
    }
}" class="relative">

    <!-- The Buttons - Positioned beside Add Another Main Task via absolute positioning or negative margin -->
    <!-- The Filament Repeater add button has a margin bottom. We can use negative margin-top to align -->
    <div class="flex gap-3 justify-end lg:absolute lg:right-0 lg:-top-11 z-10 w-full sm:w-auto mt-4 lg:mt-0">
        
        <x-filament::button 
            type="button" 
            color="warning" 
            size="md"
            icon="heroicon-o-cpu-chip"
            @click="handleGlobalGenerate"
        >
            <span x-show="!isLoadingGlobal">Total Generate Computation</span>
            <span x-show="isLoadingGlobal" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing Project...
            </span>
        </x-filament::button>

        <x-filament::button 
            type="button" 
            color="success" 
            size="md"
            icon="heroicon-o-chart-bar"
            x-bind:disabled="!hasGlobalTasks"
            @click="handleGlobalPreview"
        >
            Total Preview Computation
        </x-filament::button>
    </div>

    <!-- Global Comparative Preview Modal -->
    <x-filament::modal id="{{ $globalModalId }}" width="7xl">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">
                        Total Project Computation Preview
                    </h2>
                    <p class="text-sm font-normal text-gray-500 mt-1">
                        Comprehensive overview of all tasks across the entire project
                    </p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6" x-show="global_comparative_preview">
            <!-- Tabs Navigation -->
            <div class="flex space-x-1 bg-gray-100 p-1 rounded-xl">
                <button @click="activeTab = 'divide_conquer'"
                        :class="{ 'bg-white shadow-sm text-indigo-700': activeTab === 'divide_conquer', 'text-gray-600 hover:text-gray-900 hover:bg-white/60': activeTab !== 'divide_conquer' }"
                        class="flex-1 py-2.5 px-4 rounded-lg font-medium text-sm transition-all duration-200">
                    Divide & Conquer
                </button>
                <button @click="activeTab = 'greedy'"
                        :class="{ 'bg-white shadow-sm text-indigo-700': activeTab === 'greedy', 'text-gray-600 hover:text-gray-900 hover:bg-white/60': activeTab !== 'greedy' }"
                        class="flex-1 py-2.5 px-4 rounded-lg font-medium text-sm transition-all duration-200">
                    Greedy Algorithm
                </button>
                <button @click="activeTab = 'comparison'"
                        :class="{ 'bg-white shadow-sm text-indigo-700': activeTab === 'comparison', 'text-gray-600 hover:text-gray-900 hover:bg-white/60': activeTab !== 'comparison' }"
                        class="flex-1 py-2.5 px-4 rounded-lg font-medium text-sm transition-all duration-200">
                    Overall Analysis
                </button>
            </div>

            <div x-show="activeTab === 'divide_conquer' || activeTab === 'greedy'">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                        <p class="text-sm font-medium text-indigo-600 mb-1">Total Tasks</p>
                        <p class="text-2xl font-bold text-indigo-900" x-text="global_comparative_preview?.algorithms?.[activeTab]?.total_tasks || 0"></p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                        <p class="text-sm font-medium text-purple-600 mb-1">Total Estimated Hours</p>
                        <p class="text-2xl font-bold text-purple-900" x-text="(global_comparative_preview?.algorithms?.[activeTab]?.total_hours || 0).toFixed(1) + 'h'"></p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                        <p class="text-sm font-medium text-blue-600 mb-1">Estimated Days</p>
                        <p class="text-2xl font-bold text-blue-900" x-text="(global_comparative_preview?.algorithms?.[activeTab]?.day_required || 0) + ' Days'"></p>
                    </div>
                </div>

                <!-- Global Tasks List -->
                <div class="border rounded-xl overflow-hidden shadow-sm">
                    <div class="max-h-[500px] overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Main Task</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtask</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned To</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Est. Hours</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(task, index) in global_comparative_preview?.algorithms?.[activeTab]?.tasks" :key="activeTab + '_task_' + index">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="py-3 px-4 align-top">
                                            <span class="text-sm font-medium text-gray-900" x-text="task.main_task_name || 'N/A'"></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="task.title"></div>
                                            <div class="text-xs text-gray-500 mt-1 line-clamp-2" x-html="task.description"></div>
                                        </td>
                                        <td class="py-3 px-4 align-top">
                                            <template x-if="task.responsible_name">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900" x-text="task.responsible_name"></div>
                                                    <template x-if="task.target_role_name">
                                                        <div class="text-xs text-indigo-600" x-text="task.target_role_name"></div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!task.responsible_name">
                                                <span class="text-sm text-gray-400 italic">Unassigned</span>
                                            </template>
                                        </td>
                                        <td class="py-3 px-4 align-top">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="task.estimated_hours + 'h'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Overall Analysis Tab -->
            <div x-show="activeTab === 'comparison'" class="space-y-6">
                <!-- User Assignment List -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-indigo-50">
                        <h4 class="font-semibold text-indigo-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Global User Assignment List
                        </h4>
                        <p class="text-sm text-indigo-600 mt-1">A consolidated view of all assigned users across the entire project.</p>
                    </div>
                    <div class="p-4 bg-white max-h-[500px] overflow-y-auto">
                        <div class="space-y-4">
                            <template x-for="(assignment, index) in getUserAssignments()" :key="'global_assignment_' + index">
                                <div class="border rounded-lg p-4 bg-gray-50 shadow-sm">
                                    <div class="flex items-center justify-between border-b pb-3 mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow"
                                                x-text="(assignment.name || '').substring(0,2).toUpperCase()"></div>
                                            <div>
                                                <h5 class="text-md font-bold text-gray-900" x-text="assignment.name"></h5>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800"
                                                    x-text="assignment.role"></span>
                                            </div>
                                        </div>
                                        <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1 rounded-full shadow-sm border">
                                            <span x-text="assignment.tasks.length"></span> task(s) | <span x-text="assignment.tasks.reduce((sum, t) => sum + parseFloat(t.estimated_hours || 0), 0).toFixed(1)"></span>h
                                        </div>
                                    </div>
                                    <div>
                                        <ul class="space-y-2">
                                            <template x-for="(task, tIndex) in assignment.tasks" :key="'global_assigned_task_' + index + '_' + tIndex">
                                                <li class="flex items-start text-sm bg-white p-2 rounded border">
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-2 flex-1">
                                                        <p class="font-medium text-gray-800">
                                                            <span class="text-gray-500 mr-1" x-text="task.main_task_name ? '[' + task.main_task_name + ']' : ''"></span>
                                                            <span x-text="task.title"></span>
                                                        </p>
                                                    </div>
                                                    <div class="ml-2 text-xs font-medium text-gray-500 whitespace-nowrap">
                                                        <span x-text="task.estimated_hours + 'h'"></span>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </template>
                            <template x-if="getUserAssignments().length === 0">
                                <div class="text-center py-6 text-gray-500 italic border rounded-lg bg-gray-50">
                                    No users assigned across the project yet.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::modal>
</div>
