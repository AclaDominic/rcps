<div x-data="chartResourceUtilization()" x-init="initCharts()" wire:ignore>
    <canvas id="lineChart" class="mb-6"></canvas>
    <canvas id="barChart"></canvas>

    <!-- Interpretation Section -->
    <div id="resourceInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-3 rounded shadow">
        <h3 class="font-bold mb-1">Interpretation:</h3>
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
            this.updateInterpretation(chartData.summary);

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
                this.updateInterpretation(e.detail.summary);
            });
        },

        updateInterpretation(summary) {
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
