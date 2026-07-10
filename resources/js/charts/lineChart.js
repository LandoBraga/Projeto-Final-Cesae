import { getChartTheme } from './theme';
import { createGradient } from './gradients';


export function createLineChart(ctx, config = {}) {

    const theme = getChartTheme();

    const {
        labels = [],
        datasets = [],
        options = {}
    } = config;


    const processedDatasets = datasets.map(dataset => {

        const color = dataset.color || theme.primary;


        return {
            ...dataset,

            borderColor: color,

            backgroundColor: dataset.fill
                ? createGradient(
                    ctx,
                    color
                )
                : 'transparent',

            borderWidth: dataset.borderWidth || 3,

            pointRadius: dataset.pointRadius ?? 4,

            pointHoverRadius:
                dataset.pointHoverRadius ?? 6,

            tension:
                dataset.tension ?? 0.4,

            fill:
                dataset.fill ?? false
        };

    });


    return new Chart(ctx, {

        type: 'line',

        data: {
            labels,

            datasets: processedDatasets
        },


        options: {

            responsive: true,

            maintainAspectRatio: false,


            interaction: {

                mode: 'index',

                intersect: false
            },


            plugins: {

                legend: {

                    display: true,

                    labels: {

                        color: theme.text
                    }
                },


                tooltip: {

                    enabled: true,

                    backgroundColor:
                        theme.tooltip.background,

                    titleColor:
                        theme.tooltip.title,

                    bodyColor:
                        theme.tooltip.body
                }

            },


            scales: {

                x: {

                    grid: {

                        color:
                            theme.grid
                    },

                    ticks: {

                        color:
                            theme.text
                    }

                },


                y: {

                    beginAtZero: true,


                    grid: {

                        color:
                            theme.grid
                    },


                    ticks: {

                        color:
                            theme.text
                    }

                }

            },


            animation: {

                duration: 800,

                easing: 'easeOutQuart'
            },


            ...options

        }

    });

}
