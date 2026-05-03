<div x-data="{
    // Data for AI generation
    project_id: '',
    project_name: '',
    start_date: '',
    end_date: '',
    name: '',
    content: '',
    ai_subtask_count: 0,
    isLoading: false,
    
    // Data for preview
    preview: null,
    hasGeneratedTasks: false, // Track if tasks have been generated

    init() {
        this.updateFields();
        this.interval = setInterval(() => this.updateFields(), 300);
        
        Livewire.hook('message.processed', () => {
            setTimeout(() => this.updateFields(), 50);
        });
        
        // Listen for generation success
        Livewire.hook('message.processed', (message) => {
            // Check if AI tasks were generated in this response
            const responseData = message.response.serverMemo.data;
            if (responseData.aiResults) {
                const aiTasks = responseData.aiResults || [];
                
                if (aiTasks.length > 0) {
                    this.hasGeneratedTasks = true;
                    
                    // Calculate totals
                    const totalTasks = aiTasks.length;
                    const totalHours = aiTasks.reduce((sum, task) => {
                        return sum + parseFloat(task.estimated_hours || 0);
                    }, 0);
                    
                    // Calculate averages and other metrics
                    const avgHoursPerTask = totalHours / totalTasks;
                    const highPriorityCount = aiTasks.filter(t => t.priority_id == 1).length;
                    const criticalPathCount = aiTasks.filter(t => t.is_critical_path === true).length;
                    const highRiskCount = aiTasks.filter(t => t.risk_level === 'high').length;
                    
                    this.preview = {
                        project_name: this.project_name,
                        main_task_name: this.name,
                        start_date: this.formatDate(this.start_date),
                        end_date: this.formatDate(this.end_date),
                        total_tasks: totalTasks,
                        total_hours: Math.round(totalHours * 100) / 100,
                        day_required: Math.ceil(totalHours / 8),
                        average_total: avgHoursPerTask.toFixed(1),
                        tasks: aiTasks,
                        metrics: {
                            high_priority: highPriorityCount,
                            critical_path: criticalPathCount,
                            high_risk: highRiskCount,
                            parallelizable_tasks: aiTasks.filter(t => t.parallelizable !== false).length,
                            total_dependencies: aiTasks.reduce((sum, t) => sum + (t.dependencies ? t.dependencies.length : 0), 0),
                            assigned_tasks: aiTasks.filter(t => t.responsible_id && t.responsible_name !== 'Unassigned').length,
                            algorithm: aiTasks[0]?.algorithm || 'divide_conquer'
                        }
                    };
                    
                    // Show success notification
                    this.$dispatch('notify', {
                        message: 'AI tasks generated successfully!',
                        type: 'success'
                    });
                }
            }
        });
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
            return dateString;
        }
    },

    formatNumber(num, decimals = 1) {
        return parseFloat(num).toFixed(decimals);
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    },

    updateFields() {
        const components = window.Livewire.all();
        for (const id in components) {
            const formData = components[id].get('data');
            if (!formData) continue;

            this.project_id = formData.project_id || '';
            this.project_name = formData.project_name || '';
            this.start_date = formData.start_date || '';
            this.end_date = formData.end_date || '';
            
            if (formData.name) {
                this.name = formData.name || '';
                this.content = formData.content || '';
                this.ai_subtask_count = formData.ai_subtask_count || 0;
            }
            break;
        }
    },

    async generateAI() {
        // Client-side validation
        if (!this.project_id) {
            alert('Please select a project');
            return;
        }
        if (!this.name) {
            alert('Please enter main task name');
            return;
        }
        if (!this.content) {
            alert('Please enter main task description');
            return;
        }
        if (this.ai_subtask_count === 0 || this.ai_subtask_count > 10) {
            alert('Please enter number of subtasks between 1-10');
            return;
        }

        // SET LOADING STATE
        this.isLoading = true;
        
        // DISABLE BODY SCROLL AND INTERACTION
        document.body.style.overflow = 'hidden';
        document.body.style.pointerEvents = 'none';

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
                    project_id: this.project_id,
                    name: this.name,
                    content: this.content,
                    ai_subtask_count: this.ai_subtask_count,
                });
            }
        } catch (error) {
            console.error('Error generating AI tasks:', error);
            this.$dispatch('notify', {
                message: 'Failed to generate AI tasks',
                type: 'error'
            });
        } finally {
            // RESTORE BODY STATE
            this.isLoading = false;
            document.body.style.overflow = '';
            document.body.style.pointerEvents = '';
        }
    },

    async openPreview() {
        if (!this.hasGeneratedTasks) {
            this.$dispatch('notify', {
                message: 'Generate AI tasks first to see preview',
                type: 'warning'
            });
            return;
        }

        if (this.preview) {
            this.$dispatch('open-modal', { id: 'task-preview' });
        }
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
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Generating AI Subtasks</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Using Divide & Conquer Algorithm</p>
                
                <!-- Progress Animation -->
                <div class="w-64 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
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

    <!-- Buttons Container -->
    <div class="flex flex-col sm:flex-row gap-3">
        <!-- Generate AI Button -->
        <button type="button"
                x-on:click="generateAI"
                :disabled="isLoading || !project_id || !name || !content || ai_subtask_count === 0"
                :class="{
                    'bg-gray-400 cursor-not-allowed opacity-60': isLoading || !project_id || !name || !content || ai_subtask_count === 0,
                    'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all': !isLoading && project_id && name && content && ai_subtask_count > 0
                }"
                class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 min-w-[180px]">
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
                <span x-show="!isLoading" 
                      x-text="!project_id || !name || !content || ai_subtask_count === 0 ? 
                              'Fill All Fields' : 
                              'Generate AI Subtasks'"></span>
                <span x-show="isLoading">Generating...</span>
            </span>
            
            <!-- Badge -->
            <span x-show="!isLoading && project_id && name && content && ai_subtask_count > 0" 
                  class="bg-blue-800 bg-opacity-20 text-blue-100 text-xs px-2 py-1 rounded-full">
                AI
            </span>
        </button>

        <!-- Preview Computation Button -->
        <button 
            type="button" 
            @click="openPreview()"
            :disabled="!hasGeneratedTasks"
            :class="{
                'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all': hasGeneratedTasks,
                'bg-gray-400 cursor-not-allowed opacity-60': !hasGeneratedTasks
            }"
            class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center justify-center gap-2 min-w-[180px]"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span>Preview Computation</span>
            <span x-show="hasGeneratedTasks" 
                  class="bg-green-800 bg-opacity-20 text-green-100 text-xs px-2 py-1 rounded-full">
                <span x-text="preview?.total_tasks || 0"></span> tasks
            </span>
        </button>
    </div>

    <!-- Preview Modal -->
    <x-filament::modal id="task-preview" width="7xl" :scrollable="true" class="!p-0">
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">AI Task Computation Preview</h2>
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <span>Divide & Conquer Algorithm</span>
                            <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                            <span><span x-text="preview?.total_tasks || 0"></span> Subtasks Generated</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$dispatch('close-modal', { id: 'task-preview' })" 
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </x-slot>
        
        <div class="max-h-[70vh] overflow-y-auto">
            <template x-if="preview">
                <div class="space-y-6 p-1">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Total Tasks Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/10 p-4 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Total Subtasks</p>
                                    <h3 class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2" x-text="preview.total_tasks"></h3>
                                </div>
                                <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Total Hours Card -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/10 p-4 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-800 dark:text-green-300">Estimated Hours</p>
                                    <h3 class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2" x-text="preview.total_hours"></h3>
                                    <p class="text-xs text-green-700 dark:text-green-400 mt-1">
                                        <span x-text="preview.day_required"></span> work days
                                    </p>
                                </div>
                                <div class="p-3 bg-green-100 dark:bg-green-800 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Average Hours Card -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/10 p-4 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Avg per Task</p>
                                    <h3 class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-2" x-text="preview.average_total"></h3>
                                    <p class="text-xs text-purple-700 dark:text-purple-400 mt-1">hours per subtask</p>
                                </div>
                                <div class="p-3 bg-purple-100 dark:bg-purple-800 rounded-lg">
                                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Critical Path Card -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/10 p-4 rounded-xl border border-red-200 dark:border-red-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-red-800 dark:text-red-300">Critical Path</p>
                                    <h3 class="text-3xl font-bold text-red-900 dark:text-red-100 mt-2" x-text="preview.metrics.critical_path"></h3>
                                    <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                        <span x-text="((preview.metrics.critical_path / preview.total_tasks) * 100).toFixed(1)"></span>% of tasks
                                    </p>
                                </div>
                                <div class="p-3 bg-red-100 dark:bg-red-800 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Priority Distribution -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- High Priority -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/10 dark:to-red-900/5 p-4 rounded-xl border border-red-200 dark:border-red-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                        <p class="text-sm font-medium text-red-800 dark:text-red-300">High Priority</p>
                                    </div>
                                    <h3 class="text-2xl font-bold text-red-900 dark:text-red-100 mt-2" 
                                        x-text="preview.tasks.filter(t => t.priority_id == 1).length"></h3>
                                    <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                        <span x-text="preview.tasks.filter(t => t.priority_id == 1).reduce((sum, t) => sum + (t.estimated_hours || 0), 0).toFixed(1)"></span> hours total
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Normal Priority -->
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/10 dark:to-yellow-900/5 p-4 rounded-xl border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Normal Priority</p>
                                    </div>
                                    <h3 class="text-2xl font-bold text-yellow-900 dark:text-yellow-100 mt-2" 
                                        x-text="preview.tasks.filter(t => t.priority_id == 2).length"></h3>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">
                                        <span x-text="preview.tasks.filter(t => t.priority_id == 2).reduce((sum, t) => sum + (t.estimated_hours || 0), 0).toFixed(1)"></span> hours total
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Low Priority -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/10 dark:to-green-900/5 p-4 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <p class="text-sm font-medium text-green-800 dark:text-green-300">Low Priority</p>
                                    </div>
                                    <h3 class="text-2xl font-bold text-green-900 dark:text-green-100 mt-2" 
                                        x-text="preview.tasks.filter(t => t.priority_id == 3).length"></h3>
                                    <p class="text-xs text-green-700 dark:text-green-400 mt-1">
                                        <span x-text="preview.tasks.filter(t => t.priority_id == 3).reduce((sum, t) => sum + (t.estimated_hours || 0), 0).toFixed(1)"></span> hours total
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Algorithm Metrics -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 p-5 rounded-xl border dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Divide & Conquer Algorithm Performance
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Parallelization -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300" 
                                    x-text="((preview.metrics.parallelizable_tasks / preview.total_tasks) * 100).toFixed(1) + '%'"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Parallelizable</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-text="preview.metrics.parallelizable_tasks"></span> of <span x-text="preview.total_tasks"></span> tasks
                                </div>
                            </div>
                            
                            <!-- Dependencies -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300" 
                                    x-text="preview.metrics.total_dependencies"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Dependencies</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Avg: <span x-text="(preview.metrics.total_dependencies / preview.total_tasks).toFixed(1)"></span> per task
                                </div>
                            </div>
                            
                            <!-- High Intensity -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-700 dark:text-purple-300" 
                                    x-text="preview.tasks.filter(t => t.resource_intensity == 3).length"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">High Intensity Tasks</div>
                                <div class="text-xs text-gray-500 mt-1">Resource level 3</div>
                            </div>
                            
                            <!-- Risk Level -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-700 dark:text-red-300" 
                                    x-text="preview.metrics.high_risk"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">High Risk Tasks</div>
                                <div class="text-xs text-gray-500 mt-1">Requires attention</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Generated Subtasks</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Detailed breakdown of AI-generated tasks</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task Details</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hours</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Priority</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <template x-for="(task, index) in preview.tasks" :key="task.title">
                                        <tr :class="{
                                            'bg-yellow-50 dark:bg-yellow-900/20': task.is_critical_path,
                                            'hover:bg-gray-50 dark:hover:bg-gray-700': true
                                        }">
                                            <!-- Order -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="text-center">
                                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gradient-to-r from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 text-blue-800 dark:text-blue-300 font-semibold text-sm"
                                                        x-text="task.order || index + 1"></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Task Details -->
                                            <td class="px-4 py-4">
                                                <div class="group relative">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0 mt-1">
                                                            <div class="h-2 w-2 rounded-full"
                                                                :class="{
                                                                    'bg-red-500': task.risk_level === 'high',
                                                                    'bg-yellow-500': task.risk_level === 'medium',
                                                                    'bg-green-500': task.risk_level === 'low'
                                                                }"></div>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1" 
                                                                x-text="task.title"></h4>
                                                            <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2" 
                                                                x-text="task.description"></p>
                                                            <div x-show="task.dependencies && task.dependencies.length" 
                                                                class="mt-2 flex flex-wrap gap-1">
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">Depends on:</span>
                                                                <template x-for="dep in task.dependencies" :key="dep">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300"
                                                                        x-text="dep"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Hours -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex flex-col items-center">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold mb-1"
                                                        :class="{
                                                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': task.estimated_hours <= 2,
                                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': task.estimated_hours > 2 && task.estimated_hours <= 4,
                                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': task.estimated_hours > 4
                                                        }">
                                                        <span x-text="task.estimated_hours"></span>h
                                                    </span>
                                                    <div class="flex items-center gap-1">
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">Intensity:</div>
                                                        <div class="flex">
                                                            <template x-for="i in 3" :key="i">
                                                                <div class="w-2 h-2 rounded-full mx-px"
                                                                    :class="{
                                                                        'bg-blue-500': i <= task.resource_intensity,
                                                                        'bg-gray-200 dark:bg-gray-700': i > task.resource_intensity
                                                                    }"></div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Priority -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex flex-col gap-1">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                                        :class="{
                                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': task.priority_id == 1,
                                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': task.priority_id == 2,
                                                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': task.priority_id == 3
                                                        }"
                                                        x-text="task.priority_range || (task.priority_id == 1 ? 'High' : task.priority_id == 3 ? 'Low' : 'Normal')">
                                                    </span>
                                                    <div class="text-xs text-center text-gray-500 dark:text-gray-400">
                                                        Score: <span x-text="Math.round(task.priority_score || 0)"></span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Status -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex flex-col gap-1 items-center">
                                                    <div class="flex items-center gap-2">
                                                        <template x-if="task.is_critical_path">
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Critical
                                                            </span>
                                                        </template>
                                                        <template x-if="task.parallelizable !== false">
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Parallel
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                                        x-text="task.skill_requirements ? task.skill_requirements.join(', ') : 'General'"></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Assigned -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <template x-if="task.responsible_name && task.responsible_name !== 'Unassigned'">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-semibold"
                                                                x-text="(task.responsible_name || '').substring(0, 2).toUpperCase()">
                                                            </div>
                                                        </div>
                                                        <div class="ml-3">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white" 
                                                                x-text="task.responsible_name"></div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400"
                                                                x-text="task.responsible_id ? 'ID: ' + task.responsible_id : ''"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!task.responsible_name || task.responsible_name === 'Unassigned'">
                                                    <div class="flex items-center justify-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                            </svg>
                                                            Unassigned
                                                        </span>
                                                    </div>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!preview">
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-full mb-6">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No AI Tasks Generated Yet</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">Generate AI subtasks first to see the computation preview.</p>
                    <button type="button" @click="$dispatch('close-modal', { id: 'task-preview' })" 
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-lg transition-all duration-200">
                        Close Preview
                    </button>
                </div>
            </template>
        </div>
    </x-filament::modal>
</div>