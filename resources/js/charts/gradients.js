/*
|--------------------------------------------------------------------------
| Gradient Factory
|--------------------------------------------------------------------------
|
| Criação de gradientes reutilizáveis para Chart.js.
|
*/

import { COLORS } from "./colors";

/*
|--------------------------------------------------------------------------
| Gradiente Vertical
|--------------------------------------------------------------------------
*/

export function createVerticalGradient(

    ctx,

    color = COLORS.primary,

    height = 300

) {

    const gradient = ctx.createLinearGradient(

        0,

        0,

        0,

        height

    );

    gradient.addColorStop(0, `${color}E6`);
    gradient.addColorStop(0.5, `${color}99`);
    gradient.addColorStop(1, `${color}15`);

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Gradiente Horizontal
|--------------------------------------------------------------------------
*/

export function createHorizontalGradient(

    ctx,

    color = COLORS.primary,

    width = 400

) {

    const gradient = ctx.createLinearGradient(

        0,

        0,

        width,

        0

    );

    gradient.addColorStop(0, `${color}E6`);
    gradient.addColorStop(1, `${color}33`);

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Gradiente Linha
|--------------------------------------------------------------------------
*/

export function createLineGradient(

    ctx,

    color = COLORS.primary,

    height = 300

) {

    const gradient = ctx.createLinearGradient(

        0,

        0,

        0,

        height

    );

    gradient.addColorStop(0, `${color}66`);
    gradient.addColorStop(1, `${color}00`);

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Gradiente Área
|--------------------------------------------------------------------------
*/

export function createAreaGradient(

    ctx,

    startColor,

    endColor,

    height = 300

) {

    const gradient = ctx.createLinearGradient(

        0,

        0,

        0,

        height

    );

    gradient.addColorStop(0, startColor);
    gradient.addColorStop(1, endColor);

    return gradient;

}

/*
|--------------------------------------------------------------------------
| Paleta de Gradientes
|--------------------------------------------------------------------------
*/

export function createPaletteGradients(

    ctx,

    colors,

    height = 300

) {

    return colors.map(color =>
        createVerticalGradient(ctx, color, height)
    );

}
