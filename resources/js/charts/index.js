/*
|--------------------------------------------------------------------------
| Charts Library
|--------------------------------------------------------------------------
|
| Ponto de entrada da biblioteca de gráficos.
|
*/

/*
|--------------------------------------------------------------------------
| Theme
|--------------------------------------------------------------------------
*/

export {

    applyChartTheme,

    refreshChartTheme,

    getChartTheme

} from "./theme";

/*
|--------------------------------------------------------------------------
| Factories
|--------------------------------------------------------------------------
*/

export {

    createBarChart

} from "./barChart";

export {

    createLineChart

} from "./lineChart";

export {

    createDoughnutChart

} from "./doughnutChart";

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

export {

    formatCurrency,

    formatNumber,

    formatPercent,

    getGridColor,

    getTextColor,

    getSoftTextColor,

    getBorderColor,

    getSurfaceColor,

    getSurfaceAltColor,

    getTooltipBackground,

    getTooltipText,

    isDarkMode,

    sum,

    max,

    min

} from "./helpers";

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

export {

    COLORS,

    PALETTE,

    SOFT_PALETTE,

    getPaletteColor,

    getSoftPaletteColor,

    getStatusColor

} from "./colors";

/*
|--------------------------------------------------------------------------
| Gradients
|--------------------------------------------------------------------------
*/

export {

    createVerticalGradient,

    createHorizontalGradient,

    createLineGradient,

    createAreaGradient,

    createPaletteGradients

} from "./gradients";
