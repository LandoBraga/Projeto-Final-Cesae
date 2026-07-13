/*
|--------------------------------------------------------------------------
| Doughnut Chart Factory
|--------------------------------------------------------------------------
|
| Factory para criação de gráficos Doughnut.
|
*/

import Chart from "./register";
import { getChartTheme } from "./theme";

export function createDoughnutChart({

    canvas,

    labels = [],

    data = [],

    colors = [],

    options = {}

}) {

    const theme = getChartTheme();

    const palette = colors.length
        ? colors
        : [
            "#2563EB",
            "#16A34A",
            "#F59E0B",
            "#DC2626",
            "#7C3AED",
            "#06B6D4",
            "#EC4899",
            "#84CC16",
            "#F97316",
            "#64748B"
        ];

    return new Chart(canvas, {

        type: "doughnut",

        data: {

            labels,

            datasets: [

                {

                    data,

                    backgroundColor: palette,

                    borderWidth: 0,

                    hoverOffset: 12

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            cutout: "68%",

            interaction: {

                intersect: false

            },

            plugins: {

                legend: {

                    display: false

                },

                tooltip: {

                    callbacks: {

                        label(context) {

                            const value = context.raw ?? 0;

                            const total = context.dataset.data.reduce(
                                (sum, current) => sum + current,
                                0
                            );

                            const percentage = total
                                ? ((value / total) * 100).toFixed(1)
                                : 0;

                            return `${context.label}: ${value} (${percentage}%)`;

                        }

                    }

                }

            },

            ...options

        }

    });

}
