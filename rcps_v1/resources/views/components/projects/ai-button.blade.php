@php
    $unique_id = uniqid();
    // Create simple unique IDs
    $taskModalId = 'task-preview-' . $unique_id;
    $comparativeModalId = 'comparative-preview-' . $unique_id;
@endphp
<div x-data="{
    // Add algorithm properties
    algorithm_mode: 'divide_conquer',
    is_comparative_study: false,
    comparative_preview: null,
    project_name: '',
    start_date: '',
    end_date: '',
    main_task_name: '',
    main_task_description: '',
    ai_subtask_count: 0,
    ai_description: '',
    isLoading: false,
    preview: null,
    hasGeneratedTasks: false, 
    mode:'',
    loadingText:'',
    previewModal:'',
    currentUuid: null,
    subTaskData: [],
    
    init() {
        const element = this.$el.closest('[wire\\:key]');
        if (element) {
            const wireKey = element.getAttribute('wire:key');
            this.currentUuid = wireKey.split('.').slice(-2, -1)[0];
        }
        this.updateFields();
        this.interval = setInterval(() => this.updateFields(), 300);

        const hookId = 'ai-task-hook-' + this.currentUuid;

   
       Livewire.hook('message.processed', (message) => {
            // Check if this is the correct component instance
            const component = message.component;
            const aiResults = component.get('aiResults') || {};
            
            // Only process if this is our UUID
            if (aiResults[this.currentUuid]) {
                setTimeout(() => {
                    const result = aiResults[this.currentUuid];
                    this.processGeneratedTasks(result);
                }, 50);
            }
        });
    },

    processGeneratedTasks(result) {
        if (!result) return;
        
        this.hasGeneratedTasks = true;
        
        // DETERMINE IF COMPARATIVE
        let isComparative = false;
        let dcTasks = [];
        let greedyTasks = [];
        
        // Check multiple possible structures
        if (result.is_comparative === true) {
            isComparative = true;
            dcTasks = result.divide_conquer || [];
            greedyTasks = result.greedy || [];
        } else if (result.divide_conquer && result.greedy) {
            isComparative = true;
            dcTasks = result.divide_conquer;
            greedyTasks = result.greedy;
        } else if (Array.isArray(result)) {
            isComparative = false;
            dcTasks = result;
        } else if (result.tasks && Array.isArray(result.tasks)) {
            isComparative = false;
            dcTasks = result.tasks;
        }
        
        this.is_comparative_study = isComparative;
        
        if (isComparative) {
            // Build comparative preview
            this.comparative_preview = {
                project_name: this.project_name,
                start_date: this.formatDate(this.start_date),
                end_date: this.formatDate(this.end_date),
                main_task_name: this.main_task_name,
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
        } else {
            // Calculate totals
            const totalTasks = dcTasks.length;
            const totalHours = dcTasks.reduce((sum, task) => {
                return sum + parseFloat(task.estimated_hours || 0);
            }, 0);
            
            // Calculate averages and other metrics
            const avgHoursPerTask = totalHours / totalTasks;
            const highPriorityCount = dcTasks.filter(t => t.priority_id == 1).length;
            const criticalPathCount = dcTasks.filter(t => t.is_critical_path === true).length;
            const highRiskCount = dcTasks.filter(t => t.risk_level === 'high').length;
            
            this.preview = {
                project_name: this.project_name,
                main_task_name: this.main_task_name,
                start_date: this.formatDate(this.start_date),
                end_date: this.formatDate(this.end_date),
                total_tasks: totalTasks,
                total_hours: Math.round(totalHours * 100) / 100,
                day_required: Math.ceil(totalHours / 8),
                average_total: avgHoursPerTask.toFixed(1),
                tasks: dcTasks,
                metrics: {
                    high_priority: highPriorityCount,
                    critical_path: criticalPathCount,
                    high_risk: highRiskCount,
                    parallelizable_tasks: dcTasks.filter(t => t.parallelizable !== false).length,
                    total_dependencies: dcTasks.reduce((sum, t) => sum + (t.dependencies ? t.dependencies.length : 0), 0),
                    assigned_tasks: dcTasks.filter(t => t.responsible_id && t.responsible_name !== 'Unassigned').length,
                    algorithm: dcTasks[0]?.algorithm || 'divide_conquer'
                }
            };
        }
    },

    formatDate(dateString) {
        if (!dateString) return 'Not set';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long', 
                day: 'numeric'
            });
        } catch (error) {
            return dateString; // Return original if formatting fails
        }
    },
    
   updateFields() {
        const components = window.Livewire.all();
        
        // Use the stored currentUuid
        if (!this.currentUuid) return;
        
        for (const id in components) {
            const component = components[id];
            const formData = component.get('data');
            
            if (!formData || !formData.add_task || !formData.add_task[this.currentUuid]) continue;
            
            // Get data for THIS specific repeater
            const taskData = formData.add_task[this.currentUuid];
            const rawSubtasks = taskData.add_subtask ?? {};

            const numberedSubtasks = {};
            const uuidToIndex = {};

            let index = 1;

            // First pass: assign numbers
            Object.entries(rawSubtasks).forEach(([uuid, subtask]) => {
                if (!subtask.subtask_title) return; // skip empty

                uuidToIndex[uuid] = index;

                numberedSubtasks[index] = {
                    subtask_title: subtask.subtask_title,
                    subtask_description: subtask.subtask_description,
                    dependencies: [], // fill later
                };

                index++;
            });

            // Second pass: map dependencies
            Object.entries(rawSubtasks).forEach(([uuid, subtask]) => {
                if (!uuidToIndex[uuid]) return;

                const currentIndex = uuidToIndex[uuid];

                numberedSubtasks[currentIndex].dependencies =
                    (subtask.dependencies || [])
                        .map(depUuid => uuidToIndex[depUuid])
                        .filter(Boolean);
            });

            this.subTaskData = numberedSubtasks;
            
            
            // Update common fields
            this.project_name = formData.name || '';
            this.start_date = formData.start_date || '';
            this.end_date = formData.end_date || '';
            this.algorithm_mode = formData.algorithm_mode || 'divide_conquer';
            
            // Update task-specific fields
            this.main_task_name = taskData.main_task_name || '';
            this.main_task_description = taskData.main_task_description || '';
            
            // Check if we have generated tasks for THIS repeater
            const aiResults = component.get('aiResults') || {};
            if (aiResults[this.currentUuid]) {
                this.hasGeneratedTasks = true;
                this.is_comparative_study = aiResults[this.currentUuid].is_comparative || false;
            } else {
                this.hasGeneratedTasks = false;
            }
            
            break;
        }
    },

    isTaskDataValid() {
        if (!this.subTaskData || Object.keys(this.subTaskData).length === 0) {
            return false;
        }

        return Object.values(this.subTaskData).some(item =>
            item?.subtask_title?.trim()
        );
    },

    hasEmptySubtaskNames() {
        if (!this.subTaskData || Object.keys(this.subTaskData).length === 0) {
            return false;
        }
        
        return this.subTaskData.some(item => {
            return !item || 
                   !item.subtask_title || 
                   item.subtask_title.trim() === '';
        });
    },

    getEmptySubtasksInfo() {
        if (!this.subTaskData || Object.keys(this.subTaskData).length === 0) {
            return { hasEmpty: false, emptyIndices: [] };
        }

        const emptyIndices = [];
        const values = Object.values(this.subTaskData); // 🔥 important

        values.forEach((item, index) => {
            if (
                !item ||
                !item.subtask_title ||
                item.subtask_title.trim() === '' ||
                !item.subtask_description ||
                item.subtask_description.trim() === ''
            ) {
                emptyIndices.push(index + 1); // human-readable
            }
        });

        return {
            hasEmpty: emptyIndices.length > 0,
            emptyIndices,
        };
    },

    getSubtasksAlertMessage() {
        const emptyInfo = this.getEmptySubtasksInfo();

        if (!this.subTaskData || Object.keys(this.subTaskData).length === 0) {
            return 'Please add at least one subtask before generating computation.';
        }

        if (emptyInfo.hasEmpty) {
            const subtaskWord = emptyInfo.emptyIndices.length === 1 ? 'subtask' : 'subtasks';
            const indicesList = emptyInfo.emptyIndices.join(', ');

            return `Please fill in ${subtaskWord} #${indicesList} or remove ${
                emptyInfo.emptyIndices.length === 1 ? 'it' : 'them'
            } to proceed.`;
        }

        return null;
    },

    canGenerateAI() {
        return Boolean(
            this.project_name &&
            this.main_task_name &&
            this.main_task_description &&
            this.isTaskDataValid() &&
            !this.isLoading
        );
    },
            
    async generateAI(mode) {
        if (!this.project_name) {
            alert('Please fill in the Project Name.');
            return;
        }
        if (!this.main_task_name) {
            alert('Please fill in the Main Task Name.');
            return;
        }
        if (!this.main_task_description) {
            alert('Please fill in the Main Task Description.');
            return;
        }

        const subtaskMessage = this.getSubtasksAlertMessage();
        if (subtaskMessage) {
            alert(subtaskMessage);
            return;
        }

        if (!this.canGenerateAI()) {
            alert('Cannot generate computation at this time. Please check all fields.');
            return;
        }


        if(mode == 'comparison'){
            this.loadingText = 'Generating Both Algorithms (D&C + Greedy)...';
        } else if(mode == 'greedy'){
            this.loadingText = 'Using Greedy Algorithm';
        } else {
            this.loadingText = 'Using Divide & Conquer Algorithm';
        }


        // SET LOADING STATE
        this.isLoading = true;
        document.body.style.overflow = 'hidden';
        document.body.style.pointerEvents = 'none';
        this.hasGeneratedTasks = true;

        const components = Livewire.all();
        let targetComponent = null;

        for (const id in components) {
            const component = components[id];
            const formData = component.get('data');
            if (formData && formData.name) {
                targetComponent = component;
                break;
            }
        }

        try {
            if (targetComponent) {
                await targetComponent.call('generateAiTasks', {
                    project_name: this.project_name,
                    start_date: this.start_date,
                    end_date: this.end_date,
                    main_task_name: this.main_task_name,
                    main_task_description: this.main_task_description,
                    task_uuid: this.currentUuid,
                    mode: mode
                    // Algorithm mode is already in form data
                });
            }
        } catch (error) {
            console.error('Error generating AI tasks:', error);
        } finally {
            this.isLoading = false;
            document.body.style.overflow = '';
            document.body.style.pointerEvents = '';
            this.loadingText = '';
        }
    },
    
    async openPreview() {

         if (!this.hasGeneratedTasks) {
            alert('Please generate tasks first');
            return;
        }

        if(!this.is_comparative_study){
            alert('Please generate first the Greedy and D&C');
            return;
        }

        if (this.is_comparative_study) {
           this.$dispatch('open-modal', { id: '{{$comparativeModalId}}' });
        } else {
           this.$dispatch('open-modal', { id: '{{$comparativeModalId}}' });
        }
    },

    async closePreview() {
        this.$dispatch('close-modal', { id: '{{$comparativeModalId}}' });
    },
    
    // Helper to get algorithm display name
    getAlgorithmName(mode) {
        const names = {
            'divide_conquer': 'Divide & Conquer',
            'greedy': 'Greedy Algorithm',
            'comparison': 'Comparative Study'
        };
        return names[mode] || mode;
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


    <!-- Algorithm Selector -->
    <div class="mb-4 p-3 bg-gray-50 rounded-lg border">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
                Algorithm Strategy
            </span>
        </label>
         <div class="flex gap-2">
            <div class="px-3 py-2 rounded-md font-medium"
                :class="{
                    'bg-blue-100 text-blue-800 border border-blue-300': algorithm_mode === 'divide_conquer',
                    'bg-green-100 text-green-800 border border-green-300': algorithm_mode === 'greedy',
                    'bg-purple-100 text-purple-800 border border-purple-300': algorithm_mode === 'comparison'
                }">
                <span x-show="algorithm_mode === 'divide_conquer'">Divide & Conquer</span>
                <span x-show="algorithm_mode === 'greedy'">Greedy Algorithm</span>
                <span x-show="algorithm_mode === 'comparison'">Comparative Study</span>
            </div>
        </div>
        
        <!-- Algorithm Descriptions -->
        <div class="mt-2 text-sm space-y-1">
            <div x-show="algorithm_mode === 'divide_conquer'">
                <p class="text-blue-600">
                    <strong>Divide & Conquer:</strong> Breaks complex tasks into smaller subproblems, solves recursively, then combines solutions.
                </p>
            </div>
            <div x-show="algorithm_mode === 'greedy'">
                <p class="text-green-600">
                    <strong>Greedy Algorithm:</strong> Makes locally optimal choices at each step for quick wins and immediate value.
                </p>
            </div>
            <div x-show="algorithm_mode === 'comparison'">
                <p class="text-purple-600">
                    <strong>Comparative Study:</strong> Generates tasks using BOTH algorithms to compare their performance and results.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Buttons Container -->
    <div class="flex gap-2">
        <!-- Generate Computation Button (Both Algorithms) -->
        <button type="button"
                x-on:click="generateAI('comparison')"
                class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 min-w-[200px] bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
            <!-- Icon -->
            <svg x-show="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <!-- Loading Icon -->
            <svg x-show="isLoading" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <!-- Text -->
            <span class="whitespace-nowrap">
                <span x-show="!isLoading">Generate Computation</span>
                <span x-show="isLoading">Generating...</span>
            </span>
        </button>

         <button 
            type="button" 
            @click="openPreview()"
            {{-- :disabled="!hasGeneratedTasks"
            :class="{
                'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all': hasGeneratedTasks,
                'bg-gray-400 cursor-not-allowed opacity-60': !hasGeneratedTasks
            }" --}}
            class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 min-w-[180px] bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
                <template x-if="!is_comparative_study">
                    <span>Preview Computation</span>
                </template>
                <template x-if="is_comparative_study">
                    <span>Preview Comparative Study</span>
                </template>
                <template x-if="!is_comparative_study">
                    <span x-show="hasGeneratedTasks" 
                        class="bg-green-800 bg-opacity-20 text-green-100 text-xs px-2 py-1 rounded-full">
                        <span x-text="preview?.total_tasks || 0"></span> tasks
                    </span>
                </template>
        </button>
    </div>

    <!-- COMPARATIVE STUDY PREVIEW MODAL (new) -->
    <x-filament::modal  id="{{$comparativeModalId}}" width="7xl" :scrollable="true">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Comparative Study Preview
            </div>
        </x-slot>
        
        <div class="space-y-6" x-data="{ 
            activeTab: 'divide_conquer',
            selectedTask: null,
            showTaskDetails: false,
            
            // Helper methods
            getDividerHours() {
                return this.comparative_preview?.algorithms?.divide_conquer?.total_hours || 0;
            },
            getGreedyHours() {
                return this.comparative_preview?.algorithms?.greedy?.total_hours || 0;
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
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
                const totalTasks = tasks.length || 1;
                const parallelTasks = tasks.filter(t => t.parallelizable).length;
                return ((parallelTasks / totalTasks) * 100).toFixed(1);
            },
            countCriticalTasks(algorithm) {
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
                return tasks.filter(t => t.is_critical_path).length;
            },
            calculateAvgDependencies(algorithm) {
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
                const totalTasks = tasks.length || 1;
                const totalDependencies = tasks.reduce((sum, t) => sum + (t.dependencies?.length || 0), 0);
                return (totalDependencies / totalTasks).toFixed(1);
            },
            countQuickWins(algorithm) {
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
                return tasks.filter(t => t.quick_win_score && t.quick_win_score >= 7).length;
            },
            isDivideConquerRecommended() {
                const dividerHours = this.getDividerHours();
                const greedyHours = this.getGreedyHours();
                const dcTasks = this.comparative_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.comparative_preview?.algorithms?.greedy?.tasks || [];

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
                const dcTasks = this.comparative_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.comparative_preview?.algorithms?.greedy?.tasks || [];
                const dcComplex = dcTasks.filter(t => t.estimated_hours > 4).length;
                const greedyComplex = greedyTasks.filter(t => t.estimated_hours > 4).length;
                return dcComplex > greedyComplex ? 'High' : 'Moderate';
            },
            getParallelizationComparison() {
                const dcTasks = this.comparative_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.comparative_preview?.algorithms?.greedy?.tasks || [];
                const dcRate = dcTasks.length > 0 ? dcTasks.filter(t => t.parallelizable).length / dcTasks.length : 0;
                const greedyRate = greedyTasks.length > 0 ? greedyTasks.filter(t => t.parallelizable).length / greedyTasks.length : 0;
                return greedyRate > dcRate ? 'Greedy better' : 'D&C better';
            },
            getRiskComparison() {
                const dcTasks = this.comparative_preview?.algorithms?.divide_conquer?.tasks || [];
                const greedyTasks = this.comparative_preview?.algorithms?.greedy?.tasks || [];
                const dcHighRisk = dcTasks.filter(t => t.risk_level === 'high').length;
                const greedyHighRisk = greedyTasks.filter(t => t.risk_level === 'high').length;
                return greedyHighRisk > dcHighRisk ? 'Greedy higher risk' : 'Similar risk';
            },
            getStaffBreakdown(algorithm) {
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
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
                const tasks = this.comparative_preview?.algorithms?.[algorithm]?.tasks || [];
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
                        <p class="font-medium" x-text="comparative_preview?.project_name"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Main Task</p>
                        <p class="font-medium" x-text="comparative_preview?.main_task_name"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Timeline</p>
                        <p class="font-medium">
                            <span x-text="comparative_preview?.start_date"></span> - 
                            <span x-text="comparative_preview?.end_date"></span>
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
                                    x-text="comparative_preview?.algorithms?.divide_conquer?.total_tasks || 0"></p>
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
                                    x-text="comparative_preview?.algorithms?.divide_conquer?.total_hours || 0"></p>
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
                                    x-text="(comparative_preview?.algorithms?.divide_conquer?.tasks || []).filter(t => t.is_critical_path).length"></p>
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
                                    x-text="((comparative_preview?.algorithms?.divide_conquer?.total_hours || 0) / (comparative_preview?.algorithms?.divide_conquer?.total_tasks || 1)).toFixed(1)"></p>
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
                                    x-text="comparative_preview?.algorithms?.greedy?.total_tasks || 0"></p>
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
                                    x-text="comparative_preview?.algorithms?.greedy?.total_hours || 0"></p>
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
                                        x-text="(comparative_preview?.algorithms?.greedy?.tasks || [])
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
                                    x-text="(comparative_preview?.algorithms?.greedy?.tasks || []).filter(t => t.parallelizable).length"></p>
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
                                <template x-for="task in comparative_preview?.algorithms?.divide_conquer?.tasks || []" :key="task.title">
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
                                <template x-for="task in comparative_preview?.algorithms?.greedy?.tasks || []" :key="task.title">
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
                                    <span x-text="comparative_preview?.algorithms?.divide_conquer?.total_tasks || 0"></span> tasks
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
                                    <span x-text="comparative_preview?.algorithms?.greedy?.total_tasks || 0"></span> tasks
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
                                                    <p class="text-sm font-medium text-gray-900"
                                                    x-text="selectedTask?.responsible_name"></p>
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
                        @click="closePreview()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none">
                    Close
                </button>
            </div>
        </div>
    </x-filament::modal>
</div>
 