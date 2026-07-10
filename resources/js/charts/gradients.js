/*
|--------------------------------------------------------------------------
| Chart Gradients
|--------------------------------------------------------------------------
|
| Gradientes reutilizáveis para todos os gráficos.
|
*/

import { COLORS } from "./colors";

/*
|--------------------------------------------------------------------------
| Criar Gradiente Vertical
|--------------------------------------------------------------------------
*/

export function createVerticalGradient(context, color) {

    const chart = context.chart;

    const {
        ctx,
        chartArea
    } = chart;

    if (!chartArea) {

        return color;

    }

    const gradient = ctx.createLinearGradient(

        0,
        chartArea.bottom,

        0,
        chartArea.top

    );

    gradient.addColorStop(0, color + "55");

    gradient.addColorStop(0.45, color + "CC");

    gradient.addColorStop(1, color);

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Criar Gradiente Horizontal
|--------------------------------------------------------------------------
*/

export function createHorizontalGradient(context, color) {

    const chart = context.chart;

    const {
        ctx,
        chartArea
    } = chart;

    if (!chartArea) {

        return color;

    }

    const gradient = ctx.createLinearGradient(

        chartArea.left,

        0,

        chartArea.right,

        0

    );

    gradient.addColorStop(0, color);

    gradient.addColorStop(1, color + "55");

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Gradientes Oficiais
|--------------------------------------------------------------------------
*/

export function primaryGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.primary

    );

}

export function createGradient(context, color) {
    return createVerticalGradient(context, color);
}

export function successGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.success

    );

}

export function warningGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.warning

    );

}

export function dangerGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.danger

    );

}

export function infoGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.info

    );

}

export function purpleGradient(context) {

    return createVerticalGradient(

        context,

        COLORS.purple

    );

}

/*
|--------------------------------------------------------------------------
| Paleta para Doughnut/Pie
|--------------------------------------------------------------------------
*/

export function doughnutPalette() {

    return [

        COLORS.success,

        COLORS.primary,

        COLORS.warning,

        COLORS.danger,

        COLORS.purple,

        COLORS.info,

        COLORS.gray

    ];

}
