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
    needsRecomputation: false,
    lastGeneratedData: null,
    loadingText: '',
    activeTab: 'divide_conquer',
    selectedAlgorithm: 'divide_conquer',
    selectedTask: null,
    showTaskDetails: false,
    showCandidatesModal: false,
    currentTaskForCandidates: null,
    all_users: @js($allUsers ?? []),
    selectedUser: null,
    selectedUserTasks: [],

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
        const formData = targetComponent.get('data');

        // Check for changes
        if (formData && formData.add_task) {
            const currentDataSnapshot = JSON.stringify(formData.add_task);
            if (this.lastGeneratedData && this.lastGeneratedData !== currentDataSnapshot) {
                this.needsRecomputation = true;
            }
        }

        const taskUuids = Object.keys(aiResults);

        if (taskUuids.length === 0) {
            this.hasAnyGeneratedTasks = false;
            return;
        }

        // Check if any of them actually have tasks
        let hasTasks = false;
        taskUuids.forEach(uuid => {
            if (aiResults[uuid].has_data || aiResults[uuid].divide_conquer || aiResults[uuid].greedy) {
                hasTasks = true;
            }
        });

        if (!hasTasks) {
            this.hasAnyGeneratedTasks = false;
            return;
        }

        this.hasAnyGeneratedTasks = true;
        if (!this.lastGeneratedData && formData && formData.add_task) {
            this.lastGeneratedData = JSON.stringify(formData.add_task);
        }

        // Aggregate data
        let totalDCHours = 0;
        let totalGreedyHours = 0;
        let totalDCTasks = 0;
        let totalGreedyTasks = 0;
        
        let allDCTasks = this.overall_preview?.algorithms?.divide_conquer?.tasks || [];
        let allGreedyTasks = this.overall_preview?.algorithms?.greedy?.tasks || [];

        taskUuids.forEach(uuid => {
            const result = aiResults[uuid];
            if (result.summary) {
                if (result.summary.divide_conquer) {
                    totalDCHours += result.summary.divide_conquer.total_hours || 0;
                    totalDCTasks += result.summary.divide_conquer.total_tasks || 0;
                }
                if (result.summary.greedy) {
                    totalGreedyHours += result.summary.greedy.total_hours || 0;
                    totalGreedyTasks += result.summary.greedy.total_tasks || 0;
                }
            } else {
                // Fallback for old data structure
                if (result.divide_conquer) {
                    totalDCHours += result.divide_conquer.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0);
                    totalDCTasks += result.divide_conquer.length;
                }
                if (result.greedy) {
                    totalGreedyHours += result.greedy.reduce((sum, t) => sum + (parseFloat(t.estimated_hours) || 0), 0);
                    totalGreedyTasks += result.greedy.length;
                }
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

    canGenerateAllAI() {
        const components = window.Livewire.all();
        let targetComponent = null;
        for (const id in components) {
            const component = components[id];
            const formData = component.get('data');
            if (formData && formData.name) {
                targetComponent = component;
                break;
            }
        }
        if (!targetComponent) return false;
        const formData = targetComponent.get('data');
        const tasks = formData.add_task || {};
        const taskUuids = Object.keys(tasks);
        if (taskUuids.length === 0) return false;
        
        let allValid = true;
        taskUuids.forEach(uuid => {
            const task = tasks[uuid];
            if (!task.main_task_name || !task.main_task_description) {
                allValid = false;
            }
            const subtasks = task.add_subtask || {};
            const hasSubtasks = Object.values(subtasks).some(st => st.subtask_title?.trim());
            if (!hasSubtasks) {
                allValid = false;
            }
        });
        return allValid;
    },

    async generateAllAI() {
        const components = window.Livewire.all();
        let targetComponent = null;
        for (const id in components) {
            const component = components[id];
            const formData = component.get('data');
            if (formData && formData.name) {
                targetComponent = component;
                break;
            }
        }
        if (!targetComponent) return;

        const formData = targetComponent.get('data');
        const tasks = formData.add_task || {};
        const taskUuids = Object.keys(tasks);

        if (taskUuids.length === 0) {
            alert('No tasks to generate computation for.');
            return;
        }

        if (!this.canGenerateAllAI()) {
            alert('Please fill in all required fields (Name, Description, and at least one Subtask) for all tasks.');
            return;
        }

        this.isLoading = true;
        this.loadingText = 'Generating computations for all tasks...';

        try {
            for (const uuid of taskUuids) {
                const task = tasks[uuid];
                await targetComponent.call('generateAiTasks', {
                    project_name: formData.name,
                    start_date: formData.start_date,
                    end_date: formData.end_date,
                    main_task_name: task.main_task_name,
                    main_task_description: task.main_task_description,
                    task_uuid: uuid,
                    mode: formData.algorithm_mode || 'comparison'
                });
            }
            this.lastGeneratedData = JSON.stringify(formData.add_task);
            this.needsRecomputation = false;
            this.hasAnyGeneratedTasks = true;
        } catch (error) {
            console.error('Error generating AI tasks:', error);
        } finally {
            this.isLoading = false;
            this.loadingText = '';
        }
    },

    formatDate(dateString) {
        if (!dateString) return 'Not set';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        } catch (e) { return dateString; }
    },

    async fetchFullPreviewData() {
        this.isLoading = true;
        this.loadingText = 'Loading full analysis data...';
        
        try {
            const components = window.Livewire.all();
            let targetComponent = null;
            for (const id in components) {
                if (components[id].get('data')?.name || components[id].get('data')?.add_task) {
                    targetComponent = components[id];
                    break;
                }
            }

            if (targetComponent) {
                const fullResults = await targetComponent.call('getAiResultsForPreview');
                let allDCTasks = [];
                let allGreedyTasks = [];

                Object.keys(fullResults).forEach(uuid => {
                    const res = fullResults[uuid];
                    if (res && res.divide_conquer) {
                        const dcTasksWithProject = res.divide_conquer.map(t => ({...t, project_name: this.project_name}));
                        allDCTasks = allDCTasks.concat(dcTasksWithProject);
                    }
                    if (res && res.greedy) {
                        const greedyTasksWithProject = res.greedy.map(t => ({...t, project_name: this.project_name}));
                        allGreedyTasks = allGreedyTasks.concat(greedyTasksWithProject);
                    }
                });

                if (this.overall_preview) {
                    this.overall_preview.algorithms.divide_conquer.tasks = allDCTasks;
                    this.overall_preview.algorithms.greedy.tasks = allGreedyTasks;
                }
            }
        } catch (error) {
            console.error('Error fetching preview data:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async openOverall() {
        this.updateOverall();
        if (!this.hasAnyGeneratedTasks) {
            alert('Please generate computation for at least one task first to see overall results.');
            return;
        }
        await this.fetchFullPreviewData();
        this.$dispatch('open-modal', { id: '{{$overallModalId}}' });
    },

    async openComparativeModal() {
        this.updateOverall();
        if (!this.hasAnyGeneratedTasks) {
            alert('Please generate computation first.');
            return;
        }
        await this.fetchFullPreviewData();
        this.$dispatch('open-modal', { id: 'comparative-study-modal-stable' });
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
        
        // Fallback to database tasks if no AI tasks are found for the active tab
        if (tasks.length === 0) {
            const assignments = {};
            this.all_users.forEach(user => {
                if (user.db_tasks && user.db_tasks.length > 0) {
                    assignments[user.email] = {
                        name: user.name,
                        role: user.role || 'Assigned User',
                        tasks: user.db_tasks
                    };
                }
            });
            return Object.values(assignments);
        }

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

    getUserTasks(user) {
        const alg = this.selectedAlgorithm || 'divide_conquer';
        const tasks = this.overall_preview?.algorithms?.[alg]?.tasks || [];
        const aiTasks = tasks.filter(t => t.responsible_name === user.name);
        
        // Fallback to database tasks if no AI tasks are found
        if (aiTasks.length === 0 && user.db_tasks) {
            return user.db_tasks;
        }
        
        return aiTasks;
    },

    getGroupedUserTasks() {
        const grouped = {};
        this.selectedUserTasks.forEach(task => {
            const project = task.project_name || 'No Project';
            if (!grouped[project]) {
                grouped[project] = [];
            }
            grouped[project].push(task);
        });
        return Object.keys(grouped).map(project => ({
            project: project,
            tasks: grouped[project]
        }));
    },

    openUserTasks(user) {
        this.selectedUser = user;
        this.selectedUserTasks = this.getUserTasks(user);
        this.$dispatch('open-modal', { id: 'user-tasks-modal' });
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
    <!-- Loading Overlay -->
    <div x-show="isLoading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-8 flex flex-col items-center shadow-xl">
            <!-- AI Animation -->
            <div class="relative mb-6">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="absolute -top-2 -right-2">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center animate-bounce">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Loading Text -->
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Generating Computation</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    <span x-text="loadingText"></span>
                </p>
                
                <!-- Progress Animation -->
                <div class="w-100 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full animate-progress"></div>
                </div>
                
                <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                    <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Analyzing task complexity...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Button Container -->
    <div class="flex flex-col items-center gap-4 py-8 border-t border-gray-100 mt-6">
        <button 
            type="button" 
            @click="(!hasAnyGeneratedTasks || needsRecomputation) ? generateAllAI() : openOverall()"
            class="px-10 py-5 rounded-2xl transition-all duration-300 flex items-center justify-center gap-4 min-w-[320px] transform hover:-translate-y-1 active:scale-95 shadow-xl hover:shadow-2xl border-none"
            :class="(!hasAnyGeneratedTasks || needsRecomputation)
                ? (canGenerateAllAI() ? 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed opacity-80 shadow-none hover:transform-none')
                : 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white cursor-pointer'"
        >
            <div class="p-2 rounded-xl" :class="(!hasAnyGeneratedTasks || needsRecomputation) ? (canGenerateAllAI() ? 'bg-white/20' : 'bg-gray-200/50') : 'bg-white/20'">
                <svg class="w-7 h-7" :class="(!hasAnyGeneratedTasks || needsRecomputation) ? (canGenerateAllAI() ? 'text-white' : 'text-gray-400') : 'text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
            </div>
            <div class="flex flex-col items-start text-left">
                <span class="text-xl font-bold leading-tight" 
                      :class="(!hasAnyGeneratedTasks || needsRecomputation) ? (canGenerateAllAI() ? 'text-white' : 'text-gray-500') : 'text-white'"
                      x-text="(!hasAnyGeneratedTasks || needsRecomputation) ? 'Generate Computation' : 'Overall Computation'"></span>
                
                <span class="text-[10px] font-bold uppercase tracking-widest" 
                      :class="(!hasAnyGeneratedTasks || needsRecomputation) ? (canGenerateAllAI() ? 'text-blue-100' : 'text-gray-400') : 'text-blue-100'"
                      x-show="(!hasAnyGeneratedTasks || needsRecomputation)"
                      x-text="canGenerateAllAI() ? 'Click to compute' : 'Fill all fields first'"></span>
                
                <span class="text-[10px] font-bold uppercase tracking-widest text-blue-100" 
                      x-show="hasAnyGeneratedTasks && !needsRecomputation">View Project Analysis</span>
            </div>
        </button>

        <!-- Preview Computation Study Button -->
        <button 
            type="button" 
            @click="openComparativeModal()"
            x-show="hasAnyGeneratedTasks"
            class="px-10 py-5 rounded-2xl transition-all duration-300 flex items-center justify-center gap-4 min-w-[320px] transform hover:-translate-y-1 active:scale-95 shadow-xl hover:shadow-2xl border-none bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white cursor-pointer"
        >
            <div class="p-2 rounded-xl bg-white/20">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </div>
            <div class="flex flex-col items-start text-left">
                <span class="text-xl font-bold leading-tight text-white">Preview Comparative Study</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-green-100">View detailed metrics</span>
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
                    <button type="button" @click="activeTab = 'divide_conquer'; selectedAlgorithm = 'divide_conquer'" 
                            :class="activeTab === 'divide_conquer' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-lg text-sm transition-all duration-200">Divide & Conquer</button>
                    <button type="button" @click="activeTab = 'greedy'; selectedAlgorithm = 'greedy'" 
                            :class="activeTab === 'greedy' ? 'bg-white dark:bg-gray-700 shadow-sm text-green-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-lg text-sm transition-all duration-200">Greedy Algorithm</button>
                    <button type="button" @click="activeTab = 'team'" 
                            :class="activeTab === 'team' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                            class="flex-1 py-3 rounded-lg text-sm transition-all duration-200">Team Directory</button>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    <!-- Task Tables (Visible for D&C and Greedy) -->
                    <template x-if="activeTab !== 'team'">
                        <div class="space-y-6">
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
                                                <span class="text-xs text-gray-500">Tasks: <button type="button" @click="openUserTasks(user)" class="font-bold text-indigo-600 hover:text-indigo-800 underline" x-text="user.tasks.length"></button></span>
                                                <span class="text-sm font-black text-indigo-600" x-text="user.tasks.reduce((sum, t) => sum + parseFloat(t.estimated_hours), 0).toFixed(1) + 'h'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Team Directory (Visible for team tab) -->
                    <template x-if="activeTab === 'team'">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <template x-for="user in all_users" :key="user.email">
                                    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition-shadow relative">
                                        <!-- Task Count Badge -->
                                        <div class="absolute top-4 right-4" x-show="getUserTasks(user).length > 0">
                                            <button type="button" @click="openUserTasks(user)" class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-indigo-600 rounded-full hover:bg-indigo-700 transition-colors shadow-sm" title="View assigned tasks">
                                                <span x-text="getUserTasks(user).length"></span>
                                            </button>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-xl" x-text="user.name.charAt(0)"></div>
                                            <div class="flex-1 min-w-0">
                                                <h5 class="text-sm font-black text-gray-900 dark:text-white truncate" x-text="user.name"></h5>
                                                <p class="text-xs text-gray-500 truncate" x-text="user.email"></p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex items-center justify-between">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300" x-text="user.role"></span>
                                            <div class="flex gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-if="all_users.length === 0">
                                <div class="text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-dashed border-gray-200">
                                    <p class="text-gray-500 italic">No users found in the directory.</p>
                                </div>
                            </template>
                        </div>
                    </template>
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

    <!-- USER TASKS MODAL -->
    <x-filament::modal id="user-tasks-modal" width="3xl" :scrollable="true">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" x-text="selectedUser?.name + '\'s Tasks'"></h2>
                    <p class="text-sm text-gray-500" x-text="'Assigned tasks for ' + (selectedAlgorithm === 'divide_conquer' ? 'Divide & Conquer' : 'Greedy')"></p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4 py-4">
            <template x-if="selectedUserTasks.length > 0">
                <div class="space-y-6">
                    <template x-for="group in getGroupedUserTasks()" :key="group.project">
                        <div class="border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    <span x-text="group.project"></span>
                                    <span class="text-xs font-normal text-gray-500" x-text="'(' + group.tasks.length + ' tasks)'"></span>
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 uppercase text-xs font-bold">
                                        <tr>
                                            <th class="px-6 py-3">Task Title</th>
                                            <th class="px-6 py-3">Priority</th>
                                            <th class="px-6 py-3">Est. Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <template x-for="(task, index) in group.tasks" :key="index">
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white" x-text="task.title"></td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-black tracking-wider" 
                                                          :class="getPriorityClass(task.priority_id)" 
                                                          x-text="getPriorityText(task.priority_id)"></span>
                                                </td>
                                                <td class="px-6 py-4 font-bold text-base" :class="getHoursClass(task.estimated_hours)" x-text="task.estimated_hours + 'h'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="selectedUserTasks.length === 0">
                <div class="text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-dashed border-gray-200">
                    <p class="text-gray-500 italic">No tasks assigned to this user.</p>
                </div>
            </template>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <button 
                    type="button"
                    @click="$dispatch('close-modal', { id: 'user-tasks-modal' })"
                    class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition-all duration-200 flex items-center gap-2"
                >
                    Close
                </button>
            </div>
        </x-slot>
    </x-filament::modal>

    <!-- COMPARATIVE STUDY PREVIEW MODAL -->
    <x-filament::modal id="comparative-study-modal-stable" width="7xl" :scrollable="true">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Comparative Study Preview (Overall)
            </div>
        </x-slot>
        
        <div class="space-y-6" x-data="{ 
            activeTab: 'divide_conquer',
            selectedTask: null,
            showTaskDetails: false,
            
            // Helper methods
            getDividerHours() {
                return this.overall_preview?.algorithms?.divide_conquer?.total_hours || 0;
            },
            getGreedyHours() {
                return this.overall_preview?.algorithms?.greedy?.total_hours || 0;
            },
            getDividerWidth() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const total = dividerHours + greedyHours;
                
                if (total === 0) return '0%';
                
                const percentage = (dividerHours / total) * 100;
                return Math.min(100, percentage) + '%';
            },
            getGreedyWidth() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const total = dividerHours + greedyHours;
                
                if (total === 0) return '0%';
                
                const percentage = (greedyHours / total) * 100;
                return Math.min(100, percentage) + '%';
            },
            getTimeDifferenceValue() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                return Math.abs(greedyHours - dividerHours).toFixed(1);
            },
            getTimeDifferenceText() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const diff = greedyHours - dividerHours;
                const absDiff = Math.abs(diff).toFixed(1);

                if (diff > 0) {
                    return '+' + absDiff + 'h (Greedy slower)';
                } else if (diff < 0) {
                    return '-' + absDiff + 'h (Greedy faster)';
                } else {
                    return '0.0h (Same duration)';
                }
            },
            getTimeDifferenceClass() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const diff = greedyHours - dividerHours;

                if (diff > 0) {
                    return 'text-red-600 font-bold';
                } else if (diff < 0) {
                    return 'text-green-600 font-bold';
                } else {
                    return 'text-gray-600 font-bold';
                }
            },
            calculateParallelization(algorithm) {
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
                const totalTasks = tasks.length || 1;
                const parallelTasks = tasks.filter(t => t.parallelizable).length;
                return ((parallelTasks / totalTasks) * 100).toFixed(1);
            },
            countCriticalTasks(algorithm) {
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
                return tasks.filter(t => t.is_critical_path).length;
            },
            calculateAvgDependencies(algorithm) {
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
                const totalTasks = tasks.length || 1;
                const totalDependencies = tasks.reduce((sum, t) => sum + (t.dependencies?.length || 0), 0);
                return (totalDependencies / totalTasks).toFixed(1);
            },
            countQuickWins(algorithm) {
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
                return tasks.filter(t => t.quick_win_score && t.quick_win_score >= 7).length;
            },
            isDivideConquerRecommended() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const dcTasks = this.overall_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.overall_preview?.algorithms?.greedy?.tasks || [];

                // Multi-factor scoring: positive = D&C better, negative = Greedy better
                let score = 0;

                // 1. Time efficiency (weight: 3) — fewer hours is better
                if (dividerHours < greedyHours) score += 3;
                else if (dividerHours > greedyHours) score -= 3;

                // 2. Parallelization rate (weight: 2) — higher parallelization enables faster delivery
                const dcParallelRate = dcTasks.filter(t => t.parallelizable).length / (dcTasks.length || 1);
                const greedyParallelRate = greedyTasks.filter(t => t.parallelizable).length / (greedyTasks.length || 1);
                if (dcParallelRate > greedyParallelRate) score += 2;
                else if (dcParallelRate < greedyParallelRate) score -= 2;

                // 3. Task granularity (weight: 1) — more structured breakdown favors D&C
                if (dcTasks.length > greedyTasks.length) score += 1;
                else if (dcTasks.length < greedyTasks.length) score -= 1;

                // 4. Quick wins (weight: 1) — more quick wins favors Greedy for rapid delivery
                const dcQuickWins = dcTasks.filter(t => t.estimated_hours <= 2).length;
                const greedyQuickWins = greedyTasks.filter(t => t.estimated_hours <= 2).length;
                if (greedyQuickWins > dcQuickWins) score -= 1;
                else if (dcQuickWins > greedyQuickWins) score += 1;

                // 5. Risk (weight: 0.5) — fewer high-risk tasks is better
                const dcHighRisk = dcTasks.filter(t => t.risk_level === 'high').length;
                const greedyHighRisk = greedyTasks.filter(t => t.risk_level === 'high').length;
                if (dcHighRisk < greedyHighRisk) score += 0.5;
                else if (dcHighRisk > greedyHighRisk) score -= 0.5;

                return score >= 0;
            },
            getRecommendationClass() {
                const isDCRecommended = this.isDivideConquerRecommended();
                if (isDCRecommended) {
                    return 'border-blue-300 bg-blue-50';
                } else {
                    return 'border-green-300 bg-green-50';
                }
            },
            getRecommendationIconClass() {
                const isDCRecommended = this.isDivideConquerRecommended();
                if (isDCRecommended) {
                    return 'bg-blue-100 text-blue-600';
                } else {
                    return 'bg-green-100 text-green-600';
                }
            },
            getTaskComplexity() {
                const dcTasks = this.overall_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.overall_preview?.algorithms?.greedy?.tasks || [];
                const dcComplex = dcTasks.filter(t => t.estimated_hours > 4).length;
                const greedyComplex = greedyTasks.filter(t => t.estimated_hours > 4).length;
                return dcComplex > greedyComplex ? 'High' : 'Moderate';
            },
            getParallelizationComparison() {
                const dcTasks = this.overall_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.overall_preview?.algorithms?.greedy?.tasks || [];
                const dcRate = dcTasks.length > 0 ? dcTasks.filter(t => t.parallelizable).length / dcTasks.length : 0;
                const greedyRate = greedyTasks.length > 0 ? greedyTasks.filter(t => t.parallelizable).length / greedyTasks.length : 0;
                return greedyRate > dcRate ? 'Greedy better' : 'D&C better';
            },
            getRiskComparison() {
                const dcTasks = this.overall_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.overall_preview?.algorithms?.greedy?.tasks || [];
                const dcHighRisk = dcTasks.filter(t => t.risk_level === 'high').length;
                const greedyHighRisk = greedyTasks.filter(t => t.risk_level === 'high').length;
                return greedyHighRisk > dcHighRisk ? 'Greedy higher risk' : 'Similar risk';
            },
            getStaffBreakdown(algorithm) {
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
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
                const tasks = this.overall_preview?.algorithms?.[algorithm]?.tasks || [];
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
            getAssignmentReason(task) {
                return task.assignment_reason || 'Assigned based on workload balance';
            },
            getHoursClass(hours) {
                if (hours <= 2) return 'text-green-600';
                if (hours > 2 && hours <= 4) return 'text-yellow-600';
                return 'text-red-600';
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
            getRiskText(riskLevel) {
                if (!riskLevel) return 'Medium';
                return riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1);
            }
        }">
            <!-- Project Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-700 mb-2">Project Details</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Project Name</p>
                        <p class="font-medium" x-text="overall_preview?.project_name"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Main Task</p>
                        <p class="font-medium">All Project Tasks</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Timeline</p>
                        <p class="font-medium">
                            <span x-text="overall_preview?.start_date"></span> - 
                            <span x-text="overall_preview?.end_date"></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-4 gap-4">
                <!-- Summary Card for Active Algorithm -->
                <template x-if="activeTab === 'divide_conquer'">
                    <div class="col-span-4 grid grid-cols-4 gap-4">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Total Tasks</p>
                                    <p class="text-2xl font-bold text-blue-700" 
                                    x-text="overall_preview?.algorithms?.divide_conquer?.total_tasks || 0"></p>
                                </div>
                                <div class="p-2 bg-blue-100 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Total Hours</p>
                                    <p class="text-2xl font-bold text-blue-700" 
                                    x-text="overall_preview?.algorithms?.divide_conquer?.total_hours || 0"></p>
                                </div>
                                <div class="p-2 bg-blue-100 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Critical Tasks</p>
                                    <p class="text-2xl font-bold text-blue-700" 
                                    x-text="(overall_preview?.algorithms?.divide_conquer?.tasks || []).filter(t => t.is_critical_path).length"></p>
                                </div>
                                <div class="p-2 bg-blue-100 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Avg Hours/Task</p>
                                    <p class="text-2xl font-bold text-blue-700" 
                                    x-text="((overall_preview?.algorithms?.divide_conquer?.total_hours || 0) / (overall_preview?.algorithms?.divide_conquer?.total_tasks || 1)).toFixed(1)"></p>
                                </div>
                                <div class="p-2 bg-blue-100 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                
                <template x-if="activeTab === 'greedy'">
                    <div class="col-span-4 grid grid-cols-4 gap-4">
                        <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Total Tasks</p>
                                    <p class="text-2xl font-bold text-green-700" 
                                    x-text="overall_preview?.algorithms?.greedy?.total_tasks || 0"></p>
                                </div>
                                <div class="p-2 bg-green-100 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Total Hours</p>
                                    <p class="text-2xl font-bold text-green-700" 
                                    x-text="overall_preview?.algorithms?.greedy?.total_hours || 0"></p>
                                </div>
                                <div class="p-2 bg-green-100 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Quick Wins</p>
                                    <p class="text-2xl font-bold text-green-700" 
                                        x-text="(overall_preview?.algorithms?.greedy?.tasks || [])
                                                .filter(t => t.quick_win_score && t.quick_win_score >= 7)
                                                .length">
                                        </p>
                                </div>
                                <div class="p-2 bg-green-100 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Parallel Tasks</p>
                                    <p class="text-2xl font-bold text-green-700" 
                                    x-text="(overall_preview?.algorithms?.greedy?.tasks || []).filter(t => t.parallelizable).length"></p>
                                </div>
                                <div class="p-2 bg-green-100 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Algorithm Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button type="button" @click="activeTab = 'divide_conquer'; selectedTask = null;"
                            :class="{
                                'border-blue-500 text-blue-600': activeTab === 'divide_conquer',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'divide_conquer'
                            }"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Divide & Conquer
                    </button>
                    <button type="button" @click="activeTab = 'greedy'; selectedTask = null;"
                            :class="{
                                'border-green-500 text-green-600': activeTab === 'greedy',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'greedy'
                            }"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Greedy Algorithm
                    </button>
                    <button type="button" @click="activeTab = 'comparison'; selectedTask = null;"
                            :class="{
                                'border-purple-500 text-purple-600': activeTab === 'comparison',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'comparison'
                            }"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Comparison Analysis
                    </button>
                </nav>
            </div>

            <!-- Divide & Conquer Tab -->
            <div x-show="activeTab === 'divide_conquer'" class="space-y-4">
                <!-- Tasks Table for D&C -->
                <div class="mt-4 bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <h4 class="font-semibold text-gray-700">Divide & Conquer Tasks</h4>
                        <p class="text-sm text-gray-500">Click any task to view detailed information</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Hours</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Critical</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Risk</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Deps</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="task in overall_preview?.algorithms?.divide_conquer?.tasks || []" :key="task.title">
                                    <tr class="hover:bg-blue-50 cursor-pointer" 
                                        @click="selectedTask = task; showTaskDetails = true;">
                                        <!-- Order -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-800 text-xs font-medium"
                                                x-text="task.order"></span>
                                        </td>
                                        
                                        <!-- Title -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900" x-text="task.title"></div>
                                            <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2" x-html="task.description"></p>
                                        </td>
                                        
                                        <!-- Hours -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': task.estimated_hours <= 2,
                                                        'bg-yellow-100 text-yellow-800': task.estimated_hours > 2 && task.estimated_hours <= 4,
                                                        'bg-red-100 text-red-800': task.estimated_hours > 4
                                                    }"
                                                    x-text="task.estimated_hours + 'h'">
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Priority -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-red-100 text-red-800': task.priority_id == 1,
                                                    'bg-yellow-100 text-yellow-800': task.priority_id == 2,
                                                    'bg-green-100 text-green-800': task.priority_id == 3
                                                }"
                                                x-text="task.priority_name || (task.priority_id == 1 ? 'High' : task.priority_id == 2 ? 'Medium' : 'Low')">
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1" x-text="'Score: ' + (task.priority_score || 0).toFixed(2)"></div>
                                        </td>
                                        
                                        <!-- Assigned To -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <template x-if="task.responsible_name && task.responsible_name !== 'Unassigned'">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                                        x-text="(task.responsible_name || '').substring(0, 2).toUpperCase()">
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900" 
                                                            x-text="task.responsible_name"></div>
                                                        <template x-if="task.target_role_name">
                                                            <div class="text-xs text-blue-600" x-text="task.target_role_name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!task.responsible_name || task.responsible_name === 'Unassigned'">
                                                <span class="text-sm text-gray-400 italic">Unassigned</span>
                                            </template>
                                        </td>
                                        
                                        <!-- Critical -->
                                       <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="task?.is_critical_path">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 text-red-800 text-sm">
                                                    ★
                                                </span>
                                            </template>
                                            <template x-if="!task?.is_critical_path">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                                    Non-Critical
                                                </span>
                                            </template>
                                        </td>
                                        
                                        <!-- Risk -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800': task.risk_level === 'low',
                                                    'bg-yellow-100 text-yellow-800': task.risk_level === 'medium',
                                                    'bg-red-100 text-red-800': task.risk_level === 'high'
                                                }"
                                                x-text="task.risk_level ? task.risk_level.charAt(0).toUpperCase() + task.risk_level.slice(1) : 'Medium'">
                                            </span>
                                        </td>
                                        
                                        <!-- Dependencies -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800': (task.dependencies || []).length == 0,
                                                    'bg-yellow-100 text-yellow-800': (task.dependencies || []).length <= 2,
                                                    'bg-red-100 text-red-800': (task.dependencies || []).length > 2
                                                }"
                                                x-text="(task.dependencies || []).length">
                                            </span>
                                        </td>
                                        
                                        <!-- Details Button -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <button type="button" 
                                                    @click.stop="selectedTask = task; showTaskDetails = true;"
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Greedy Algorithm Tab -->
            <div x-show="activeTab === 'greedy'" class="space-y-4">
                <!-- Tasks Table for Greedy -->
                <div class="mt-4 bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <h4 class="font-semibold text-gray-700">Greedy Algorithm Tasks</h4>
                        <p class="text-sm text-gray-500">Click any task to view detailed information</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-green-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Hours</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Quick Win</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Parallel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Risk</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="task in overall_preview?.algorithms?.greedy?.tasks || []" :key="task.title">
                                    <tr class="hover:bg-green-50 cursor-pointer"
                                        @click="selectedTask = task; showTaskDetails = true;">
                                        <!-- Order -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 text-green-800 text-xs font-medium"
                                                x-text="task.order"></span>
                                        </td>
                                        
                                        <!-- Title -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900" x-text="task.title"></div>
                                             <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2" x-html="task.description"></p>
                                        </td>
                                        
                                        <!-- Hours -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': task.estimated_hours <= 2,
                                                        'bg-yellow-100 text-yellow-800': task.estimated_hours > 2 && task.estimated_hours <= 4,
                                                        'bg-red-100 text-red-800': task.estimated_hours > 4
                                                    }"
                                                    x-text="task.estimated_hours + 'h'">
                                                </span>
                                                <template x-if="task.quick_win_score && task.quick_win_score > 7">
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        Quick
                                                    </span>
                                                </template>
                                            </div>
                                        </td>
                                        
                                        <!-- Priority -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-red-100 text-red-800': task.priority_id == 1,
                                                    'bg-yellow-100 text-yellow-800': task.priority_id == 2,
                                                    'bg-green-100 text-green-800': task.priority_id == 3
                                                }"
                                                x-text="task.priority_name || (task.priority_id == 1 ? 'High' : task.priority_id == 2 ? 'Medium' : 'Low')">
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1" 
                                                x-text="'Score: ' + (task.quick_win_score || task.priority_score || 0).toFixed(2)"></div>
                                        </td>
                                        
                                        <!-- Assigned To -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <template x-if="task.responsible_name && task.responsible_name !== 'Unassigned'">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                                        x-text="(task.responsible_name || '').substring(0, 2).toUpperCase()">
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900" 
                                                            x-text="task.responsible_name"></div>
                                                        <template x-if="task.target_role_name">
                                                            <div class="text-xs text-green-600" x-text="task.target_role_name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!task.responsible_name || task.responsible_name === 'Unassigned'">
                                                <span class="text-sm text-gray-400 italic">Unassigned</span>
                                            </template>
                                        </td>
                                        
                                        <!-- Quick Win -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="task.estimated_hours <= 2">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 text-green-800 text-sm">
                                                    ✓
                                                </span>
                                            </template>
                                            <template x-if="task.quick_win_score && task.quick_win_score >= 5">
                                                <div class="text-xs text-gray-500" 
                                                    x-text="'Score: ' + task.quick_win_score"></div>
                                            </template>
                                        </td>
                                        
                                        <!-- Parallel -->
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <template x-if="task.parallelizable">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-800 text-sm">
                                                    ⚡
                                                </span>
                                            </template>
                                        </td>
                                        
                                        <!-- Risk -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800': task.risk_level === 'low',
                                                    'bg-yellow-100 text-yellow-800': task.risk_level === 'medium',
                                                    'bg-red-100 text-red-800': task.risk_level === 'high'
                                                }"
                                                x-text="task.risk_level ? task.risk_level.charAt(0).toUpperCase() + task.risk_level.slice(1) : 'Medium'">
                                            </span>
                                        </td>
                                        
                                        <!-- Details Button -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <button type="button" 
                                                    @click.stop="selectedTask = task; showTaskDetails = true;"
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Comparison Analysis Tab -->
            <div x-show="activeTab === 'comparison'" class="space-y-4">
                <!-- Comparison Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Time Efficiency Comparison -->
                    <div class="bg-white rounded-lg shadow border p-6">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Time Efficiency Comparison
                        </h4>
                        <div class="space-y-4">
                            <!-- Divide & Conquer -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-blue-600">Divide & Conquer</span>
                                    <span class="text-lg font-bold text-blue-700">
                                        <span x-text="getDividerHours()"></span>h
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" 
                                        :style="'width: ' + getDividerWidth()"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-text="overall_preview?.algorithms?.divide_conquer?.total_tasks || 0"></span> tasks
                                </div>
                            </div>
                            
                            <!-- Greedy Algorithm -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-green-600">Greedy Algorithm</span>
                                    <span class="text-lg font-bold text-green-700">
                                        <span x-text="getGreedyHours()"></span>h
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" 
                                        :style="'width: ' + getGreedyWidth()"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-text="overall_preview?.algorithms?.greedy?.total_tasks || 0"></span> tasks
                                </div>
                            </div>
                            
                            <!-- Difference -->
                            <div class="pt-4 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Time Difference</span>
                                    <span :class="getTimeDifferenceClass()" x-text="getTimeDifferenceText()"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Task Characteristics -->
                    <div class="bg-white rounded-lg shadow border p-6">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Task Characteristics
                        </h4>
                        <div class="space-y-4">
                            <!-- Parallelization -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Parallelization Rate</h5>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-blue-600">D&C: 
                                        <span x-text="calculateParallelization('divide_conquer')"></span>%
                                    </span>
                                    <span class="text-green-600">Greedy: 
                                        <span x-text="calculateParallelization('greedy')"></span>%
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Critical Path -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Critical Path Tasks</h5>
                                <div class="flex justify-between text-sm">
                                    <span class="text-blue-600">D&C: 
                                        <span x-text="countCriticalTasks('divide_conquer')"></span>
                                    </span>
                                    <span class="text-green-600">Greedy: 
                                        <span x-text="countCriticalTasks('greedy')"></span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Dependencies -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Average Dependencies</h5>
                                <div class="flex justify-between text-sm">
                                    <span class="text-blue-600">D&C: 
                                        <span x-text="calculateAvgDependencies('divide_conquer')"></span>
                                    </span>
                                    <span class="text-green-600">Greedy: 
                                        <span x-text="calculateAvgDependencies('greedy')"></span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Quick Wins -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Quick Wins (&lt;2h)</h5>
                                <div class="flex justify-between text-sm">
                                    <span class="text-blue-600">D&C: 
                                        <span x-text="countQuickWins('divide_conquer')"></span>
                                    </span>
                                    <span class="text-green-600">Greedy: 
                                        <span x-text="countQuickWins('greedy')"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommendation -->
                    <div class="bg-white rounded-lg shadow border p-6">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Recommendation
                        </h4>
                        <div class="p-4 rounded-lg border-2" 
                            :class="getRecommendationClass()">
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-full"
                                    :class="getRecommendationIconClass()">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <template x-if="isDivideConquerRecommended()">
                                        <div>
                                            <h5 class="font-bold text-lg text-blue-700">Recommended: Divide & Conquer</h5>
                                            <p class="text-blue-600 mt-2">
                                                More time-efficient with better structured task breakdown.
                                            </p>
                                            <ul class="text-sm text-blue-600 mt-2 space-y-1">
                                                <li>• Better for complex, structured tasks</li>
                                                <li>• Clear dependency management</li>
                                                <li>• Optimized for team collaboration</li>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="!isDivideConquerRecommended()">
                                        <div>
                                            <h5 class="font-bold text-lg text-green-700">Recommended: Greedy Algorithm</h5>
                                            <p class="text-green-600 mt-2">
                                                Faster completion with immediate value delivery.
                                            </p>
                                            <ul class="text-sm text-green-600 mt-2 space-y-1">
                                                <li>• Quick wins and faster delivery</li>
                                                <li>• Minimal dependencies</li>
                                                <li>• High parallelization potential</li>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Key Factors -->
                        <div class="mt-4 pt-4 border-t">
                            <h5 class="text-sm font-medium text-gray-600 mb-2">Key Decision Factors:</h5>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2 bg-gray-50 rounded">
                                    <span class="font-medium">Time Efficiency:</span>
                                    <span x-text="getTimeDifferenceValue() + 'h difference'"></span>
                                </div>
                                <div class="p-2 bg-gray-50 rounded">
                                    <span class="font-medium">Task Complexity:</span>
                                    <span x-text="getTaskComplexity()"></span>
                                </div>
                                <div class="p-2 bg-gray-50 rounded">
                                    <span class="font-medium">Parallelization:</span>
                                    <span x-text="getParallelizationComparison()"></span>
                                </div>
                                <div class="p-2 bg-gray-50 rounded">
                                    <span class="font-medium">Risk Level:</span>
                                    <span x-text="getRiskComparison()"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Workload Breakdown -->
                <div class="mt-6 bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <h4 class="font-semibold text-gray-700 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Staff Assignment Breakdown
                        </h4>
                        <p class="text-sm text-gray-500 mt-1">Why each staff was assigned — per algorithm</p>
                    </div>

                    <!-- Two-column layout: D&C | Greedy -->
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200">

                        <!-- D&C Staff Table -->
                        <div class="p-4">
                            <h5 class="text-sm font-semibold text-blue-700 mb-3 flex items-center gap-1">
                                <span class="w-2 h-2 bg-blue-500 rounded-full inline-block"></span>
                                Divide &amp; Conquer Assignment
                            </h5>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-blue-50 text-blue-700 text-xs uppercase">
                                        <th class="px-3 py-2 text-left">Staff</th>
                                        <th class="px-3 py-2 text-center">Tasks</th>
                                        <th class="px-3 py-2 text-center">Hours</th>
                                        <th class="px-3 py-2 text-left">Reason</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" wire:ignore>
                                    <template x-for="(staff, index) in getStaffBreakdown('divide_conquer')" :key="'dc_staff_' + index">
                                        <tr class="hover:bg-blue-50">
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-7 w-7 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold"
                                                        x-text="(staff.name || '').substring(0,2).toUpperCase()"></div>
                                                    <span class="font-medium text-gray-800" x-text="staff.name"></span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-800 font-semibold text-xs"
                                                    x-text="staff.taskCount"></span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': staff.totalHours <= 4,
                                                        'bg-yellow-100 text-yellow-800': staff.totalHours > 4 && staff.totalHours <= 8,
                                                        'bg-red-100 text-red-800': staff.totalHours > 8
                                                    }"
                                                    x-text="staff.totalHours.toFixed(1) + 'h'"></span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-500 max-w-[180px]">
                                                <span x-text="getAssignmentReason(staff.tasks[0])"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Greedy Staff Table -->
                        <div class="p-4">
                            <h5 class="text-sm font-semibold text-green-700 mb-3 flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span>
                                Greedy Algorithm Assignment
                            </h5>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-green-50 text-green-700 text-xs uppercase">
                                        <th class="px-3 py-2 text-left">Staff</th>
                                        <th class="px-3 py-2 text-center">Tasks</th>
                                        <th class="px-3 py-2 text-center">Hours</th>
                                        <th class="px-3 py-2 text-left">Reason</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" wire:ignore>
                                    <template x-for="(staff, index) in getStaffBreakdown('greedy')" :key="'gr_staff_' + index">
                                        <tr class="hover:bg-green-50">
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-7 w-7 rounded-full bg-green-500 flex items-center justify-center text-white text-xs font-bold"
                                                        x-text="(staff.name || '').substring(0,2).toUpperCase()"></div>
                                                    <span class="font-medium text-gray-800" x-text="staff.name"></span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 text-green-800 font-semibold text-xs"
                                                    x-text="staff.taskCount"></span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': staff.totalHours <= 4,
                                                        'bg-yellow-100 text-yellow-800': staff.totalHours > 4 && staff.totalHours <= 8,
                                                        'bg-red-100 text-red-800': staff.totalHours > 8
                                                    }"
                                                    x-text="staff.totalHours.toFixed(1) + 'h'"></span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-500 max-w-[180px]">
                                                <span x-text="getAssignmentReason(staff.tasks[0])"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- User Assignment List -->
                <div class="mt-6 bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-indigo-50">
                        <h4 class="font-semibold text-indigo-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            User Assignment List
                        </h4>
                        <p class="text-sm text-indigo-600 mt-1">A consolidated view of all assigned users, their roles, and their assigned subtasks.</p>
                    </div>
                    <div class="p-4 bg-white">
                        <div class="space-y-4">
                            <template x-for="(assignment, index) in getUserAssignments()" :key="'assignment_' + index">
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
                                            <span x-text="assignment.tasks.length"></span> task(s)
                                        </div>
                                    </div>
                                    <div>
                                        <ul class="space-y-2">
                                            <template x-for="(task, tIndex) in assignment.tasks" :key="'assigned_task_' + index + '_' + tIndex">
                                                <li class="flex items-start text-sm bg-white p-2 rounded border">
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-2 flex-1">
                                                        <p class="font-medium text-gray-800" x-text="task.title"></p>
                                                        <p class="text-xs text-gray-500 line-clamp-1" x-html="task.description"></p>
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
                                    No users assigned yet.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Task Details Modal -->
            <div x-show="showTaskDetails && selectedTask" 
                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900" x-text="selectedTask?.title"></h3>
                                <p class="text-sm text-gray-500 mt-1">Task Details</p>
                            </div>
                            <button type="button" 
                                    @click="showTaskDetails = false; selectedTask = null;"
                                    class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Task Details -->
                        <div class="space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Order</label>
                                    <p class="mt-1 font-medium" x-text="selectedTask?.order"></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Estimated Hours</label>
                                    <p class="mt-1 font-medium text-lg"
                                    :class="getHoursClass(selectedTask?.estimated_hours)"
                                    x-text="selectedTask?.estimated_hours + ' hours'"></p>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div>
                                <label class="text-sm font-medium text-gray-500">Description</label>
                                <p class="mt-1 text-gray-700 bg-gray-50 p-3 rounded" x-html="selectedTask?.description"></p>
                            </div>
                            
                            <!-- Priority & Critical -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Priority</label>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                            :class="getPriorityClass(selectedTask?.priority_id)"
                                            x-text="selectedTask?.priority_name || getPriorityText(selectedTask?.priority_id)"></span>
                                        <div class="text-xs text-gray-500 mt-1" 
                                            x-text="'Score: ' + (selectedTask?.priority_score || 'N/A')"></div>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Critical Path</label>
                                    <div class="mt-1">
                                        <template x-if="selectedTask?.is_critical_path">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                Critical Path Task
                                            </span>
                                        </template>
                                        <template x-if="!selectedTask?.is_critical_path">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                                Non-Critical
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Assigned To -->
                            <div>
                                <label class="text-sm font-medium text-gray-500">Assigned To</label>
                                <div class="mt-1">
                                    <template x-if="selectedTask?.responsible_name && selectedTask?.responsible_name !== 'Unassigned'">
                                        <div>
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium"
                                                    x-text="(selectedTask?.responsible_name || '').substring(0, 2).toUpperCase()">
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-blue-600 hover:text-blue-800 cursor-pointer underline"
                                                    @click="showCandidatesModal = true; currentTaskForCandidates = selectedTask;"
                                                    x-text="selectedTask?.responsible_name"></p>
                                                    <template x-if="selectedTask?.selected_user_score">
                                                        <p class="text-xs text-gray-500 mt-0.5">
                                                            Suitability Score: <span class="font-semibold text-gray-700" x-text="selectedTask.selected_user_score"></span>
                                                        </p>
                                                    </template>
                                                    <template x-if="selectedTask?.target_role_name">
                                                        <p class="text-xs text-indigo-600 mt-0.5" x-text="selectedTask.target_role_name"></p>
                                                    </template>
                                                </div>
                                            </div>
                                            <template x-if="selectedTask?.assignment_reason">
                                                <div class="mt-2 p-2 bg-indigo-50 rounded text-xs text-indigo-700">
                                                    <span class="font-semibold">Why assigned: </span>
                                                    <span x-text="selectedTask.assignment_reason"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!selectedTask?.responsible_name || selectedTask?.responsible_name === 'Unassigned'">
                                        <div class="text-gray-400 italic">Not assigned</div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Risk & Resource -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Risk Level</label>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                            :class="getRiskClass(selectedTask?.risk_level)"
                                            x-text="getRiskText(selectedTask?.risk_level)"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Resource Intensity</label>
                                    <div class="mt-1">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" 
                                                    :style="'width: ' + ((selectedTask?.resource_intensity || 2) / 3 * 100) + '%'"></div>
                                            </div>
                                            <span class="ml-2 text-sm font-medium" 
                                                x-text="selectedTask?.resource_intensity + '/3'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Parallel & Dependencies -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Parallelizable</label>
                                    <div class="mt-1">
                                        <template x-if="selectedTask?.parallelizable">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                Yes
                                            </span>
                                        </template>
                                        <template x-if="!selectedTask?.parallelizable">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                No (Sequential)
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Dependencies</label>
                                    <div class="mt-1">
                                        <template x-if="selectedTask?.dependencies && selectedTask?.dependencies.length > 0">
                                            <div class="text-sm">
                                                <span class="font-medium" x-text="selectedTask?.dependencies.length"></span> dependencies
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <span x-text="selectedTask?.dependencies.join(', ')"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!selectedTask?.dependencies || selectedTask?.dependencies.length === 0">
                                            <span class="text-gray-400 italic">No dependencies</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Skill Requirements -->
                            <div x-show="selectedTask?.skill_requirements && selectedTask?.skill_requirements.length > 0">
                                <label class="text-sm font-medium text-gray-500">Skill Requirements</label>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <template x-for="skill in selectedTask?.skill_requirements || []" :key="skill">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            <span x-text="skill.replace('_', ' ')"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Greedy Specific Fields -->
                            <template x-if="activeTab === 'greedy'">
                                <div class="grid grid-cols-3 gap-4 border-t pt-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Quick Win Score</label>
                                        <p class="mt-1 font-medium" x-text="selectedTask?.quick_win_score || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Immediate Impact</label>
                                        <p class="mt-1 font-medium" x-text="selectedTask?.immediate_impact || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Effort/Value Ratio</label>
                                        <p class="mt-1 font-medium" x-text="selectedTask?.effort_to_value_ratio || 'N/A'"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                     <!-- Footer -->
                        <div class="mt-8 pt-6 border-t flex justify-end">
                            <button type="button"
                                    @click="showTaskDetails = false; selectedTask = null;"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none">
                                Close
                            </button>
                        </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t flex justify-end">
                <button type="button"
                        @click="$dispatch('close-modal', { id: 'comparative-study-modal-stable' })"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none">
                    Close
                </button>
            </div>
        </div>
    </x-filament::modal>

    <!-- Candidates Modal -->
    <div x-show="showCandidatesModal && currentTaskForCandidates" 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4"
        style="z-index: 999999;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Alternative Candidates</h3>
                        <p class="text-sm text-gray-500 mt-1" x-text="'For task: ' + currentTaskForCandidates?.title"></p>
                    </div>
                    <button type="button" 
                            @click="showCandidatesModal = false; currentTaskForCandidates = null;"
                            class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Candidates List -->
                <div class="space-y-4">
                    <template x-for="candidate in (currentTaskForCandidates?.candidates || [])" :key="candidate.id">
                        <div class="p-4 bg-gray-50 rounded-lg flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-700 font-medium"
                                    x-text="candidate.name.substring(0, 2).toUpperCase()">
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900" x-text="candidate.name"></p>
                                    <p class="text-xs text-gray-500" x-text="candidate.reason"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                    x-text="'Score: ' + Math.round(candidate.score)"></span>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="!(currentTaskForCandidates?.candidates?.length > 0)">
                        <div class="text-center text-gray-500 py-4">
                            No alternative candidates found with the same role.
                            <div class="text-xs text-gray-400 mt-2" x-text="'Debug: ' + JSON.stringify(currentTaskForCandidates?.candidates)"></div>
                        </div>
                    </template>
                </div>
                
                <!-- Footer -->
                <div class="mt-8 pt-6 border-t flex justify-end">
                    <button type="button"
                            @click="showCandidatesModal = false; currentTaskForCandidates = null;"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
