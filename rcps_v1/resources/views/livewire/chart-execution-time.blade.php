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

            // Update Completion Badge
            const completionBadge = document.getElementById('executionCompletionBadge');
            if(data.completionPercentage !== undefined) {
                completionBadge.innerText = `Project Completion: ${data.completionPercentage}%`;
                if(data.completionPercentage >= 100) {
                    completionBadge.className = "bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else if(data.completionPercentage >= 50) {
                    completionBadge.className = "bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else {
                    completionBadge.className = "bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                }
            }

            const labels = data.labels;
            let interpretation = "";

            if (!data.showActual) {
                interpretation = `At least 50% project completion is required to compare theoretical estimates with actual execution reality. Currently showing only the AI's theorized totals.`;
            } else if (data.datasets.length >= 2) {
                const theorizedDataset = data.datasets[0].data;
                const actualDataset = data.datasets[1].data;

                const greedyEst = theorizedDataset[0];
                const divideEst = theorizedDataset[1];
                const greedyAct = actualDataset[0];
                const divideAct = actualDataset[1];

                let comparison = "";
                if (divideAct < greedyAct) {
                    comparison = `Divide & Conquer executed faster in total (${divideAct} hrs) compared to Greedy (${greedyAct} hrs). This confirms Divide & Conquer handled dependencies more efficiently.`;
                } else if (greedyAct < divideAct) {
                    comparison = `Greedy executed faster in total (${greedyAct} hrs) compared to Divide & Conquer (${divideAct} hrs). This confirms Greedy was more time-efficient for these tasks.`;
                } else {
                    comparison = `Both modes had the same total actual execution time (${greedyAct} hrs).`;
                }

                interpretation = `${comparison} Additionally, we can see the variance between the AI's theorized estimate and the actual reality for both algorithms.`;
            } else {
                interpretation = "Not enough data to interpret.";
            }

            document.getElementById('executionInterpretationText').innerText = interpretation;
        }
    }
}
</script>
