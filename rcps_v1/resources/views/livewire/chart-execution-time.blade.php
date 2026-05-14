<div x-data="executionTimeChart()" x-init="initChart()">
    <canvas id="executionTimeChartCanvas" width="400" height="300"></canvas>
</div>

<!-- Interpretation Section -->
<div id="executionInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-4 rounded-lg shadow">
    <div class="flex justify-between items-center mb-2">
        <h3 class="font-bold">Interpretation</h3>
        <span id="executionCompletionBadge" class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Completion: 0%</span>
    </div>
    <p id="executionInterpretationText">Loading interpretation...</p>
</div>

<script>
function executionTimeChart() {
    let chart = null;

    return {
        initChart() {
            const ctx = document.getElementById('executionTimeChartCanvas').getContext('2d');

            const chartData = @json($chartData);

            chart = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    // maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: {
                            display: true,
                            text: 'Total Execution Time (Theorized vs Actual) by Dependency Mode'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Time (hours)'
                            }
                        }
                    }
                }
            });
            // Initial interpretation
            this.updateInterpretation(chartData);

            // Update interpretation when new data comes in
            window.addEventListener('chart-data-execution-time-updated', event => {
                chart.data = event.detail;
                chart.update();
                this.updateInterpretation(chart.data);
            });
        },

        updateInterpretation(data) {
            if (!data || !data.datasets || !data.datasets.length) {
                document.getElementById('executionInterpretationText').innerText =
                    "No dataset available to interpret.";
                return;
            }

            const completion = data.completionPercentage || 0;
            
            // Update Completion Badge
            const completionBadge = document.getElementById('executionCompletionBadge');
            if(data.completionPercentage !== undefined) {
                completionBadge.innerText = `Project Completion: ${completion}%`;
                if(completion >= 100) {
                    completionBadge.className = "bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else if(completion >= 50) {
                    completionBadge.className = "bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else {
                    completionBadge.className = "bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                }
            }

            const labels = data.labels;
            let interpretation = "";

            // Calculate Confidence Level
            let confidence = "Low";
            let confidenceColor = "text-red-500";
            if (completion > 80) { confidence = "High"; confidenceColor = "text-green-600"; }
            else if (completion > 40) { confidence = "Medium"; confidenceColor = "text-yellow-600"; }

            if (!data.showActual) {
                interpretation = `<span class="font-semibold">Current State:</span> The project is in its early stages (${completion}% complete). <br><br>` +
                    `At least 50% project completion is required to compare theoretical estimates with actual execution reality. ` +
                    `Currently, we are only visualizing the AI's <span class="italic text-blue-600">Theorized Totals</span>. <br><br>` +
                    `<span class="text-xs text-gray-500 italic">Note: Analysis reliability is currently <b>Low</b> and will increase as tasks are completed.</span>`;
            } else if (data.datasets.length >= 2) {
                const theorizedDataset = data.datasets[0].data;
                const actualDataset = data.datasets[1].data;

                const greedyEst = theorizedDataset[0];
                const divideEst = theorizedDataset[1];
                const greedyAct = actualDataset[0];
                const divideAct = actualDataset[1];

                let comparison = "";
                if (greedyAct > 0 && divideAct > 0) {
                    if (divideAct < greedyAct) {
                        comparison = `<span class="text-green-600 font-bold">Divide & Conquer</span> is currently leading in efficiency, executing faster in total (${divideAct} hrs) compared to Greedy (${greedyAct} hrs).`;
                    } else if (greedyAct < divideAct) {
                        comparison = `<span class="text-green-600 font-bold">Greedy Algorithm</span> is currently leading in efficiency, executing faster in total (${greedyAct} hrs) compared to Divide & Conquer (${divideAct} hrs).`;
                    } else {
                        comparison = `Both modes are performing equally in terms of actual execution time (${greedyAct} hrs).`;
                    }
                } else if (greedyAct > 0 && divideAct === 0) {
                    comparison = `Active progress detected in <span class="font-bold">Greedy</span> (${greedyAct} hrs), while <span class="font-bold italic text-gray-400">Divide & Conquer</span> data is pending task completion.`;
                } else if (divideAct > 0 && greedyAct === 0) {
                    comparison = `Active progress detected in <span class="font-bold">Divide & Conquer</span> (${divideAct} hrs), while <span class="font-bold italic text-gray-400">Greedy</span> data is pending task completion.`;
                } else {
                    comparison = `Actual execution data is not yet available for either algorithm. Theorized totals are shown for planning purposes.`;
                }

                interpretation = `<span class="font-semibold">Insight:</span> ${comparison} <br><br>` +
                    `The project is <b>${completion}% complete</b>. Because this is a mid-project analysis, these trends are <span class="italic">dynamic</span>. ` +
                    `Expect the variance between theory and reality to shift as more critical-path tasks are finalized. <br><br>` +
                    `<span class="text-xs text-gray-500">Data Confidence: <b class="${confidenceColor}">${confidence}</b></span>`;
            } else {
                interpretation = "Not enough data to interpret.";
            }

            document.getElementById('executionInterpretationText').innerHTML = interpretation;
        }
    }
}
</script>
