@php
    // Use a stable ID instead of uniqid() to prevent Livewire re-renders from breaking the modal
    $overallModalId = 'overall-computation-modal-stable';
@endphp

<div x-data="{
    overall_preview: null,
    project_name: '',
    start_date: '',
    end_date: '',
    isLoading: false,
    hasAnyGeneratedTasks: false,
    activeTab: 'divide_conquer',
    selectedTask: null,
    showTaskDetails: false,

    init() {
        this.updateOverall();
        // Update more frequently to reflect AI generation progress
        this.interval = setInterval(() => this.updateOverall(), 1000);
    },

    closeModal() {
        this.$dispatch('close-modal', { id: '{{$overallModalId}}' });
    },

    updateOverall() {
        const components = window.Livewire.all();
        let targetComponent = null;

        for (const id in components) {
            const component = components[id];
            const formData = component.get('data');
            // Check if this is the project creation/edit form
            if (formData && (formData.name || formData.add_task)) {
                targetComponent = component;
                this.project_name = formData.name || 'Untitled Project';
                this.start_date = formData.start_date;
                this.end_date = formData.end_date;
                break;
            }
        }

        if (!targetComponent) return;

        const aiResults = targetComponent.get('aiResults') || {};
        const taskUuids = Object.keys(aiResults);

        if (taskUuids.length === 0) {
            this.hasAnyGeneratedTasks = false;
            return;
        }

        // Check if any of them actually have tasks
        let hasTasks = false;
        taskUuids.forEach(uuid => {
            if (aiResults[uuid].divide_conquer || aiResults[uuid].greedy) {
                hasTasks = true;
            }
        });

        if (!hasTasks) {
            this.hasAnyGeneratedTasks = false;
            return;
        }

        this.hasAnyGeneratedTasks = true;

        // Aggregate data
        let totalDCHours = 0;
        let totalGreedyHours = 0;
        let totalDCTasks = 0;
        let totalGreedyTasks = 0;
        
        let allDCTasks = [];
        let allGreedyTasks = [];

        taskUuids.forEach(uuid => {
            const result = aiResults[uuid];
            if (result.divide_conquer) {
                const dcTasks = result.divide_conquer;
                allDCTasks = allDCTasks.concat(dcTasks);
                totalDCHours += dcTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0);
                totalDCTasks += dcTasks.length;
            }
            if (result.greedy) {
                const greedyTasks = result.greedy;
                allGreedyTasks = allGreedyTasks.concat(greedyTasks);
                totalGreedyHours += greedyTasks.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0);
                totalGreedyTasks += greedyTasks.length;
            }
        });

        this.overall_preview = {
            project_name: this.project_name,
            start_date: this.formatDate(this.start_date),
            end_date: this.formatDate(this.end_date),
            algorithms: {
                divide_conquer: {
                    tasks: allDCTasks,
                    total_tasks: totalDCTasks,
                    total_hours: totalDCHours,
                    day_required: Math.ceil(totalDCHours / 8)
                },
                greedy: {
                    tasks: allGreedyTasks,
                    total_tasks: totalGreedyTasks,
                    total_hours: totalGreedyHours,
                    day_required: Math.ceil(totalGreedyHours / 8)
                }
            }
        };
    },

    formatDate(dateString) {
        if (!dateString) return 'Not set';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        } catch (e) { return dateString; }
    },

    openOverall() {
        this.updateOverall();
        if (!this.hasAnyGeneratedTasks) {
            alert('Please generate computation for at least one task first to see overall results.');
            return;
        }
        this.$dispatch('open-modal', { id: '{{$overallModalId}}' });
    },

    // Helper methods for metrics (adapted from ai-button.blade.php)
    getDividerHours() { return this.overall_preview?.algorithms?.divide_conquer?.total_hours || 0; },
    getGreedyHours() { return this.overall_preview?.algorithms?.greedy?.total_hours || 0; },
    
    getDividerWidth() {
        const total = this.getDividerHours() + this.getGreedyHours();
        return total === 0 ? '0%' : Math.min(100, (this.getDividerHours() / total) * 100) + '%';
    },
    
    getGreedyWidth() {
        const total = this.getDividerHours() + this.getGreedyHours();
        return total === 0 ? '0%' : Math.min(100, (this.getGreedyHours() / total) * 100) + '%';
    },

    getTimeDifferenceText() {
        const diff = this.getGreedyHours() - this.getDividerHours();
        const absDiff = Math.abs(diff).toFixed(1);
        if (diff > 0) return '+' + absDiff + 'h (Greedy slower)';
        if (diff < 0) return '-' + absDiff + 'h (Greedy faster)';
        return '0.0h (Same duration)';
    },

    getTimeDifferenceClass() {
        const diff = this.getGreedyHours() - this.getDividerHours();
        if (diff > 0) return 'text-red-600 font-bold';
        if (diff < 0) return 'text-green-600 font-bold';
        return 'text-gray-600 font-bold';
    },

    isDivideConquerRecommended() {
        const dcHours = this.getDividerHours();
        const greedyHours = this.getGreedyHours();
        return dcHours <= greedyHours; // Simple logic for overall
    },

    getUserAssignments() {
        const algorithm = this.activeTab;
        const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
        const assignments = {};
        tasks.forEach(task => {
            const id = task.responsible_id;
            if (!id) return;
            if (!assignments[id]) {
                assignments[id] = {
                    name: task.responsible_name || 'Unknown',
                    role: task.target_role_name || 'Assigned User',
                    tasks: []
                };
            }
            assignments[id].tasks.push(task);
        });
        return Object.values(assignments);
    },

    getPriorityClass(priorityId) {
        if (priorityId == 1) return 'bg-red-100 text-red-800';
        if (priorityId == 2) return 'bg-yellow-100 text-yellow-800';
        return 'bg-green-100 text-green-800';
    },

    getPriorityText(priorityId) {
        if (priorityId == 1) return 'High';
        if (priorityId == 2) return 'Medium';
        return 'Low';
    },

    getRiskClass(riskLevel) {
        if (riskLevel === 'low') return 'bg-green-100 text-green-800';
        if (riskLevel === 'medium') return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    },

    getHoursClass(hours) {
        if (hours <= 2) return 'text-green-600';
        if (hours > 2 && hours <= 4) return 'text-yellow-600';
        return 'text-red-600';
    }
}">
    <!-- Button Container -->
    <div class="flex justify-center py-8 border-t border-gray-100 mt-6">
        <button 
            type="button" 
            @click="openOverall()"
            class="px-10 py-5 rounded-2xl transition-all duration-300 flex items-center justify-center gap-4 min-w-[320px] transform hover:-translate-y-1 active:scale-95 shadow-xl hover:shadow-2xl border-none"
            :class="hasAnyGeneratedTasks 
                ? 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white cursor-pointer' 
                : 'bg-gray-100 text-gray-400 cursor-not-allowed opacity-80 shadow-none hover:transform-none'"
        >
            <div class="p-2 rounded-xl" :class="hasAnyGeneratedTasks ? 'bg-white/20' : 'bg-gray-200/50'">
                <svg class="w-7 h-7" :class="hasAnyGeneratedTasks ? 'text-white' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
            </div>
            <div class="flex flex-col items-start text-left">
                <span class="text-xl font-bold leading-tight" :class="hasAnyGeneratedTasks ? 'text-white' : 'text-gray-500'">Overall Computation</span>
                <span class="text-[10px] font-bold uppercase tracking-widest" :class="hasAnyGeneratedTasks ? 'text-blue-100' : 'text-gray-400'" x-show="hasAnyGeneratedTasks">View Project Analysis</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400" x-show="!hasAnyGeneratedTasks">Generate tasks first</span>
            </div>
        </button>
    </div>

    <!-- OVERALL COMPUTATION MODAL -->
    <x-filament::modal id="{{$overallModalId}}" width="7xl" :scrollable="true">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Overall Project Computation</h2>
                    <p class="text-sm text-gray-500">Aggregated analysis across all main tasks</p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-8 py-4">
            <!-- Project Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Project Name Card -->
                <div class="p-5 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Project Name</p>
                            <p class="text-lg font-black text-blue-900 dark:text-blue-100 truncate max-w-[180px]" x-text="project_name || 'Untitled Project'"></p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Timeline Card -->
                <div class="p-5 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Timeline</p>
                            <div class="text-sm font-black text-blue-900 dark:text-blue-100 flex flex-col">
                                <span x-text="overall_preview?.start_date || 'Not set'"></span>
                                <span class="text-[10px] text-blue-400 my-0.5 text-center">↓ UNTIL ↓</span>
                                <span x-text="overall_preview?.end_date || 'Not set'"></span>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Aggregated Tasks Card -->
                <div class="p-5 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Aggregated Tasks</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-blue-900 dark:text-blue-100" x-text="overall_preview?.algorithms?.divide_conquer?.total_tasks || 0"></span>
                                <span class="text-xs font-bold text-blue-500 uppercase">Subtasks</span>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Algorithm Comparison Summary -->
            <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Efficiency Comparison
                </h3>
                
                <div class="space-y-8">
                    <!-- Progress Bar Comparison -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-end mb-2">
                            <div>
                                <span class="text-sm font-semibold text-blue-600 uppercase tracking-wider">Divide & Conquer</span>
                                <div class="text-2xl font-black text-gray-900 dark:text-white" x-text="getDividerHours() + 'h'"></div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-green-600 uppercase tracking-wider">Greedy Algorithm</span>
                                <div class="text-2xl font-black text-gray-900 dark:text-white" x-text="getGreedyHours() + 'h'"></div>
                            </div>
                        </div>
                        
                        <div class="relative h-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
                            <div class="h-full bg-blue-500 transition-all duration-1000 ease-out" :style="{ width: getDividerWidth() }"></div>
                            <div class="h-full bg-green-500 transition-all duration-1000 ease-out" :style="{ width: getGreedyWidth() }"></div>
                        </div>
                        
                        <div class="flex justify-center">
                            <div class="bg-white dark:bg-gray-800 px-4 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm text-sm">
                                Difference: <span :class="getTimeDifferenceClass()" x-text="getTimeDifferenceText()"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation Box -->
                    <div class="p-5 rounded-xl border-2 transition-all duration-500" 
                         :class="isDivideConquerRecommended() ? 'border-blue-200 bg-blue-50/50' : 'border-green-200 bg-green-50/50'">
                        <div class="flex items-start gap-4">
                            <div class="p-2 rounded-lg" :class="isDivideConquerRecommended() ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900" x-text="isDivideConquerRecommended() ? 'Divide & Conquer Recommended' : 'Greedy Algorithm Recommended'"></h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    Based on the overall project scope, the <span class="font-bold" x-text="isDivideConquerRecommended() ? 'Divide & Conquer' : 'Greedy'"></span> 
                                    approach provides better time efficiency and workload distribution across all tasks.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Tabs -->
            <div class="mt-8">
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-xl mb-6">
                    <button type="button" @click="activeTab = 'divide_conquer'" 
                            :class="activeTab === 'divide_conquer' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-lg text-sm transition-all duration-200">Divide & Conquer</button>
                    <button type="button" @click="activeTab = 'greedy'" 
                            :class="activeTab === 'greedy' ? 'bg-white dark:bg-gray-700 shadow-sm text-green-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-lg text-sm transition-all duration-200">Greedy Algorithm</button>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    <!-- Task List Table -->
                    <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-2xl">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-xs font-bold">
                                <tr>
                                    <th class="px-6 py-4">Task Title</th>
                                    <th class="px-6 py-4">Responsible</th>
                                    <th class="px-6 py-4">Priority</th>
                                    <th class="px-6 py-4">Risk</th>
                                    <th class="px-6 py-4">Est. Hours</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="(task, index) in overall_preview?.algorithms?.[activeTab]?.tasks || []" :key="index">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white" x-text="task.title"></td>
                                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold" x-text="task.responsible_name?.charAt(0)"></div>
                                                <span x-text="task.responsible_name"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black tracking-wider" 
                                                  :class="getPriorityClass(task.priority_id)" 
                                                  x-text="getPriorityText(task.priority_id)"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black tracking-wider" 
                                                  :class="getRiskClass(task.risk_level)" 
                                                  x-text="task.risk_level"></span>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-base" :class="getHoursClass(task.estimated_hours)" x-text="task.estimated_hours + 'h'"></td>
                                    </tr>
                                </template>
                                
                                <template x-if="!(overall_preview?.algorithms?.[activeTab]?.tasks?.length > 0)">
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                            No tasks generated for this algorithm yet.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- User Workload Summary -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-6">
                        <h4 class="text-base font-bold text-gray-800 dark:text-white mb-4">Workload Distribution (Overall)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="user in getUserAssignments()" :key="user.name">
                                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold" x-text="user.name.charAt(0)"></div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900 dark:text-white" x-text="user.name"></div>
                                            <div class="text-xs text-gray-500" x-text="user.role"></div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-end">
                                        <span class="text-xs text-gray-500">Tasks: <span class="font-bold text-gray-700" x-text="user.tasks.length"></span></span>
                                        <span class="text-sm font-black text-indigo-600" x-text="user.tasks.reduce((sum, t) => sum + parseFloat(t.estimated_hours), 0).toFixed(1) + 'h'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <button 
                    type="button"
                    @click="closeModal()"
                    class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition-all duration-200 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close Analysis
                </button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
