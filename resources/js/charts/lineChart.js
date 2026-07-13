/*
|--------------------------------------------------------------------------
| Line Chart Factory
|--------------------------------------------------------------------------
|
| Factory para criação de gráficos de linhas.
|
*/

import Chart from "./register";
import { getChartTheme } from "./theme";

export function createLineChart(canvas, config = {}) {

    const theme = getChartTheme();

    const {

        labels = [],

        datasets = [],

        options = {}

    } = config;

    const chartDatasets = datasets.map(dataset => ({

        label: dataset.label,

        data: dataset.data,

        borderColor: dataset.color ?? theme.primary,

        backgroundColor: dataset.fill
            ? (dataset.backgroundColor ?? `${dataset.color ?? theme.primary}20`)
            : "transparent",

        fill: dataset.fill ?? false,

        borderWidth: 3,

        tension: 0.35,

        pointRadius: 4,

        pointHoverRadius: 7,

        pointBackgroundColor: dataset.color ?? theme.primary,

        pointBorderWidth: 2,

        pointBorderColor: "#ffffff"

    }));

    return new Chart(canvas, {

        type: "line",

        data: {

            labels,

            datasets: chartDatasets

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            interaction: {

                intersect: false,

                mode: "index"

            },

            plugins: {

                legend: {

                    display: true,

                    position: "bottom",

                    labels: {

                        usePointStyle: true,

                        padding: 20,

                        color: theme.text

                    }

                }

            },

            scales: {

                x: {

                    grid: {

                        display: false

                    },

                    ticks: {

                        color: theme.text

                    }

                },

                y: {

                    beginAtZero: true,

                    grid: {

                        color: theme.grid

                    },

                    ticks: {

                        color: theme.text

                    }

                }

            },

            ...options

        }

    });

}
