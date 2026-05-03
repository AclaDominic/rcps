<div class="h-70 w-70 mx-auto" x-data="chartSchedulingAccurancy()" x-init="initCharts()" wire:ignore>
    <canvas id="schedulingAccuracyChart"></canvas>

   
</div>

 <div id="schedulingInterpretation" class="mt-4 text-sm text-gray-700 bg-gray-50 p-4 rounded-lg shadow">
        <h3 class="font-bold mb-2">Interpretation</h3>
        <p id="schedulingInterpretationText">Loading interpretation...</p>
    </div>

<script>
function chartSchedulingAccurancy() {
    let schedulingChart = null;

    return {
        initCharts() {
            const idElement = document.getElementById('schedulingAccuracyChart').getContext('2d');
            idElement.canvas.width = 600;  // width in px
            idElement.canvas.height = 325; // height in px
            schedulingChart = new Chart(idElement, {
               type: 'doughnut', 
                data: @json($chartSchedulingAccuracy),
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom' },
                        title: {
                            display: true,
                            text: 'Scheduling Accuracy by Dependency Mode'
                        }
                    }
                }
            });

            this.updateInterpretation(schedulingChart.data);

            window.addEventListener('chart-data-scheduling-accuracy-updated', e => {
                schedulingChart.data = e.detail;
                schedulingChart.update();
                this.updateInterpretation(schedulingChart.data);
            });

            
        },
        updateInterpretation(data) {
            if (!data || !data.datasets || !data.datasets.length) return;

            const dataset = data.datasets[0];
            const labels = data.labels;
            const values = dataset.data;

            let interpretation = "Not enough data to interpret.";
   
            if (labels.length >= 2 && values.length >= 2) {
                const greedyIndex = labels.findIndex(l => l.toLowerCase().includes('greedy'));
                const divideIndex = labels.findIndex(l => l.toLowerCase().includes('divide'));

                if (greedyIndex !== -1 && divideIndex !== -1) {
                    const greedyVal = values[greedyIndex];
                    const divideVal = values[divideIndex];

                   if (greedyVal > divideVal) {
                        interpretation = `Greedy mode achieved higher scheduling accuracy (${greedyVal}%) compared to Divide & Conquer (${divideVal}%). This suggests Greedy is more consistent in meeting schedules.`;
                    } else if (divideVal > greedyVal) {
                        interpretation = `Divide & Conquer achieved higher scheduling accuracy (${divideVal}%) compared to Greedy (${greedyVal}%). This suggests Divide & Conquer is better at handling dependencies while maintaining schedules.`;
                    } else {
                        interpretation = `Both modes had the same scheduling accuracy (${greedyVal}%).`;
                    }
                }
            }

            document.getElementById('schedulingInterpretationText').innerText = interpretation;
        }
    };
}
</script>