/*
|--------------------------------------------------------------------------
| Global Chart Theme
|--------------------------------------------------------------------------
|
| Configuração global para todos os gráficos da aplicação.
|
*/

import Chart from "./register";

import {
    getGridColor,
    getTextColor,
    getTooltipBackground,
    getTooltipText
} from "./helpers";

/*
|--------------------------------------------------------------------------
| Aplicar Tema
|--------------------------------------------------------------------------
*/

export function applyChartTheme() {

    /*
    |--------------------------------------------------------------------------
    | Fontes
    |--------------------------------------------------------------------------
    */

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.font.weight = "500";

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */

    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.layout.padding = 8;

    /*
    |--------------------------------------------------------------------------
    | Cores
    |--------------------------------------------------------------------------
    */

    Chart.defaults.color = getTextColor();
    Chart.defaults.borderColor = getGridColor();

    /*
    |--------------------------------------------------------------------------
    | Animações
    |--------------------------------------------------------------------------
    */

    Chart.defaults.animation = {
        duration: 900,
        easing: "easeOutQuart"
    };

    /*
    |--------------------------------------------------------------------------
    | Elementos
    |--------------------------------------------------------------------------
    */

    if (Chart.defaults.elements.bar) {
        Chart.defaults.elements.bar.borderRadius = 12;
        Chart.defaults.elements.bar.borderSkipped = false;
    }

    if (Chart.defaults.elements.line) {
        Chart.defaults.elements.line.tension = 0.35;
    }

    if (Chart.defaults.elements.point) {
        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hoverRadius = 7;
    }

    if (Chart.defaults.elements.arc) {
        Chart.defaults.elements.arc.borderWidth = 0;
        Chart.defaults.elements.arc.hoverOffset = 14;
    }

    /*
    |--------------------------------------------------------------------------
    | Plugins
    |--------------------------------------------------------------------------
    */

    Chart.defaults.plugins.legend.display = false;

    Chart.defaults.plugins.tooltip = {

        enabled: true,

        displayColors: true,

        cornerRadius: 14,

        padding: 14,

        borderWidth: 1,

        backgroundColor: getTooltipBackground(),

        titleColor: getTooltipText(),

        bodyColor: getTooltipText()

    };

}

/*
|--------------------------------------------------------------------------
| Tema utilizado pelos Factories
|--------------------------------------------------------------------------
*/

export function getChartTheme() {

    return {

        primary: "#2563EB",

        secondary: "#7C3AED",

        success: "#16A34A",

        warning: "#D97706",

        danger: "#DC2626",

        text: getTextColor(),

        grid: getGridColor(),

        tooltip: {

            background: getTooltipBackground(),

            title: getTooltipText(),

            body: getTooltipText()

        }

    };

}

/*
|--------------------------------------------------------------------------
| Atualizar Light / Dark
|--------------------------------------------------------------------------
*/

export function refreshChartTheme() {

    Chart.defaults.color = getTextColor();

    Chart.defaults.borderColor = getGridColor();

    Chart.defaults.plugins.tooltip.backgroundColor =
        getTooltipBackground();

    Chart.defaults.plugins.tooltip.titleColor =
        getTooltipText();

    Chart.defaults.plugins.tooltip.bodyColor =
        getTooltipText();

}

/*
|--------------------------------------------------------------------------
| Observer
|--------------------------------------------------------------------------
*/

const observer = new MutationObserver(() => {

    refreshChartTheme();

});

observer.observe(document.documentElement, {

    attributes: true,

    attributeFilter: ["class"]

});

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

applyChartTheme();
