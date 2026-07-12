import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default class extends Controller {
    static values = {
        type: String,
        data: Object,
        options: { type: Object, default: {} },
    };

    connect() {
        const canvas = this.element.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        this.chart = new Chart(ctx, {
            type: this.typeValue,
            data: this.dataValue,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#9ca3af' },
                    },
                },
                scales: this.typeValue === 'radar' ? {} : {
                    x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                    y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' }, beginAtZero: true },
                },
                ...this.optionsValue,
            },
        });
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}
