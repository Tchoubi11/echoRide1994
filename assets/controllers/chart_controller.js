import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    connect() {
        if (!(this.element instanceof HTMLCanvasElement)) {
            console.error("chart_controller: L'élément n'est pas un <canvas>", this.element);
            return;
        }

        const chartDataRaw = this.element.dataset.chart;
        if (!chartDataRaw) {
            console.error('chart_controller: Aucune donnée trouvée dans data-chart');
            return;
        }

        let chartData;
        try {
            chartData = JSON.parse(chartDataRaw);
        } catch (e) {
            console.error('chart_controller: Erreur de parsing JSON', e);
            return;
        }

        const ctx = this.element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                }
            }
        });
    }
}
