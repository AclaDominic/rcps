@php
    $overallModalId = 'overall-computation-modal-' . uniqid();
@endphp

<div x-data="{
    overallPreview: null,
    isLoadingOverall: false,
    showOverallModal: false,

    getOverallComputation() {
        const components = window.Livewire.all();
        let allDcTasks = [];
        let allGreedyTasks = [];
        let foundAny = false;

        for (const id in components) {
            const component = components[id];
            const aiResults = component.get('aiResults') || {};

            for (const uuid in aiResults) {
                const result = aiResults[uuid];
                if (!result) continue;

                if (result.is_comparative || (result.divide_conquer && result.greedy)) {
                    allDcTasks = allDcTasks.concat(result.divide_conquer || []);
                    allGreedyTasks = allGreedyTasks.concat(result.greedy || []);
                    foundAny = true;
                } else if (result.divide_conquer) {
                    allDcTasks = allDcTasks.concat(result.divide_conquer);
                    foundAny = true;
                } else if (result.greedy) {
                    allGreedyTasks = allGreedyTasks.concat(result.greedy);
                    foundAny = true;
                } else if (Array.isArray(result)) {
                    allDcTasks = allDcTasks.concat(result);
                    foundAny = true;
                }
            }
        }

        if (!foundAny || (allDcTasks.length === 0 && allGreedyTasks.length === 0)) {
            alert('No generated computations found. Please generate computation for at least one task first.');
            return;
        }

        const dcHours = allDcTasks.reduce((s, t) => s + (parseFloat(t.estimated_hours) || 0), 0);
        const greedyHours = allGreedyTasks.reduce((s, t) => s + (parseFloat(t.estimated_hours) || 0), 0);

        this.overallPreview = {
            divide_conquer: {
                tasks: allDcTasks,
                total_tasks: allDcTasks.length,
                total_hours: Math.round(dcHours * 100) / 100,
                days_required: Math.ceil(dcHours / 8),
                critical_tasks: allDcTasks.filter(t => t.is_critical_path).length,
                high_risk: allDcTasks.filter(t => t.risk_level === 'high').length,
                parallel_tasks: allDcTasks.filter(t => t.parallelizable).length,
                avg_hours: allDcTasks.length ? (dcHours / allDcTasks.length).toFixed(1) : 0,
            },
            greedy: {
                tasks: allGreedyTasks,
                total_tasks: allGreedyTasks.length,
                total_hours: Math.round(greedyHours * 100) / 100,
                days_required: Math.ceil(greedyHours / 8),
                quick_wins: allGreedyTasks.filter(t => t.quick_win_score && t.quick_win_score >= 7).length,
                high_risk: allGreedyTasks.filter(t => t.risk_level === 'high').length,
                parallel_tasks: allGreedyTasks.filter(t => t.parallelizable).length,
                avg_hours: allGreedyTasks.length ? (greedyHours / allGreedyTasks.length).toFixed(1) : 0,
            },
            combined_hours: Math.round((dcHours + greedyHours) * 100) / 100,
            total_main_tasks: new Set([...allDcTasks, ...allGreedyTasks].map(t => t.main_task_name || t.title || '')).size,
        };

        this.showOverallModal = true;
    },

    getStaffBreakdown(tasks) {
        const breakdown = {};
        tasks.forEach(task => {
            const id = task.responsible_id || 'unassigned';
            const name = task.responsible_name || 'Unassigned';
            if (!breakdown[id]) {
                breakdown[id] = { name, taskCount: 0, totalHours: 0 };
            }
            breakdown[id].taskCount++;
            breakdown[id].totalHours += parseFloat(task.estimated_hours || 0);
        });
        return Object.values(breakdown).sort((a, b) => b.totalHours - a.totalHours);
    },

    getTimeDiffText() {
        if (!this.overallPreview) return '';
        const diff = (this.overallPreview.greedy.total_hours - this.overallPreview.divide_conquer.total_hours).toFixed(1);
        if (diff > 0) return '+' + diff + 'h (Greedy slower)';
        if (diff < 0) return diff + 'h (Greedy faster)';
        return '0.0h (Same)';
    },

    getTimeDiffClass() {
        if (!this.overallPreview) return '';
        const diff = this.overallPreview.greedy.total_hours - this.overallPreview.divide_conquer.total_hours;
        return diff > 0 ? 'text-red-600 font-bold' : diff < 0 ? 'text-green-600 font-bold' : 'text-gray-600 font-bold';
    }
}">
    {{-- Overall Computation Button --}}
    <button
        type="button"
        @click="getOverallComputation()"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span>Overall Computation</span>
    </button>

    {{-- Overall Computation Modal --}}
    <div
        x-show="showOverallModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-60 p-4"
        style="display: none;"
    >
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl my-6" @click.outside="showOverallModal = false">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-violet-600 to-indigo-600 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Overall Computation</h2>
                        <p class="text-sm text-violet-100">Aggregated results across all main tasks</p>
                    </div>
                </div>
                <button type="button" @click="showOverallModal = false"
                    class="p-2 rounded-lg text-white hover:bg-white hover:bg-opacity-20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6" x-show="overallPreview">

                {{-- Summary Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                        <p class="text-xs text-blue-500 font-medium uppercase tracking-wider">D&C Total Tasks</p>
                        <p class="text-3xl font-bold text-blue-700 mt-1" x-text="overallPreview?.divide_conquer?.total_tasks || 0"></p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                        <p class="text-xs text-blue-500 font-medium uppercase tracking-wider">D&C Total Hours</p>
                        <p class="text-3xl font-bold text-blue-700 mt-1" x-text="(overallPreview?.divide_conquer?.total_hours || 0) + 'h'"></p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                        <p class="text-xs text-green-500 font-medium uppercase tracking-wider">Greedy Total Tasks</p>
                        <p class="text-3xl font-bold text-green-700 mt-1" x-text="overallPreview?.greedy?.total_tasks || 0"></p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                        <p class="text-xs text-green-500 font-medium uppercase tracking-wider">Greedy Total Hours</p>
                        <p class="text-3xl font-bold text-green-700 mt-1" x-text="(overallPreview?.greedy?.total_hours || 0) + 'h'"></p>
                    </div>
                </div>

                {{-- Detailed Stats --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- D&C Column --}}
                    <div class="bg-white rounded-xl border-2 border-blue-200 overflow-hidden">
                        <div class="px-4 py-3 bg-blue-600 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <h3 class="font-bold text-white">Divide & Conquer — Overall</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Total Subtasks</span>
                                <span class="font-semibold text-blue-700" x-text="overallPreview?.divide_conquer?.total_tasks"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Total Hours</span>
                                <span class="font-semibold text-blue-700" x-text="(overallPreview?.divide_conquer?.total_hours || 0) + 'h'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Days Required (8h/day)</span>
                                <span class="font-semibold text-blue-700" x-text="overallPreview?.divide_conquer?.days_required + ' days'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Avg Hours/Task</span>
                                <span class="font-semibold text-blue-700" x-text="overallPreview?.divide_conquer?.avg_hours + 'h'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Critical Path Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"
                                    x-text="overallPreview?.divide_conquer?.critical_tasks"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Parallelizable Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700"
                                    x-text="overallPreview?.divide_conquer?.parallel_tasks"></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-500">High Risk Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700"
                                    x-text="overallPreview?.divide_conquer?.high_risk"></span>
                            </div>

                            {{-- Staff Breakdown --}}
                            <div class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-semibold text-blue-700 mb-2">Staff Workload (D&C)</h4>
                                <div class="space-y-2">
                                    <template x-for="(staff, i) in getStaffBreakdown(overallPreview?.divide_conquer?.tasks || [])" :key="'dc_overall_' + i">
                                        <div class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="h-7 w-7 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold"
                                                    x-text="(staff.name || '').substring(0,2).toUpperCase()"></div>
                                                <span class="text-sm font-medium text-gray-800" x-text="staff.name"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-medium"
                                                    x-text="staff.taskCount + ' tasks'"></span>
                                                <span class="bg-white border border-blue-200 text-blue-700 px-2 py-0.5 rounded-full font-medium"
                                                    x-text="staff.totalHours.toFixed(1) + 'h'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Greedy Column --}}
                    <div class="bg-white rounded-xl border-2 border-green-200 overflow-hidden">
                        <div class="px-4 py-3 bg-green-600 flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <h3 class="font-bold text-white">Greedy Algorithm — Overall</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Total Subtasks</span>
                                <span class="font-semibold text-green-700" x-text="overallPreview?.greedy?.total_tasks"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Total Hours</span>
                                <span class="font-semibold text-green-700" x-text="(overallPreview?.greedy?.total_hours || 0) + 'h'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Days Required (8h/day)</span>
                                <span class="font-semibold text-green-700" x-text="overallPreview?.greedy?.days_required + ' days'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Avg Hours/Task</span>
                                <span class="font-semibold text-green-700" x-text="overallPreview?.greedy?.avg_hours + 'h'"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Quick Win Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"
                                    x-text="overallPreview?.greedy?.quick_wins"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Parallelizable Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700"
                                    x-text="overallPreview?.greedy?.parallel_tasks"></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-500">High Risk Tasks</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700"
                                    x-text="overallPreview?.greedy?.high_risk"></span>
                            </div>

                            {{-- Staff Breakdown --}}
                            <div class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-semibold text-green-700 mb-2">Staff Workload (Greedy)</h4>
                                <div class="space-y-2">
                                    <template x-for="(staff, i) in getStaffBreakdown(overallPreview?.greedy?.tasks || [])" :key="'gr_overall_' + i">
                                        <div class="flex items-center justify-between bg-green-50 rounded-lg px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="h-7 w-7 rounded-full bg-green-500 flex items-center justify-center text-white text-xs font-bold"
                                                    x-text="(staff.name || '').substring(0,2).toUpperCase()"></div>
                                                <span class="text-sm font-medium text-gray-800" x-text="staff.name"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-medium"
                                                    x-text="staff.taskCount + ' tasks'"></span>
                                                <span class="bg-white border border-green-200 text-green-700 px-2 py-0.5 rounded-full font-medium"
                                                    x-text="staff.totalHours.toFixed(1) + 'h'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Comparison Summary --}}
                <div class="bg-gradient-to-r from-violet-50 to-indigo-50 border border-violet-200 rounded-xl p-5">
                    <h3 class="font-bold text-violet-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Overall Comparison Summary
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500">Time Difference</p>
                            <p class="font-bold text-base mt-1" :class="getTimeDiffClass()" x-text="getTimeDiffText()"></p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500">D&C Days Required</p>
                            <p class="font-bold text-base text-blue-700 mt-1" x-text="(overallPreview?.divide_conquer?.days_required || 0) + ' days'"></p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500">Greedy Days Required</p>
                            <p class="font-bold text-base text-green-700 mt-1" x-text="(overallPreview?.greedy?.days_required || 0) + ' days'"></p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                            <p class="text-xs text-gray-500">Recommended</p>
                            <p class="font-bold text-base mt-1"
                                :class="(overallPreview?.divide_conquer?.total_hours || 0) <= (overallPreview?.greedy?.total_hours || 0) ? 'text-blue-700' : 'text-green-700'"
                                x-text="(overallPreview?.divide_conquer?.total_hours || 0) <= (overallPreview?.greedy?.total_hours || 0) ? 'D&C' : 'Greedy'"></p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-end">
                <button type="button" @click="showOverallModal = false"
                    class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
