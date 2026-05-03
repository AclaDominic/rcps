<div x-data="executionTimeChart()" x-init="initChart()">
    <canvas id="executionTimeChartCanvas" width="400" height="300"></canvas>
</div>

<!-- Interpretation Section -->
<div id="executionInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-4 rounded-lg shadow">
    <h3 class="font-bold mb-2">Interpretation</h3>
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
                            text: 'Average Execution Time by Dependency Mode'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Execution Time (hours)'
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

            const dataset = data.datasets[0]; // only one dataset
            const labels = data.labels;
            const values = dataset.data;

            let interpretation = "Not enough data to interpret.";

            if (labels.length >= 2 && values.length >= 2) {
                const firstLabel = labels[0];
                const secondLabel = labels[1];
                const firstVal = values[0];
                const secondVal = values[1];

                if (firstVal < secondVal) {
                    interpretation =
                        `${firstLabel} executed faster on average (${firstVal} hrs) compared to ${secondLabel} (${secondVal} hrs). ` +
                        `This suggests ${firstLabel} is more time-efficient.`;
                } else if (secondVal < firstVal) {
                    interpretation =
                        `${secondLabel} executed faster on average (${secondVal} hrs) compared to ${firstLabel} (${firstVal} hrs). ` +
                        `This suggests ${secondLabel} handles dependencies more efficiently.`;
                } else {
                    interpretation = `Both modes had the same average execution time (${firstVal} hrs).`;
                }
            }

            document.getElementById('executionInterpretationText').innerText = interpretation;
        }
    }
}
</script>
