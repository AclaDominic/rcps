<div x-data="chartComponent()" x-init="initChart()" wire:ignore>
    <canvas id="accuracyChart"></canvas>

    <!-- Interpretation Section -->
    <div id="chartInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-3 rounded shadow">
        <h3 class="font-bold mb-1">Interpretation:</h3>
        <p id="interpretationText">Loading interpretation...</p>
    </div>
</div>

<script>
    function chartComponent() {
        let chart = null;

        return {
            initChart() {
                const ctx = document.getElementById('accuracyChart').getContext('2d');
                chart = new Chart(ctx, {
                    type: 'line',
                    options: {
                        responsive: true,
                    },
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [
                            {
                                label: 'Greedy',
                                data: @json($chartData['greedyData']),
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                fill: false
                            },
                            {
                                label: 'Divide & Conquer',
                                data: @json($chartData['divideData']),
                                borderColor: 'rgba(54, 162, 235, 1)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                fill: false
                            }
                        ]
                    }
                });

                // Initial interpretation
                this.updateInterpretation(
                    chart.data.datasets[0].data,
                    chart.data.datasets[1].data
                );

                // Update on new data
                window.addEventListener('chart-data-updated', event => {
                    chart.data.labels = event.detail.labels;
                    chart.data.datasets[0].data = event.detail.greedyData;
                    chart.data.datasets[1].data = event.detail.divideData;
                    chart.update();

                    this.updateInterpretation(
                        event.detail.greedyData,
                        event.detail.divideData
                    );
                });
            },

            updateInterpretation(greedyData, divideData) {
                const avgGreedy = greedyData.length ? (greedyData.reduce((a,b) => a+b, 0) / greedyData.length).toFixed(2) : 0;
                const avgDivide = divideData.length ? (divideData.reduce((a,b) => a+b, 0) / divideData.length).toFixed(2) : 0;

                let interpretation = '';
                if (avgGreedy > avgDivide) {
                    interpretation = `Greedy mode performed better with an average accuracy of ${avgGreedy}% compared to Divide & Conquer's ${avgDivide}%. This suggests Greedy is more consistent for the given period.`;
                } else if (avgDivide > avgGreedy) {
                    interpretation = `Divide & Conquer performed better with an average accuracy of ${avgDivide}% compared to Greedy's ${avgGreedy}%. This suggests it is more effective when handling dependencies.`;
                } else {
                    interpretation = `Both modes performed equally with an average accuracy of ${avgGreedy}%.`;
                }

                document.getElementById('interpretationText').innerText = interpretation;
            }
        };
    }
</script>
