/*
|--------------------------------------------------------------------------
| Bar Chart Factory
|--------------------------------------------------------------------------
|
| Factory para criação de gráficos de barras.
|
*/

import Chart from "./register";
import { getChartTheme } from "./theme";

export function createBarChart({

    canvas,

    labels = [],

    data = [],

    label = "",

    backgroundColor = null,

    borderColor = null,

    options = {}

}) {

    const theme = getChartTheme();

    return new Chart(canvas, {

        type: "bar",

        data: {

            labels,

            datasets: [

                {

                    label,

                    data,

                    borderRadius: 10,

                    borderSkipped: false,

                    backgroundColor:

                        backgroundColor ?? theme.primary,

                    borderColor:

                        borderColor ?? theme.primary,

                    borderWidth: 0

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            animation: {

                duration: 800

            },

            interaction: {

                intersect: false,

                mode: "index"

            },

            plugins: {

                legend: {

                    display: false

                },

                tooltip: {

                    position: "average"

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
