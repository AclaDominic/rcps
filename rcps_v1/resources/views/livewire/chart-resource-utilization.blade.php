<div x-data="chartResourceUtilization()" x-init="initCharts()" wire:ignore>
    <canvas id="lineChart" class="mb-6"></canvas>
    <canvas id="barChart"></canvas>

    <!-- Interpretation Section -->
    <div id="resourceInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-4 rounded-lg shadow">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold">Interpretation:</h3>
            <span id="resourceCompletionBadge" class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Completion: 0%</span>
        </div>
        <p id="resourceInterpretationText">Loading interpretation...</p>
    </div>
</div>

<script>
function chartResourceUtilization() {
    let lineChart = null;
    let barChart = null;

    return {
        initCharts() {
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const barCtx = document.getElementById('barChart').getContext('2d');

            const chartData = @json($chartResourceUtilization);

            lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                }
            });

            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: chartData.summary.labels,
                    datasets: [{
                        label: 'Average Resource Utilization',
                        data: chartData.summary.data,
                        backgroundColor: chartData.summary.backgroundColor,
                        borderColor: chartData.summary.borderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });

            // Initial interpretation
            this.updateInterpretation(chartData);

            // Update dynamically
            window.addEventListener('chart-data-resource-utilization-updated', e => {
                // Update Line Chart
                lineChart.data.labels = e.detail.labels;
                lineChart.data.datasets = e.detail.datasets;
                lineChart.update();

                // Update Bar Chart
                barChart.data.labels = e.detail.summary.labels;
                barChart.data.datasets[0].data = e.detail.summary.data;
                barChart.update();

                // Update interpretation
                this.updateInterpretation(e.detail);
            });
        },

        updateInterpretation(chartData) {
            const summary = chartData.summary;
            
            // Update Completion Badge
            const completionBadge = document.getElementById('resourceCompletionBadge');
            if(chartData.completionPercentage !== undefined) {
                completionBadge.innerText = `Project Completion: ${chartData.completionPercentage}%`;
                if(chartData.completionPercentage >= 100) {
                    completionBadge.className = "bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else if(chartData.completionPercentage >= 50) {
                    completionBadge.className = "bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                } else {
                    completionBadge.className = "bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded";
                }
            }

            if (!summary || !summary.data.length) return;

            const results = summary.labels.map((label, i) => {
                return { label: label, value: summary.data[i] || 0 };
            });

            let best = results.reduce((a, b) => a.value > b.value ? a : b);
            let worst = results.reduce((a, b) => a.value < b.value ? a : b);

            let interpretation = `Among the modes, <b>${best.label}</b> achieved the highest resource utilization at ${best.value}%. 
                                  Meanwhile, <b>${worst.label}</b> had the lowest at ${worst.value}%. 
                                  This suggests ${best.label} is more effective in keeping resources actively engaged.`;

            document.getElementById('resourceInterpretationText').innerHTML = interpretation;
        }
    };
}
</script>
